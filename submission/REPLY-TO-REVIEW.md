# Reply to WooCommerce Marketplace review (Bruna)

Copy/paste into the partner portal reply. Zip: `dist/my-account-dashboard-builder.zip` (v1.0.1).

---

Hi Bruna,

Thank you for the review. All ten items are fixed in **1.0.1**:

1. **Reserved endpoint names** — `page`, `product`, and other store routes cannot be used as endpoint slugs (safe key + skip rewrite).
2. **Non-Latin labels** — keys are ASCII only; Menu Items always loads; no Reset needed to remove items.
3. **Reset / Export / Import / Uninstall** — only this plugin’s options and known transients (not other `acfw_` plugins).
4. **Smart tags** — customer values are plain text only (no shortcode execution).
5. **Buy again** — runs `woocommerce_add_to_cart_validation` like the rest of the store.
6. **Custom endpoints** — no Dashboard under their content (replace placement + empty WC handler).
7. **Shop managers** — Settings save with `manage_woocommerce`.
8. **Same-name items** — e.g. Page “Orders” becomes `orders-2`; defaults are not replaced.
9. **Storefront double icons** — theme `::before` icons cleared when our menu is active.
10. **Admin menu** — under **WooCommerce → Account Dashboard**.

This build also includes admin layout/UX polish since the previous upload (Addresses grid, Design Studio accordion, look “In use” badge, etc.).

Please re-test with the attached **1.0.1** zip.

Thank you,  
Rcube
