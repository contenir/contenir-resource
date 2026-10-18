# CMS previews

When a site renders the Contenir CMS's previews with
[contenir-preview](https://github.com/contenir/contenir-preview) and its framework adapter
([contenir-preview-laminas-mvc](https://github.com/contenir/contenir-preview-laminas-mvc), or contenir-preview-mezzio),
the resource a preview is of shows the editor's unsaved values wherever the site loads it, drafts and resources not
saved yet included, with no site code. Which page shows a resource is the framework adapter's part: contenir-resource-
laminas-mvc and contenir-resource-mezzio ship resolvers for workflow pages.

## The resource manager while a preview is rendered

`ConfigProvider` registers `Container\PreviewResourceManagerDelegator` on the resource manager. Where the preview
context service (`Contenir\Preview\PreviewContext`) exists, it wraps the manager in a `Preview\PreviewResourceManager`;
elsewhere the manager is left as it is.

1. A lookup the previewed resource answers finds it with the editor's values: by its id, or by the criteria a lookup
   names (a detail page's slug, a page's workflow, a type) matched against those values. Status criteria are passed
   over, so a draft is found as it would be once published.
2. Every other lookup goes to the site's manager. Where that finds the previewed resource, alone or in a list, the
   copy with the editor's values stands in its place; a resource not saved yet joins no list.
3. Only plain columns take the editor's values, converted as stored ones would be (contenir-db-model's `RowValues`): a
   flag, a date, the status. Lists are related records (images, tags), which stay as stored. A value the site cannot
   convert, such as a half-typed date, is left out rather than failing the page.

A preview counts only when it is of the resource table: keyed by `resource_id`, or not saved yet and naming its
`resource_type_id`. The previewed resource is loaded once the preview has begun and kept for the rest of the request.
Nothing is saved: the copies are not managed by the entity manager.
