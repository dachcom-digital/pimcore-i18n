# Pimcore - i18n Manager

[![Software License](https://img.shields.io/badge/license-GPLv3-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Latest Release](https://img.shields.io/packagist/v/dachcom-digital/i18n.svg?style=flat-square)](https://packagist.org/packages/dachcom-digital/i18n)
[![Tests](https://img.shields.io/github/workflow/status/dachcom-digital/pimcore-i18n/Codeception/master?style=flat-square&logo=github&label=codeception)](https://github.com/dachcom-digital/pimcore-i18n/actions?query=workflow%3ACodeception+branch%3Amaster)
[![PhpStan](https://img.shields.io/github/workflow/status/dachcom-digital/pimcore-i18n/PHP%20Stan/master?style=flat-square&logo=github&label=phpstan%20level%204)](https://github.com/dachcom-digital/pimcore-i18n/actions?query=workflow%3A"PHP+Stan"+branch%3Amaster)

![i18n](https://user-images.githubusercontent.com/700119/27761666-f3ed6746-5e60-11e7-955a-3030453c68ff.jpg)

## Scheme
![i18n scheme](https://user-images.githubusercontent.com/700119/141124503-59576527-e5b1-47b3-a38e-d06e51555bde.png)

## Introduction
Pimcore already comes with some great features to build internationalized websites. 
But there are some gaps we have to handle by ourselves: search engine guidelines, geo based redirects, dynamic link handling for internal documents and of course: full qualified URLs for and in every context. 
This bundle helps you to master this challenges and gives you the freedom to elaborate complex URL building and (country) based localization strategies.
**Please read the read the [I18n overview page](./docs/1_I18n.md) before starting!**

### Release Plan
| Release | Supported Pimcore Versions        | Supported Symfony Versions | Release Date | Maintained     | Branch     |
|---------|-----------------------------------|----------------------------|--------------|----------------|------------|
| **4.x** | `10.1`                            | `5.3`                      | 12.11.2021   | Feature Branch | master     |
| **3.x** | `6.0` - `6.3`, `6.5` - `6.9`      | `3.4`, `^4.4`              | 18.07.2019   | Unsupported    | 3.x        |
| **2.4** | `5.4`, `5.5`, `5.6`, `5.7`, `5.8` | `3.4`                      | 24.05.2019   | Unsupported    | 2.4        |

### Installation  

```json
"require" : {
    "dachcom-digital/i18n" : "~4.0.0"
}
```

- Execute: `$ bin/console pimcore:bundle:enable I18nBundle`
- Execute: `$ bin/console pimcore:bundle:install I18nBundle`

## Upgrading
- Execute: `$ bin/console doctrine:migrations:migrate --prefix 'I18nBundle\Migrations'`

## Features
- Generate fully qualified URLs in any context with symfony's default router
- Geo redirects (read more about the redirector adapter [here](docs/51_RedirectorAdapter.md))
- Thanks to the hardlink element you can easily create copies of webpages with additional country information without adding and maintaining duplicate content
- Manage [href-lang](docs/25_HrefLang.md) tags
- Domain mapping (`domain.com`) and/or language slug (`/en`) strategies
- [front page mapping](docs/30_FrontPageMapping.md) for hardlink trees

### Before you start
When using this bundle, you should:
- **not** using any router but the default `RouterInterface` object. 
- **not** using `pimcore_url` or `$staticRoute->assemble()` but using the default `RouterInterface` instead
- extend your `LinkGeneratorInterface` objects with the `I18nLinkGeneratorInterface` and adjust dem accordingly
- **read** the [How I18nBundle works](./docs/1_I18n.md) section

### Preparation
- If you're using `system` as your `locale_adapter`, which is the default, you need to enable all required locales in pimcore system settings
- Always be sure that every document translation is connected via the [localization tool](https://www.pimcore.org/docs/5.0.0/Multi_Language_i18n/Localize_your_Documents.html).
- If you're using the country detection, you need a valid maxmind geo ip [data provider](docs/10_GeoControl.md)

## Further Information
- [I18n Overview Page](./docs/1_I18n.md): Learn all about the i18n principals.
- [Geo IP/Control](docs/10_GeoControl.md): Enable GeoIP Data Provider.
- [Zone Definitions](docs/20_Zones.md): Learn more about i18n zone definitions and how to manage them.
  - [Custom I18n Context Look-Up](docs/21_I18nContext.md)] (🔥 New!)
- [Href-Lang](docs/25_HrefLang.md): Find out more about the href-lang tag generator.
- [Language Configuration](docs/26_Languages.md): Configure languages.
- [Country Configuration](docs/27_Countries.md): Configure countries.
- Route and Alternate Links Generation
  - [Document Routes](docs/90_DocumentRoutes.md): Build document routes
  - [Static Routes](docs/91_StaticRoutes.md): Build translatable static routes and implement href-lang tags.
  - [Symfony Route](docs/92_SymfonyRoutes.md): Build translatable symfony routes and implement href-lang tags.
- [Front Page Mapping](docs/30_FrontPageMapping.md): Learn how to map a custom front page.
- [Localized Error Documents](docs/40_LocaleErrorDocument.md): Learn how to create localized error documents.
- [Custom Locale Adapter](docs/50_CustomLocaleAdapter.md): Learn how to create a custom locale adapter.
- [Redirector Adapter](docs/51_RedirectorAdapter.md): Learn more about redirector adapter and how to implement a custom one.
- [Pimcore Redirects with I18n](docs/52_PimcoreRedirects.md): Learn how to create localized pimcore redirects.
- [Code Examples](docs/60_CodeExamples.md): See some examples.
- [Context Switch Event](docs/70_ContextSwitch.md): Detect zone/language/country switches.
- [Canonical Links](docs/80_CanonicalLinks.md): Canonical links in hardlinks.
- [Navigation Caching](docs/110_NavigationCaching.md): Cache your navigation right!
- [Cookie Settings](docs/120_CookieSettings.md): Change Symfony default cookie settings.

## Copyright and License
Copyright: [DACHCOM.DIGITAL](http://dachcom-digital.ch)  
For licensing details please visit [LICENSE.md](LICENSE.md)

## Upgrade Info
Before updating, please [check our upgrade notes!](UPGRADE.md)  
