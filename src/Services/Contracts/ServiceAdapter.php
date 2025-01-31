<?php

namespace Ninja\Censor\Services\Contracts;

interface ServiceAdapter
{
    /**
     * @param  array<string, mixed>  $response
     */
    public function adapt(string $text, array $response): ServiceResponse;
}
