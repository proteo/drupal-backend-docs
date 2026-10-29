<section>
    <h2>More talks</h2>
    <x-drupal::related :entity="$talk" :criteria="['topics', 'tracks']" order="-created"/>
</section>
