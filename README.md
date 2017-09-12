# Pimcore 5 - i18n Manager

![i18n](https://user-images.githubusercontent.com/700119/27761666-f3ed6746-5e60-11e7-955a-3030453c68ff.jpg)

## Requirements
* Pimcore 5. Only with Build 108 or greater.

#### Pimcore 4 
Get the Pimcore4 Version [here](https://github.com/dachcom-digital/pimcore-i18n/tree/pimcore4).

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
- frontpage mapping for hardlink trees

### Preparation
- If you're using `i18n.adapter.language.system` as your language adapter, which is the default, you need to enable all global languages in pimcore system settings
- If you're using `i18n.adapter.country.system` as your country adapter, which is the default, you need to enable all global countries (also called languages in pimcore) in pimcore system settings
- Always be sure that every document translation is connected via the [localization tool](https://www.pimcore.org/docs/5.0.0/Multi_Language_i18n/Localize_your_Documents.html).

### Zones
[Click here](docs/20_Zones.md) to learn more about i18n zones and how to manage them.

### Front Page Mapping
[Click here](docs/30_FrontPageMapping.md) to learn how to map a custom front page.

### Localized Error Documents
[Click here](docs/40_LocaleErrorDocument.md) to learn how to create localized error documents.

### Custom Adapter
[Click here](docs/50_CustomAdapter.md) to learn how to create a custom adapter.

### Code Examples
[Click here](docs/60_CodeExamples.md) to see some examples.

## Copyright and License
Copyright: [DACHCOM.DIGITAL](http://dachcom-digital.ch)  
For licensing details please visit [LICENSE.md](LICENSE.md)  