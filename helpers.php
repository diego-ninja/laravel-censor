<?php

use Ninja\Censor\Actions\CheckAction;
use Ninja\Censor\Actions\CleanAction;
use Ninja\Censor\Result\Contracts\Result;

if (! function_exists('is_offensive')) {
    function is_offensive(string $text): bool
    {
        /** @var Result $result */
        $result = CheckAction::run($text);

        return $result->offensive();
    }
}

if (! function_exists('clean')) {
    function clean(string $text): string
    {
        /** @var string $result */
        $result = CleanAction::run($text);

        return $result;
    }
}
