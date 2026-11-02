<?php

namespace App\Services\Generator;

final class ScenarioParser
{
    /**
     * Extract scenario names from Gherkin-style acceptance criteria.
     *
     * @return array<int, string>
     */
    public static function names(string $criteria, string $fallbackName): array
    {
        preg_match_all('/^\s*(?:scenario|skenario)\s*:\s*(.+)$/mi', $criteria, $matches);

        $names = array_values(array_filter(
            array_map('trim', $matches[1]),
            fn (string $name): bool => $name !== '',
        ));

        return $names === [] ? [$fallbackName] : $names;
    }
}
