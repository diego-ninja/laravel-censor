<?php

namespace Ninja\Censor\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Ninja\Censor\Enums\Category;
use Ninja\Censor\Result\AbstractResult;

/**
 * @property AbstractResult $resource
 *
 * @mixin AbstractResult
 */
class CensorResultResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'text' => [
                'original' => $this->resource->original(),
                'replaced' => $this->resource->replaced(),
            ],
            'offensive' => $this->resource->offensive(),
            'words' => $this->resource->words(),
            'score' => $this->resource->score()?->value(),
            'confidence' => $this->resource->confidence()?->value(),
            'categories' => array_map(function (Category $category) {
                return $category->value;
            }, $this->resource->categories()),
            'matches' => MatchResource::collection($this->resource->matches()),
        ];
    }
}
