<?php

namespace Ninja\Censor\Dictionary;

use Generator;
use IteratorAggregate;
use Ninja\Censor\Exceptions\DictionaryFileNotFound;
use SplFixedArray;

/**
 * @implements IteratorAggregate<int, string>
 */
final class LazyDictionary implements IteratorAggregate
{
    private const CHUNK_SIZE = 1000;

    /** @var array<string,SplFixedArray<string>> */
    private array $chunks = [];

    /** @var array<string>|null */
    private ?array $customWords = null;

    /**
     * @param  string[]  $languages
     */
    public function __construct(
        private readonly array $languages,
        private ?string $dictionaryPath = null
    ) {
        /** @var string $path */
        $path = config('censor.dictionary_path');
        $this->dictionaryPath = $dictionaryPath ?? $path;
    }

    /**
     * @return Generator<int, string>
     */
    public function getIterator(): Generator
    {
        yield from $this->loadWordsLazily();
    }

    /**
     * @return Generator<int, string>
     */
    private function loadWordsLazily(): Generator
    {
        if ($this->customWords !== null) {
            foreach ($this->customWords as $word) {
                if (is_string($word)) {
                    yield $word;
                }
            }

            return;
        }

        $seenWords = [];

        foreach ($this->languages as $language) {
            $dictionaryFile = sprintf('%s/%s.php', $this->dictionaryPath, $language);

            if (! file_exists($dictionaryFile)) {
                throw DictionaryFileNotFound::withFile($dictionaryFile);
            }

            /** @var array<string> $words */
            $words = include $dictionaryFile;
            $totalChunks = ceil(count($words) / self::CHUNK_SIZE);

            for ($i = 0; $i < $totalChunks; $i++) {
                /** @var array<int,string> $chunk */
                $chunk = array_values(array_slice($words, $i * self::CHUNK_SIZE, self::CHUNK_SIZE));

                $chunkKey = "{$language}_$i";
                $fixedArray = SplFixedArray::fromArray($chunk);
                $this->chunks[$chunkKey] = $fixedArray;

                foreach ($this->chunks[$chunkKey] as $word) {
                    if (is_string($word) && ! isset($seenWords[$word])) {
                        $seenWords[$word] = true;
                        yield $word;
                    }
                }

                unset($this->chunks[$chunkKey]);
            }

            unset($words);
            gc_collect_cycles();
        }
    }

    /**
     * @param  string[]  $words
     */
    public static function withWords(array $words): self
    {
        $filteredWords = array_filter($words, fn ($word) => $word !== '');
        $instance = new self(['custom']);
        $instance->customWords = array_unique($filteredWords);

        return $instance;
    }

    /**
     * @param  string[]  $languages
     */
    public static function withLanguages(array $languages): self
    {
        return new self($languages);
    }

    /**
     * @return array<string>
     */
    public function getWords(): array
    {
        return iterator_to_array($this->getIterator());
    }
}
