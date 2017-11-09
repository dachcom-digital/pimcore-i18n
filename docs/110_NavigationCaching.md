# Navigation Caching
Every navigation has a default cache key.
If you're using this bundle, your navigation links may change the url structure after switching the context (browsing from `/en` to `/en-us` for example).

> Since `/en-us/` could be a hardlink, all underlying links need to be transformed (otherwise they would still point to `/en/xy`)

So, always bind your navigation cache key to the current locale:
```yaml
{% set menu = pimcore_build_nav(page.getDocument(), page.getDocument(), null, app.request.locale) %}
```