# Create a website with country context
This is only required if you're using i18n with a country adapter.

There are two ways to implement a country page like `en-us`:
1. Copy and paste the `en` document tree and rename `en` to `en-us`.
2. Create a hardlink, call it `en-us` and set the reference to `en`.
3. Change language to `en_US`.

> If you're using a hardlink, check out the [canonical information part](80_CanonicalLinks.md).

## Naming Convention
You might be wondering why we named the hard link `en-us`. For default documents, that's not a problem. 
But you'll run into serious troubles if you need to build static routes with it. Read [this section](28_StaticRoutes.md) to solve this bravely.

## Preparation
Localize your [documents first](26_Languages.md).

## Country Wrapper
If you're using a hardlink for country specific data, create it.
Otherwise just create a document as you would normally do.

1. Create a document property (dropdown or text) and call it `country`.
2. Apply the property to all your global pages (like `/de`, `/en`) and set the value to `GLOBAL`
3. Apply the property to all your real website hardlinks/documents and set the desired country ISO-Code (uppercase)

> This property should be available in your predefined properties since it gets automatically installed, you may extend the default configuration.
 
Read more about the [static route configuration](28_StaticRoutes.md).

## Magic
All hardlink references will be generated automatically.
So if you have have a page in `/en/about-us` the href-lang generator or context helper searches in all available hardlinks.
If you'r having a `/en-us` hardlink, a link to `/en-us/about-us` will be automatically generated (for href-lang and the `getLinkedLanguages` [context helper](60_CodeExamples.md) for example).
In case, you don't want to have a `about-us` page in `/en-us`, just create the page `/en-us/about-us` and disable it.

There is also some further magic you need to know about: If you have just one language but multiple countries, you're unable to link them via the localization tool.
I81nBundle will then try to generate all hardlinks references automatically.
