<?php

namespace Ninja\Censor\Factories;

use Illuminate\Contracts\Container\BindingResolutionException;
use Ninja\Censor\Checkers\AzureAI;
use Ninja\Censor\Checkers\Censor;
use Ninja\Censor\Checkers\Contracts\ProfanityChecker;
use Ninja\Censor\Checkers\PerspectiveAI;
use Ninja\Censor\Checkers\PurgoMalum;
use Ninja\Censor\Checkers\TisaneAI;
use Ninja\Censor\Decorators\CachedProfanityChecker;
use Ninja\Censor\Enums\Provider;
use Ninja\Censor\Processors\Contracts\Processor;
use Ninja\Censor\Services\Contracts\ServiceAdapter;
use Ninja\Censor\Services\Pipeline\TransformationPipeline;
use RuntimeException;

final readonly class ProfanityCheckerFactory
{
    /**
     * @param  array<string,mixed>  $config
     * @throws BindingResolutionException
     */
    public static function create(Provider $service, array $config = []): ProfanityChecker
    {
        $pipeline = app()->make(TransformationPipeline::class);

        /** @var class-string<ProfanityChecker> $class */
        $class = match ($service) {
            Provider::Local => Censor::class,
            Provider::Perspective => PerspectiveAI::class,
            Provider::PurgoMalum => PurgoMalum::class,
            Provider::Tisane => TisaneAI::class,
            Provider::Azure => AzureAI::class,
        };

        if (! class_exists($class)) {
            throw new RuntimeException(sprintf('The class %s does not exist.', $class));
        }

        $checker = match ($service) {
            Provider::Local => new $class(
                processor: app()->make(Processor::class),
                adapter: app()->make(ServiceAdapter::class),
                pipeline: $pipeline
            ),
            Provider::Azure => new $class(
                endpoint: $config['endpoint'],
                key: $config['key'],
                version: $config['version'],
                adapter: app()->make(ServiceAdapter::class),
                pipeline: $pipeline
            ),
            Provider::Perspective, Provider::Tisane => new $class(
                key: $config['key'],
                adapter: app()->make(ServiceAdapter::class),
                pipeline: $pipeline
            ),
            Provider::PurgoMalum => new $class(
                adapter: app()->make(ServiceAdapter::class),
                pipeline: $pipeline
            ),
        };

        if (config('censor.cache.enabled', false) === true) {
            /** @var int $ttl */
            $ttl = config('censor.cache.ttl', 3600);

            return new CachedProfanityChecker($checker, $ttl);
        }

        return $checker;
    }
}
