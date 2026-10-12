=== My Account Dashboard Builder ===
Contributors: Rcube
Tags: woocommerce, my account, account page, endpoints, customizer
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
WC requires at least: 6.0
WC tested up to: 11.2
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Turn WooCommerce My Account into a branded customer dashboard: drag-and-drop menu, live Design Studio, personal offers, returns and self-service.

== Description ==

The default WooCommerce **My Account** page is a plain list of links. My Account Dashboard Builder rebuilds it into a branded customer dashboard — without touching a template file or writing a line of code.

= Build the menu =

* Add unlimited items: **endpoints** (own URL + content), **pages**, **links** (internal or external) and **groups** (collapsible parents).
* Build it on a canvas drawn the way customers see it: drag to reorder or nest, switch any item on or off, duplicate or delete it in place.
* Rename every item, give a custom endpoint its own URL, and pick an icon from the bundled Font Awesome library, a Dashicon, or upload your own.
* Add a **badge** to any item: "New", or a live value such as `{points_balance} pts`.
* Control who sees what: by **user role**, **date range**, **products the customer bought**, **number of orders** (at least or at most), **total spent**, or **how long since their last order**.
* **Menus for customer groups** — wholesale buyers, members or any role get a menu of their own, while item settings stay shared.
* Each item on the canvas says who can see it ( e.g. "Customer · 2+ orders" ).
* **Status badges** that update themselves: "1 to pay" on Orders, "1 open" on Returns, and a dot where account details are missing.
* Set the landing endpoint customers open on, plus login and logout redirects.

= See it the way your customers do =

* **View as a customer** — pick any customer and see My Account exactly as they do: their menu or group menu, badges, offers and dashboard.
* A bar lists everything hidden from them, and why: "Needs 3+ orders (has 1)", "Only for Wholesale", "Only for customers with no orders yet".
* Read-only by design: buttons and forms are switched off, no coupon is created, nothing is counted, and the customer's cart and session are left alone. The preview link is signed for you and expires after two hours.

= Fill it with content =

* Give each endpoint its own content using either the **Classic editor** or the native **Gutenberg block editor**.
* Insert content **before**, **after**, or **instead of** the default WooCommerce output.
* Add promotional **banners** per endpoint — text, image or icon, with the same visibility rules as menu items, a schedule, your own link text and a count badge for orders, downloads, cart items or reward points.
* **Personal offers** — a banner can hand each customer a coupon code of their own (one use, their email only, ending after the days you set), shown with Copy and "Use it now". Aim it at first-time buyers or customers who have not ordered in 90 days.
* **Smart tags** personalise any text: `{display_name}`, `{first_name}`, `{last_name}`, `{username}`, `{user_email}`, `{site_title}`, `{order_count}`, `{download_count}`, `{total_spent}`, `{member_since}`, `{cart_count}`, `{billing_phone}`, `{billing_city}`, `{billing_country}`, `{points_balance}`, `{membership_plan}`, `{last_login}`, `{account_url}`, `{shop_url}`, `{site_url}`, and `{field_…}` for your own customer fields.

= Design it visually =

* **Design Studio** — the real My Account page beside the controls, at desktop, tablet and phone widths. Colours, sizes and menu styles change the preview as you pick them, and nothing goes live until you save.
* **Ready-made looks** to start from, and your own saved looks alongside them.
* A contrast check on the accent colour, and one **Density** choice (compact, comfortable, roomy) instead of three spacing sliders.
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
* **Arrange the dashboard** — drag the greeting, heading, numbers, meter, tiles, tracking and Buy again into any order, or hide them.

= Let customers help themselves =

* **Cancel orders** that have not shipped yet, within the hours you allow.
* **Returns** — customers ask to return items from a completed order, with a reason and a photo; you answer from one screen and they are emailed.
* **Address book** — more saved addresses, made the shipping or billing address in a click, and picked from at the classic checkout.
* **Privacy page** — customers request a copy of their data or its erasure, through WordPress's own confirmed requests.
* **Customer fields** — ask for a VAT number, company, birthday or anything else on the registration and account forms; answers show on the order screen and the user's profile.

= See what customers use =

**Insights** shows account page views per day and per page (and which pages nobody opens), banner views and click rates, and how many personal offer codes were used and the sales they brought. Only daily totals are kept; nothing personal, and shop managers are not counted.

= Anywhere on the site =

Render the customised menu outside the account page with the `[acfw_account_menu]` shortcode, the **Account Menu** block, or the classic **Account Menu** widget. On classic themes, **Appearance → Menus** gets a **My Account** box: a link that reads *Log in* for visitors and *My account* for customers, and one that drops down the customer's account pages.

= Built to fit =

* A **How to use** guide in the admin header: every feature in a few steps, with a button to the screen where it is done. It opens on the section for the tab you are on and can be searched.
* Optional AJAX navigation between endpoints.
* **Import / Export** the whole configuration as JSON, and a full reset tool.
* Translation-ready, with string registration for **WPML** and **Polylang**.
* Account pages from **WooCommerce Subscriptions**, **Memberships**, **Bookings**, points, wallet and ticket plugins show in the menu with fitting icons; **YITH** and **TI WooCommerce Wishlist** get a Wishlist page inside My Account.
* Declares **HPOS** (custom order tables) and cart/checkout blocks compatibility.
* No external service calls, no telemetry, no remotely loaded fonts. Font Awesome and Select2 ship locally.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`, or install the zip via **Plugins → Add New → Upload Plugin**.
2. Activate through the **Plugins** screen. WooCommerce must be active.
3. Open **WooCommerce → Account Dashboard**. Tabs cover Menu Items, Design, Banners, Fields, Returns, Insights and Settings.
4. Default WooCommerce endpoints are pre-loaded, so the page keeps working before you change anything.
5. Optional: enable Buy Again, Recently viewed and Order tracking in **My Account → Settings → General**, order cancelling, returns, the address book and the privacy page in **Settings → Orders & privacy**, and avatar uploads in **My Account → Design → Profile card**.
6. New to the plugin? Click **How to use** in the plugin's header for a short guide to every feature.

== Frequently Asked Questions ==

= Do I have to edit theme templates? =

No. The plugin replaces the WooCommerce account menu and endpoint output through the hooks WooCommerce already provides. Nothing is copied into your theme.

= Will it break if I deactivate the plugin? =

No. Your custom endpoints stop resolving and the page falls back to the standard WooCommerce menu. Your configuration is kept, so reactivating restores everything.

= Where do the design options live? =

In **My Account → Design**, the Design Studio, where you edit them beside a live preview of the account page. Content and menu structure live under **Menu Items**. The WordPress Customizer keeps a **My Account** section that links to the Studio.

= Can customers upload their own profile picture? =

Yes, once you enable it in My Account → Design → Profile card. Uploads are restricted to JPG, PNG, GIF and WebP, checked against a size limit you set, and verified to be real images before being stored. The uploaded picture then replaces the Gravatar everywhere on the site.

= Does the Buy again tab let customers reorder anything? =

No. It only offers products that customer actually purchased on a completed order, and it re-checks purchase history, stock and the store's normal `woocommerce_add_to_cart_validation` rules on every add-to-cart request.

= Is it compatible with HPOS and the block-based cart and checkout? =

Yes, compatibility with both is declared. The plugin reads orders through the WooCommerce CRUD API only.

= Is it translation-ready? =

Yes. A `.pot` file ships in `/languages`, and admin-entered strings (item labels, banner titles and content) are registered for WPML and Polylang.

= What data does it store, and what happens on uninstall? =

Options this plugin owns (an allowlist under the `acfw_` prefix, so tools never touch other plugins that share that prefix); user meta keys `acfw_avatar_id`, `acfw_last_login`, `acfw_order_stats` (a cache of the dashboard counts), `acfw_addresses` (the address book), `acfw_field_{key}` (customer field answers) and `acfw_offer_{banner}` (the coupon each customer was given); return requests with their photos; and a `{prefix}acfw_stats` table of daily usage totals for Insights. Deleting the plugin from the Plugins screen removes only this plugin's data, including uploaded avatar images. The coupons made for personal offers are ordinary WooCommerce coupons that customers may still hold, so they stay. Deactivating removes nothing.

= Can I see the account area the way a particular customer sees it? =

Yes. Click **⋯ → Preview** on any tab ( or **View as** above the Design Studio preview ) and search for the customer. You see their menu, badges, offers and dashboard, with a list of what is hidden from them and why. It is read-only: nothing is changed or sent for them, and staff accounts cannot be viewed as.

= Can different customers get different menus? =

Yes. On Menu Items, **+ Menu for a customer group** makes a menu for chosen roles ( wholesale buyers, members … ). It starts as a copy of the main menu; take items out, add others and reorder it. Item settings stay shared, and everyone else keeps the main menu.

= Can I change the order of the dashboard? =

Yes. In **Design → Dashboard → Arrange the dashboard**, drag WooCommerce's greeting, your heading, the account numbers, the profile meter, the shortcut tiles, order tracking, Buy again and other plugins' widgets into any order, and switch any of them off. The preview follows as you drag.

= Can customers cancel or return orders themselves? =

Yes, once you switch it on in **Settings → Orders & privacy**. Customers can cancel orders that have not shipped, within the hours you allow, and ask to return items from completed orders with a reason and a photo. You answer each request on the **Returns** tab, and the customer is emailed.

= Are personal offer codes ordinary coupons? =

Yes. They are WooCommerce coupons, listed under Marketing → Coupons, limited to one use and to the customer's email address. Coupons must be switched on in WooCommerce → Settings → General.

== Screenshots ==

1. The Menu Items canvas — the account menu as customers see it; drag to reorder, switch items on or off, and edit any item beside it.
2. Editing an item: label, icon, visibility rules by role, date and purchased product, and the content editor.
3. Ready-made looks at the top of the Design Studio.
4. The Design Studio: controls grouped by what customers see, beside a live preview of the account page.
5. The customised My Account page: avatar block, icon menu with counts, dashboard stats and profile meter.
6. The Buy again tab — one-click reorder of previously purchased products.
7. Settings — commerce features, redirects and navigation behaviour.
8. Import / Export of the full configuration.

== Changelog ==

= 1.0.1 =

**Fix (WooCommerce Marketplace review)**

* Endpoint names such as Page or Product no longer register rewrites that 404 other store pages.
* Non-Latin labels (Chinese, Cyrillic, …) no longer crash Menu Items; keys stay ASCII and the screen always loads.
* Reset, Export, Import and Uninstall only touch this plugin's options (not other `acfw_` plugins such as Advanced Coupons).
* Smart tags insert customer values as plain text only; shortcodes in a name are not executed.
* Buy again runs the same `woocommerce_add_to_cart_validation` rules as the rest of the store.
* Custom endpoints no longer show the Dashboard under their content when placement is "Before default content".
* Shop managers can save Settings (`manage_woocommerce` capability).
* Adding an item named like a default (e.g. Orders) creates a separate item instead of replacing it.
* Storefront no longer shows two icons on each default menu item.
* Admin screen moved under WooCommerce → Account Dashboard.

**New**

* View as a customer, status badges, arrange the dashboard, How to use guide, personal offers, customer fields, cancel/returns, address book, privacy page, menus for customer groups, Insights, Design Studio, Menu Items canvas, and related visibility / smart-tag improvements. Full list in changelog.txt.

**Tweak / Accessibility**

* See changelog.txt.

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

= 1.0.1 =
Fixes all items from the WooCommerce Marketplace review: reserved endpoint names, non-Latin labels, scoped Reset/Export/Import/Uninstall, plain-text smart tags, Buy again validation, custom endpoint content, shop manager Settings saves, same-name menu items, Storefront double icons, and admin menu under WooCommerce.

= 1.0.0 =
First public release.
