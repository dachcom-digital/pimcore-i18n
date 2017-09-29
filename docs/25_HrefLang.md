# href-lang Tags
<img width="752" src="https://user-images.githubusercontent.com/700119/31016922-f57b5566-a526-11e7-9bfb-c3d088bffc4e.png">

The i18nBundle will handle the href lang tags for you which are very important for search engines.

## Prearrangement
It's very important, that you're connect your localized documents using the [pimcore localization service](https://pimcore.com/docs/5.0.x/Multi_Language_i18n/Localize_your_Documents.html#page_Localization_Tool).

> **Note**: If you're **not** localizing your documents, no hreflang tags will be generated which will lead to a negative impact on your SEO strategies.  

## x-default
i18n will generate a x-default link, based on default values:

### default language workflow
- First, the i18nBundle will search for the `default_language` config.
- If not defined, the default system language will be used
- If not defined, NULL will be returned (which means, no x-default tag)
> **Note**: It's possible to override the `getDefaultLanguage()` method within a custom language adapter since it's placed in the abstract class.

### default country workflow
- First, the i18nBundle will search for the `default_country` config.
- If not defined, the default system country (for example, en_US => US) will be used
- If not defined, NULL will be returned
> **Note**: It's possible to override the `getDefaultCountry()` method within a custom country adapter since it's placed in the abstract class.

**Important:** Be sure that both default values (language, country) are valid (in current zone). Otherwise no x-default tag will be generated.