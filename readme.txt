=== My Account Dashboard Builder ===
Contributors: Rcube
Tags: woocommerce, my account, account page, endpoints, customizer
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
WC requires at least: 6.0
WC tested up to: 11.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Turn the default WooCommerce My Account page into a branded customer dashboard: custom endpoints, block editor content, avatar uploads and one-click design templates.

== Description ==

The default WooCommerce **My Account** page is a plain list of links. My Account Dashboard Builder rebuilds it into a branded customer dashboard — without touching a template file or writing a line of code.

= Build the menu =

* Add unlimited items: **endpoints** (own URL + content), **pages**, **links** (internal or external) and **groups** (collapsible parents).
* Drag to reorder; toggle any default WooCommerce endpoint on or off.
* Rename every item, give a custom endpoint its own URL, and pick an icon from the bundled Font Awesome library, a Dashicon, or upload your own.
* Add a **badge** to any item: "New", or a live value such as `{points_balance} pts`.
* Control who sees what: by **user role**, **date range**, **products the customer bought**, **number of orders** or **total spent**.
* A live preview in the builder shows each item as customers will see it.
* Set the landing endpoint customers open on, plus login and logout redirects.

= Fill it with content =

* Give each endpoint its own content using either the **Classic editor** or the native **Gutenberg block editor**.
* Insert content **before**, **after**, or **instead of** the default WooCommerce output.
* Add promotional **banners** per endpoint — text, image or icon, with role targeting, a schedule, your own link text and a count badge for orders, downloads, cart items or reward points.
* **Smart tags** personalise any text: `{display_name}`, `{first_name}`, `{last_name}`, `{username}`, `{user_email}`, `{site_title}`, `{order_count}`, `{download_count}`, `{total_spent}`, `{member_since}`, `{cart_count}`, `{billing_phone}`, `{billing_city}`, `{billing_country}`, `{points_balance}`, `{membership_plan}`, `{last_login}`, `{account_url}`, `{shop_url}`, `{site_url}`.

= Design it visually =

* **Templates tab** — apply a complete, ready-made design in one click.
* **Customizer panel** with live preview, split into Avatar, Navigation, and Layout & Colors.
* Menu position (left, right or top), menu style, hover animation, active indicator, sticky menu, collapsible groups, menu search.
* Colours (accent, text, menu background, hover, active), typography (family, size, weight), radius, item padding, gap, colour scheme, and a Custom CSS field.
* Save your own settings as a **preset** and reapply it later.

= Give customers more than links =

* **Profile picture upload** — customers set their own avatar from the account page; it replaces the Gravatar site-wide. MIME, size and real-image validated.
* **Buy again** — a tab and dashboard tile for one-click reordering of previously purchased products.
* **Recently viewed** — a tab listing products the customer just browsed.
* **Order tracking** — a dashboard summary that auto-detects WooCommerce Shipment Tracking and falls back to a status timeline.
* **Dashboard stats** — orders, pending, total spent, refunds, downloads, points, latest order, and an orders-by-status chart. Each card links to the page it sums up.
* **Profile completeness meter** that lists what is missing, with a link to each form, and optional quick-link tiles.

= Anywhere on the site =

Render the customised menu outside the account page with the `[acfw_account_menu]` shortcode, the **Account Menu** block, or the classic **Account Menu** widget.

= Built to fit =

* Optional AJAX navigation between endpoints.
* **Import / Export** the whole configuration as JSON, and a full reset tool.
* Translation-ready, with string registration for **WPML** and **Polylang**.
* Declares **HPOS** (custom order tables) and cart/checkout blocks compatibility.
* No external service calls, no telemetry, no remotely loaded fonts. Font Awesome and Select2 ship locally.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`, or install the zip via **Plugins → Add New → Upload Plugin**.
2. Activate through the **Plugins** screen. WooCommerce must be active.
3. A top-level **My Account** menu appears in wp-admin, with tabs for Menu Items, Templates, Settings, Customizer and Banners.
4. Default WooCommerce endpoints are pre-loaded, so the page keeps working before you change anything.
5. Optional: enable Buy Again, Recently viewed and Order tracking in **My Account → Settings → General**, and avatar uploads in **Customizer → My Account → Avatar**.

== Frequently Asked Questions ==

= Do I have to edit theme templates? =

No. The plugin replaces the WooCommerce account menu and endpoint output through the hooks WooCommerce already provides. Nothing is copied into your theme.

= Will it break if I deactivate the plugin? =

No. Your custom endpoints stop resolving and the page falls back to the standard WooCommerce menu. Your configuration is kept, so reactivating restores everything.

= Where do the design options live? =

In the WordPress Customizer, under the **My Account** panel, so you edit them against a live preview. Content and menu structure live in the wp-admin **My Account** screen.

= Can customers upload their own profile picture? =

Yes, once you enable it in Customizer → My Account → Avatar. Uploads are restricted to JPG, PNG, GIF and WebP, checked against a size limit you set, and verified to be real images before being stored. The uploaded picture then replaces the Gravatar everywhere on the site.

= Does the Buy again tab let customers reorder anything? =

No. It only offers products that customer actually purchased on a completed order, and it re-checks purchase history and stock on every add-to-cart request.

= Is it compatible with HPOS and the block-based cart and checkout? =

Yes, compatibility with both is declared. The plugin reads orders through the WooCommerce CRUD API only.

= Is it translation-ready? =

Yes. A `.pot` file ships in `/languages`, and admin-entered strings (item labels, banner titles and content) are registered for WPML and Polylang.

= What data does it store, and what happens on uninstall? =

Options prefixed `acfw_`, plus three user meta keys (`acfw_avatar_id`, `acfw_last_login`, and `acfw_order_stats`, a cache of the dashboard counts). Deleting the plugin from the Plugins screen removes all of it, including uploaded avatar images. Deactivating does not.

== Screenshots ==

1. The Menu Items builder — drag to reorder, toggle items, edit any one in the right-hand pane.
2. Editing an item: label, icon, visibility rules by role, date and purchased product, and the content editor.
3. The Templates tab — starter designs applied in one click.
4. Live design editing in the Customizer, with the account page previewed beside the controls.
5. The customised My Account page: avatar block, icon menu with counts, dashboard stats and profile meter.
6. The Buy again tab — one-click reorder of previously purchased products.
7. Settings — commerce features, redirects and navigation behaviour.
8. Import / Export of the full configuration.

== Changelog ==

= Unreleased =
* New: endpoint URLs, item badges, product / order-count / spend visibility rules, per-group "start expanded", banner schedules, badge sources and link text, clickable dashboard stats, an actionable profile meter and nine new smart tags.
* New: the Menu Items screen is organised in tabs with a live customer preview, list markers and an unsaved-changes save bar.
* Fix: custom endpoints no longer show the dashboard underneath; banner position saves; non-Latin labels no longer vanish; items and banners can no longer overwrite each other; nested endpoints resolve; Customizer colours and radius reach the menu; rgba banner colours, the Reward points stat, the landing endpoint and one-step logout work.
* Accessibility: visible keyboard focus, keyboard-operable groups, tab-pattern settings, readable muted text and warnings that stay until closed.
* Full list in changelog.txt.

= 1.0.0 =
* First public release.
* Menu builder: endpoints, pages, links and collapsible groups, drag-to-reorder, per-item icons (Font Awesome, Dashicons or uploaded).
* Per-endpoint content with the Classic or the Gutenberg block editor, placed before, after or instead of the default output.
* Visibility rules by user role, date range and purchased product.
* Design templates, a Customizer panel (Avatar / Navigation / Layout & Colors) with live preview, reusable presets and a Custom CSS field.
* Customer avatar upload with MIME, size and image validation, overriding Gravatar site-wide.
* Commerce widgets, all off by default: Buy again one-click reorder, Recently viewed, and an order-tracking summary.
* Dashboard stats, orders-by-status chart, quick-link tiles and a profile completeness meter.
* Per-endpoint promotional banners with role targeting.
* Smart tags for personalised labels and content.
* Shortcode, block and widget for rendering the menu anywhere.
* Optional AJAX endpoint navigation, login/logout redirects and a configurable landing endpoint.
* JSON import / export plus a full reset tool.
* WPML and Polylang string registration; HPOS and cart/checkout blocks compatibility declared.

== Upgrade Notice ==

= 1.0.0 =
First public release.
