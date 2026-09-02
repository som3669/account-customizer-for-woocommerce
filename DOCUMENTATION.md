# How to Customize the WooCommerce My Account Page

**My Account Dashboard Builder** lets you completely rebuild the WooCommerce
**My Account** page — reorder and add menu items, create custom endpoints, links,
pages and groups, style everything live in the Customizer, apply one‑click starter
templates, add banners, and enrich the dashboard.

This guide walks through installation, the admin interface, endpoint setup, the
style Customizer, settings, and third‑party compatibility.

---

## Installation

1. In WordPress admin, go to **Plugins → Add New → Upload Plugin**.
2. Upload the plugin ZIP (`my-account-dashboard-builder.zip`) and click **Install Now**.
3. Click **Activate**. WooCommerce must be installed and active.
4. A new top‑level **My Account** menu appears in the admin sidebar. Open it to start.

> Requires WordPress, WooCommerce 6.0+, and PHP 7.4+.

---

## Admin Interface Overview

The plugin admin (WP Admin → **My Account**) is organised into a header bar and a
working area. It has four regions.

### 1. Top Section

The header bar contains:

- **Tab navigation** — *Menu Items, Templates, Settings, Customizer, Banners, Import / Export*.
- **Action buttons** (top‑right), which change per tab:
  - *Menu Items:* **Add endpoint**, **Add group**, **Add link**, **Add page**.
  - *Banners:* **Add banner**.
- **Floating buttons** (pinned to the right edge, always available):
  - **Preview** — opens the live My Account page in an in‑page overlay.
  - **View My Account** — opens the live page in a new browser tab.

### 2. Left Panel

On **Menu Items** and **Banners**, the left panel is the **ordered list** of items.
Each row shows the icon, label, a type badge, an enable/disable switch, and
duplicate/delete controls. Drag the handle to reorder or to nest items into groups.

### 3. Main Content Area

Selecting a row opens its **options** in the main area on the right — labels, icon,
content, visibility, banners and more (see *Endpoints Customization Options*).

### 4. Bottom Section

Each editor ends with a **Save changes** action. A **toast** notification confirms
the save. On the Settings tab you’ll also find **Design presets** and **Reset all settings**.

---

## Endpoints Setup and Configuration

Menu items are managed on the **Menu Items** tab. There are four item types:

- **Endpoint** — a real My Account endpoint (Dashboard, Orders, …) or a custom one, with optional custom content and a banner.
- **Group** — a collapsible heading that contains child items.
- **Link** — points to any URL.
- **Page** — points to an existing WordPress Page.

### Endpoint Controls

For each item in the left list:

- **Drag handle** — reorder items, or drag an item onto a group to nest it (one level deep).
- **Enable/disable switch** — show or hide the item without deleting it.
- **Duplicate** — copy an item.
- **Delete** — remove a custom item. *Default WooCommerce items can be disabled but not deleted.*

### Endpoints Customization Options

Selecting an endpoint reveals these options:

- **Endpoint label** — the text shown in the menu.
- **Endpoint icon** — **Choose icon** (searchable FontAwesome picker) or **Upload icon** (image from the Media Library).
- **CSS class** — extra class on the menu item.
- **User roles** — restrict the item to selected roles (empty = everyone).
- **Show from / Show until** — date window during which the item appears.
- **Purchased product** — only show to customers who bought a given product ID.
- **Content editor** — **Classic** (TinyMCE) or **Block** (Gutenberg) editor for custom content.
- **Custom content** — extra content for this endpoint (supports smart tags and shortcodes; block content is rendered with the block engine).
- **Custom content position** — *Before* / *After* the default content, or *Replace* it.
- **Banner** + **Banner position** — attach a saved banner at the top or bottom.

### How to Add a New Endpoint?

1. On **Menu Items**, click **Add endpoint** (top‑right).
2. Enter a label and click **Create**. The new endpoint is added at the end of the list.
3. Select it to set its icon, content, visibility and banner.
4. Click **Save changes**.

### Add Group

1. Click **Add group**, enter a name, and **Create**.
2. Drag other items onto the group to nest them.
3. Groups expand/collapse on the front end (default folder icon). Use **Expand groups by default** in the Customizer to keep them open.

### Add Link

1. Click **Add link**, enter a label, and **Create**.
2. Set the **URL** and optionally **Open in new tab**.

### Add Page

1. Click **Add page**, enter a label, and **Create**.
2. Choose an existing WordPress **Page** from the dropdown (links to that page’s permalink). Optionally **Open in new tab**.

---

## Starter Templates

Ready‑made designs applied in one click, on the **Templates** tab.

1. Open **My Account → Templates**.
2. Each card shows a live mini‑preview of the real menu plus a short description.
3. Click **Apply template**. The applied card shows an **Applied** badge and a disabled button.

Applying a template **resets all design options to defaults first**, then applies the
template’s values, so the result exactly matches the preview. You can fine‑tune
anything afterwards in the Customizer; your tweaks persist until you apply another template.

**Included templates:** Classic Sidebar · Modern Cards · Rounded Pills · Tabbed Top · Minimal · Theme Native.

---

## My Account Page Style Customizer

All visual styling is done live in the WordPress Customizer. Open **My Account →
Customizer** (or *Appearance → Customize → My Account* panel). Changes preview
instantly. The panel has three sections: **Avatar**, **Navigation**, **Layout & Colors**.

### Layout & Design

- **Menu style** — the single control for the overall look: **Theme style, Simple, Classic, Modern cards, Minimal, Pills, Tabs**.
- **Menu position** — **Left**, **Right**, or **Top (horizontal)**.
- **Accent color**, **Text color**, **Active color**, **Menu item background**, **Hover background**.
- **Color scheme** — **Light** (default), **Dark**, or **Auto** (follows the visitor’s OS). Dark is opt‑in so it never clashes with a light theme.

### Customizing Navigation Menu

- **Show menu icons** / **Show item counts** (order/download count badges).
- **Active indicator** — **Bar**, **Underline**, **Dot**, or **None** (how the current item is marked).
- **Hover animation** — **None**, **Slide**, or **Grow**.
- **Menu search box** — a live filter above the menu.
- **Collapsible icon rail** — collapse the menu to icons only.
- **Let customers pin favorites** — a star to pin items to the top.
- **Sticky menu** — keeps the menu in view on scroll.
- **Confirm before logout** — confirm dialog on Log out.
- **Expand groups by default**.
- **AJAX navigation** — load endpoints without a full page reload.

### Customizing Profile Settings

The **Avatar** section adds a customer card above the menu.

- **Show avatar** — enable the block. Its sub‑options appear when enabled:
  - **Custom avatar image** (overrides the gravatar), **Shape** (circle/square), **Alignment**, **Size**, **Show display name**, **Show user role**.

### Spacing

Fine‑tune metrics under **Layout & Colors**:

- **Corner radius** — item rounding.
- **Item spacing** — gap between items.
- **Item padding** — inner padding.
- **Font size**, **Font weight**, **Font family**.

### Customizing Additional CSS

- **Custom CSS** — a code field in **Layout & Colors** for your own rules, injected on the account page.

### Dashboard Widgets

On the Dashboard endpoint you can add (from the Navigation section):

- **Dashboard title** — a custom heading (supports smart tags, e.g. `Welcome, {first_name}!`).
- **Dashboard content position** — Left / Middle / Right.
- **Dashboard stat widgets** — a master toggle plus individual cards: **Total orders, Pending orders, Total spent, Downloads, Refunds, Reward points, Latest order,** and an **Orders pie chart** (SVG donut).
- **Quick‑link tiles** — a grid of shortcuts to your endpoints.
- **Profile completeness meter** — a progress bar.

### Preview Controls

- **Preview** (floating button) — an in‑page overlay of the live page.
- **View My Account** (floating button) — opens the live page in a new tab.
- The Customizer itself previews all design changes live before you publish.

### Restoring the Settings and Customization

- **Design presets** (Settings tab) — save the current design as a named preset and re‑apply it anytime.
- **Reset all settings** (Settings tab) — restore endpoints, design and banners to defaults. *This cannot be undone.*

---

## Banners

Reusable promotional blocks attached to endpoints, on the **Banners** tab.

1. Click **Add banner** (header, or below the list).
2. Choose a **Banner type** — the form shows only the relevant fields:
   - **Widget** — icon, title, text, colors (hex or rgba), optional item‑count badge, link.
   - **Image** — an uploaded image with an optional link.
3. **Banner link** — **None**, **Endpoint** (choose one), or **External URL**.
4. **Show banner to** — restrict by role.
5. Click **Create / Save banner**.
6. Attach it to an endpoint via the endpoint’s **Banner** + **Banner position** options.

---

## Settings

The **Settings** tab holds behaviour options and maintenance tools.

### General Settings

- **AJAX navigation** — load endpoints without reloading the page.
- **Default endpoint** — which endpoint opens first.
- **After‑login redirect** / **After‑logout redirect**.
- **Guest message** — shown above the login form for logged‑out visitors.
- **Track endpoint views** — count how often each endpoint is viewed.

### Import / Export

On the **Import / Export** tab:

- **Export** — download all endpoints, design settings and banners as a JSON file.
- **Import** — upload (or paste) a JSON file to restore or clone a configuration.

### Developer Options

Source styles/scripts are compiled with **Gulp** (SCSS→CSS + JS minify) and
**@wordpress/scripts** (block editor bundle); package manager is **pnpm**.

```bash
pnpm install          # install toolchain
pnpm build            # compile SCSS→CSS, minify JS, build block editor bundle
npx gulp watch        # rebuild on change
pnpm zip              # build + package the distributable ZIP
```

- Edit `assets/scss/*.scss` and `assets/js/*.js` sources — not the compiled `assets/css/*.css`.
- Assets enqueue minified with a filemtime cache‑buster; define `SCRIPT_DEBUG` for unminified.
- **Template overrides:** copy files from `templates/` into `yourtheme/my-account-dashboard-builder/`.
- **Filters:** `acfw_prebuilt_templates`, `acfw_design_option_keys`, `acfw_design_option_defaults`, `acfw_menu_styles`, `acfw_default_type_icon`, `acfw_smart_tags`, `acfw_item_is_visible`, `acfw_item_classes`, `acfw_endpoint_content`, `acfw_is_account_page`.

**Embedding the menu elsewhere:**

- Shortcode — `[acfw_account_menu]`
- Block — **Account Menu** (`acfw/account-menu`)
- Widget — **Account Menu** classic widget

### Save Changes

- **Menu Items** — one **Save changes** button saves the entire list + order.
- **Settings** — **Save changes** stores the general options.
- **Banners** — **Create / Save banner** per banner.
- All saves confirm with a toast notification.

---

## Smart Tags

Dynamic placeholders usable in custom content, the dashboard title and banners:

- `{display_name}` — Display name
- `{first_name}` — First name
- `{last_name}` — Last name
- `{username}` — Username
- `{user_email}` — Email address
- `{site_title}` — Site title
- `{order_count}` — Order count
- `{download_count}` — Download count
- `{last_login}` — Last login date
- `{points_balance}` — Points balance *(WooCommerce Points & Rewards)*
- `{membership_plan}` — Membership plan *(WooCommerce Memberships)*

In the Classic editor, use the **Add smart tags** button beside **Add Media**.

---

## Compatibility with Third‑Party WooCommerce Plugins

- **WooCommerce Points & Rewards** — the `{points_balance}` smart tag and the **Reward points** dashboard stat read the customer’s balance.
- **WooCommerce Memberships** — the `{membership_plan}` smart tag shows the active plan.
- **Themes** — the menu neutralises common theme interference on list rows; the **Theme style** menu style intentionally inherits the active theme’s look. Block content pulls in core block styles so blocks render correctly on the account page.
- **Block editor** — custom endpoint content can use the standalone Gutenberg editor (core blocks, media, embeds, patterns).

---

*Front‑end behaviours (group toggle, search filter, pin, collapsible rail, logout
confirm, mobile drawer, AJAX) are handled in `assets/js/frontend.js`. For a full
option‑key reference and contributor notes, see `CLAUDE.md`.*
