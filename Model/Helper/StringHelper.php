<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\Helper;

class StringHelper
{
    /**
     * Pluralize word
     *
     * @param string $word
     * @return string
     */
    public function pluralize(string $word): string
    {
        if (empty($word)) {
            return $word;
        }

        $irregulars = [
            'person' => 'people',
            'child' => 'children',
            'man' => 'men',
            'woman' => 'women',
        ];

        if (isset($irregulars[$word])) {
            return $irregulars[$word];
        }

        $lastChar = substr($word, -1);
        $lastTwo = substr($word, -2);

        if (in_array($lastChar, ['s', 'x', 'z']) || in_array($lastTwo, ['ch', 'sh'])) {
            return $word . 'es';
        }

        if ($lastChar === 'y' && !in_array(substr($word, -2, 1), ['a', 'e', 'i', 'o', 'u'])) {
            return substr($word, 0, -1) . 'ies';
        }

        return $word . 's';
    }
}
