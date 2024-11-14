# Localize Documents
**Q: Which documents do i need to translate within a zone?**  
A: Basically every "physically" document. For example we want to add a `product` page:

![localization](https://user-images.githubusercontent.com/700119/31600534-2ad51926-b257-11e7-849e-e1642fff29aa.png)

In this example there are several documents available:

| Document | Locale |
|:--------|:------------|
| `domain4/de/produkte` | de |
| `domain4/en/products` | en |
| `domain4/nl-nl/producten` | nl_NL |
| `domain4/en-us/products` | en_US |
| `domain5/produits` | fr |

## Localization
Use the [localization service](https://pimcore.com/docs/platform/Pimcore/Best_Practice/Multilanguage_Setup#localization-tool) to connect all available documents:

- `domain4/de/produkte`
- `domain4/en/products`
- `domain4/nl-nl/producten`
- `domain4/en-us/products`
- `domain5/produits`

If you're going to open one of those pages and check the source code you'll find the generated href-tags on top. Now let's check each element again:

| Document | Description |
|:--------|:------------|
| `domain4/de/produkte` | It's a real document.  |
| `domain4/en/products` | It's a real document. |
| `domain4/nl-nl/producten` | It's a real document. |
| `domain4/de-ch` | It's a hardlink. I18n Bundle will add this document automatically for you. That's the ["magic"](27_Countries.md#magic) we talked about earlier. |
| `domain4/fr-ch` | Also a hardlink. I18n Bundle will add this document automatically for you. That's the ["magic"](27_Countries.md#magic) we talked about earlier. |
| `domain4/en-us/products` | A hardlink! But now there is a dedicated document so you also need to add it to the translation service. If you want to hide it, skip the translation service and just disable it. |
| `domain4/en_AU/products` | Also a hardlink. I18n Bundle will add this document automatically for you. That's the ["magic"](27_Countries.md#magic) we talked about earlier. |
| `domain5/produits` | It's a real document. |
| `domain6/it` | There is no product page, so no translation needed! |


