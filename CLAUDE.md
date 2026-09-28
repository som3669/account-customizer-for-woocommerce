# My Account Dashboard Builder — project guide

WooCommerce **My Account** customizer plugin. Repo: https://github.com/som3669/my-account-dashboard-builder.git

Reference plugin (ThemeGrill commercial): `../customize-my-account-page-for-woocommerce` — UI/feature parity target (visuals similar, **not** a verbatim copy).

## Build pipeline (IMPORTANT — source of truth)

**Package manager: pnpm.** Build: **Gulp (`gulpfile.mjs`, ES module)** compiling SCSS→CSS + minifying JS.

```
pnpm install          # install toolchain
npx gulp build        # SCSS -> CSS (+ *.min.css) and JS -> *.min.js   (alias: pnpm build)
npx gulp watch        # rebuild on change
npx gulp zip          # build + package dist/my-account-dashboard-builder.zip  (pnpm zip)
```

- **Edit `assets/scss/*.scss`, NOT `assets/css/*.css`.** CSS is compiled output — direct `.css` edits are overwritten on the next build.
- Same for JS: hand-authored sources are `assets/js/{admin,frontend,customize-controls}.js`; `*.min.js` are generated.
- `assets/scss/_variables.scss` holds shared design tokens (`$accent`, `$border`, `$radius`, `$toggle-on/off`, …). SCSS vars feed the inline CSS custom props (`--acfw-*`) via `#{v.$var}`.
- Bundled libs are **not** built: `assets/css/fontawesome/`, `assets/{css,js}/select2/` ship as-is.

## Enqueue

`acfw_asset_src( $rel )` in `includes/helper/functions-acfw.php` resolves each asset to its `.min` build with a **filemtime** cache-buster. `SCRIPT_DEBUG` true → serves unminified sources. Used by admin, frontend, and customizer enqueues.

## Key files

- `my-account-dashboard-builder.php` — bootstrap, constants (`ACFW_DIR`, `ACFW_ASSETS_URL`, `ACFW_VERSION`).
- `includes/admin/class-acfw-admin.php` — top-level "My Account" admin menu + tabs (Menu Items, Settings, Customizer, Banners, Import/Export). Two-pane builder (left list + right detail) shared by Menu Items **and** Banners.
- `includes/frontend/class-acfw-frontend.php` — menu render, dynamic CSS vars, banners, avatar, redirects.
- `includes/class-acfw-items.php` — default endpoints + icons (defaults are **FontAwesome** `fas fa-*` so the icon picker pre-selects them).
- `includes/customize/class-acfw-customizer.php` — "My Account" Customizer panel (Avatar / Navigation / Layout & Colors) + custom controls in `includes/customize/class-acfw-customize-controls.php`.
- `includes/helper/functions-acfw.php` — helpers: `acfw_asset_src`, `acfw_sanitize_color` (hex/rgba), `acfw_sanitize_icon`, `acfw_icon_list`, smart tags.

## Conventions / gotchas

- Design options live in the **Customizer**, not the Settings API group (registering design keys in the settings group wiped them on Settings save).
- JS-templated Customizer controls require `$wp_customize->register_control_type()` or their templates don't print.
- Icon fields: `icon_source` = `choose|upload`. On save, when `upload`, the `choose` FA class is cleared (and vice-versa) so a stale icon doesn't render.
- `[hidden]` attribute is overridden by `display:grid/flex` — hidden `.acfw-field`/rows need an explicit `[hidden]{display:none}` rule to actually hide (used for banner type/link conditional fields).
- Banner forms: two exist on the page (`__new__` create + each existing). Never emit duplicate element `id`s across them (broke `<label for>` radios) — buttonset wraps the input in the label instead.
- Color control = vanilla port of the reference React control: round checkerboard swatch → popover (native picker + opacity + hex), stores hex or `rgba()`. Banner colours go through `acfw_sanitize_color()` (not `sanitize_hex_color()`) so the alpha survives.
- Item keys: new keys come from `acfw_unique_item_key()` (ASCII, never a WooCommerce/WordPress/reserved name, never a key or slug already in the menu). Never run `sanitize_text_field()`/`sanitize_textarea_field()` on a key or on the posted order JSON: they strip `%xx` octets. Decode the order and pass it to `acfw_sanitize_order_tree()`; read posted keys with `sanitize_title()`.
- Custom endpoints need a `woocommerce_account_{key}_endpoint` handler, or `woocommerce_account_content()` prints the dashboard under them. `ACFW_Frontend::setup_endpoint_content()` adds an empty one.
- CSS tokens: the static fallbacks sit in `:where(.woocommerce-MyAccount-navigation.acfw-menu)` (zero specificity) so the inline Customizer values (`.acfw-menu{…}`) win. Don't give the fallback rule real specificity again. The inline tokens are also declared on the dashboard widgets, banners and notice.
- Visibility rules (roles, dates in the site timezone, products, min orders, min spent) are evaluated by `acfw_visibility_passes()` for items and banners alike.
- Items tab detail pane: sections (General / Content / Visibility / Advanced) are ARIA tabs; select2 must be refreshed (`change.select2`) whenever a pane or section becomes visible, or multi-select placeholders measure 0px. `repaintClassicEditor()` rebuilds TinyMCE via `wp.editor.initialize()` and re-attaches the "Add smart tags" button.
- Icon pickers print only the current option; the library is sent once as `window.acfwIconChoices` and searched by a select2 `ajax.transport`. Use `icon_picker()` for any new picker.

## Release / QA

- **Node 18+ required for block builds.** `pnpm zip` runs `wp-scripts build` (`@wordpress/scripts` ^30); on Node 14 it fails. Fall back to `npx gulp build && npx gulp zip` when only SCSS/JS changed, since `assets/build/` is committed.
- **`gulp zip` deletes the whole `dist/` folder** (`cleanDist`). Keep nothing there. Marketplace notes, screenshots and the demo seeder live in `submission/`, which the zip excludes.
- Lint gate: `phpcs --standard=phpcs.xml.dist` (currently 0 errors, 0 warnings). `phpcbf` fixes the CRLF ones. CI runs both in `.github/workflows/ci.yml`.
- `.pot`: `wp i18n make-pot . languages/my-account-dashboard-builder.pot --exclude=node_modules,dist,submission,assets/src`. wp-cli emits absolute Windows paths in the `#:` references here — strip the prefix afterwards.
- QIT: `qit woo:validate-zip <zip>` runs locally. The cloud suites (`run:security`, `run:phpstan`, `run:validation`, `run:plugin-check`, `run:phpcompatibility`) need the slug registered in the partner portal first; `run:activation`/E2E need WSL.
- `wp eval` is broken against this WP 7.0.4 install (wp-cli fatals on a missing core function at `init`). Use a token-gated PHP script in the webroot for runtime pokes, and delete it afterwards.

## Testing (no live browser in CLI)

Authed fetch + headless Chrome screenshot: generate cookies with `wp eval` (`wp_generate_auth_cookie` for both `auth` + `logged_in`), `curl -b` the admin page to an HTML file, then `chrome --headless --screenshot`. Load the saved HTML **over http (same origin)** when checking FontAwesome — a `file://` page can't cross-origin-load the webfont (renders tofu; false alarm).
