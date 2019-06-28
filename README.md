# Pimcore 5 - i18n Manager

[![Software License](https://img.shields.io/badge/license-GPLv3-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Latest Release](https://img.shields.io/packagist/v/dachcom-digital/i18n.svg?style=flat-square)](https://packagist.org/packages/dachcom-digital/i18n)
[![Travis](https://img.shields.io/travis/com/dachcom-digital/pimcore-i18n/master.svg?style=flat-square)](https://travis-ci.com/dachcom-digital/pimcore-i18n)
[![PhpStan](https://img.shields.io/badge/PHPStan-level%202-brightgreen.svg?style=flat-square)](#)

![i18n](https://user-images.githubusercontent.com/700119/27761666-f3ed6746-5e60-11e7-955a-3030453c68ff.jpg)

## Requirements
* Pimcore >= 5.4.0

## Introduction
Pimcore already comes with some great features to build internationalized websites. But there are some gaps we have to handle by ourselves: search engine guidelines, geo based redirects and a dynamic link handling for internal documents. 
This Bundle helps you mastering this challenges and gives you the freedom to elaborate complex country based localization strategies.

### Release Plan

| Release | Supported Pimcore Versions        | Supported Symfony Versions | Release Date | Maintained     | Branch     |
|---------|-----------------------------------|----------------------------|--------------|----------------|------------|
| **3.0** | `6.0`                             | `3.4`, `^4.0`               | --          | Feature Branch | dev-master |
| **2.4** | `5.4`, `5.5`, `5.6`, `5.7`, `5.8` | `3.4`                      | 24.05.2019   | Bugfix only    | 2.4        |

### Installation  

```json
"require" : {
    "dachcom-digital/i18n" : "~2.4.0"
}
```

### Installation via Extension Manager
After you have installed the I18n Bundle via composer, open pimcore backend and go to `Tools` => `Extension`:
- Click the green `+` Button in `Enable / Disable` row
- Click the green `+` Button in `Install/Uninstall` row

## Upgrading

### Upgrading via Extension Manager
After you have updated the I18n Bundle via composer, open pimcore backend and go to `Tools` => `Extension`:
- Click the green `+` Button in `Update` row

### Upgrading via CommandLine
After you have updated the I18n Bundle via composer:
- Execute: `$ bin/console pimcore:bundle:update I18nBundle`

### Migrate via CommandLine
Does actually the same as the update command and preferred in CI-Workflow:
- Execute: `$ bin/console pimcore:migrations:migrate -b I18nBundle`

## Features
- Geo redirects (read more about the redirector adapter [here](docs/51_RedirectorAdapter.md))
- Thanks to the hardlink element you can easily create copies of webpages with additional country information without adding and maintaining duplicate content
- Manage [href-lang](docs/25_HrefLang.md) tags
- [Handle](docs/90_InternalLinkRewriter.md) internal link redirects based on hardlink context
- Domain mapping (`domain.com`) and/or language slug (`/en`) strategies
- [front page mapping](docs/30_FrontPageMapping.md) for hardlink trees

### Preparation
- If you're using `system` as your `locale_adapter`, which is the default, you need to enable all required locales in pimcore system settings
- Always be sure that every document translation is connected via the [localization tool](https://www.pimcore.org/docs/5.0.0/Multi_Language_i18n/Localize_your_Documents.html).

## Further Information
- [Zones](docs/20_Zones.md): Learn more about i18n zones and how to manage them.
- [Href-Lang](docs/25_HrefLang.md): Find out more about the href-lang tag generator.
- [Language Configuration](docs/26_Languages.md): Configure languages.
- [Country Configuration](docs/27_Countries.md): Configure countries.
- [Static Routes](docs/28_StaticRoutes.md): Configure translatable static routes and implement href-lang tags.
- [Front Page Mapping](docs/30_FrontPageMapping.md): Learn how to map a custom front page.
- [Localized Error Documents](docs/40_LocaleErrorDocument.md): Learn how to create localized error documents.
- [Custom Locale Adapter](docs/50_CustomLocaleAdapter.md): Learn how to create a custom locale adapter.
- [Redirector Adapter](docs/51_RedirectorAdapter.md): Learn more about redirector adapter and how to implement a custom one.
- [Code Examples](docs/60_CodeExamples.md): See some examples.
- [Context Switch Event](docs/70_ContextSwitch.md): Detect zone/language/country switches.
- [Canonical Links](docs/80_CanonicalLinks.md): Canonical links in hardlinks.
- [Internal Links](docs/90_InternalLinkRewriter.md): Rewrite Internal links.
- [Navigation Caching](docs/110_NavigationCaching.md): Cache your navigation right!

## Why is there no Version 1?
There is already an i18n plugin for pimcore4 which is not public. With Pimcore5 we decided to move this project to a public github repository. You're welcome. :)

## Copyright and License
Copyright: [DACHCOM.DIGITAL](http://dachcom-digital.ch)  
For licensing details please visit [LICENSE.md](LICENSE.md)

## Upgrade Info
Before updating, please [check our upgrade notes!](UPGRADE.md)  
