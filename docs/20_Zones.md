# Zones
Learn how to use i18n Zones.

![zone](https://user-images.githubusercontent.com/700119/28177968-0a3e592e-67fd-11e7-99a3-52b8f77683a4.jpg)


### Configuration

```yaml
i18n:

    # set mode (language|country)
    mode: language

    # define a language adapter (system|custom)
    language_adapter: i18n.adapter.language.system

    # define a country adapter (system|coreshop|custom)
    country_adapter: i18n.adapter.country.system

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
                language_adapter: i18n.adapter.language.system
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
                language_adapter: i18n.adapter.language.system
                country_adapter: i18n.adapter.country.system
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
                language_adapter: i18n.adapter.language.system
                country_adapter: ~
                global_prefix: ~
                translations: ~
```