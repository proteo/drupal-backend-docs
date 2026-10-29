@inject('fetcher', 'Proteo\DrupalFront\Content\ContentFetcher')

{{-- A Reference fails soft: no review id, a missing review or an unreachable site all give null. --}}
@php($review = $fetcher->reference('review', $talk->review_id))

@if ($review)
    <p><a href="{{ $review->url() }}">{{ $review->title }}</a></p>
@endif
