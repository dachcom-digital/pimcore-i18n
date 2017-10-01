# Internal Link Rewriter
We love the hardlink context. Creating sites for different countries was never easier with pimcore.
But there are also some challenges. After we found a solution for the [canonical](80_CanonicalLinks.md) relation, there is still "one more thing":

## Example
You have one simple page, call it `/en`. You also need some content pages, so here we go:

```markdown
/en
  - about (document)
  - company (document)
  - team (link to /en/company)
```
But now your client needs this page also for the US (and he also tells you that the most of the content should stay the same). 
Not a problem you think - just create a hardlink with [country references](27_Countries.md), like we learned before. 

Your page tree may looks like this:
 
```markdown
/en
  - about (document)
  - company (document)
  - team (link to /en/company)
/en-us (hardlink)
  - special-content-for-the-us
```
Now open the link `en-us/team`: It will lead you to `en/company` page, which is bad. Very bad.
But hey - there are good news, we also found a solution for that. We implemented two listeners:

### LinkPathListener
Every redirect will be transformed to the right context. 
The link from `/en-us/team` will redirect you to `/en-us/team`.

### FrontendPathListener
If there is a link element in `/en/about` which points to `/en/company`, this listener will transform it automatically to `/en-us/company` as long the user is located in `/en-us`.