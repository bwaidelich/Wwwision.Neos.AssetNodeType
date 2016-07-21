# Wwwision.Neos.AssetNodeType

Simple [Neos](https://neos.io) package providing custom "Asset" &amp; "AssetList" node types

## Introduction

This is an attempt to combine the UX of `inline properties` with actual child nodes, in this case with a new node type
`AssetList` that behaves like the `AssetList` node type of the [TYPO3.Neos.NodeTypes](https://github.com/neos/neos-nodetypes)
package but internally creates actual `Asset` child nodes.

This has a couple of advantages:

1. A new node type `Asset` that can be used when only a single asset is required
2. More flexibility with single asset nodes as they can be navigated to and modified individually (i.e. one could add
   additional properties for a more fine-grained rendering control)
3. When using the [Elastic Search adapter](https://github.com/Flowpack/Flowpack.ElasticSearch.ContentRepositoryAdaptor#working-with-assets--attachments)
   asset nodes can be indexed allowing to find individual "asset results".

## Known issues

1. If the `AssetList` doesn't render a "frame" around the single assets it's difficult to select an existing list in
   the backend. This could be solved by adding a "handle" to the `AssetList` template or by preventing the `Asset` child
   nodes from being clickable.
2. When changing the assets via the `AssetList` property editor (i.e. changing the order of assets via drag 'n drop),
   the Structure Panel is not automatically refreshed. It will only reflect those changes after clicking "refresh" (or
   navigating to the page again). The other way around works nicely btw: `Asset` nodes can be (re)moved in the Structure
   panel and the `AssetList` will reflect those changes immediately.