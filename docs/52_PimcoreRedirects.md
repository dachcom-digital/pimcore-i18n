# Pimcore Redirects with I18n

![Pimcore Redirects](https://user-images.githubusercontent.com/700119/63445786-4917fe80-c439-11e9-8007-e19576cdf8bc.png)

If you want to implement the i18n redirect guesser, you need to add `{i18n_localized_target_page=4}` instead of the document path in the `target` section.

## Facts
- Be sure that your documents are connected, otherwise the detection won`t work.
- If you want to redirect an unknown host (Example A), be sure you set the redirect `priority` to `99`.

## Example

### Given Structure
**Domain**: `mydomain.com`   
**Document Tree**:
- de
  - kollektion
  - kampagne
- en
  - collection
  - landingpage

## Example A:
- Enter host `my-old-collection-domain.com`
- i18n should redirect to `mydomain.com/de/kollektion` if user is german
- i18n should redirect to `mydomain.com/en/collection` if user is english

## Example B:
- Enter host `mydomain.com/landingpage`
- i18n should redirect to `mydomain.com/de/kampagne` if user is german
- i18n should redirect to `mydomain.com/en/landingpage` if user is english

