# Pimcore 5 - i18n Manager

![i18n](https://user-images.githubusercontent.com/700119/27761666-f3ed6746-5e60-11e7-955a-3030453c68ff.jpg)

## Requirements
* Pimcore 5.

## Introduction
Pimcore already comes with some great features to build internationalized websites. But there are some gaps we have to handle by ourselves: search engine guidelines, geo based redirects and a dynamic link handling for internal documents. 
This Bundle helps you mastering this challenges and gives you freedom to elaborating complex country based localization strategies.

### Installation  
1. Add code below to your `composer.json`    
2. Activate & install it through the ExtensionManager

```json
"require" : {
    "dachcom-digital/i18n" : "dev-master",
}
```

## Features
- Geo redirects
- Thanks to the hardlink element you can easily create copies of webpages with additional country information without adding and maintain duplicate content
- Manage href-lang tags
- Handle internal link redirects based on hardlink context
- Domain mapping (`domain.com`) and/or language slug (`/en`) strategies
- front page mapping for hardlink trees

### Preparation
- If you're using `system` as your language adapter, which is the default, you need to enable all global languages in pimcore system settings
- If you're using `system` as your country adapter, which is the default, you need to enable all global countries (also called languages in pimcore) in pimcore system settings
- Always be sure that every document translation is connected via the [localization tool](https://www.pimcore.org/docs/5.0.0/Multi_Language_i18n/Localize_your_Documents.html).

## Further Information
- [Zones](docs/20_Zones.md): Learn more about i18n zones and how to manage them.
- [Href-Lang](docs/25_HrefLang.md): Find out more about the href-lang tag generator.
- [Language Configuration](docs/26_Languages.md): Configure Languages.
- [Country Configuration](docs/27_Countries.md): Configure Countries.
- [Front Page Mapping](docs/30_FrontPageMapping.md): Learn how to map a custom front page.
- [Localized Error Documents](docs/40_LocaleErrorDocument.md): Learn how to create localized error documents.
- [Custom Adapter](docs/50_CustomAdapter.md): Learn how to create a custom adapter.
- [Code Examples](docs/60_CodeExamples.md): See some examples.
- [Context Switch Event](docs/70_ContextSwitch.md): Detect Zone/Language/Country Switches.

## Copyright and License
Copyright: [DACHCOM.DIGITAL](http://dachcom-digital.ch)  
For licensing details please visit [LICENSE.md](LICENSE.md)

## Upgrade Info
Before updating, please [check our upgrade notes!](UPGRADE.md)  