# Upgrade Notes

## 5.0.1
- Remove `isEnabled` and `setEnabled` from redirector adapter since disabled services aren't available anymore [#149](https://github.com/dachcom-digital/pimcore-i18n/issues/149)
- Remove legacy mode check in profiler [#150](https://github.com/dachcom-digital/pimcore-i18n/issues/150)

## Migrating from Version 4.x to Version 5.0

### Global Changes
- Recommended folder structure by symfony adopted

### New Features
- [BC BREAK] Config node `mode` has been removed and will be handled internally which simplifies i18n usability
- [BC BREAK] Config node `cookie` has been removed. Please use `i18n.registry.redirector.cookie.config.cookie` instead.  
  See [Redirector Adapter](docs/51_RedirectorAdapter.md) for further reference
- Fully configurable redirector adapters

***

I18nBundle 4.x Upgrade Notes: https://github.com/dachcom-digital/pimcore-i18n/blob/4.x/UPGRADE.md
