# Reply to WooCommerce Marketplace review (Bruna)

Copy/paste into the partner portal reply. Zip: `dist/my-account-dashboard-builder.zip` (v1.0.2).

---

Hi Bruna,

Thank you for the follow-up. The remaining required item and the minors are fixed in **1.0.2**:

1. **Advanced Coupons coexistence** — Our admin page is now `my-account-dashboard-builder` (not `acfw-settings`), Settings API groups are `madb_settings` / `madb_settings_orders`, and admin asset handles use a `madb-` prefix. With Advanced Coupons active, Coupons → Settings opens their screen, and Account Dashboard menu items edit/add/reorder normally.
2. **Shop managers** — Settings save uses `option_page_capability_madb_settings` / `_orders` → `manage_woocommerce` (options.php was still checking `manage_options`).
3. **MEMORY.md** — Excluded from the release zip.
4. **Return photos** — Stored under a private uploads folder (direct URL blocked) and served only to shop staff or the customer who uploaded them.
5. **WC tested up to** — Header and readme are **11.2**.

Please re-test with the attached **1.0.2** zip.

Note: after uploading, QIT reported two Security Test warnings on return-photo serving. We have a hardened build ready, but zip replacement is disabled on this reply. If you reopen the file field, we can upload it straight away.

Thank you,  
Rcube
