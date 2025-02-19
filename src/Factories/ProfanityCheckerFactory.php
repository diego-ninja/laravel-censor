<?php

namespace Ninja\Censor\Factories;

use EchoLabs\Prism\Prism;
use Ninja\Censor\Checkers\AzureAI;
use Ninja\Censor\Checkers\Censor;
use Ninja\Censor\Checkers\Contracts\ProfanityChecker;
use Ninja\Censor\Checkers\PerspectiveAI;
use Ninja\Censor\Checkers\PrismAI;
use Ninja\Censor\Checkers\PurgoMalum;
use Ninja\Censor\Checkers\TisaneAI;
use Ninja\Censor\Decorators\CachedProfanityChecker;
use Ninja\Censor\Enums\Provider;
use Ninja\Censor\Processors\Contracts\Processor;
use RuntimeException;

final readonly class ProfanityCheckerFactory
{
    /**
     * @param  array<string,mixed>  $config
     */
    public static function create(Provider $service, array $config = []): ProfanityChecker
    {
        /** @var class-string<ProfanityChecker> $class */
        $class = match ($service) {
            Provider::Local => Censor::class,
            Provider::Perspective => PerspectiveAI::class,
            Provider::PurgoMalum => PurgoMalum::class,
            Provider::Tisane => TisaneAI::class,
            Provider::Azure => AzureAI::class,
            Provider::Prism => PrismAI::class,
        };

        if (false === class_exists($class)) {
            throw new RuntimeException(sprintf('The class %s does not exist.', $class));
        }

        if (Provider::Local === $service) {
            $checker = new $class(app(Processor::class));
        } elseif (Provider::Prism === $service) {
            $checker = new $class(app(Prism::class));
        } else {
            $checker = new $class(...$config);
        }

        if (true === config('censor.cache.enabled', false)) {
            $ttl = config('censor.cache.ttl', 3600);
            if (false === is_int($ttl)) {
                $ttl = 3600;
            }

            return new CachedProfanityChecker($checker, $ttl);
        }

        return $checker;
    }
}
