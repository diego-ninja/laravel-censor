<?php

namespace Ninja\Censor\Services\Adapters;

use Ninja\Censor\Collections\MatchCollection;
use Ninja\Censor\Enums\Category;
use Ninja\Censor\Result\AbstractResult;
use Ninja\Censor\Services\Contracts\ServiceResponse;
use Ninja\Censor\ValueObject\Confidence;
use Ninja\Censor\ValueObject\Score;
use Ninja\Censor\ValueObject\Sentiment;
use Ninja\Censor\Whitelist;

final readonly class CensorAdapter extends AbstractAdapter
{
    /**
     * @param  array{result: AbstractResult}  $response
     */
    public function adapt(string $text, array $response): ServiceResponse
    {
        /** @var AbstractResult $result */
        $result = $response['result'];

        return new class($result) implements ServiceResponse
        {
            public function __construct(
                private readonly AbstractResult $result
            ) {}

            public function original(): string
            {
                return $this->result->original();
            }

            public function replaced(): string
            {
                return $this->result->replaced();
            }

            public function matches(): ?MatchCollection
            {
                return $this->result->matches();
            }

            public function score(): ?Score
            {
                return $this->result->score();
            }

            public function confidence(): ?Confidence
            {
                return $this->result->confidence();
            }

            /**
             * @return array<Category>
             */
            public function categories(): array
            {
                return $this->result->categories();
            }

            public function sentiment(): ?Sentiment
            {
                return $this->result->sentiment();
            }
        };
    }
}
