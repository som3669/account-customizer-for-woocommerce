# Security auditor memory — My Account Dashboard Builder

## Auth mechanisms
- Admin POST actions: single gate in `ACFW_Admin::handle_actions()` — `manage_woocommerce` + `check_admin_referer( 'acfw_admin_action' )`.
- Design AJAX: `ACFW_Design::guard()` — nonce + `manage_woocommerce`.
- View-as: HMAC-signed query args (`sign(real, target, exp)`), GET/HEAD only, read-only guard; `manage_woocommerce` to mint.
- Customer AJAX: avatar / buy-again / track — logged-in + nonce; buy-again also ownership checks.
- Frontend forms (returns, privacy, addresses): per-feature nonces.

## Capabilities
- Primary admin capability: `manage_woocommerce` (menu, Settings API `register_setting`, design AJAX, view-as).
- Settings forms: `option_page_capability_madb_settings` / `madb_settings_orders` → `manage_woocommerce` (options.php ignores register_setting capability alone).
- Content: `unfiltered_html` gates raw HTML on item save and import.

## Custom / shared prefixes (collision surface)
- Admin page slug: `my-account-dashboard-builder` (not Advanced Coupons' `acfw-settings`). Settings groups: `madb_settings` / `madb_settings_orders`. Admin asset handles: `madb-*`.
- Options: allowlisted via `acfw_is_owned_option()` / `acfw_list_owned_option_names()` (fix for Reset/Export/Import/option uninstall).
- Still shared with Advanced Coupons-style `acfw_` ecosystem: PHP functions `acfw_*`, classes `ACFW*`, constants `ACFW_*`, CSS `.acfw-*`, shortcode `acfw_account_menu`, block `acfw/account-menu`, CPT `acfw_return`, table `{prefix}acfw_stats`, cookie `acfw_recently_viewed`, user meta `acfw_*`.
- Transients: uninstall still deletes ALL `_transient_acfw_%` / `_transient_timeout_acfw_%` (not allowlisted) — incomplete coexistence fix.
- Return photos: private dir `madb-return-photos` + `.htaccess` deny + `wp_ajax_acfw_return_photo` gate.

## Known safe / mitigated patterns
- Smart tags: strip shortcodes + `esc_html` + neutralize `[`/`]` before later `do_shortcode()` on endpoint content.
- Design save: `sanitize_values()` allowlists keys from `ACFW_Design::fields()`.
- Import options: skips non-owned names; `acfw_items_order` always tree-sanitized.
- Rewrite flush: flag option `acfw_flush_rewrite_rules`, flushed once on `init` 22 — not every request.
- No remote fonts/CDNs/telemetry HTTP calls observed; FA webfonts local (incomplete file set).

## Confirmed false positives / skip
- Cap mismatch shop-manager Settings save — fixed (`option_page_capability_*` + register_setting capability).
- Option reset/export/import wiping foreign options — fixed for options allowlist (do not re-report unless allowlist broken).
- Smart-tag shortcode execution from customer values — fix present and ordered before `do_shortcode`.
- Advanced Coupons `acfw-settings` collision — fixed (own page slug + madb settings groups + madb asset handles).

## Attack surface (public / customer)
- My Account endpoints + custom content (`do_blocks` / `do_shortcode`).
- AJAX: `acfw_avatar_*`, `acfw_reorder_*`, `acfw_track`, design `acfw_design_*` (admin), `acfw_view_as_search`, `acfw_return_photo` (auth+nonce).
- Uploads: avatar (strong MIME), return photo (private dir + gated serve; MIME via `wp_check_filetype`).
- Cookie: `acfw_recently_viewed` product IDs.
- Privacy exporters/erasers: fields, addresses, avatar only — gaps for returns CPT, offers meta, last_login, order_stats cache.

## Packaging notes
- Dist zip ships `DOCUMENTATION.md` (gulp excludes `CLAUDE.md`, `MEMORY.md`, `DOCUMENTATION.docx`).
- `icon.svg` aria-label still "Account Customizer for WooCommerce".
