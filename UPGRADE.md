# Upgrade Notes

## 4.0.1
- [IMPROVEMENT] Add cookie configuration [@tomhatzer](https://github.com/dachcom-digital/pimcore-i18n/issues/88)

## Migrating from Version 3.x to Version 4.0

⚠️ If you're still on version `2.x` or `< 3.2.8`, you need to update to `3.2.8` first, then [migrate](https://github.com/dachcom-digital/pimcore-i18n/blob/3.x/UPGRADE.md) to `3.3`. 
After that, you're able to update to `^4.0`.

> 💀 I18n has changed fundamentally! Please be careful while migrating!

Please read the [How I18n Works](./docs/1_I18n.md) section before start migrating!

***

### New default properties
You need to define the default scheme and port on every environment

```yaml
i18n:
    request_scheme: 'http'
    request_port: 80
```

### New zone properties
```yaml
i18n:
    zones:
        zone1:
            id: 1
            domains:
                - ['www.test-domain2.test', 'https', 443]   # defined as array you're able to pass scheme and port
                - 'test-domain3.test'                       # still working, default values (i18n.request_scheme, i18n.request_port) will be selected
```

### Global Changes
- `$staticRoute->assemble()` is **not** supported anymore, you always need to call `$router->generate()`:
    - Every PIMCORE LinkGenerator needs to implement the `I18nLinkGeneratorInterface`
    - You need to pass the `_18n => [ type = RouteItemInterface::TYPE, routeParameters => [] ]` block via `$router->generate()` (Or use `RouteParameterBuilder` for parameter building)
- `url()`, `path()`, `pimcore_url()` twig helper are not supported, use `i18n_entity_route()`, `i18n_static_route()` and `i18n_symfony_route()` instead
- Context Adapter and Manager have been removed (All corresponding information are available via `I18nContextInterface` directly)
- PHP8 return type declarations added: you may have to adjust your extensions accordingly
- `LocaleProviderInterface` changes:
    - Namespace changed from `I18nBundle\Adapter\Locale` to `I18nBundle\Adapter\LocaleProvider`
    - `LocaleProviderInterface` signatures changed:
         - `::setCurrentZoneConfig()` removed
         - `::getLocaleData()` removed
         - `::getActiveLocales(ZoneInterface $zone)` signature changed
         - `::getDefaultLocale(ZoneInterface $zone)` signature changed
         - `::getGlobalInfo(ZoneInterface $zone)` signature changed
- `PathGeneratorInterface` changes: 
     - `::getUrl(I18nZoneInterface $zone, bool $onlyShowRootLanguages = false)` signature changed
     - `::configureOptions(OptionsResolver $options)` added
- `RedirectorBag` Changes:
    - Options `i18nType`, `document`, `documentLocale`, `documentCountry`, `defaultLocale` removed. New `zone` option added.
- Cache runtime variables `i18n.locale`, `i18n.locale` and `i18n.locale` has been removed. You can access them via `$i18nContext->getLocaleDefinition()` which will return model `LocaleDefinitionInterface`
- Error Document Changes:
    - PIMCORE X is supporting localized error documents [by default](https://github.com/pimcore/pimcore/pull/9270) now, so there
      is no need to add custom logic anymore. Make sure that your error documents are defined in site configuration and/or system settings.
- Context Switch Detector:
    - This Listener is now disabled by default and can be enabled by setting configuration node `i18n.enable_context_switch_detector: true`

### Event Changes
- `AlternateStaticRouteEvent` Event has been renamed to `AlternateDynamicRouteEvent` which also allows symfony routes now
    - Argument Changes:
        - `::getI18nList()` has been removed. Use ``::getAlternateRouteItems()`` instead
        - `::setRoutes()` has been removed. Not required anymore
        - `::getRequestAttributes()` has been removed. use `::getCurrentRouteAttributes()` instead (returns simple array)
        - `::getCurrentStaticRoute()` has been removed. use `::getCurrentRouteName()` instead
        - `::getCurrentDocument()` has been removed
        - `::getCurrentLanguage()` has been removed
        - `::getCurrentCountry()` has been removed
        - `::getType()` has been added
        - `::getCurrentLocale()` has been added
        - `::isCurrentRouteHeadless()` has been added
        - `::getCurrentRouteName()` has been added
        - `::getCurrentRouteAttributes()` has been added
        - `::getCurrentRouteParameters()` has been added

### Bugfixes
- DetectorListener: Skip redirecting if request is frontend request by admin [@lorextera](https://github.com/dachcom-digital/pimcore-i18n/pull/83)

### Additional new Features
- Check Akamai CDN header [@florian25686](https://github.com/dachcom-digital/pimcore-i18n/pull/76/files)
- Allow different I18nContext look-ups [#70](https://github.com/dachcom-digital/pimcore-i18n/issues/70), read more about it [here](./docs/21_I18nContext.md)
- Allow symfony routes [#65](https://github.com/dachcom-digital/pimcore-i18n/issues/65)

***

I18nBundle 3.x Upgrade Notes: https://github.com/dachcom-digital/pimcore-i18n/blob/3.x/UPGRADE.md