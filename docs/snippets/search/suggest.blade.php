<input type="search" id="q" autocomplete="off" aria-label="Search" aria-controls="suggestions">
<ul id="suggestions" role="listbox"></ul>

<script>
    const input = document.getElementById('q');
    const list = document.getElementById('suggestions');
    let timer;

    input.addEventListener('input', () => {
        clearTimeout(timer);

        // Wait for a pause in typing so each keystroke does not become a request.
        timer = setTimeout(async () => {
            const response = await fetch(@js(route('search.suggest')) + '?q=' + encodeURIComponent(input.value));
            const suggestions = response.ok ? await response.json() : [];

            list.replaceChildren(...suggestions.map((suggestion) => {
                const item = document.createElement('li');
                const link = document.createElement('a');

                link.href = suggestion.url;
                link.textContent = suggestion.subtitle ? `${suggestion.title} (${suggestion.subtitle})` : suggestion.title;
                item.append(link);

                return item;
            }));
        }, 200);
    });
</script>
