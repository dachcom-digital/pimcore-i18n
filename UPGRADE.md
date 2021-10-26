# Upgrade Notes

## Migrating from Version 3.x to Version 4.0

⚠️ If you're still on version `2.x` or `< 3.2.8`, you need to update to `3.2.8` first,
then [migrate](https://github.com/dachcom-digital/pimcore-i18n/blob/3.x/UPGRADE.md) to `3.3`. 
After that, you're able to update to `^4.0`.

> 💀 I18n has changed fundamentally! Please be careful while migrating!

### Processing Changes: Zone Resolver
I18n will generate a Zone Object at every request which is accessible via a new Service `ZoneResolverInterface`.
But it is also possible to create a custom zone object to fetch all linked documents / objects during process.
Please check out  the [code examples](./docs/60_CodeExamples.md) doc section to learn more about accessing zone information.

- Model `I18nZoneInterface`, `I18nSiteInterface` and `I18nContextInterface` introduced
    - `I18nZoneInterface` contains:
        - all available site of given zone (if no zone has been configured, a default zone will be created)
        - locale provider: provides all available/valid locales for given zone 
        - context: current language/country information
        - path generator: corresponding path generator (document, static route or symfony route)
    - `I18nSiteInterface` contains:
        - all active sites of given zone. You can access them via `$zone->getSites()` but also to fetch the active one via `getCurrentSite()`
    - `I18nContextInterface` contains:
        - current locale information (within given zone)

#### Zone Changes
- API Fetch Change: Instead of calling `$this->zoneManager->getCurrentZoneInfo('mode')` you now need to call `$zone->getMode()`
- TWIG Fetch Change: Instead of calling `i18n_zone_info('mode')` you now need to call `i18n_zone().mode`

#### Context Changes
Context Adapter and Manager have been removed (All corresponding information are available via `I18nZoneInterface` directly).
Every Zone object comes with a context object, which holds the  **current** zone locale (mostly current document locale) information.

- API Fetch Change: Instead of calling `getCurrentContextInfo('url)` you now need to call `$zone->getCurrentSite()->getUrl()`
- TWIG Fetch Change: Instead of calling `i18n_context('getCurrentContextInfo', ['url'])` you now need to call `i18n_zone().currentSite.url`
- `getCurrentLanguageInfo` and `getCurrentCountryInfo` has been removed:
    - API Fetch Change: Instead of calling `$this->contextManager->getContext()->getCurrentLanguageInfo('id')` you now need to call `$zone->getActiveLocaleInfo('id')`
    - API Fetch Change: Instead of calling `$this->contextManager->getContext()->getCurrentCountryInfo('id')` you now need to call `$zone->getActiveLocaleInfo('id')`
    - TWIG Fetch Change: Instead of calling `i18n_context('getCurrentLanguageInfo', ['id']))` you now need to call `i18n_zone().activeLocaleInfo('id')`
    - TWIG Fetch Change: Instead of calling `i18n_context('getCurrentCountryInfo', ['id']))` you now need to call `i18n_zone().activeLocaleInfo('id')`

### Global Changes
- PHP8 return type declarations added: you may have to adjust your extensions accordingly
- Locale Provider:
    - Namespace changed from `I18nBundle\Adapter\Locale` to `I18nBundle\Adapter\LocaleProvider`
- Cache runtime variables `i18n.locale`, `i18n.locale` and `i18n.locale` has been removed. You can access them
  via `zone->getContext()` which will return model `I18nContextInterface`
- `PathGeneratorInterface:getUrl()` signature changed: first parameter `Document` has been removed#
- `RedirectorBag` Changes:
    - Options `i18nType`, `document`, `documentLocale`, `documentCountry`, `defaultLocale` removed. New `zone` option added.
- Error Document Changes:
    - PIMCORE X is supporting localized error documents [by default](https://github.com/pimcore/pimcore/pull/9270) now, so there
      is no need to add custom logic anymore. Make sure that your error documents are defined in site configuration and/or system settings.
- Context Switch Detector:
    - This Listener is now disabled by default and can be enabled by setting configuration
      node `i18n.enable_context_switch_detector: true`

### Event Changes
- `AlternateStaticRouteEvent` Event has been renamed to `DynamicRouteEvent` which also allows symfony routes now
    - Argument Changes:
        - `::getRequestAttributes()` has been removed. use `::getAttributes()` instead (returns simple array)
        - `::getCurrentStaticRoute()` has been removed. use `::getAttributes()['_route']` instead
        - `::getCurrentDocument()` has been removed
        - `::getCurrentLanguage()` has been removed
        - `::getCurrentCountry()` has been removed
        - `::getCurrentLocale()` has been added

### Bugfixes
- DetectorListener: Skip redirecting if request is frontend request by
  admin [@lorextera](https://github.com/dachcom-digital/pimcore-i18n/pull/83)

### Additional new Features
- Check Akamai CDN header [@florian25686](https://github.com/dachcom-digital/pimcore-i18n/pull/76/files)
- Allow different zone look-ups [#70](https://github.com/dachcom-digital/pimcore-i18n/issues/70), read more about it [here](./docs/21_CustomZoneLookUp.md)
- Allow symfony routes [#65](https://github.com/dachcom-digital/pimcore-i18n/issues/65)

***

I18nBundle 3.x Upgrade Notes: https://github.com/dachcom-digital/pimcore-i18n/blob/3.x/UPGRADE.md