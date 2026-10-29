{{-- One result row. `$item` is the entity, `$highlight` the sanitised terms. --}}
<a href="{{ $item->url() }}"><x-drupal::highlight :text="$item->title" :terms="$highlight"/></a>
