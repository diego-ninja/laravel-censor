<?php

namespace Ninja\Censor\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Ninja\Censor\Checkers\Contracts\ProfanityChecker;

final readonly class CleanAction
{
    use AsAction;

    public function __construct(private ProfanityChecker $checker) {}

    public function handle(string $text): string
    {
        return $this->checker->check($text)->replaced();
    }
}
