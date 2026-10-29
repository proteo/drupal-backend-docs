<div>
    <ul>
        @foreach($items as $item)
            <li wire:key="talk-{{ $item->id }}">
                <a href="{{ $item->url() }}">{{ $item->title }}</a>
            </li>
        @endforeach
    </ul>

    @isset($pages['next'])<a href="{{ $pages['next'] }}" rel="next">More talks</a>@endisset
</div>
