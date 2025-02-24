<?php

namespace Ninja\Sentinel;

use Ninja\Sentinel\Checkers\Contracts\ProfanityChecker;
use Ninja\Sentinel\Enums\Provider;
use Ninja\Sentinel\Exceptions\ClientException;
use Ninja\Sentinel\Result\Contracts\Result;
use Throwable;

class Sentinel
{
    /**
     * @throws ClientException
     */
    public function check(string $text): Result
    {
        try {
            /** @var ProfanityChecker $service */
            $service = app(ProfanityChecker::class);

            return $service->check($text);
        } catch (Throwable $e) {
            /** @var Provider $fallbackService */
            $fallbackService = config('sentinel.fallback_service');
            if ($fallbackService) {
                /** @var ProfanityChecker $fallback */
                $fallback = app($fallbackService->value);

                return $fallback->check($text);
            }

            throw new ClientException('Error analyzing text', 0, $e);
        }
    }

    /**
     * @throws ClientException
     */
    public function offensive(string $text): bool
    {
        return $this->check($text)->offensive();
    }

    /**
     * @throws ClientException
     */
    public function clean(string $text): string
    {
        return $this->check($text)->replaced();
    }

    public function with(Provider $service, string $text): ?Result
    {
        /** @var ProfanityChecker $checker */
        $checker = app($service->value);

        return $checker->check($text);
    }
}
