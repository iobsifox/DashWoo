# DashWoo — UI Platform for WooCommerce × Elementor

DashWoo turns every visual decision of a WooCommerce shop into locally served
assets and design tokens. Fonts, icons, images, SVG and custom code are managed
inside WordPress — **with zero dependency on any external CDN**.

> **فارسی:** DashWoo کنترل کامل ظاهر فروشگاه ووکامرس را در پیشخوان وردپرس می‌دهد؛
> فونت‌ها، آیکون‌ها، تصاویر و توکن‌های طراحی همه **به‌صورت محلی** ذخیره و سرو می‌شوند
> و پس از نصب، هیچ درخواستی به گوگل یا هیچ CDN دیگری زده نمی‌شود.

## Features

- **Local Google Fonts** — search 31 families, download only the weights/subsets
  you need, keep the upstream metadata so updates touch only changed files.
- **Material Symbols & Material Icons** — variable font axes (weight, FILL, GRAD,
  optical size) with per-icon overrides, RTL-safe ligature rendering.
- **Asset library** — images, sanitized SVG, uploaded fonts and custom CSS/JS,
  stored in `wp-content/uploads/dashwoo/` (your files survive plugin updates).
- **Design tokens → CSS variables** — 16 token categories compiled to
  `--dw-*` variables plus a versioned stylesheet in the uploads cache.
- **Elementor integration on three levels** — CSS variables, a real token picker
  control, and optional Global Kit sync with backup + revert.
- **Settings Center** — 30 sections / 129 fields, schema-driven, exportable and
  importable as JSON.
- **Version compatibility layer** — adapters per component, activation check,
  warning screen and automatic Compatibility Mode instead of a hard dependency.
  Optional host capabilities (no `php-gd`, no `php-zip`) are reported as neutral
  *informational* rows with a Persian explanation, and never turn the report yellow.
- **REST API** `dashwoo/v1` — 30 routes for settings, fonts, icons, assets, tokens,
  plus `/system/diagnostics` with the raw server facts (extensions,
  `disable_functions`, ini limits) for support tickets.
- **Full Persian / RTL** — the admin is Persian-first and `dir="rtl"` aware.

## Installation

1. Download `dashwoo-1.0.0.zip` from the [Releases](../../releases) page.
2. WordPress → **Plugins → Add New → Upload Plugin** → choose the zip → **Install**.
3. Activate, then open **DashWoo** in the admin menu.

Requires **PHP 8.0+**, **WordPress 6.4+**. WooCommerce 8.5+ and Elementor 3.24+
are optional: without them DashWoo keeps working and simply reports a warning.

## Where files live

```
wp-content/uploads/dashwoo/
├── fonts/    icons/    images/    svg/    custom/
└── cache/    compiled design-token + icon stylesheets
```

Nothing is ever written inside the plugin folder, so updating the plugin never
deletes your fonts, icons or images. Uninstalling keeps them too, unless you tick
“remove data on uninstall” in the settings.

## Integrity

Every build ships `BUILD-MANIFEST.txt` with the sha256 of each file, and the
release is verified by `bin/verify-build.php` (archive layout, hashes, plugin
header, PHP syntax of every shipped file, full autoloader class-map resolution
and a CDN scan) before publishing.

## License

GPL-2.0-or-later — see [LICENSE](LICENSE). The bundled Google Fonts catalogue
data is provided for convenience; the fonts themselves are served under their own
licenses (mostly SIL OFL 1.1) by Google Fonts.
