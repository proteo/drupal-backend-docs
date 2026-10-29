<x-drupal::related :entity="$talk" :criteria="['topics', 'tracks']" order="-created">
    <h2>More talks ({{ $component->entities()->count() }})</h2>
    <ul>
        @foreach ($component->entities() as $entity)
            <li><a href="{{ $entity->url() }}">{{ $entity->title }}</a></li>
        @endforeach
    </ul>
</x-drupal::related>
