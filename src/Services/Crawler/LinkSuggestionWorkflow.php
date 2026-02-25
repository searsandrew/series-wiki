<?php

namespace Searsandrew\SeriesWiki\Services\Crawler;

use Illuminate\Support\Str;
use Searsandrew\SeriesWiki\Models\Entry;
use Searsandrew\SeriesWiki\Models\EntryBlock;
use Searsandrew\SeriesWiki\Models\EntrySnapshot;
use Searsandrew\SeriesWiki\Models\LinkSuggestion;

class LinkSuggestionWorkflow
{
    /**
     * Mark a suggestion accepted (does not modify content).
     */
    public function accept(LinkSuggestion $suggestion): LinkSuggestion
    {
        $suggestion->status = 'accepted';
        $suggestion->save();

        return $suggestion;
    }

    /**
     * Mark a suggestion dismissed (does not modify content).
     */
    public function dismiss(LinkSuggestion $suggestion): LinkSuggestion
    {
        $suggestion->status = 'dismissed';
        $suggestion->save();

        return $suggestion;
    }

    /**
     * Apply a suggestion to content by inserting a markdown link in the specified block.
     *
     * This will:
     * - refuse if suggestion is not 'new' (unless $allowReapply)
     * - refuse if content changed (snapshot hash mismatch) unless $ignoreStale
     * - refuse if the anchor phrase is already linked in markdown
     * - link only the first occurrence by default (can extend later)
     *
     * @return array{applied:bool, reason?:string, updated_block?:EntryBlock}
     */
    public function applyToBlock(
        LinkSuggestion $suggestion,
        bool $allowReapply = false,
        bool $ignoreStale = false
    ): array {
        if (! $allowReapply && $suggestion->status !== 'new') {
            return ['applied' => false, 'reason' => 'Suggestion is not new'];
        }

        $entry = Entry::query()->find($suggestion->entry_id);
        if (! $entry) {
            return ['applied' => false, 'reason' => 'Source entry not found'];
        }

        $blockKey = $suggestion->block_key;
        if (! $blockKey) {
            return ['applied' => false, 'reason' => 'Suggestion missing block_key'];
        }

        /** @var EntryBlock|null $block */
        $block = EntryBlock::query()
            ->where('entry_id', $entry->id)
            ->where('key', $blockKey)
            ->first();

        if (! $block) {
            return ['applied' => false, 'reason' => 'Target block not found'];
        }

        // Optional stale protection: compare against latest snapshot hash
        if (! $ignoreStale && $suggestion->snapshot_hash) {
            $latestHash = EntrySnapshot::query()
                ->where('entry_id', $entry->id)
                ->orderByDesc('created_at')
                ->value('hash');

            if ($latestHash && ! hash_equals((string) $latestHash, (string) $suggestion->snapshot_hash)) {
                return ['applied' => false, 'reason' => 'Suggestion is stale (entry changed since crawl)'];
            }
        }

        $phrase = (string) ($suggestion->meta['phrase'] ?? $suggestion->anchor_text);
        $phrase = trim($phrase);

        if ($phrase === '') {
            return ['applied' => false, 'reason' => 'Missing phrase'];
        }

        $target = Entry::query()->find($suggestion->suggested_entry_id);
        if (! $target) {
            return ['applied' => false, 'reason' => 'Suggested entry not found'];
        }

        // Build default URL format (host app can override later)
        $url = $this->entryUrl($target);

        $body = (string) ($block->body_full ?? '');

        if ($body === '') {
            return ['applied' => false, 'reason' => 'Block body is empty'];
        }

        // If already linked as markdown anchor, refuse
        if ($this->isAlreadyLinked($body, $phrase)) {
            return ['applied' => false, 'reason' => 'Phrase already linked'];
        }

        $updated = $this->linkFirstOccurrence($body, $phrase, $url);

        if ($updated === $body) {
            return ['applied' => false, 'reason' => 'Phrase not found in block body'];
        }

        $block->body_full = $updated;
        $block->save();

        // Mark suggestion accepted
        $suggestion->status = 'accepted';
        $suggestion->save();

        return ['applied' => true, 'updated_block' => $block];
    }

    protected function isAlreadyLinked(string $text, string $phrase): bool
    {
        $pattern = '/\[' . preg_quote($phrase, '/') . '\]\(/i';
        return (bool) preg_match($pattern, $text);
    }

    /**
     * Replace the first occurrence of phrase with [phrase](url), word-boundary-ish.
     */
    protected function linkFirstOccurrence(string $text, string $phrase, string $url): string
    {
        $pattern = '/(?<!\w)(' . preg_quote($phrase, '/') . ')(?!\w)/i';

        return preg_replace_callback($pattern, function ($m) use ($url) {
            $match = $m[1];
            return '[' . $match . '](' . $url . ')';
        }, $text, 1) ?? $text;
    }

    protected function entryUrl(Entry $entry): string
    {
        $gen = config('series-wiki.links.url_generator');

        if ($gen === null) {
            return '/wiki/' . $entry->slug;
        }

        if (is_callable($gen)) {
            return (string) $gen($entry);
        }

        if (is_string($gen) && class_exists($gen)) {
            $callable = app($gen);
            return (string) $callable($entry);
        }

        // Fallback: safest default
        return '/wiki/' . $entry->slug;
    }
}