<?php

namespace Ninja\Censor;

use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Validator;
use Ninja\Censor\Cache\Contracts\PatternCache;
use Ninja\Censor\Checkers\AzureAI;
use Ninja\Censor\Checkers\Contracts\ProfanityChecker;
use Ninja\Censor\Checkers\PerspectiveAI;
use Ninja\Censor\Checkers\PurgoMalum;
use Ninja\Censor\Checkers\TisaneAI;
use Ninja\Censor\Dictionary\LazyDictionary;
use Ninja\Censor\Enums\Provider;
use Ninja\Censor\Factories\ProfanityCheckerFactory;
use Ninja\Censor\Index\TrieIndex;
use Ninja\Censor\Processors\AbstractProcessor;
use Ninja\Censor\Processors\Contracts\Processor;
use Ninja\Censor\Processors\DefaultProcessor;
use Ninja\Censor\Services\Adapters\AzureAdapter;
use Ninja\Censor\Services\Adapters\CensorAdapter;
use Ninja\Censor\Services\Adapters\PerspectiveAdapter;
use Ninja\Censor\Services\Adapters\PurgoMalumAdapter;
use Ninja\Censor\Services\Adapters\TisaneAdapter;
use Ninja\Censor\Services\Contracts\ServiceAdapter;
use Ninja\Censor\Services\Pipeline\Stage\MatchesStage;
use Ninja\Censor\Services\Pipeline\Stage\MetadataStage;
use Ninja\Censor\Services\Pipeline\Stage\OffensiveStage;
use Ninja\Censor\Services\Pipeline\Stage\ScoreStage;
use Ninja\Censor\Services\Pipeline\Stage\TextStage;
use Ninja\Censor\Services\Pipeline\TransformationPipeline;
use Ninja\Censor\Support\PatternGenerator;

final class CensorServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/censor.php' => config_path('censor.php'),
            ], 'censor-config');

            $this->publishes([
                __DIR__.'/../resources/dict' => resource_path('dict'),
            ], 'censor-dictionaries');
        }

        app('validator')->extend(
            rule: 'censor_check',
            extension: function ($attribute, $value, $parameters, Validator $validator) {
                if ($value === null) {
                    return true;
                }

                if (! is_string($value)) {
                    $validator->addReplacer('censor_check', function () {
                        return 'The :attribute must be a string.';
                    });

                    return false;
                }

                return ! \Ninja\Censor\Facades\Censor::check($value)->offensive();
            },
            message: 'The :attribute contains offensive language.'
        );

        $this->loadRoutesFrom(__DIR__.'/../routes/censor.php');
    }

    public function register(): void
    {
        $this->app->bind(ServiceAdapter::class, function (Application $app) {
            return match (config('censor.default_service', Provider::Local)) {
                Provider::Azure => $app->make(AzureAdapter::class),
                Provider::Perspective => $app->make(PerspectiveAdapter::class),
                Provider::PurgoMalum => $app->make(PurgoMalumAdapter::class),
                Provider::Tisane => $app->make(TisaneAdapter::class),
                default => $app->make(CensorAdapter::class),
            };
        });

        $this->app->singleton(CensorAdapter::class);
        $this->app->singleton(AzureAdapter::class);
        $this->app->singleton(PerspectiveAdapter::class);
        $this->app->singleton(PurgoMalumAdapter::class);
        $this->app->singleton(TisaneAdapter::class);

        $this->app->when(AzureAI::class)->needs(ServiceAdapter::class)->give(AzureAdapter::class);
        $this->app->when(TisaneAI::class)->needs(ServiceAdapter::class)->give(TisaneAdapter::class);
        $this->app->when(\Ninja\Censor\Checkers\Censor::class)->needs(ServiceAdapter::class)->give(CensorAdapter::class);
        $this->app->when(PerspectiveAI::class)->needs(ServiceAdapter::class)->give(PerspectiveAdapter::class);
        $this->app->when(PurgoMalum::class)->needs(ServiceAdapter::class)->give(PurgoMalumAdapter::class);

        $this->app->singleton(TransformationPipeline::class, function () {
            return (new TransformationPipeline)
                ->addStage(new ScoreStage)
                ->addStage(new MatchesStage)
                ->addStage(new TextStage(app(Whitelist::class)))
                ->addStage(new MetadataStage)
                ->addStage(new OffensiveStage);
        });

        $this->registerProfanityProviders();

        /** @var Provider $default */
        $default = config('censor.default_service', Provider::Local);
        $this->app->bind(ProfanityChecker::class, function () use ($default): ProfanityChecker {
            /** @var ProfanityChecker $service */
            $service = app($default->value);

            return $service;
        });

        $this->app->singleton(PatternCache::class, function () {
            /** @var string $cache */
            $cache = config('censor.cache', 'file');

            return match ($cache) {
                'redis' => new Cache\RedisPatternCache,
                'octane' => new Cache\OctanePatternCache,
                default => new Cache\MemoryPatternCache,
            };
        });

        $this->app->singleton(PatternGenerator::class, function () {
            /** @var LazyDictionary $dictionary */
            $dictionary = app(LazyDictionary::class);

            return PatternGenerator::withDictionary($dictionary);
        });

        $this->app->singleton(LazyDictionary::class, function (): LazyDictionary {
            /** @var string[] $languages */
            $languages = config('censor.languages', [config('app.locale')]);

            return LazyDictionary::withLanguages($languages);
        });

        $this->app->singleton(Whitelist::class, function (): Whitelist {
            /** @var string[] $whitelist */
            $whitelist = config('censor.whitelist', []);

            return (new Whitelist)->add($whitelist);
        });

        $this->app->singleton(TrieIndex::class, function (): TrieIndex {
            /** @var LazyDictionary $dictionary */
            $dictionary = app(LazyDictionary::class);

            return new TrieIndex($dictionary->getWords());
        });

        $this->app->singleton(Processor::class, function (): AbstractProcessor {
            /** @var class-string<AbstractProcessor> $processorClass */
            $processorClass = config('censor.services.local.processor', DefaultProcessor::class);

            return new $processorClass(
                app(Whitelist::class),
                app(LazyDictionary::class)
            );

        });

        $this->app->bind('censor', function () {
            return new Censor;
        });

        $this->mergeConfigFrom(__DIR__.'/../config/censor.php', 'censor');
    }

    private function registerProfanityProviders(): void
    {
        $services = Provider::values();
        foreach ($services as $service) {
            /** @var array<string,mixed> $config */
            $config = config(sprintf('censor.services.%s', $service->value));

            if ($config !== null) {
                $this->app->singleton($service->value, function () use ($service, $config): ProfanityChecker {
                    return ProfanityCheckerFactory::create($service, $config);
                });
            }
        }

        $this->app->singleton(Provider::Local->value, function () {
            /** @var Processor $processor */
            $processor = app(Processor::class);

            /** @var ServiceAdapter $adapter */
            $adapter = app(ServiceAdapter::class);

            return new \Ninja\Censor\Checkers\Censor(
                processor: $processor,
                adapter: $adapter,
                pipeline: app(TransformationPipeline::class)
            );
        });
    }
}
