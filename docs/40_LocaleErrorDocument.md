# Localized Error Documents

Pimcore provides two ways to add a error document in case something went wrong or a specific page could not be found.

1.) specify a error page in your systemsettings
2.) specify a error page per site

If you're using this plugin you probably need some abstraction. 
This Bundle will help you to generate specific error documents depending on languages and/or country settings.

Just create a document called like the error page in your system settings or the error page of a specific site and place it in your tree:

```text
site.com
    - de
        - error
    - en
        - error
    - en-US
        - error
site2.com
    - error
```

The i18nBundle will search for the closest document depending on your host. 
If nothing could be found, the default pimcore error handling will handle the exception.