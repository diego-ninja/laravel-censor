<?php

namespace Ninja\Censor\Index;

use Generator;
use Ninja\Censor\Dictionary\LazyDictionary;

final class TrieIndex
{
    /** @var array<string, array<string, array<string, bool|array<string, bool|array<string, bool|array<string, bool>>>>>> */
    private array $root;

    /**
     * @param  array<int, string>|LazyDictionary  $words
     */
    public function __construct(array|LazyDictionary $words = [])
    {
        $this->root = [];
        foreach ($words as $word) {
            $this->insert($word);
        }
    }

    public function insert(string $word): void
    {
        /** @var array<string, array<string, bool|array<string, bool|array<string, bool|array<string, bool>>>>> $node */
        $node = &$this->root;
        foreach (str_split(strtolower($word)) as $char) {
            if (! isset($node[$char])) {
                $node[$char] = [];
            }
            $node = &$node[$char];
        }
        $node['$'] = true;
    }

    public function search(string $word): bool
    {
        $node = $this->root;
        foreach (str_split(strtolower($word)) as $char) {
            if (! isset($node[$char])) {
                return false;
            }
            $node = $node[$char];
        }

        return isset($node['$']);
    }

    /**
     * @return array<int, string>
     */
    public function searchPrefix(string $prefix): array
    {
        $node = $this->root;
        $words = [];

        foreach (str_split(strtolower($prefix)) as $char) {
            if (! isset($node[$char])) {
                return [];
            }
            $node = $node[$char];
        }

        $this->collectWords($node, $prefix, $words);

        return $words;
    }

    /**
     * @param  array<string, bool|array<string, bool|array<string, bool|array<string, bool>>>>  $node
     * @param  array<int, string>  $words
     */
    private function collectWords(array $node, string $prefix, array &$words): void
    {
        if (isset($node['$'])) {
            $words[] = $prefix;
        }

        foreach ($node as $char => $child) {
            if ($char !== '$') {
                $this->collectWords($child, $prefix.$char, $words);
            }
        }
    }
}
