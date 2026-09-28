# How to Customize the WooCommerce My Account Page

**My Account Dashboard Builder** lets you completely rebuild the WooCommerce
**My Account** page — reorder and add menu items, create custom endpoints, links,
pages and groups, design the page in the Design Studio beside a live preview, start
from ready‑made looks, add banners, and enrich the dashboard.

This guide walks through installation, the admin interface, endpoint setup, the
Design Studio, settings, and third‑party compatibility.

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

- **Tab navigation** — *Menu Items, Design, Settings, Banners* (Import / Export sits under Settings).
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
   Use **Start expanded** on a group, or **Open every group** in the Design Studio (Menu), to keep them open.

### Add Link

1. Click **Add link**, enter a label, and **Create**.
2. Set the **URL** and optionally **Open in new tab**.

### Add Page

1. Click **Add page**, enter a label, and **Create**.
2. Choose an existing WordPress **Page** from the dropdown (links to that page’s permalink). Optionally **Open in new tab**.

---

## Design Studio

Everything about how the account area looks is set on **My Account → Design**:
the controls on the left, the real My Account page on the right. The page shows your
own account, so it has real data. Nothing changes on your site until you click
**Save design**.

### Start from a look

A row of ready‑made looks sits at the top: **Classic Sidebar, Modern Cards, Rounded
Pills, Tabbed Top, Minimal, Theme Native**, followed by any looks you saved. Each card is
a miniature of the menu it gives. Click one to try it in the preview; the controls
below follow. A look sets the menu's style and colours but leaves your Custom CSS,
profile card settings and the item‑count setting alone.

**Save current as a look** stores the design you have now, under a name, as one more
card (the same looks appear under *Settings → Presets & Reset*).

### The controls

Controls are grouped by what the customer sees.

- **Look** — **Accent** (with a live check of whether current‑page text on its tint meets
  the WCAG AA contrast minimum), **Text**, **Colour scheme** (Light, Dark, Follow device),
  **Typeface**, **Weight**, **Text size**, **Roundness**, and **Density**: *Compact*,
  *Comfortable* or *Roomy*, with **Fine‑tune spacing** for item height and the space
  between items.
- **Menu** — **Style**, picked from miniature menus (Theme style, Simple, Classic,
  Modern cards, Minimal, Pills, Tabs); **Placement** (left of, right of or above the
  content); **Current page marker** (bar, underline, dot or colour only); **On hover**
  (tint only, nudge, lift); the current‑page, item and hover colours; and switches for
  icons, order and download counts, a search box, pinning favourites, collapsing to an
  icon rail, staying in view on scroll, opening every group and asking before log out.
- **Profile card** — show the card above the menu, a default picture, its shape,
  alignment and size, the name and role, and whether customers may upload their own
  picture (and how large).
- **Dashboard** — a heading (smart tags work, e.g. `Welcome back, {first_name}!`), its
  alignment, **Account numbers** (Total orders, Orders in progress, Total spent,
  Downloads, Refunds, Reward points, Latest order, Orders by status chart — each card
  links to the page it sums up), **Shortcut tiles** and **Profile completeness** (which
  lists what is missing, with a link to each form).
- **Custom CSS** — your own rules, loaded on account pages after the plugin's styles,
  with syntax highlighting when it is on in your WordPress profile.

Switches that other controls depend on reveal them only when they are on (the stat
cards under Account numbers, the picture options under the profile card).

### The preview

- **Desktop / Tablet / Phone** set the preview's width; the phone width shows the
  menu's mobile drawer.
- Colours, sizes, the style, placement and markers change the preview as you pick them.
  Switches that add or remove parts of the page (a search box, the profile card, stat
  cards, Custom CSS) reload the preview a moment later.
- Links inside My Account keep working in the preview; links elsewhere and Log out
  are switched off, so previewing never signs you out.
- The preview is only shown to you: it reads your unsaved design, and customers keep
  seeing the saved one.

### Saving

The bar at the bottom says whether there are **Unsaved changes**; it floats in view
while there are. **Save design** publishes them, **Discard changes** goes back to the
saved design, and leaving the page with unsaved changes asks first.

*Appearance → Customize* keeps a **My Account** section that links here.

### Restoring the Settings and Customization

- **Design presets** (Settings tab) — the looks you saved; apply or delete them there too.
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
- **Filters:** `acfw_design_fields` (the Design Studio's controls), `acfw_prebuilt_templates`, `acfw_design_option_keys`, `acfw_design_option_defaults`, `acfw_template_preserved_keys`, `acfw_menu_styles`, `acfw_default_type_icon`, `acfw_smart_tags`, `acfw_smart_tag_values`, `acfw_item_is_visible`, `acfw_banner_is_visible`, `acfw_item_classes`, `acfw_endpoint_content`, `acfw_is_account_page`, `acfw_default_endpoint`, `acfw_reserved_item_keys`, `acfw_profile_meter_fields`.
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
