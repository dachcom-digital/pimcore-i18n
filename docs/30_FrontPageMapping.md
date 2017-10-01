# Front Page Mapping
Sometimes you want to clone a page and expand it with some [country](27_Countries.md) information. For example:

```text
- /en
    - about-us
    - team
- /en-us -> that's your hardlink with a en_US locale. 
```

> Note: Learn how to implement a country site based on hardlinks by [clicking here](27_Countries.md).

Now you have a brand new shiny website. If you open the `/en-us/team` page, it will automatically load the content from `/en/team`. That's nice.
If you want to override the team content you just need to copy your `/en/team` into the `/en-us/` tree. Now you're good to go to add some additional content.

But what if you want to override the front page content? That's a tricky one since `en-us` is just a hardlink.

### i18n to the rescue
Just add a new (document) property  named `front_page_map` to your hardlink and assign a custom document. 
You can leave the document unpublished, so it stays private.

> This property should be available in your predefined properties since it gets automatically installed.

If your opening your `en-us/` front page again you'll see the new content. Bravo!