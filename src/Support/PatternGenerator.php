<?php

namespace Ninja\Censor\Support;

use Ninja\Censor\Dictionary\LazyDictionary;

final class PatternGenerator
{
    /**
     * @var array<string>
     */
    private array $patterns = [];

    /**
     * @param  array<string, string>  $replacements
     */
    public function __construct(private array $replacements = [], private bool $fullWords = true) {}

    public static function withDictionary(LazyDictionary $dictionary): self
    {
        /** @var array<string, string> $replacements */
        $replacements = config('censor.replacements', []);

        $generator = new self($replacements);

        foreach ($dictionary as $word) {
            if (! empty($word)) {
                $generator->patterns = array_merge(
                    $generator->patterns,
                    $generator->forWord($word)
                );
            }
        }

        return $generator;
    }

    /**
     * @return array<string>
     */
    public function forWord(string $word): array
    {
        $basePattern = $this->createBasePattern($word);
        $patterns = [];

        if ($this->fullWords) {
            $patterns[] = '/\b'.$basePattern.'\b/iu';
        } else {
            $patterns[] = '/'.$basePattern.'/iu';
            $patterns[] = '/'.implode('\s+', str_split($basePattern)).'/iu';
            $patterns[] = '/'.implode('[.\-_]+', str_split($basePattern)).'/iu';
        }

        return array_filter($patterns, fn ($pattern) => $this->isValidPattern($pattern));
    }

    /**
     * @param  array<string>  $words
     * @return array<string>
     */
    public function forWords(array $words): array
    {
        foreach ($words as $word) {
            $this->patterns = array_merge($this->patterns, $this->forWord($word));
        }

        return array_unique($this->patterns);
    }

    /**
     * @return array<string>
     */
    public function getPatterns(): array
    {
        return $this->patterns;
    }

    private function createBasePattern(string $word): string
    {
        $escaped = preg_quote($word, '/');

        if ($this->fullWords) {
            return $escaped;
        }

        return str_ireplace(
            array_map(fn ($key) => preg_quote($key, '/'), array_keys($this->replacements)),
            array_values($this->replacements),
            $escaped
        );
    }

    private function isValidPattern(string $pattern): bool
    {
        return @preg_match($pattern, '') !== false;
    }

    public function setFullWords(bool $fullWords): self
    {
        $this->fullWords = $fullWords;

        return $this;
    }

    /**
     * @param  array<string>  $replacements
     */
    public function setReplacements(array $replacements): self
    {
        $this->replacements = $replacements;

        return $this;
    }
}
