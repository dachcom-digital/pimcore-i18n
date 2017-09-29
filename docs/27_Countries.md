# Countries
This is only required if you're using i18n with a country adapter.

## Preparation
Localize your [documents first](26_Languages.md).

## Country Wrapper
If you're using a hardlink for country specific data, create it. Otherwise just create a document as you would normally do.

1. Create a document property (dropdown or text) and call it `country`.
2. Apply the property to all your global pages (like /de, /en) and set the value to `GLOBAL`
3. Apply the property to all your real website hardlinks/documents and set the desired country ISO-Code (uppercase)