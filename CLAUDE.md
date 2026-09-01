# My Account Customizer — project guide

WooCommerce **My Account** customizer plugin. Repo: https://github.com/som3669/my-account-customizer.git

Reference plugin (ThemeGrill commercial): `../customize-my-account-page-for-woocommerce` — UI/feature parity target (visuals similar, **not** a verbatim copy).

## Build pipeline (IMPORTANT — source of truth)

**Package manager: pnpm.** Build: **Gulp (`gulpfile.mjs`, ES module)** compiling SCSS→CSS + minifying JS.

```
pnpm install          # install toolchain
npx gulp build        # SCSS -> CSS (+ *.min.css) and JS -> *.min.js   (alias: pnpm build)
npx gulp watch        # rebuild on change
npx gulp zip          # build + package dist/my-account-customizer.zip  (pnpm zip)
```

- **Edit `assets/scss/*.scss`, NOT `assets/css/*.css`.** CSS is compiled output — direct `.css` edits are overwritten on the next build.
- Same for JS: hand-authored sources are `assets/js/{admin,frontend,customize-controls}.js`; `*.min.js` are generated.
- `assets/scss/_variables.scss` holds shared design tokens (`$accent`, `$border`, `$radius`, `$toggle-on/off`, …). SCSS vars feed the inline CSS custom props (`--acfw-*`) via `#{v.$var}`.
- Bundled libs are **not** built: `assets/css/fontawesome/`, `assets/{css,js}/select2/` ship as-is.

## Enqueue

`acfw_asset_src( $rel )` in `includes/helper/functions-acfw.php` resolves each asset to its `.min` build with a **filemtime** cache-buster. `SCRIPT_DEBUG` true → serves unminified sources. Used by admin, frontend, and customizer enqueues.

## Key files

- `my-account-customizer.php` — bootstrap, constants (`ACFW_DIR`, `ACFW_ASSETS_URL`, `ACFW_VERSION`).
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
- Color control = vanilla port of the reference React control: round checkerboard swatch → popover (native picker + opacity + hex), stores hex or `rgba()`.

## Release / QA

- **Node 18+ required for block builds.** `pnpm zip` runs `wp-scripts build` (`@wordpress/scripts` ^30); on Node 14 it fails. Fall back to `npx gulp build && npx gulp zip` when only SCSS/JS changed, since `assets/build/` is committed.
- **`gulp zip` deletes the whole `dist/` folder** (`cleanDist`). Keep nothing there. Marketplace notes, screenshots and the demo seeder live in `submission/`, which the zip excludes.
- Lint gate: `phpcs --standard=phpcs.xml.dist` (currently 0 errors, 0 warnings). `phpcbf` fixes the CRLF ones. CI runs both in `.github/workflows/ci.yml`.
- `.pot`: `wp i18n make-pot . languages/my-account-customizer.pot --exclude=node_modules,dist,submission,assets/src`. wp-cli emits absolute Windows paths in the `#:` references here — strip the prefix afterwards.
- QIT: `qit woo:validate-zip <zip>` runs locally. The cloud suites (`run:security`, `run:phpstan`, `run:validation`, `run:plugin-check`, `run:phpcompatibility`) need the slug registered in the partner portal first; `run:activation`/E2E need WSL.
- `wp eval` is broken against this WP 7.0.4 install (wp-cli fatals on a missing core function at `init`). Use a token-gated PHP script in the webroot for runtime pokes, and delete it afterwards.

## Testing (no live browser in CLI)

Authed fetch + headless Chrome screenshot: generate cookies with `wp eval` (`wp_generate_auth_cookie` for both `auth` + `logged_in`), `curl -b` the admin page to an HTML file, then `chrome --headless --screenshot`. Load the saved HTML **over http (same origin)** when checking FontAwesome — a `file://` page can't cross-origin-load the webfont (renders tofu; false alarm).
