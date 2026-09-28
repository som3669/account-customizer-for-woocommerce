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

Selecting a row opens its **options** in the main area on the right. At the top, a
**What customers see** strip shows the item the way it will read in the customer's
menu (icon, label, badge, its address, and a lock when rules limit who sees it). It
updates as you type. Below it, the options are grouped in tabs: **General**,
**Content** (endpoints), **Visibility** and **Advanced** (see *Endpoints
Customization Options*).

### 4. Bottom Section

Each editor ends with a **Save changes** action. As soon as something changes, the
save bar floats at the bottom of the window with an **Unsaved changes** marker, and
leaving the page asks first. A **toast** confirms the save, and you land back on the
item you were editing. Warnings and errors stay on screen until you close them. On
the Settings tab you’ll also find **Design presets** and **Reset all settings**.

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
- **Markers** — a badge chip shows an item's badge text, a lock shows that visibility rules
  limit who sees it, and *Off in Settings* marks Buy again / Recently viewed while that
  feature is switched off.
- **Search** — filters the list; a group stays listed when one of its items matches.

### Endpoints Customization Options

Selecting an item reveals its options, grouped in tabs.

**General**

- **Label** — the text shown in the menu.
- **Endpoint URL** *(custom endpoints)* — the address under My Account, e.g.
  `/my-account/help/`. Lowercase letters, numbers and dashes. A URL already used by a
  WooCommerce endpoint, a WordPress query var or another item is refused with a
  warning, and the endpoint keeps its address. The URLs of WooCommerce's own
  endpoints are set in *WooCommerce → Settings → Advanced*.
- **URL** / **Page** *(links and pages)* — where the item points, plus **Open in new tab**.
- **Start expanded** *(groups)* — show the group open on page load. A group always
  opens on one of its own pages.
- **Badge** — short text in a pill beside the label, shown instead of the item count
  (e.g. `New`). Smart tags work, e.g. `{points_balance} pts`.
- **Icon** — **Choose icon** (searchable Font Awesome library) or **Upload icon** (image
  from the Media Library).

**Content** *(endpoints)*

- **Editor** — **Classic** (TinyMCE) or **Block** (Gutenberg) editor for custom content.
- **Custom content** — extra content for this endpoint (supports smart tags and shortcodes; block content is rendered with the block engine).
- **Placement** — *Before* / *After* the default content, or *Replace* it. A custom
  endpoint has no default content: it shows only what you give it.
- **Show banners** + **Banner position** — attach saved banners at the top or bottom, in the order picked.

**Visibility** — the tab shows how many rules are set. A customer must pass every rule;
shop managers always see every item so they can preview it.

- **User roles** — restrict the item to selected roles (empty = everyone).
- **Dates** — *From … until …*, whole days in the site timezone. Either end can stay open.
- **Bought any of** — search the catalogue; customers who bought at least one of the
  products see the item.
- **Order history** — *At least N orders* and / or *at least X spent*.

**Advanced**

- **CSS class** — extra class on the menu item.
- **Item key** — the internal key, used in filters and in the item's
  `woocommerce-MyAccount-navigation-link--{key}` class.

### How to Add a New Endpoint?

1. On **Menu Items**, click **Add endpoint** (top‑right).
2. Enter a label and click **Create**. The new endpoint is added at the end of the list
   and opens for editing. Its key and URL come from the label; a label that matches an
   existing item (e.g. "Orders") gets a numbered key instead of overwriting it, and a
   label with no Latin letters gets a short ASCII key you can rename under **Endpoint URL**.
3. Set its icon, content, visibility and banners.
4. Click **Save changes**. The endpoint's page title is its label.

### Add Group

1. Click **Add group**, enter a name, and **Create**.
2. Drag other items onto the group to nest them.
3. Groups expand/collapse on the front end (default folder icon) and can be opened from the keyboard.
   Use **Start expanded** on a group, or **Expand groups by default** in the Customizer, to keep them open.

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

Applying a template **resets the design options it controls to defaults first**, then
applies the template’s values, so the result exactly matches the preview. Your Custom
CSS, avatar settings and the item-count setting are left as they are. You can fine‑tune
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
- **Dashboard stat widgets** — a master toggle plus individual cards: **Total orders, Pending orders, Total spent, Downloads, Refunds, Reward points, Latest order,** and an **Orders pie chart** (SVG donut). Each card links to the page it sums up, when that page is in the menu. Counts are cached per customer and refreshed whenever one of their orders changes.
- **Quick‑link tiles** — a grid of shortcuts to your endpoints, including those inside groups.
- **Profile completeness meter** — a progress bar, plus the fields still missing, each linking to the form where the customer fills it in.

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
   - **Widget** — icon, title, text, colors (hex or rgba), optional count badge, link.
     The badge can show **orders**, **downloads**, **items in the cart** or **reward points**.
   - **Image** — an uploaded image with an optional link.
3. **Banner link** — **None**, **Endpoint** (choose one), or **External URL**, with your own
   **Link text** (default *Learn more*).
4. **Show banner to** — restrict by role, and **Show from / Show until** to schedule it.
5. Click **Create / Save banner**. A new banner never replaces an existing one with the same name.
6. Attach it to an endpoint via the endpoint’s **Banner** + **Banner position** options.

---

## Settings

The **Settings** tab holds behaviour options and maintenance tools.

### General Settings

- **AJAX navigation** — load endpoints without reloading the page.
- **Default endpoint** — the endpoint customers land on when they open My Account. With
  anything other than Dashboard, the plain My Account address redirects there and the
  dashboard moves to its own `/dashboard/` address (still linked from the menu).
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
- **Filters:** `acfw_prebuilt_templates`, `acfw_design_option_keys`, `acfw_design_option_defaults`, `acfw_template_preserved_keys`, `acfw_menu_styles`, `acfw_default_type_icon`, `acfw_smart_tags`, `acfw_smart_tag_values`, `acfw_item_is_visible`, `acfw_banner_is_visible`, `acfw_item_classes`, `acfw_endpoint_content`, `acfw_is_account_page`, `acfw_default_endpoint`, `acfw_reserved_item_keys`, `acfw_profile_meter_fields`.
- **Events:** after an AJAX page change the front end triggers `acfw:navigated` on `document.body`, with the new URL.

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
- `{member_since}` — Registration date
- `{total_spent}` — Total spent, as a price
- `{cart_count}` — Items in the cart
- `{billing_phone}`, `{billing_city}`, `{billing_country}` — From the billing address
- `{points_balance}` — Points balance *(WooCommerce Points & Rewards)*
- `{membership_plan}` — Membership plan *(WooCommerce Memberships)*
- `{account_url}`, `{shop_url}`, `{site_url}` — Addresses, e.g. for links in content

Only the tags a text uses are looked up, so a costly one (order or download counts)
costs nothing where it does not appear. Add your own with the `acfw_smart_tag_values`
filter.

In the Classic editor, use the **Add smart tags** button beside **Add Media**.

---

## Compatibility with Third‑Party WooCommerce Plugins

- **WooCommerce Points & Rewards** — the `{points_balance}` smart tag and the **Reward points** dashboard stat read the customer’s balance.
- **WooCommerce Memberships** — the `{membership_plan}` smart tag shows the active plan.
- **Themes** — the menu neutralises common theme interference on list rows; the **Theme style** menu style intentionally inherits the active theme’s look. Block content pulls in core block styles so blocks render correctly on the account page.
- **Block editor** — custom endpoint content can use the standalone Gutenberg editor (core blocks, media, embeds, patterns).

---

*Front‑end behaviours (group toggle, search filter, pin, collapsible rail, logout
confirm, mobile drawer, AJAX) are handled in `assets/js/frontend.js`. AJAX navigation
supports Back / Forward and leaves Ctrl / Cmd‑clicks to the browser; pinned items are
remembered per customer in that browser. For a full
option‑key reference and contributor notes, see `CLAUDE.md`.*
