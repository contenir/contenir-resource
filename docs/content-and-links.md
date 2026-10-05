# Summaries and links

## ResourceSummary

`ResourceSummary::summarise(mixed $content)` gives a short plain-text summary, for listings and teasers:

- a resource entity summarises its description; if it has none and it implements `SectionAwareInterface` with
  section content, that content is rendered by the configured `SectionRendererInterface` and summarised;
- a string, number or `Stringable` summarises itself; anything else gives `''`.

The text has markup removed (also markup that only appears after entities are decoded), entities decoded,
whitespace collapsed, and is cut to `summary_length` characters (default 249) with an ellipsis. The result contains
no markup but is still text: escape it when writing it into HTML.

A section renderer turns section content into HTML. The Mezzio adapter ships one built on mezzio-template; a site can
implement `SectionRendererInterface` itself and name the service in `contenir_resource.section_renderer`.

## ExternalUrl

`ExternalUrl::normalise($url)` makes an editor-entered link safe to use as an `href` (once HTML-escaped):

| Input | Result |
| --- | --- |
| `https://example.com/a`, `http://...` | kept |
| `mailto:...`, `tel:...` | kept |
| `/contact`, `//cdn.example.com/x` | kept |
| `example.com/page` | `https://example.com/page` |
| `javascript:...`, `data:...`, `vbscript:...`, `ftp://...`, any other scheme | `null` |
| empty | `null` |

## ResourceLink

`ResourceLink` holds a link's `url` (null when there is nothing to link to) and `target`. `toArray()` gives the
`[url, target]` pair the laminas-mvc 1.x `ResourceUrl` helper returned.
