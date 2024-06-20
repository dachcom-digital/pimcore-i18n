# Upgrade Notes

## 5.1.0
- [FEATURE] pimcore backend: object preview button
## 5.0.6
- [IMPROVEMENT] Try to determinate locale and site when inline renderer is active (mostly via `checkMissingRequiredEditable()`)
## 5.0.5
- [BUGFIX] Fix exception handler decoration
## 5.0.4
- [IMPROVEMENT] Add locale and site automatically in export mode (xliff, word export), based on given document
## 5.0.3
- [BUGFIX] Fix exception handler priority to prevent authentication exception hijacking
- [BUGFIX] Revert [#af8bfe7](https://github.com/dachcom-digital/pimcore-i18n/commit/af8bfe74488fd85ebcdb14e4300f3a9f7ddc7dbe)
## 5.0.2
- fixed bug when no locale url mapping is available [#153](https://github.com/dachcom-digital/pimcore-i18n/pull/153)
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
