# Zones

![zone](https://user-images.githubusercontent.com/700119/28177968-0a3e592e-67fd-11e7-99a3-52b8f77683a4.jpg)

By default the i18nBundle works with the global settings (see example below). 
But you may need some more complex structures, so we implemented the zone manager.

### Default Configuration Options

| Name | Description |
|------|-------------|
| mode | Set mode (`language` or `country`) |
| language_adapter | Language Adapter (`system`, by default) |
| country_adapter | Country Adapter (`system`, by default) |
| default_language | Define a default country (ISO-Code, lowercase) |
| default_country | Define a default country (ISO-Code, uppercase) |
| global_prefix | Define a global prefix (like de-global or de-int) |
| translations | Static Route translations |
| zones | Array for complex Zones |

### Zone Configuration Options
Basically there are the same options as in the default configuration, except that you can't define zones within zones.

| Name | Description |
|------|-------------|
| mode | Set mode (`language` or `country`) |
| language_adapter | Language Adapter (`system`, by default) |
| country_adapter | Country Adapter (`system`, by default) |
| default_language | Define a default country (ISO-Code, lowercase) |
| default_country | Define a default country (ISO-Code, uppercase) |
| global_prefix | Define a global prefix (like de-global or de-int) |
| translations | Static Route translations |


### Configuration

```yaml
i18n:

    # set mode (language|country)
    mode: language

    # define a language adapter (system|custom)
    language_adapter: system

    # define a country adapter (system|coreshop|custom)
    country_adapter: system

    # define a default language - this value is optional and does not to be defined
    default_language: 'en'

    # define a default country - this value is optional and does not to be defined
    default_country: 'US'

    # define a global prefix (like de-global or de-int)
    global_prefix: ~

    # static route translations
    translations: ~

    zones:

        # zone 1: language
        -
            id: 1
            name: 'zone 1'
            # domains must be the main domain of page
            domains:
                - 'pimcore5-domain1.dev'
                - 'pimcore5-domain2.dev'
                - 'pimcore5-domain3.dev'
            config:
                mode: language
                language_adapter: system
                country_adapter: ~
                global_prefix: ~
                translations: ~

        # zone 2: language and country
        -
            id: 2
            name: 'zone 2'
            domains:
                - 'pimcore5-domain4.dev'
                - 'pimcore5-domain5.dev'
                - 'pimcore5-domain6.dev'
            config:
                mode: country
                language_adapter: system
                country_adapter: system
                global_prefix: ~
                translations: ~

        # zone 3: no language switch. just a simple website.
        -
            id: 3
            name: 'zone 3'
            domains:
                - 'pimcore5-domain7.dev'
            config:
                mode: language
                language_adapter: system
                country_adapter: ~
                global_prefix: ~
                translations: ~
```