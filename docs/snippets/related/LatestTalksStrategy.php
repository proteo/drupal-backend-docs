<?php

namespace App\Content\Related;

use Illuminate\Support\Collection;
use Proteo\DrupalFront\Content\ContentFetcher;
use Proteo\DrupalFront\Content\Entity;
use Proteo\DrupalFront\Content\Related\RelatedRequest;
use Proteo\DrupalFront\Content\Related\RelatedStrategy;

/**
 * Suggests the newest talks instead of ranking by shared topics.
 */
final class LatestTalksStrategy implements RelatedStrategy
{
    public function __construct(private readonly ContentFetcher $fetcher) {}

    public function related(RelatedRequest $request): Collection
    {
        // One extra, so dropping the current talk still leaves a full list.
        return $this->fetcher->all($request->source->type, 1, $request->limit + 1)->items
            ->reject(fn (Entity $talk): bool => $talk->id === $request->source->id)
            ->take($request->limit)
            ->values();
    }
}
