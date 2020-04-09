# Zones

![zone](https://user-images.githubusercontent.com/700119/28177968-0a3e592e-67fd-11e7-99a3-52b8f77683a4.jpg)

By default the i18nBundle works with the global settings (see example below). 
But you may need some more complex structures, so we implemented the zone manager.

### Default Configuration Options

| Name | Description |
|------|-------------|
| mode | Set mode (`language` or `country`) - `country` also includes language |
| locale_adapter | Locale Adapter (`system`, by default) |
| default_locale | Define a default locale (like `de`, `en_US`) |
| translations | [Static Route translations](28_StaticRoutes.md) |
| zones | Array for complex Zones |

### Zone Configuration Options
Basically there are the same options as in the default configuration, except that you can't define zones within zones.

| Name | Description |
|------|-------------|
| mode | Set mode (`language` or `country`) - `country` also includes language |
| locale_adapter | Locale Adapter (`system` by default) |
| default_locale | Define a default locale (like `de`, `en_US`) |
| translations | [Static Route translations](28_StaticRoutes.md) |

### Configuration

```yaml
i18n:

    # set mode (language|country)
    mode: language

    # define a locale adapter (system|custom)
    locale_adapter: system

    # define a default locale - this value is optional
    default_locale: 'en'

    # static route translations
    translations: ~

    zones:

        # zone 1: language
        zone1:
            id: 1
            # domains must be the main domain of page
            domains:
                - 'pimcore5-domain1.dev'
                - 'pimcore5-domain2.dev'
                - 'pimcore5-domain3.dev'
            config:
                mode: language
                locale_adapter: system
                translations: ~

        # zone 2: language and country
        zone2:
            id: 2
            domains:
                - 'pimcore5-domain4.dev'
                - 'pimcore5-domain5.dev'
                - 'pimcore5-domain6.dev'
            config:
                mode: country
                locale_adapter: system
                translations: ~

        # zone 3: no language switch. just a simple website.
        zone3:
            id: 3
            domains:
                - 'pimcore5-domain7.dev'
            config:
                mode: language
                locale_adapter: system
                translations: ~
```
