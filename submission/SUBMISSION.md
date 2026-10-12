# WooCommerce Marketplace — Submit Product form answers

Zip to upload: `dist/my-account-dashboard-builder.zip` (491 KB, v1.0.1 — rebuild with `npm run zip`)

Reviewer reply: `submission/REPLY-TO-REVIEW.md`

---

## Product Details

**Name** (4–60 chars) — change the portal field from “My Account Customizer”
```
My Account Dashboard Builder
```
(28 chars. Do not use “Woo” / “WooCommerce” in the Name field. “My Account Customizer”
was too close to an existing WordPress.org plugin — keep this distinguishing name.
Description fields may still say WooCommerce freely.)

**Category** (primary)
```
Store Content and Customizations
```
Fallback if that option isn't present: **Store Management** / **Personalization**.

**Product short description** (≤140 chars)
```
Branded My Account dashboard: drag-and-drop menu, Design Studio, Buy again, returns, personal offers and self-service tools.
```
(124 chars.)

---

## Business Details

**Describe your product** / “How value provided?” (3–5 sentences)
```
My Account Dashboard Builder turns the default WooCommerce My Account page into a branded customer dashboard—without theme edits. Merchants build the menu on a live canvas (endpoints, pages, links and groups), add Classic or Gutenberg content, and design colours, layout and Typography in a Design Studio with a live preview. Customers get avatar upload, Buy again, recently viewed, order tracking, dashboard stats, personal offer coupons, returns, an address book, a privacy page and custom registration fields. Shop managers can “View as” any customer to see exactly what they see, and Insights shows which account pages and offers actually get used. It is translation-ready (WPML/Polylang), HPOS and cart/checkout-blocks compatible, and ships with no external service calls.
```

**How does your product compare to existing solutions?** (2–3 sentences)
```
Most My Account plugins only restyle the menu. My Account Dashboard Builder adds a Design Studio with live preview, View as customer, Gutenberg content, personal offers, returns, Insights and dashboard widgets that drive repeat purchases (Buy again, tracking, profile completeness). It covers the strongest features of tools like YITH Customize My Account Page while staying one self-contained plugin with zero external dependencies.
```

---

## Market presence

**Is this product sold elsewhere?**
```
No
```
(Not yet listed on WordPress.org or any other marketplace. If you later publish a free version on WordPress.org, update this.)

---

## Pricing

**Suggested price**
```
49 USD / year
```
(Matches the segment: KoalaApps $49, Extendons $49, ThemeGrill $59. Pick the closest tier the dropdown offers.)

Monetization model, per the Marketplace expectations: a **single-site annual
licence** under the standard paid-licence model. No SaaS backend, no per-order or
usage fee, no external subscription, no paid add-on, and one price for the whole
feature set. The Billing API and a partnership agreement are only needed for
integration/SaaS plugins, so neither applies here.

Two constraints to respect later:
- **Price parity.** If the plugin is ever sold direct as well, the single-site
  price there must match this listing.
- **One price per listing.** No Basic/Pro tiers inside a single listing; if a
  multi-site licence is sold elsewhere, the listing tracks the single-site fee.

Revenue share is 70% to the vendor.

---

## Languages

**Available languages**
```
English
```
(The plugin is fully translation-ready — .pot-ready text domain `my-account-dashboard-builder` + WPML/Polylang string registration. Add other languages only if you ship actual translation files.)

---

## Integrations and requirements

**Products with which your product offers special integration**
```
WooCommerce Shipment Tracking
```
(The Order Tracking widget auto-detects its tracking meta. Optional — the widget falls back to a status timeline when absent.)

**Other WooCommerce.com product(s) required before activating**
```
(leave blank — none required)
```

**Slugs of WordPress.org plugins required before activating**
```
(leave blank — none required)
```
(Polylang / WPML are optional enhancements, not requirements.)

---

## Testing

**Setup instructions**
```
1. Upload and activate the plugin. Open WooCommerce → Account Dashboard.
2. Menu Items: default WooCommerce endpoints are pre-loaded. Add endpoints, pages, links or groups; set icons and content (Classic or Block editor); drag to reorder.
3. Design: pick a look, then fine-tune Look / Menu / Profile card / Dashboard / Custom CSS beside the live preview. Nothing goes live until you click Save design.
4. Settings → General: enable optional features (all OFF by default) — Buy again, Recently viewed, Order tracking. Enable avatar upload under Design → Profile card.
5. Settings → Orders & privacy: optional Cancel orders, Returns, Address book, Privacy page.
6. Visit My Account as a customer (or Design → View as) to see the result.

Commerce features need a customer with at least one Completed order containing a purchasable product. Demo logins are in this field / on the demo site.
```

**Demo site URL**
```
https://rcube.thulo.eu.org/account-customizer/
```
That home page introduces the product and links straight to the customer demo
(`/my-account/`) and to the plugin's admin screen
(`/wp-admin/admin.php?page=acfw-settings`), so a reviewer can reach either from
one URL. Its block markup is in `demo-home-page.html` in this folder — paste it
into a new page via the editor's Code editor view, publish, then set it as the
homepage under Settings > Reading.

WordPress lives in the /account-customizer/ subdirectory. WooCommerce and this
plugin are both installed and active — the account page enqueues the plugin's
stylesheet and inline design tokens. Two things are still missing before a
reviewer can use it:

1. Products. The shop is empty, and Buy again has nothing to list without
   purchasable items. Products > Import with WooCommerce's own
   `sample-data/sample_products.csv` is the quickest fill.
2. Demo data and access. Run `demo-seed.php` from this folder (set its token and
   a real password first, upload to the WordPress root, load once, delete). It
   creates the `reviewer` customer, a completed order, a processing order, and
   switches every optional feature on.

The account page itself still shows the WooCommerce login form to a logged-out
visitor. Two ways to fix that, and they are not exclusive:

- Install `acfw-demo-autologin.zip` from this folder (Plugins > Add New >
  Upload). Any logged-out visitor to the account page is then signed in as the
  demo customer automatically, so the demo needs no password at all. Deactivate
  it to switch the demo off.
- And/or write the reviewer login (`reviewer` + the seeder's password) into the
  Setup instructions field above. That field is private to the review team.

Give the reviewers an admin login through the Setup instructions field too — the
Menu Items builder, Templates and Customizer are most of what they assess, and
those live in wp-admin. Never publish admin credentials.

> Note: the host answers non-browser clients with a JavaScript cookie challenge.
> Reviewers browsing normally are unaffected, but plain HTTP clients get an empty
> response — a failed `curl` against this URL is not an outage.

---

## Product Upload

**Product slug**
```
my-account-dashboard-builder
```
(Matches the zip's top-level folder name — required.)

**Currently listed on WordPress.org?**
```
Unchecked (No)
```

**Zip file**
```
Upload: dist/my-account-dashboard-builder.zip
```

---

## Additional information

**Notes for reviewers**
```
Everything optional ships OFF by default, so a fresh activation changes nothing
about the store: Buy again, Recently viewed, Order tracking, dashboard stats,
quick-link tiles, the profile meter and customer avatar upload all start
disabled. Enable them in "WooCommerce → Account Dashboard → Settings" and under
Design → Profile card.

Scope and behaviour
- The plugin only touches the WooCommerce My Account area. It replaces the
  account menu and endpoint output through WooCommerce's own hooks; no theme
  template is copied or modified, and deactivating restores the stock page while
  keeping the configuration.
- Admin UI lives under WooCommerce → Account Dashboard (not a top-level menu).
- Custom endpoints are registered as WooCommerce account endpoints, so rewrite
  rules are flushed on activation and on save, not on every page load. Reserved
  route names (page, product, cart, …) cannot be used as endpoint slugs.
- Orders are read only through the WooCommerce CRUD API. HPOS (custom order
  tables) and cart/checkout blocks compatibility are both declared.

No external dependencies
- No third-party service calls, no telemetry, no analytics, no remote fonts.
  Font Awesome 5 Free (CC BY 4.0 / SIL OFL / MIT) and Select2 (MIT) are bundled
  locally with their licence files. The plugin is GPLv2 or later.
- Optional integration: the order-tracking widget reads the
  _wc_shipment_tracking_items meta written by the Shipment Tracking extension,
  and falls back to a status timeline when that plugin is absent.

Security
- Every admin write is gated by a nonce plus the manage_woocommerce capability
  (shop managers can open and save), verified centrally before any tab handler runs.
- Front-end AJAX (avatar upload/remove, one-click reorder) checks a nonce and
  requires a logged-in user, and validates ownership: Buy again only offers
  products that customer actually purchased on a completed order, re-checked at
  add-to-cart time, including woocommerce_add_to_cart_validation.
- Smart tags insert customer-entered values as plain text only (no shortcode execution).
- Avatar uploads are validated by extension, real MIME (wp_check_filetype_and_ext)
  and getimagesize, restricted to JPG/PNG/GIF/WebP under an admin-set size limit.
- Rich content is filtered with wp_kses_post on save, and re-filtered on import
  for users without unfiltered_html, so an import file cannot smuggle scripts in.
- Reset / Export / Import / Uninstall use an allowlist of options this plugin
  owns; other acfw_* plugins (e.g. Advanced Coupons) are never touched.

Privacy and data
- Stores options this plugin owns (allowlist under acfw_) plus user meta keys
  acfw_avatar_id, acfw_last_login (and related feature meta). Registers a
  personal-data exporter and eraser for the uploaded avatar and customer fields.
- Deleting the plugin removes only this plugin's options, its meta keys and the
  avatar attachments it stored. Deactivating removes nothing.

Quality
- Translation-ready: languages/my-account-dashboard-builder.pot ships with 328 strings,
  plus WPML and Polylang string registration for admin-entered text.
- PHPCS (WordPress-Extra) passes with zero errors and zero warnings. 50 unit
  tests cover the output sanitisers and the import filter. CI lints the tree on
  PHP 7.4 through 8.3.
- Verified against WordPress 7.0.4 and WooCommerce 11.0.1 on PHP 8.3.

Monetization: a single-site annual licence sold through the Marketplace. There
is no SaaS backend, no per-order or usage fee, no external subscription, and no
paid add-on - every feature described is included in the one price, and the
plugin is not sold anywhere else.

Testing the commerce features needs a customer with a Completed order containing
a purchasable product. The demo site is seeded with exactly that, and the login
links in the Setup instructions land you straight in it.
```

---

## Before you submit — checklist
- [ ] Public demo URL live + reviewer login added to Setup instructions / Demo field.
      Fast path: install the plugin on the demo host, copy `submission/demo-seed.php` to the
      WordPress root, set its token, hit it once in the browser, then delete it. It creates the
      `reviewer` customer, a completed order (Buy again), a processing order (order tracking),
      and switches every optional feature on.
- [x] Screenshots ready: `submission/screenshots/screenshot-1.png` … `screenshot-8.png`
      (captions match the `== Screenshots ==` block in readme.txt; more in `screenshots/extras/`).
      Still missing: the listing **banner/header** graphic, if the form asks for one.
- [ ] Tick "I agree to the terms of the Partner Agreement".
- [x] `qit woo:validate-zip dist/my-account-dashboard-builder.zip` — **passes** ("ZIP file content is valid").
- [ ] QIT cloud tests. These cannot run until the product exists in the partner portal: with an
      unregistered slug every run fails with *"Could not find Woo Extension with slug
      my-account-dashboard-builder"*. So create the product entry first, then run:
      `qit run:security <slug> --zip=dist/my-account-dashboard-builder.zip` and the same for
      `run:phpstan`, `run:validation`, `run:plugin-check`, `run:phpcompatibility`.
      Note `run:phpcs` no longer exists in current QIT — `run:validation` and `run:plugin-check`
      replace it. `run:activation` and any E2E run need **WSL** on Windows.
- [x] Local PHPCS (`WordPress-Extra` ruleset): 0 errors, 5 cosmetic warnings.
