<?php

return [
    'spoilers' => [
        // Default behavior when a gated block is locked. Options: 'safe', 'stub'
        'default_locked_mode' => 'safe',

        // Text used for stub blocks (when locked_mode = 'stub').
        'stub_text' => 'Spoiler content hidden. Continue reading to unlock this section.',
    ],
    'links' => [
        /**
         * How to generate URLs to an entry for markdown link insertion.
         *
         * Supported:
         * - null (default): "/wiki/{slug}"
         * - callable: function(\Searsandrew\SeriesWiki\Models\Entry $entry): string
         * - class-string: a class with __invoke(Entry $entry): string (resolved from container)
         */
        'url_generator' => null,
    ],
];