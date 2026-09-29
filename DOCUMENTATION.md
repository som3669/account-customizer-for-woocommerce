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

- **Tab navigation** — *Menu Items, Design, Banners, Fields, Returns, Insights, Settings* (Import / Export and Orders & privacy sit under Settings).
- **How to use** (top‑right, on every tab) — a guide panel with every feature in a few
  steps and a button to the screen where it is done. It opens on the section for the tab
  you are on; search it by word (e.g. *coupon*), and close it with Escape or ×.
- **Action buttons** (top‑right), which change per tab:
  - *Banners:* **Add banner**.
  - *Menu Items* has none: items are added with **Add to menu**, under the menu (see below).
- **⋯ More actions**:
  - **Preview** — opens the live My Account page in an overlay, as you or, with **View as**, as a customer.
  - **View My Account** — opens the live page in a new browser tab.

### 2. Left Panel

On **Menu Items**, the left panel is the **menu canvas**: your account menu drawn the
way customers see it, in the style, colours and spacing saved in the Design Studio.

- Click an item to edit it. It is marked the way the storefront marks the page a
  customer is on.
- Drag an item to reorder it or to drop it into a group. From the keyboard,
  **Alt + ↑ / ↓** moves the focused item and **Alt + → / ←** moves it into the group
  above it or back out.
- Every item has its **on/off** switch on the right. Hover an item (or select it) for
  **Duplicate** and **Delete**.
- **Add to menu**, under the menu, opens a small dialog: pick the type, type a label,
  **Add item**. The item goes at the end of the menu; drag it where it belongs, or into
  a group.
- Switched-off items stay on the canvas, dimmed and marked with an eye.
- When rules limit who sees an item, a line under it says so, e.g. *Customer · 2+ orders*.
- A menu shown across the top of the page (or in the Tabs style) is drawn as a list
  here, so groups stay easy to edit.

On **Banners**, the left panel lists your banners; select one to edit it.

### 3. Main Content Area

Selecting an item opens its **options** on the right. The head shows its name and
type, an on/off switch, **Duplicate** and **Delete** (built-in items can be switched
off but not deleted), and the address it opens. The options are grouped in tabs:
**General**, **Content** (endpoints), **Visibility** and **Advanced** (see *Endpoints
Customization Options*). The canvas follows as you type: the label, badge, icon and
visibility line change at once.

### 4. Bottom Section

Each editor ends with a save action (**Save menu** on Menu Items). As soon as something changes, the
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

For each item on the canvas:

- **Drag** — reorder items, or drop an item into a group to nest it (one level deep).
  **Alt + arrow keys** do the same from the keyboard.
- **Enable/disable switch** — show or hide the item without deleting it (on the item, and in its options).
- **Duplicate** — copy an item (on hover, or in its options).
- **Delete** — remove a custom item (on hover, or in its options). *Default WooCommerce items can be disabled but not deleted.*
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
shop managers skip the rules so they can preview the item, except the dates: outside
them the item is hidden for everyone.

- **User roles** — restrict the item to selected roles (empty = everyone).
- **Dates** — *From … until …*, whole days in the site timezone. Either end can stay open.
- **Bought any of** — search the catalogue; customers who bought at least one of the
  products see the item.
- **Order history** — *At least N orders*, *at most N orders* (0 = customers with no
  orders yet; visitors who are not logged in pass), *at least X spent*, and *last order
  more than N days ago* (customers who have not ordered for a while).

**Advanced**

- **CSS class** — extra class on the menu item.
- **Item key** — the internal key, used in filters and in the item's
  `woocommerce-MyAccount-navigation-link--{key}` class.

### How to Add a New Endpoint?

1. On **Menu Items**, click **Add to menu** under the menu.
2. Pick **Endpoint**, enter a label and click **Add item** (or press Enter). The endpoint
   is added at the end of the menu and opens for editing (drag it where it belongs);
   anything else you changed is saved with it. Its key and URL come from the label; a label that matches an
   existing item (e.g. "Orders") gets a numbered key instead of overwriting it, and a
   label with no Latin letters gets a short ASCII key you can rename under **Endpoint URL**.
3. Set its icon, content, visibility and banners.
4. Click **Save menu**. The endpoint's page title is its label.

### Add Group

1. Click **Add to menu**, pick **Group**, enter a name and click **Add item**.
2. Drag items into the group.
3. Groups expand/collapse on the front end (default folder icon) and can be opened from the keyboard.
   Use **Start expanded** on a group, or **Open every group** in the Design Studio (Menu), to keep them open.

### Add Link

1. Click **Add to menu**, pick **Link**, enter a label and click **Add item**.
2. Set the **URL** and optionally **Open in new tab**.

### Add Page

1. Click **Add to menu**, pick **Page**, enter a label and click **Add item**.
2. Choose an existing WordPress **Page** from the dropdown (links to that page’s permalink). Optionally **Open in new tab**.

### Menus for Customer Groups

Give a group of customers, such as wholesale buyers or members, a menu of its own.

1. On **Menu Items**, click **Menu for a customer group** in the bar above the menu.
2. Name the menu and tick the roles it is for, then click **Create menu**. It starts as a
   copy of the main menu.
3. The **Menu for** list switches between the main menu (*Everyone*) and each group menu.
   In a group menu:
   - drag items to reorder it, and **Save menu**;
   - the row's **Take out of this menu** button removes an item from this menu only. It
     stays in the main menu and moves to the **Not in this menu** list under the menu,
     where **Add to this menu** puts it back;
   - **Add to menu** and **Duplicate** add the new item to this menu *and* to the main
     menu, limited there to this group's roles (a notice says so; change it under the
     item's **Visibility**).
4. **Name and roles** renames the menu or changes its roles; **Delete this menu** gives its
   customers the main menu again.

A customer gets the first group menu one of their roles matches, and the main menu
otherwise. Item settings (label, icon, content, rules) are shared: set an item up once
and it looks the same in every menu it is in. New pages that other plugins add to My
Account show in every menu until you take them out.

### Add the Account Menu to a Site Menu

On classic themes, **Appearance → Menus** has a **My Account** box with two links that
change with the visitor:

- **Log in / My account** — *Log in* for visitors, *My account* for customers.
- **My account, with its pages** — the account link with every page of the customer's
  account menu below it, as a dropdown (what they see follows the menu's rules and
  group menus).

WooCommerce's own **WooCommerce endpoints** box lists your custom endpoints too. Block
themes build site menus with the Navigation block; use WooCommerce's **Customer account**
block there.

---

## Design Studio

Everything about how the account area looks is set on **My Account → Design**:
the controls on the left, the real My Account page on the right. The page shows your
own account, so it has real data. Nothing changes on your site until you click
**Save design**. Hover the ⓘ beside a control to see what it changes.

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
  icons, order and download counts, **Status badges**, a search box, pinning favourites,
  collapsing to an icon rail, staying in view on scroll, opening every group and asking
  before log out.
- **Profile card** — show the card above the menu, a default picture, its shape,
  alignment and size, the name and role, and whether customers may upload their own
  picture (and how large).
- **Dashboard** — a heading (smart tags work, e.g. `Welcome back, {first_name}!`), its
  alignment, and **Arrange the dashboard** (see below), where **Account numbers**,
  **Profile completeness** and **Shortcut tiles** are switched on. Under it, the cards
  Account numbers show: Total orders, Orders in progress, Total spent, Downloads,
  Refunds, Reward points, Latest order and the Orders by status chart. Each card links
  to the page it sums up; the profile meter lists what is missing, with a link to each
  form.
- **Custom CSS** — your own rules, loaded on account pages after the plugin's styles,
  with syntax highlighting when it is on in your WordPress profile.

Switches that other controls depend on reveal them only when they are on (the stat
cards under Account numbers, the picture options under the profile card).

### Arrange the dashboard

The **Dashboard** group lists every part of the dashboard in the order customers see it:
the **Notice** (written under *Settings → General*), **WooCommerce greeting** (the
"Hello … From your account dashboard" text), **Heading**, **Account numbers**, **Profile
completeness**, **Shortcut tiles**, **Order tracking** and **Buy again** (switched on under
*Settings → General*), and **Other plugins** (what other plugins add to the dashboard).

- Drag a part by its handle, or use its arrows, to move it. The preview follows at once.
- Its switch shows or hides it on the dashboard. Hiding Buy again here keeps its own
  page in the menu; hiding the WooCommerce greeting removes that text.
- A part with nothing to show (no notice written, no heading) takes no room.

The dashboard's banners keep their own place, set on the Dashboard menu item (top or
bottom). When the Dashboard item's content *replaces* WooCommerce's, the arrangement is
not used.

### Status badges

With **Status badges** on (*Menu*, on by default), menu items say where the customer has
something to do, in place of the plain count:

- **Orders** — *1 to pay* while an order waits for payment (pending or failed);
- **Returns** — *1 open* while a return request is open (requested, approved or received);
- **Account details** and **Addresses** — an amber dot while something the profile
  meter checks is missing there (a name, a phone number, a billing address). Screen
  readers hear what is missing.

A badge you type on an item (*General → Badge*) still wins. Shortcut tiles show the same
badges. Add your own with the `acfw_status_badges` filter.

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

### View as a customer

Pick a customer in **View as** (in the preview's toolbar here, and in **⋯ → Preview** on
every tab) to see My Account exactly as they do: their menu or group menu, their rules,
badges, offers and dashboard. Search by name, email or username; **Yourself** goes back
to your own account.

A bar at the bottom says who you are viewing as and which menu they get, and **N hidden
from them** lists every menu item and banner on the page they do not see, with why:

- *Needs 3+ orders (has 1)*, *Needs £500.00 spent (has £189.00)*;
- *Only for Wholesale (this customer is Customer)*;
- *Only for customers with no orders yet (has 2)*;
- *Only when the last order is over 90 days old (the last was 3 days ago)*;
- *Shows from 1 October 2026*, *Switched off*, *Its feature is switched off in Settings*.

The preview only looks. Links inside My Account work; buttons and forms (Log out, Pay,
Cancel, Order again, Buy again, picture upload, every form) are switched off, on the
page and on the server. It never signs you in as them: the link is signed for you,
works on My Account pages only and stops working after two hours. Nothing is written
for the customer: no coupon is made for a personal offer (the banner shows their code
if they have one, or a placeholder), nothing is counted in Insights, and WooCommerce's
"last active" time, their cart and session are left alone (WooCommerce may refresh its
own cached total of what they spent). Staff accounts (anyone who can edit posts or
manage the shop) cannot be viewed as. The cart in the site header, which the theme
loads separately, is still yours.

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

Reusable promotional blocks attached to endpoints, on the **Banners** tab. A banner's
settings are split into tabs like a menu item's: **General** (name, type, icon, text or
image), **Style** (width, colours, count badge; widget banners only), **Link**, **Offer**
(widget banners only) and **Visibility**.

1. Click **Add banner** (header, or below the list).
2. Choose a **Banner type** — the form shows only the relevant fields:
   - **Widget** — icon, title, text, colors (hex or rgba), optional count badge, link.
     The badge can show **orders**, **downloads**, **items in the cart** or **reward points**.
   - **Image** — an uploaded image with an optional link.
3. **Banner link** — **None**, **Endpoint** (choose one), or **External URL**, with your own
   **Link text** (default *Learn more*).
4. **Visibility** — the same rules as menu items: roles, **Show from / Show until**,
   products bought, and order history (at least / at most N orders, amount spent, last
   order more than N days ago).
5. Click **Create / Save banner**. A new banner never replaces an existing one with the same name.
6. Attach it to an endpoint via the endpoint’s **Banner** + **Banner position** options.

### Personal Offers

Switch on **Personal coupon** on a banner's **Offer** tab and each customer who sees the
banner gets a coupon code of their own:

- **Discount** — *Percent off* or *Amount off*, and how much.
- **Valid for** — days from when the customer first sees it (0 = no end). The code works
  until the end of the last day, in the site's timezone.
- **Minimum spend**, **Free shipping too** (needs a free shipping method that accepts a
  coupon) and **Code starts with** (e.g. `THANKS` gives `THANKS-7KQ2MX`).

Each code is for one use, by that customer's email address only. The banner shows it with
**Copy** and **Use it now** (which applies it to the cart), and goes away once the code is
used or has run out. Put `{offer_code}`, `{offer_amount}` or `{offer_expiry}` in the
banner's title or text to mention them. Aim offers with **Visibility**: *at most 1 order*
for a second-order offer, or *last order more than 90 days ago* to win customers back.

Coupons must be switched on in *WooCommerce → Settings → General*. Shop managers see a
`PREFIX-PREVIEW` code instead of collecting coupons. The coupons are ordinary WooCommerce
coupons, listed under *Marketing → Coupons*.

---

## Customer Fields

The **Fields** tab adds your own questions to the customer's account: a VAT number, a
company, a birthday, "How did you hear about us?".

- **Add a field**, give it a **Label** (the **Key** follows it until you change the key),
  and pick a **Type**: text, long text, email, phone, number, date, dropdown, choices
  (radio) or tick box. Dropdowns and choices take **Options, one per line**.
- **Required**, **Placeholder** and **Help text** work as on WooCommerce's own fields.
- **Show it on** — the **Registration form**, **Account details**, and **The customer's
  orders (admin)**, where the answers show under the billing address on the order screen.
- Drag cards to reorder them; **Save fields**.

Answers are kept on the customer. They show and can be edited on the user's profile in
*Users*, are included when the customer asks for a copy of their data (and removed when
they ask for it to be erased), and work as smart tags: `{field_vat}` for the key `vat`.

---

## Returns and Cancelling Orders

Both are switched on under *Settings → Orders & privacy*.

**Cancel orders** — customers can cancel an order that has not shipped yet (pending, on
hold or processing) for a number of hours after ordering (0 = until it ships). An order
counts as shipped once it is completed or has tracking from WooCommerce Shipment
Tracking. Cancelling asks first and puts the stock back; a paid order gets a note that it
still needs refunding from its screen.

**Returns** — a **Returns** page in My Account, and **Return items** on completed orders
for a number of days after completion. The customer picks the items and quantities, a
reason from your list, and can add a comment and a photo (JPG, PNG or WebP up to 5 MB).
You are emailed, and the order gets a note.

On the **Returns** tab, each request shows the order, customer, items, reason, comment
and photo. Set its status (*Requested*, *Approved*, *Not accepted*, *Received*,
*Refunded*), optionally with a message, and **Update and email the customer**. Requests
also show in a **Returns** box on the order screen. Items in an open or approved request
cannot be asked for twice; a request that was not accepted frees them again. Refunds
themselves are made from the order screen as usual.

---

## Insights

The **Insights** tab shows how customers use their account area over the last 7, 30 or 90
days:

- **Page views**, **banner clicks**, **offers used** and **sales from offers**;
- page views per day;
- each account page's views, with pages nobody opened marked *Not opened*;
- each banner's views, clicks and click rate;
- each personal offer's codes given, codes used and the sales they brought.

Switch on **Record usage** under *Settings → General*. Only logged-in customers are
counted (not shop managers), and only totals per day are kept, nothing personal.

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
- **Record usage** — count account page views, banner views and clicks, and personal
  offers used, for the **Insights** tab.

### Orders & Privacy

What customers can do for themselves:

- **Cancel orders** and **Returns** — see *Returns and Cancelling Orders*. The return
  reasons customers choose from are edited here, one per line.
- **Address book** — customers keep more addresses in a **More addresses** section of the
  Addresses page, and make any of them their shipping or billing address in a click.
  The classic checkout lists them too (the Checkout block does not).
- **Privacy page** — a **Privacy** page where customers ask for a copy of their data or for
  it to be erased. WordPress emails them to confirm; you finish the request under
  *Tools → Export / Erase Personal Data*.

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
- **Filters:** `acfw_status_badges` (the menu's status badges), `acfw_dashboard_blocks` (the dashboard's parts; print a new one on `acfw_dashboard_part_{key}`), `acfw_endpoint_items` (endpoints to register), `acfw_disabled_keys` (items hidden because their feature is off), `acfw_default_icons` (icons for known account pages), `acfw_smart_tag_value` (one smart tag's value), `acfw_design_fields` (the Design Studio's controls), `acfw_prebuilt_templates`, `acfw_design_option_keys`, `acfw_design_option_defaults`, `acfw_template_preserved_keys`, `acfw_menu_styles`, `acfw_default_type_icon`, `acfw_smart_tags`, `acfw_smart_tag_values`, `acfw_item_is_visible`, `acfw_banner_is_visible`, `acfw_item_classes`, `acfw_endpoint_content`, `acfw_is_account_page`, `acfw_default_endpoint`, `acfw_reserved_item_keys`, `acfw_profile_meter_fields`.
- **Events:** after an AJAX page change the front end triggers `acfw:navigated` on `document.body`, with the new URL.

**Embedding the menu elsewhere:**

- Shortcode — `[acfw_account_menu]`
- Block — **Account Menu** (`acfw/account-menu`)
- Widget — **Account Menu** classic widget

### Save Changes

- **Menu Items** — one **Save menu** button saves every item and the order.
- **Settings** — **Save changes** stores the options of the section you are on.
- **Banners** — **Create / Save banner** per banner.
- **Fields** — **Save fields**.
- **Returns** — **Update and email the customer** per request.
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
- `{field_…}` — A customer field's answer, e.g. `{field_vat}`
- `{offer_code}`, `{offer_amount}`, `{offer_expiry}` — A banner's personal offer *(in that banner only)*

Only the tags a text uses are looked up, so a costly one (order or download counts)
costs nothing where it does not appear. Add your own with the `acfw_smart_tag_values`
filter.

A badge built only on tags that come back empty is hidden rather than showing a stray
word: `{points_balance} pts` on a store without a points plugin shows no badge.

In the Classic editor, use the **Add smart tags** button beside **Add Media**.

---

## Compatibility with Third‑Party WooCommerce Plugins

- **Account pages from other plugins** — pages that WooCommerce Subscriptions, Memberships, Bookings, points, wallet, ticket and affiliate plugins add to My Account appear in the menu on their own, with a fitting icon. Arrange, rename and restrict them like any other item.
- **Wishlists** — with YITH WooCommerce Wishlist or TI WooCommerce Wishlist active, a **Wishlist** page is added inside My Account.
- **WooCommerce Points & Rewards**, **YITH Points and Rewards** and **myCred** — the `{points_balance}` smart tag and the **Reward points** dashboard stat read the customer’s balance.
- **WooCommerce Memberships** — the `{membership_plan}` smart tag shows the active plan.
- **Themes** — the menu neutralises common theme interference on list rows; the **Theme style** menu style intentionally inherits the active theme’s look. Block content pulls in core block styles so blocks render correctly on the account page.
- **Block editor** — custom endpoint content can use the standalone Gutenberg editor (core blocks, media, embeds, patterns).

---

*Front‑end behaviours (group toggle, search filter, pin, collapsible rail, logout
confirm, mobile drawer, AJAX) are handled in `assets/js/frontend.js`. AJAX navigation
supports Back / Forward and leaves Ctrl / Cmd‑clicks to the browser; pinned items are
remembered per customer in that browser. For a full
option‑key reference and contributor notes, see `CLAUDE.md`.*
