# Account Customizer for WooCommerce — Documentation

Customize the WooCommerce **My Account** page: rebuild the navigation, add custom
endpoints/links/pages/groups, style the menu live in the Customizer, apply
one‑click starter templates, add banners, a rich dashboard, and more.

- **Requires:** WordPress + WooCommerce, PHP 7.4+
- **Admin home:** WP Admin → **My Account** (top‑level menu, WooCommerce capability)
- **Where design lives:** the **Customizer** (live preview). Behaviour/content lives in the plugin admin tabs.

---

## Table of contents
1. [Admin overview](#admin-overview)
2. [Menu Items (builder)](#menu-items)
3. [Item types: Endpoint, Group, Link, Page](#item-types)
4. [Icons](#icons)
5. [Custom content — Classic & Block editor](#custom-content)
6. [Visibility rules](#visibility)
7. [Starter templates](#templates)
8. [Customizer — design options](#customizer)
9. [Avatar block](#avatar)
10. [Dashboard widgets](#dashboard)
11. [Banners](#banners)
12. [Settings tab](#settings)
13. [Import / Export](#import-export)
14. [Smart tags](#smart-tags)
15. [Shortcode / Widget / Block](#embedding)
16. [Developer: build, hooks, templates](#developer)

---

<a name="admin-overview"></a>
## 1. Admin overview

The plugin adds a top‑level **My Account** menu with tabs:

| Tab | Purpose |
|-----|---------|
| **Menu Items** | Build & order the account menu (endpoints, groups, links, pages). |
| **Templates** | One‑click starter designs. |
| **Settings** | Behaviour: AJAX nav, default endpoint, redirects, guest message, view tracking, design presets, reset. |
| **Customizer** | Opens the WP Customizer focused on the “My Account” panel (all visual styling). |
| **Banners** | Reusable widget/image banners to attach to endpoints. |
| **Import / Export** | Back up or move the whole configuration as JSON. |

### Header bar

The header has the **tab navigation** (left) and **context action buttons** (right), plus two **floating buttons** pinned to the screen’s right edge.

**Action buttons (top‑right), by tab:**
| Tab | Buttons |
|-----|---------|
| Menu Items | **Add endpoint**, **Add group**, **Add link**, **Add page** |
| Banners | **Add banner** |

**Floating buttons (always visible on the admin page):**
- **Preview** — opens the live My Account page in an in‑page overlay (iframe), no navigation away.
- **View My Account** — opens the live My Account page in a new tab.

There is also a **second “Add banner”** button directly beneath the banner list, and the **Save changes** button in each editor footer.

---

<a name="menu-items"></a>
## 2. Menu Items (builder)

A two‑pane builder: the ordered list on the left, the selected item’s options on the right.

**Steps**
1. Go to **My Account → Menu Items**.
2. Click **Add endpoint / Add group / Add link / Add page** (top‑right) and type a label → **Create**. New items land at the end.
3. Click a row to edit it in the right pane.
4. **Drag** the handle to reorder; drag an item onto a group to nest it (one level).
5. Toggle the switch to enable/disable an item; use the copy icon to **duplicate**, the trash icon to **delete** (default WooCommerce items can be disabled but not deleted).
6. Click **Save changes** (single save for the whole list).

---

<a name="item-types"></a>
## 3. Item types

- **Endpoint** — a real WooCommerce account endpoint (Dashboard, Orders, …) or your own. Can carry custom content + a banner.
- **Group** — a non‑clickable heading that expands/collapses to reveal child items (drag items into it). Default icon: folder.
- **Link** — points anywhere via a URL. Option: **Open in new tab**. Default icon: link.
- **Page** — links to an existing WordPress **Page** (chosen from a dropdown); resolves to that page’s permalink. Option: **Open in new tab**. Default icon: file.

---

<a name="icons"></a>
## 4. Icons

Each item shows an icon. In the item’s options:

- **Choose icon** — pick a FontAwesome icon from the searchable dropdown.
- **Upload icon** — upload/select an image from the Media Library (overrides the FA icon).

Default WooCommerce endpoints ship with sensible FontAwesome defaults (Dashboard → tachometer, Orders → cart, etc.), and those defaults are pre‑selected in the picker.

---

<a name="custom-content"></a>
## 5. Custom content — Classic & Block editor

For **Endpoint** items you can add extra content shown on that endpoint.

**Steps**
1. Select an endpoint → find **Content editor**.
2. Choose **Classic** (TinyMCE) or **Block** (Gutenberg).
   - **Classic** — the familiar visual/text editor with Add Media + smart‑tag button.
   - **Block** — a standalone block editor (paragraphs, headings, images, buttons, columns, embeds, etc.). Block markup is stored and rendered with `do_blocks()` + oEmbed on the front end.
3. Set **Custom content position**: *Before* / *After* the default endpoint content, or *Replace* it.
4. Optionally attach a **Banner** + position (top/bottom).
5. **Save changes.**

> The Block editor only appears when its bundle is built (`pnpm build`). Otherwise the field falls back to Classic automatically.

---

<a name="visibility"></a>
## 6. Visibility rules

Per item (right pane):

- **User roles** — show only to selected roles (empty = everyone). Admins always see everything.
- **Show from / Show until** — date window during which the item appears.
- **Purchased product** — only show to customers who bought a given product ID.

---

<a name="templates"></a>
## 7. Starter templates

Ready‑made designs you can apply in one click.

**Steps**
1. Go to **My Account → Templates**.
2. Each card shows a live mini‑preview of the real menu + a description.
3. Click **Apply template**. The applied card shows an **Applied** badge and a disabled **Applied** button.

**What happens:** applying **resets all design options to defaults first**, then applies the template’s values — so the result is deterministic and matches the preview exactly. You can then fine‑tune anything in the Customizer; your tweaks persist until you apply another template.

**Shipped templates:** Classic Sidebar · Modern Cards · Rounded Pills · Tabbed Top · Minimal · Theme Native.

---

<a name="customizer"></a>
## 8. Customizer — design options

**My Account → Customizer** (or Appearance → Customize → *My Account* panel). All changes preview live. Three sections:

### Avatar
See [Avatar block](#avatar).

### Navigation
- **Menu style** — the single control that sets the whole look: **Theme style, Simple, Classic, Modern cards, Minimal, Pills, Tabs**. (Internally this maps to a layout + item‑skin combination.)
- **Show menu icons** / **Show item counts**.
- **Dashboard quick‑link tiles**, **Dashboard title**, **Dashboard content position** (Left/Middle/Right).
- **Dashboard stat widgets** + individual toggles (see [Dashboard](#dashboard)).
- **Menu search box** — filter box above the menu.
- **Collapsible icon rail** — collapse the menu to icons only.
- **Let customers pin favorites** — a star to pin items to the top.
- **Profile completeness meter** — progress bar on the dashboard.
- **Sticky menu** — menu sticks on scroll.
- **Confirm before logout**.
- **Active indicator** — **Bar / Underline / Dot / None** (how the current item is marked).
- **Hover animation** — None / Slide / Grow.
- **Expand groups by default**.
- **AJAX navigation** — load endpoints without a full page reload.

### Layout & Colors
- **Accent / Text / Active** colors, **Menu item background**, **Hover background**.
- **Corner radius**, **Item spacing (gap)**, **Item padding**, **Font size**, **Font weight**, **Font family**.
- **Color scheme** — **Light** (default), **Dark**, or **Auto** (follows the visitor’s OS). Dark is opt‑in so it never clashes with a light theme.
- **Custom CSS**.

---

<a name="avatar"></a>
## 9. Avatar block

A customer card shown **above the menu** in the same column.

**Steps**
1. Customizer → **Avatar** → enable **Show avatar**.
2. When enabled, the sub‑options appear: **Custom avatar image** (overrides the gravatar), **Shape** (circle/square), **Alignment**, **Size**, **Show display name**, **Show user role**.

---

<a name="dashboard"></a>
## 10. Dashboard widgets

On the Dashboard endpoint you can add:

- **Dashboard title** — a custom heading (supports [smart tags](#smart-tags), e.g. `Welcome, {first_name}!`).
- **Stat widgets** (toggle the master **Dashboard stat widgets** + each card): **Total orders, Pending orders, Total spent, Downloads, Refunds, Reward points, Latest order,** and an **Orders pie chart** (inline SVG donut of orders by status).
- **Quick‑link tiles** — a grid of shortcuts to your endpoints.
- **Profile completeness meter**.
- **Content position** — align the dashboard column Left / Middle / Right.

---

<a name="banners"></a>
## 11. Banners

Reusable promo blocks attached to endpoints.

**Steps**
1. **My Account → Banners → Add banner** (or the button below the list).
2. Pick a **Banner type**:
   - **Widget** — icon + title + text + colors + optional item‑count badge + link.
   - **Image** — an uploaded image with an optional link.
   (The form shows only the fields relevant to the chosen type.)
3. **Banner link**: None / **Endpoint** (pick one) / **External URL** (the matching field shows only for that choice).
4. **Show banner to** — restrict by role.
5. **Create / Save banner.**
6. Attach it to an endpoint from **Menu Items** → the endpoint’s **Banner** + **Banner position** fields.

Colors support hex **and** rgba (alpha) via the round swatch → popover picker.

---

<a name="settings"></a>
## 12. Settings tab

**My Account → Settings**:

- **AJAX navigation** — endpoints load without reloading the page.
- **Default endpoint** — which endpoint opens first.
- **After‑login redirect** / **After‑logout redirect**.
- **Guest message** — shown above the login form for logged‑out visitors.
- **Track endpoint views** — count how often each endpoint is viewed.
- **Design presets** — save the current design as a named preset and re‑apply it later (your own snapshots; distinct from shipped Templates).
- **Reset all settings** — restore endpoints, design and banners to defaults (cannot be undone).

Saving shows a **toast** notification.

---

<a name="import-export"></a>
## 13. Import / Export

**My Account → Import / Export**:

- **Export** — download all endpoints, design settings and banners as a JSON file.
- **Import** — upload a JSON file (or paste JSON) to restore/clone a configuration.

Use it to move a setup between sites or keep backups.

---

<a name="smart-tags"></a>
## 14. Smart tags

Dynamic placeholders usable in custom content, the dashboard title and banners:

`{display_name}` `{first_name}` `{last_name}` `{username}` `{user_email}`
`{site_title}` `{order_count}` `{download_count}` `{last_login}`
`{points_balance}` *(Points & Rewards)* `{membership_plan}` *(Memberships)*

In the Classic editor, use the **Add smart tags** button next to Add Media.

---

<a name="embedding"></a>
## 15. Shortcode / Widget / Block

The account menu can also be rendered outside the account page — all three reuse the same styling as the main menu:

- **Shortcode** — `[acfw_account_menu]`.
- **Block** — **Account Menu** (`acfw/account-menu`) in the block inserter (editor script `assets/js/block.js`).
- **Widget** — the **Account Menu** classic widget (`ACFW_Menu_Widget`).

---

<a name="developer"></a>
## 16. Developer: build, hooks, templates

### Build pipeline
Source of truth is **SCSS/JS sources**, compiled with **Gulp** (SCSS→CSS + JS minify) and **@wordpress/scripts** (block editor bundle). Package manager: **pnpm**.

```bash
pnpm install          # install toolchain
pnpm build            # gulp (scss→css, *.min.js) + block editor bundle
npx gulp watch        # rebuild on change
pnpm zip              # build + package dist/account-customizer-for-woocommerce.zip
```

- Edit `assets/scss/*.scss` and `assets/js/*.js` (sources) — **not** the compiled `assets/css/*.css`.
- Design tokens live in `assets/scss/_variables.scss`.
- Assets are enqueued minified with a filemtime cache‑buster; define `SCRIPT_DEBUG` to load unminified sources.

### Template overrides
Copy any file from `templates/` into
`yourtheme/account-customizer-for-woocommerce/{template}.php` to override:
`myaccount-menu.php`, `myaccount-menu-item.php`, `myaccount-avatar.php`.

### Useful filters
- `acfw_prebuilt_templates` — add/modify starter templates.
- `acfw_design_option_keys` / `acfw_design_option_defaults` — extend the design option set.
- `acfw_menu_styles` — add a Menu style choice.
- `acfw_default_type_icon` — change default icons per item type.
- `acfw_smart_tags` / `acfw_apply_smart_tags` — register custom smart tags.
- `acfw_get_items`, `acfw_item_is_visible`, `acfw_item_classes`, `acfw_endpoint_content`, `acfw_is_account_page`.

---

<a name="frontend-behaviours"></a>
## 17. Front‑end behaviours (JavaScript)

Handled by `assets/js/frontend.js`:

- **Group expand/collapse** — click a group title to open; click again (or click outside, in Tabs layout) to close.
- **Menu search filter** — live‑filters items as you type (when the search box is enabled).
- **Pin favourites** — star an item to pin it to the top (stored per browser).
- **Collapsible icon rail** — toggle the menu between full and icons‑only.
- **Confirm before logout** — optional confirm dialog on the Log out item.
- **Mobile nav drawer** — on small screens the menu collapses to an “Account menu” button that opens a drawer.
- **AJAX navigation** — endpoint clicks swap the content area without a full reload.

---

## 18. Complete options reference

All options are stored as WordPress options. **Design** options are edited in the **Customizer**; **behaviour** options in the **Settings** tab; **content** in **Menu Items / Banners**.

### Design (Customizer)
| Option | Default | Notes |
|--------|---------|-------|
| `acfw_menu_position` | `vertical-left` | left / right / horizontal |
| `acfw_menu_style` | `simple` | theme, simple, classic, modern, minimal, pill, tabs |
| `acfw_accent_color` | `#2563eb` | |
| `acfw_text_color` | `#383838` | |
| `acfw_active_color` | *(empty)* | falls back to accent |
| `acfw_menu_bg` / `acfw_hover_bg` | *(empty)* | item + hover background |
| `acfw_menu_radius` | `8` | corner radius (px) |
| `acfw_menu_gap` | `4` | spacing between items (px) |
| `acfw_item_padding` | `11` | item padding (px) |
| `acfw_font_size` | `15` | px |
| `acfw_font_weight` | `500` | 400 / 500 / 600 |
| `acfw_font_family` | `inherit` | system / serif / mono / inherit |
| `acfw_color_scheme` | `light` | light / dark / auto |
| `acfw_active_indicator` | `bar` | bar / underline / dot / none |
| `acfw_hover_anim` | `none` | none / slide / grow |
| `acfw_show_icons` | `yes` | |
| `acfw_show_counts` | `yes` | endpoint count badges |
| `acfw_group_open` | `no` | expand groups by default |
| `acfw_custom_css` | *(empty)* | |
| `acfw_avatar_enable` | `no` | + `_image`, `_shape`, `_align`, `_size`, `_show_name`, `_show_role` |

### Navigation behaviour / dashboard (Customizer)
| Option | Default | Notes |
|--------|---------|-------|
| `acfw_menu_search` | `no` | search box |
| `acfw_collapsible` | `no` | icon rail toggle |
| `acfw_pin_enable` | `no` | pin favourites |
| `acfw_profile_meter` | `no` | dashboard progress bar |
| `acfw_sticky_menu` | `no` | |
| `acfw_logout_confirm` | `no` | |
| `acfw_dashboard_tiles` | `no` | quick‑link tiles |
| `acfw_dashboard_title` | *(empty)* | supports smart tags |
| `acfw_dashboard_align` | `left` | left / center / right |
| `acfw_dashboard_stats` | `no` | master toggle |
| `acfw_stat_orders` / `_pending` / `_spent` / `_downloads` | `yes` | stat cards |
| `acfw_stat_refunds` / `_points` / `_latest` / `_piechart` | `no` | stat cards |

### Behaviour (Settings tab)
| Option | Default | Notes |
|--------|---------|-------|
| `acfw_ajax_navigation` | `no` | |
| `acfw_default_endpoint` | `dashboard` | |
| `acfw_login_redirect` / `acfw_logout_redirect` | *(default)* | |
| `acfw_guest_message` | *(empty)* | above login form |
| `acfw_track_views` | `no` | counts stored in `acfw_endpoint_views` |

### Storage (not user‑facing)
`acfw_items_order` (menu tree), `acfw_item_{key}` (per‑item options), `acfw_presets` (saved presets), `acfw_active_template` (applied template), banner options, `acfw_flush_rewrite_rules`.

### Per‑item options (Menu Items)
`type` (endpoint/group/link/page), `label`, `icon` / `icon_url` / `icon_source`, `class`, `active`, `content`, `editor_type` (classic/block), `content_position` (before/after/override), `url`, `page_id`, `target_blank`, `usr_roles`, `visibility`, `vis_from`, `vis_to`, `vis_product`, `banner_slug`, `banner_position`, `children`.

### Banner options
`type` (widget/image), `title`, `content`, `image_url`, `icon` / `icon_url` / `icon_source`, `icon_width`, `widget_width`, `show_count`, `link_type` (none/endpoint/external), `link_endpoint`, `link`, `roles`, and colours: `text_color`, `text_hover`, `bg_color`, `bg_hover`, `border_color`, `border_hover`.

---

*For a shorter contributor‑oriented reference, see `CLAUDE.md`.*
