# Canonical Links
If you're using hardlinks as country wrapper, there is something you need to know:
Pimcore automatically adds a canonical link to each request header, if a page is wrapped into a hardlink.

## Explanation

```markdown
Document: `/en/news`
HardLink: `/en-us` (source path would be `/en`)
```

Since the `news` document only exists in `/en`, pimcore adds a canonical link to avoid duplicate content - which is perfectly fine.
So if you're going to check the page `/en-us/news`, there should be a canonical relation like this:

```markdown
HTTP/2 200 
link: <https://pimcore5-domain4.dev/en/news>; rel="canonical"
```

But in this case, we want to remove the canonical link because we need country specific content and we'll tell search engines about the references via [href-lang tags](25_HrefLang.md) anyway.
So the i18nBundle will remove this canonical tag automatically for you. If you check your http request after installing this bundle, the canonical link will be gone.

## Oh no!
**But!** What if you have some hardlinks inside the country site? For Example:

```markdown
Document: `/en-us/products/demo`
HardLink: `/en-us/special-products` (source path would be `/en-us/products`)
```

Don't worry. Just check the header for `/en-us/special-products/demo`, the canonical relation is still available. The I18nBundle only checks hardlinks on root (`/`), since that's the only place where country based hardlinks makes sense.

```markdown
HTTP/2 200 
link: <https://pimcore5-domain4.dev/en-us/products/demo>; rel="canonical"
```
