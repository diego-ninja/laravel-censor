<?php

namespace Ninja\Censor\Services\Contracts;

use Ninja\Censor\Collections\MatchCollection;
use Ninja\Censor\Enums\Category;
use Ninja\Censor\ValueObject\Confidence;
use Ninja\Censor\ValueObject\Score;
use Ninja\Censor\ValueObject\Sentiment;

interface ServiceResponse
{
    public function original(): string;

    public function replaced(): ?string;

    public function matches(): ?MatchCollection;

    public function score(): ?Score;

    public function confidence(): ?Confidence;

    /** @return array<Category>|null */
    public function categories(): ?array;

    public function sentiment(): ?Sentiment;
}