# WooCommerce Marketplace — Submit Product form answers

Zip to upload: `dist/account-customizer-for-woocommerce.zip` (291 KB)

---

## Product Details

**Name** (4–60 chars)
```
Account Customizer for WooCommerce
```
(34 chars. "for WooCommerce" is descriptive use — allowed by the trademark guidelines. Does not start with "Woo/WooCommerce".)

**Category** (primary)
```
Store Content and Customizations
```
Fallback if that option isn't present: **Store Management**.

**Product short description** (≤140 chars)
```
Redesign the WooCommerce My Account page: custom endpoints, block editor content, avatar uploads, reorder, and one-click design templates.
```
(139 chars.)

---

## Business Details

**Describe your product** (3–5 sentences)
```
Account Customizer for WooCommerce turns the default My Account page into a branded customer dashboard. Store owners build unlimited menu endpoints (endpoint, page, link or grouped), add custom content with either the Classic or the native Gutenberg block editor, and apply one-click design templates or fine-tune everything (layout, colours, typography, avatar, active indicators) live in the WordPress Customizer. Customers get modern touches their store previously lacked: their own uploaded profile picture, a "Buy again" one-click reorder tab, a Recently Viewed tab, an order-tracking summary, and per-endpoint promotional banners. Every design choice is visual and preview-driven, so no code or theme edits are required. It is translation-ready (WPML and Polylang), HPOS and cart/checkout-blocks compatible, and ships as a self-contained plugin with no external service calls.
```

**How does your product compare to existing solutions?** (2–3 sentences)
```
Unlike other My Account customizers which mostly restyle the page, this plugin adds a native Gutenberg block editor for endpoint content, ready-made design templates, and dashboard widgets that actually drive revenue — Buy Again / one-click reorder, Recently Viewed, and order tracking. It also matches their strongest paid features (customer avatar upload, per-role/date/purchase visibility rules, WPML/Polylang) while remaining a single self-contained plugin with zero external dependencies.
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

---

## Languages

**Available languages**
```
English
```
(The plugin is fully translation-ready — .pot-ready text domain `account-customizer-for-woocommerce` + WPML/Polylang string registration. Add other languages only if you ship actual translation files.)

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
1. Upload and activate the plugin. A top-level "My Account" menu appears in wp-admin.
2. Menu Items tab: default WooCommerce endpoints are pre-loaded. Add endpoints/links/pages/groups, set icons, and add custom content (Classic or Block editor). Drag to reorder.
3. Templates tab: click any starter template to apply a full design in one click.
4. Customizer: open "My Account Customizer" to fine-tune layout, colours, typography and avatar with a live preview.
5. Settings > General: enable the commerce features (all OFF by default):
   - Buy Again  -> adds a "Buy again" account tab + dashboard tile (one-click reorder of past products).
   - Recently viewed -> adds a "Recently viewed" account tab.
   - Order tracking -> adds a tracking summary to the dashboard (auto-detects WooCommerce Shipment Tracking).
   Also enable "Let customers upload their own picture" under the Customizer > Avatar section for avatar uploads.
6. Visit the My Account page (logged in as a customer) to see the result.

To test the commerce features you need a customer account with at least one Completed order containing a purchasable product.

Test login (reviewer): provided in the Demo site URL below / on request.
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
account-customizer-for-woocommerce
```
(Matches the zip's top-level folder name — required.)

**Currently listed on WordPress.org?**
```
Unchecked (No)
```

**Zip file**
```
Upload: dist/account-customizer-for-woocommerce.zip
```

---

## Additional information

**Notes for reviewers**
```
- All commerce features (Buy Again, Recently Viewed, Order Tracking) and customer avatar upload are OFF by default; enable them in Settings > General and the Customizer > Avatar section.
- No external/third-party service calls. Google Fonts are not loaded remotely; no telemetry/analytics.
- HPOS (custom order tables) and cart/checkout blocks compatibility are declared.
- Security: all admin actions are gated by a single nonce + manage_woocommerce capability; front-end AJAX (avatar upload, reorder) checks nonce + login and validates ownership/purchase; uploads are MIME/size/getimagesize-validated; reorder only allows products the customer actually purchased.
- Bundled third-party libraries with their licenses included: Font Awesome 5 Free (CC BY 4.0 / SIL OFL / MIT) and Select2 (MIT). Plugin is GPLv2+.
- Translation-ready; registers strings for WPML and Polylang.
- To exercise commerce features, create a customer with a Completed order containing a purchasable product (reviewer test data can be seeded on the demo site).
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
- [x] `qit woo:validate-zip dist/account-customizer-for-woocommerce.zip` — **passes** ("ZIP file content is valid").
- [ ] QIT cloud tests. These cannot run until the product exists in the partner portal: with an
      unregistered slug every run fails with *"Could not find Woo Extension with slug
      account-customizer-for-woocommerce"*. So create the product entry first, then run:
      `qit run:security <slug> --zip=dist/account-customizer-for-woocommerce.zip` and the same for
      `run:phpstan`, `run:validation`, `run:plugin-check`, `run:phpcompatibility`.
      Note `run:phpcs` no longer exists in current QIT — `run:validation` and `run:plugin-check`
      replace it. `run:activation` and any E2E run need **WSL** on Windows.
- [x] Local PHPCS (`WordPress-Extra` ruleset): 0 errors, 5 cosmetic warnings.
