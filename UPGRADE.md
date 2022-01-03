# Upgrade Notes
![upgrade](https://user-images.githubusercontent.com/700119/31535145-3c01a264-affa-11e7-8d86-f04c33571f65.png)  

***

After every update you should check the pimcore extension manager. 
Just click the "update" button or execute the migration command to finish the bundle update.

#### Update from Version 3.2.8 to Version 3.2.9
- [IMPROVEMENT] Add cookie configuration [@tomhatzer](https://github.com/dachcom-digital/pimcore-i18n/issues/88)

#### Update from Version 3.2.7 to Version 3.2.8
- [BUGFIX] Fix path generator strpos check

#### Update from Version 3.2.6 to Version 3.2.7
- **[ENHANCEMENT]**: Allow to reinitialize Zones at any time (`$this->zoneManager->reinitializeZones()`)

#### Update from Version 3.2.5 to Version 3.2.6
- **[BUGFIX]**: Remove Hardlink Context Listener [@75](https://github.com/dachcom-digital/pimcore-i18n/pull/75).

#### Update from Version 3.2.4 to Version 3.2.5
- **[BUGFIX]**: Improve redirects when country code is not set in user languages [@pascalmoser](https://github.com/dachcom-digital/pimcore-i18n/pull/68).

#### Update from Version 3.2.3 to Version 3.2.4
- **[BUGFIX]**: Don't add documents to i18n tree, if not available in other context [@66](https://github.com/dachcom-digital/pimcore-i18n/pull/66).
- **[BUGFIX]**: Use root language if Accept-Language locale does not exist as pimcore language [@BlackbitNeueMedien](https://github.com/dachcom-digital/pimcore-i18n/pull/63).

#### Update from Version 3.2.2 to Version 3.2.3
- **[BUGFIX]**: Fix wrong request listener [#dd2102](https://github.com/dachcom-digital/pimcore-i18n/commit/dd2102)

#### Update from Version 3.2.1 to Version 3.2.2
- **[ENHANCEMENT]**: Log http exception to `http_error_log` table [@59](https://github.com/dachcom-digital/pimcore-i18n/pull/59).

#### Update from Version 3.2.0 to Version 3.2.1
- **[ENHANCEMENT]**: Pimcore 6.6.0 ready
- **[ENHANCEMENT]**: Remove empty attributes from alternate links (`link href="http://pimcore.test/de" rel="alternate" type="" title="" hreflang="de"` becomes `link href="http://pimcore.test/de" rel="alternate" hreflang="de"`)
- **[ENHANCEMENT]**: Allow changing [redirect status code](https://github.com/dachcom-digital/pimcore-i18n/blob/master/docs/51_RedirectorAdapter.md#define-redirect-status-code)
- **[ENHANCEMENT]**: Localized error document when main domain of a site has www. subdomain [@BlackbitNeueMedien](https://github.com/dachcom-digital/pimcore-i18n/pull/56)
- **[ENHANCEMENT]**: Redirect based on Accept-Language HTTP header [@BlackbitNeueMedien](https://github.com/dachcom-digital/pimcore-i18n/pull/57)

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