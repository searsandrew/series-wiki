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
         * 'planet' => [
         *   'rules' => [
         *     'gravity_g' => 'sometimes|numeric|min:0',
         *     'atmosphere.breathable' => 'sometimes|boolean',
         *   ],
         *   'defaults' => [
         *     'gravity_g' => 1.0,
         *     'atmosphere' => ['breathable' => false],
         *   ],
         *   'fields' => [
         *     ['key' => 'gravity_g', 'label' => 'Gravity (g)'],
         *     ['key' => 'atmosphere.breathable', 'label' => 'Breathable'],
         *   ],
         * ],
         */
        'types' => [],
    ],
];