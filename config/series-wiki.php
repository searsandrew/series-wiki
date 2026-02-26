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
    'blocks' => [
        'allow_unknown_types' => false,

        /**
         * Override or add new block types.
         *
         * Structure:
         * 'type-name' => [
         *   'data' => [ 'field' => 'rule|rule', ... ],
         *   'body_full' => 'rule|rule' (optional),
         *   'body_safe' => 'rule|rule' (optional),
         * ]
         */
        'types' => [],
    ],
    'entries' => [
        'allow_unknown_types' => true,

        /**
         * Entry meta validation rules by entry type.
         * These rules apply to Entry->meta (JSON cast to array).
         *
         * Example:
         * 'ship' => [
         *   'class' => 'sometimes|string|max:100',
         *   'length_m' => 'sometimes|numeric|min:0',
         *   'crew' => 'sometimes|integer|min:0',
         *   'homeworld_entry_id' => 'sometimes|string|max:26',
         * ]
         */
        'types' => [],
    ],
];