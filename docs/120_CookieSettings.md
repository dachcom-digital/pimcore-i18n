# Cookie settings

If you want to change the Symfony default cookie settings, you have an option to do so.

## Set custom cookie settings

You can have custom settings with fallbacks to default by adding each of them to your projects config like this:

> Customizing the `raw` cookie parameter using this config is not implemented right now!

```yaml
# app/config/config.yaml
i18n:
    cookie:
        path: '/yourpath/'
        domain: 'yourdomain.com'
        secure: true
        httpOnly: true
        sameSite: strict
```

### Default values

```yaml
# app/config/config.yaml
i18n:
    cookie:
        path: '/'
        domain: null
        secure: false
        httpOnly: true
        sameSite: lax
```

### Possible values for sameSite parameter

The sameSite parameter supports the following values:

- none
- lax
- strict
