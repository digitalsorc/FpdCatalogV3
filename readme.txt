=== FPD Catalog V3 ===
Contributors: DigitalSorc
Tags: elementor, fancy product designer, fpd, catalog, grid
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.0
Stable tag: 3.0.0
License: GPLv2 or later

Elementor widget that renders a visual product catalog grid compositing FPD base products and designs.

== Description ==

This plugin provides an Elementor widget to display a visual product catalog grid. It dynamically composites Fancy Product Designer (FPD) base product images with FPD designs placed inside the product's defined printing box. The compositing happens in the browser at runtime using HTML5 Canvas or CSS layering.

### Features
* Elementor Widget with extensive controls.
* Canvas API compositing for pixel-perfect mockups.
* CSS Layers fallback.
* Lazy loading via IntersectionObserver.
* REST API endpoint for fetching items.
* JS Event Bus for custom filter/sort integration.
* URL parameter sync for shareable filtered views.

### Filter & Sort Integration
You can trigger filters from any custom JS by firing the `fpd_catalog_filter_changed` event:

```javascript
// Example: Triggering a filter change
window.fpdCatalogUpdateFilter({
    category: '12,15', // Comma separated category IDs
    base_product: '42', // Base product ID
    orderby: 'title',
    order: 'ASC'
});
```

### WordPress Filter Hooks
* `fpd_catalog_v3_query_args`: Modify the query arguments before fetching items from the database.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/fpd-catalog-v3` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Edit a page with Elementor and drag the "FPD Catalog V3" widget onto the page.

== Known Limitations / TODOs ==

* **FPD Database Tables**: The plugin assumes FPD data is stored in `wp_fpd_products`, `wp_fpd_categories`, `wp_fpd_views`, and `wp_fpd_designs`. Depending on the FPD version, these table names or structures might differ. 
  * ## TODO: Verify exact table names and column names for the installed FPD version.
* **Printing Box Extraction**: The plugin attempts to extract the printing box from the `elements` JSON column in the `wp_fpd_views` table. It looks for an element with the title "printing box" or "Printing Box".
  * ## TODO: Adjust the JSON parsing logic if the printing box is stored differently in newer FPD versions.
* **Design Image Source**: The plugin assumes the design image URL is stored in the `image` or `thumbnail` column of `wp_fpd_designs`, or within the `parameters` JSON.
  * ## TODO: Verify design image extraction logic.
* **Server-side Thumbnail**: The `Server-side thumbnail` render mode is currently a stub and defaults to Canvas.
  * ## TODO: Implement server-side image generation (e.g., using ImageMagick or GD) if required.
