# Upgrade Notes
![upgrade](https://user-images.githubusercontent.com/700119/31535145-3c01a264-affa-11e7-8d86-f04c33571f65.png)  

***

After every update you should check the pimcore extension manager. 
Just click the "update" button or execute the migration command to finish the bundle update.

#### Update from Version 3.0.0 to Version 3.1.0
- **[NEW FEATURE]**: Allow [pimcore redirect modification](https://github.com/dachcom-digital/pimcore-i18n/issues/33).
- **[BUGFIX]**: Disable context switch event if [pimcore full page cache](https://github.com/dachcom-digital/pimcore-i18n/issues/18) is enabled

#### Update from Version 2.x to Version 3.0.0
- **[NEW FEATURE]**: Pimcore 6.0.0 ready

***

#### Update from Version 2.3.x to Version 2.4.0
- **[ATTENTION]**: Installer has moved to the [MigrationBundle](https://github.com/dachcom-digital/pimcore-i18n/issues/19). After updating to this version you need to enable this extension again!
- **[BUGFIX]**: [Check Pimcore Context on Kernel Response](https://github.com/dachcom-digital/pimcore-i18n/pull/26) 
- **[NEW FEATURE]**: Pimcore 5.6.0 ready
- ([Milestone for 2.4.0](https://github.com/dachcom-digital/pimcore-i18n/milestone/3?closed=1))

#### Update from Version 2.3.2 to Version 2.3.3
- implemented [PackageVersionTrait](https://github.com/pimcore/pimcore/blob/master/lib/Extension/Bundle/Traits/PackageVersionTrait.php)

#### Update from Version 2.2.x to Version 2.3
> **IMPORTANT**: Version 2.3.0 comes with a lot BC Breaks, so check your installation after upgrading!

- **[MAINTENANCE]**: the document property `country` has been removed. Please remove this property from your documents and also from the predefined property list
- **[NEW FEATURE]**: [redirector adapter](./docs/51_RedirectorAdapter.md) implemented
- **[BC BREAK]**: setting `default_language` has been removed, use `default_locale` instead
- **[BC BREAK]**: setting `default_country` has been removed, use `default_locale` instead
- **[BC BREAK]**: setting and service `language_adapter` has been removed, use `locale_adapter` instead
- **[BC BREAK]**: setting and service `country_adapter` has been removed, use `locale_adapter` instead
- **[BC BREAK]**: `getCurrentContextInfo()` does not allow a `$locale` argument anymore
- **[DEPRECATION REMOVED]**: `getActiveCountryLocalizations()` removed from Country Adapter

#### Update from Version 2.1.x to Version 2.2
- **[BC BREAK]**: Zones now need to be configured as identifier:

Instead of
```yml
zones:
    -
        id: 1
        name: 'zone 1'
        [...]
    -
        id: 2
        name: 'zone 2'
        [...]
```
use
```yml
zones:
    zone1:
        id: 1
        name: 'zone 1'
        [...]
    zone2:
        id: 2
        name: 'zone 2'
        [...]
```

#### Update from Version 2.0.x to Version 2.1
- `global_prefix` has been removed, please update your i18n config parameters (remove it before you updating).
- static route handler implemented
- translatable static route fragments implemented

***

#### Update from Version 1.x to Version 2.0.0
Event `website.i18nSwitch`: `I18nEvents::CONTEXT_SWITCH`
Event `website.i18n.staticRoute.alternate`: `I18nEvents::PATH_ALTERNATE_STATIC_ROUTE`