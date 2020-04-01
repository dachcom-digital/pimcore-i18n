# Upgrade Notes
![upgrade](https://user-images.githubusercontent.com/700119/31535145-3c01a264-affa-11e7-8d86-f04c33571f65.png)  

***

After every update you should check the pimcore extension manager. 
Just click the "update" button or execute the migration command to finish the bundle update.

#### Update from Version 3.2.0 to Version 3.2.1
- **[ENHANCEMENT]**: Pimcore 6.6.0 ready
- **[ENHANCEMENT]**: Localized error document when main domain of a site has www. subdomain [@BlackbitNeueMedien](https://github.com/dachcom-digital/pimcore-i18n/pull/56)

#### Update from Version 3.1.x to Version 3.2.0
- **[ENHANCEMENT]**: Pimcore 6.5.0 ready
- **[ENHANCEMENT]**: PHP 7.4 Support
- **[IMPORTANT]**: Because of unfixed major issues within Pimcore 6.4.x, we're unable to support this version. We recommend to directly upgrade to pimcore 6.5!
- **[IMPORTANT]**: GeoLite Database is not available automatically due new download restrictions by maxmind. Read more about it [here](./docs/10_GeoControl.md), [here](https://github.com/pimcore/pimcore/issues/5512) and [here](https://blog.maxmind.com/2019/12/18/significant-changes-to-accessing-and-using-geolite2-databases/) 

#### Update from Version 3.1.0 to Version 3.1.1
- **[ENHANCEMENT]**: Use locale instead of languageIso in [Document PathGenerator](https://github.com/dachcom-digital/pimcore-i18n/issues/41)
- **[NEW FEATURE]**: Respect [LinkGenerator](https://github.com/dachcom-digital/pimcore-i18n/issues/15)

#### Update from Version 3.0.0 to Version 3.1.0
- **[NEW FEATURE]**: Allow [pimcore redirect modification](https://github.com/dachcom-digital/pimcore-i18n/issues/33).
- **[BUGFIX]**: Disable context switch event if [pimcore full page cache](https://github.com/dachcom-digital/pimcore-i18n/issues/18) is enabled
- **[BUGFIX]**: Fix context on [xliff export](https://github.com/dachcom-digital/pimcore-i18n/issues/28)

#### Version 3.0.0
- **[NEW FEATURE]**: Pimcore 6.0.0 ready

***

Upgrade History for 2.x: [Click here](https://github.com/dachcom-digital/pimcore-i18n/blob/2.4/UPGRADE.md).