# href-lang Tags
<img width="752" src="https://user-images.githubusercontent.com/700119/31016922-f57b5566-a526-11e7-9bfb-c3d088bffc4e.png">

The i18nBundle will handle the href lang tags for you which are very important for search engines.

## Prearrangement
It's very important that you connect your localized documents using the [pimcore localization service](https://pimcore.com/docs/5.0.x/Multi_Language_i18n/Localize_your_Documents.html#page_Localization_Tool).

> **Note**: If you're **not** localizing your documents, no href lang tags will be generated, which will lead to a negative impact on your SEO strategies. There is, however, some magic if you're using the [country mode](27_Countries.md).

**Localization Connector:** There's a [dedicated help document](100_LocalizeDocuments.md) we wrote for you to get all the knowledge for heroic document localization!

## X-Default
i18n will generate a x-default link, based on default values:

### Default Locale Workflow
- First, the i18nBundle will search for the `default_locale` config.
- If not defined, the default system locale will be used
- If not defined, NULL will be returned (which means, no x-default tag)
> **Note**: It's possible to override the `getDefaultLocale()` method within a custom locale adapter.

**Important:** Be sure that your defined default locale is valid (in current zone). Otherwise no x-default tag will be generated.

## Static Routes
If you're using static routes, please [read this](28_StaticRoutes.md) to enable href-lang tags for dynamic routes.
