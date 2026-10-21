# Page metadata

`PageMetadataBuilder::build(MetadataInterface $resource, string $requestUrl)` turns any
[contenir-metadata](https://github.com/contenir/contenir-metadata) `MetadataInterface`, such as a resource entity,
into a `PageMetadata`:

| Field | From |
| --- | --- |
| `url` | The canonical URL: the request path on `base_url`, or on the request's origin |
| `title` | `getMetaTitle()` |
| `description` | `getMetaDescription()`, cleaned (`MetaText::clean()`) and cut at a word break to `description_length` characters |
| `image` | `getMetaImage()`, resolved by the `ImageUrlResolverInterface` |
| `updated` | `getMetaModified()`, or else `getMetaPublish()` |
| `siteName` | `contenir_resource.site_name`, on every page |

`PageMetadata` also takes `robots`, `imageAlt`, `imageWidth` and `imageHeight`, which a site sets itself (for example
from contenir/seo's settings and the image's asset record); the builder leaves them unset.

Blank values are left out, and the tags that depend on them are not emitted.

## Security

- **Canonical URL.** Without `base_url`, the origin comes from the request, whose host the client controls (the
  Host header). Configure `base_url` in production. The query string, fragment and user info are never part of the
  canonical URL, and `og:url`/`twitter:url` use the canonical URL too, so query parameters are never reflected into
  share URLs.
- **Images.** `PathImageUrlResolver` keeps http(s) URLs, gives protocol-relative URLs the site's scheme, puts paths
  on `image_base_url` (or the site origin), and rejects every other scheme (`javascript:`, `data:`, ...).
- **Output.** Every `PageMetadata` value is plain text. `MetaTagRenderer` escapes it all
  (`htmlspecialchars` with `ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5`); any other renderer must escape too.

## Rendering

`PageMetadata::getLinks()` and `getMetaTags()` give the canonical link and the tags as arrays, for templates that
build their own markup. `MetaTagRenderer` renders them:

```php
$renderer->render($metadata);     // <title>, then the link and meta tags, one per line
$renderer->renderTags($metadata); // the same without <title>
```

Output it unescaped in `<head>` (for example `{{ meta|raw }}` in Twig). Open Graph tags use `property`, and Twitter
tags use `name`. `twitter:card` is `summary_large_image` when there is an image, and `summary` otherwise. The image's
width and height are written only when both are known. The tags, in order, with every value set:

```html
<title>About us</title>
<link rel="canonical" href="https://www.example.com/about">
<meta property="og:type" content="website">
<meta property="og:url" content="https://www.example.com/about">
<meta name="twitter:url" content="https://www.example.com/about">
<meta name="twitter:card" content="summary_large_image">
<meta property="og:site_name" content="Example">
<meta name="robots" content="noindex, follow">
<meta property="og:title" content="About us">
<meta name="twitter:title" content="About us">
<meta name="description" content="Who we are">
<meta property="og:description" content="Who we are">
<meta name="twitter:description" content="Who we are">
<meta property="og:image" content="https://cdn.example.com/about.jpg">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="Our team">
<meta name="twitter:image" content="https://cdn.example.com/about.jpg">
<meta name="twitter:image:alt" content="Our team">
<meta property="og:updated_time" content="2024-01-02T03:04:05+00:00">
```

## MetaText

| Method | Does |
| --- | --- |
| `clean($html)` | Decodes entities, strips markup, straightens typographic quotes, collapses all whitespace |
| `summarise($text, $length = 160)` | `clean()`, then cut at the last word break within `$length` characters (mid-word if there is none) |
| `keywords($text, $max = 25, $minWordLength = 4, $bannedWords = [])` | The most frequent words longer than `$minWordLength` characters |
