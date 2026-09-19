=== DashWoo ===
Contributors: dashwoo
Tags: woocommerce, elementor, design system, fonts, icons
Requires at least: 6.4
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 1.2.0
License: GPL-2.0-or-later

Full UI platform for WooCommerce x Elementor with a local Design & Assets System.

== Description ==

DashWoo turns WordPress into a single control center for the whole store UI:

* Design tokens (colors, typography, spacing, radius, shadows, components) compiled to CSS variables.
* Local Google Fonts pipeline - one click, stored in `wp-content/uploads/dashwoo/fonts/`, zero runtime calls to Google.
* Material Symbols / Material Icons variable font manager + a fully controllable Elementor Icon widget.
* Asset Manager for fonts, icons, images, SVG and custom CSS/JS.
* Version Compatibility Layer with per-component adapters and an automatic Compatibility Mode.
* Full RTL / Persian support.

== Installation ==

1. Upload the `dashwoo` folder to `/wp-content/plugins/`.
2. Activate the plugin and review the activation compatibility report.
3. Open **DashWoo -> Dashboard** and start with the Design System.

== Changelog ==

= 1.2.0 =
* WooCommerce never lists DashWoo as an incompatible plugin any more: per-feature compatibility declarations on `before_woocommerce_init` (28 audited features + anything the live WooCommerce feature list adds).
* New Host Capabilities layer: missing host extensions turn a feature off automatically and back on when they appear (gd/imagick → image optimization, zip → compressed backups, multisite, dom → strict SVG audit).
* New "قابلیت‌ها" tab in the System group + `GET/POST /system/capabilities` REST routes.
* Compatibility report gained the `woocommerce.feature_declarations` row so the declaration can be verified from the admin.
* Fixed: dynamic static call in the declaration code, duplicated feature id, nested `extra` payload in the WooCommerce adapter row.

= 1.1.0 =
* Hierarchical admin menu: menu → submenu → tab row → tabs (no flat lists).
* Settings Center grew to 31 sections / 133 fields in 6 groups.

= 1.0.1 =
* Yellow `gd`/`zip` capability checks became neutral informational rows.

= 1.0.0 =
* First public release.
