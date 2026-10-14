# WooCommerce.com product page — paste-ready copy

Use on **Products → My Account… → Edit**. Submit for Review when image + gallery + description are filled.

Customer-facing copy also lives in `DOCUMENTATION.md` / `DOCUMENTATION.docx` (**Product overview** + **Frequently Asked Questions**).

---

## Title

```
My Account Dashboard Builder
```

(Change from “My Account Customizer” — that name collides with a WordPress.org plugin.)

Permalink stays: `https://woocommerce.com/products/my-account-dashboard-builder/`

---

## Product short description (≤140 chars)

```
Branded My Account dashboard: drag-and-drop menu, Design Studio, Buy again, returns, personal offers and self-service tools.
```

---

## Product description

Paste into the visual editor (or switch to Text and paste the HTML below).

```html
<p>The default WooCommerce <strong>My Account</strong> page is a plain list of links. <strong>My Account Dashboard Builder</strong> turns it into a branded customer dashboard — without editing theme templates or writing code.</p>

<h3>Build the menu customers actually use</h3>
<ul>
<li>Drag-and-drop canvas drawn the way customers see it: endpoints, pages, links and collapsible groups.</li>
<li>Icons (Font Awesome, Dashicons or upload), badges, and visibility by role, dates, products bought, order count, total spent or time since last order.</li>
<li>Separate menus for customer groups (wholesale, members, …) while item settings stay shared.</li>
<li>Live status badges — e.g. “1 to pay” on Orders, “1 open” on Returns.</li>
</ul>

<h3>Design Studio with a live preview</h3>
<ul>
<li>Edit colours, menu layout, density and typography beside the real account page at desktop, tablet and phone widths.</li>
<li>Ready-made looks to start from; save your own looks to reuse later.</li>
<li>Nothing goes live until you click Save.</li>
</ul>

<h3>See it as your customers do</h3>
<p><strong>View as a customer</strong> shows their menu, badges, offers and dashboard — plus a list of what is hidden from them and why. Read-only by design: nothing is changed or counted for them.</p>

<h3>Content, banners and personal offers</h3>
<ul>
<li>Classic or Gutenberg content on any endpoint (before, after or instead of the default output).</li>
<li>Promotional banners with schedules and the same visibility rules as menu items.</li>
<li>Personal offer coupons — one-use codes limited to each customer’s email, with Copy and “Use it now”.</li>
<li>Smart tags such as <code>{first_name}</code>, <code>{order_count}</code>, <code>{total_spent}</code> and your own customer fields.</li>
</ul>

<h3>Self-service that cuts support load</h3>
<ul>
<li>Avatar upload, Buy again, recently viewed products and order tracking.</li>
<li>Cancel unshipped orders, return requests with a photo, an address book and a privacy request page.</li>
<li>Custom registration and account fields (VAT, company, birthday, …).</li>
</ul>

<h3>Know what gets used</h3>
<p><strong>Insights</strong> shows account page views, banner click rates and how personal offers convert — daily totals only, no personal tracking of shop managers.</p>

<h3>Fits your stack</h3>
<ul>
<li>HPOS and cart/checkout blocks compatible.</li>
<li>WPML / Polylang ready; works alongside Subscriptions, Memberships, Bookings, wishlists and points plugins.</li>
<li>No external services, no telemetry, no remotely loaded fonts.</li>
</ul>

<p>Open <strong>WooCommerce → Account Dashboard</strong> after activate. Default endpoints are pre-loaded so My Account keeps working before you change anything. Use <strong>How to use</strong> in the header for a short guide to every feature.</p>
```

### Images inside the description

The whole description, laid out like the Marketplace page with every image in place, is in `PRODUCT-DESCRIPTION.docx` ( rebuild with `python submission/product-description.py` ).

Other My Account plugins on WooCommerce.com put a screenshot or GIF under each heading of the description. Upload these from `submission/docs-media/` (captions in its `README.md`) and insert each one under its heading, after the list:

| Heading | Image |
| --- | --- |
| (top, after the first paragraph) | `01-my-account-dashboard.png` |
| Build the menu customers actually use | `05-drag-menu-items.gif`, then `08-item-visibility.png` |
| Design Studio with a live preview | `14-design-looks.gif`, then `18-arrange-dashboard.gif` |
| See it as your customers do | `21-view-as-customer.gif`, then `22-view-as-hidden.png` |
| Content, banners and personal offers | `26-front-offer-banner.png`, then `24-banner-offer.png` |
| Self-service that cuts support load | `29-front-order-actions.png`, `30-front-return-request.png`, `41-address-book.png` |
| Know what gets used | `33-insights.png` |
| Fits your stack | `43-front-phone.png` + `43b-front-phone-menu.png` |

GIFs: upload them as they are (the Media Library keeps the animation; the largest is under 400 KB).

---

## Product FAQs

Click **Add FAQ** for each extra item. Paste question + answer as written.

**1 — Do I need to edit theme templates?**
```
No. The plugin replaces the WooCommerce account menu and endpoint output through the hooks WooCommerce already provides. Nothing is copied into your theme.
```

**2 — Can I see My Account the way a particular customer sees it?**
```
Yes. Use View as / Preview, search for the customer, and you see their menu, badges, offers and dashboard — plus what is hidden from them and why. It is read-only: nothing is changed or sent for them, and staff accounts cannot be viewed as.
```

**3 — What happens if I deactivate the plugin?**
```
My Account falls back to the standard WooCommerce menu. Your configuration is kept, so reactivating restores everything. Uninstall from the Plugins screen removes only this plugin’s data.
```

**4 — Where do I change colours and layout?**
```
In WooCommerce → Account Dashboard → Design (the Design Studio). You edit beside a live preview of the account page at desktop, tablet and phone widths. Nothing goes live until you save. Menu structure and content live under Menu Items.
```

**5 — Can customers upload their own profile picture?**
```
Yes, once you enable it in Design → Profile card. Uploads are limited to common image types, checked against a size limit you set, and verified as real images. The picture then replaces the Gravatar site-wide.
```

**6 — Can different customers get different menus?**
```
Yes. On Menu Items, add a menu for a customer group (for example wholesale or members). It starts as a copy of the main menu; remove or reorder items as needed. Item settings stay shared, and everyone else keeps the main menu.
```

**7 — Can customers cancel or return orders themselves?**
```
Yes, when enabled under Settings → Orders & privacy. Customers can cancel orders that have not shipped (within the hours you allow) and request returns on completed orders with a reason and optional photo. You answer each return on the Returns tab; the customer is emailed.
```

**8 — Are personal offer codes ordinary WooCommerce coupons?**
```
Yes. They appear under Marketing → Coupons, limited to one use and to the customer’s email. Coupons must be enabled in WooCommerce → Settings → General.
```

**9 — Does Buy again let customers reorder anything?**
```
No. It only offers products that customer purchased on a completed order, and it re-checks purchase history, stock and your store’s normal add-to-cart validation on every request.
```

**10 — Is it compatible with HPOS and the block cart/checkout?**
```
Yes. Compatibility with High-Performance Order Storage and the block-based cart and checkout is declared. Orders are read through the WooCommerce CRUD API only.
```

**11 — Is it translation-ready? Does it work with WPML or Polylang?**
```
Yes. Strings are translation-ready, and admin-entered labels, banner titles and content are registered for WPML and Polylang.
```

**12 — Can I rearrange what appears on the dashboard?**
```
Yes. In Design → Dashboard → Arrange the dashboard, drag the greeting, heading, account numbers, profile meter, shortcut tiles, order tracking, Buy again and other widgets into any order, or hide any of them. The preview updates as you drag.
```

**13 — Does it work with Subscriptions, Memberships or wishlists?**
```
Account pages from common extensions (Subscriptions, Memberships, Bookings, points, wallets, tickets) appear in the menu with fitting icons. YITH and TI WooCommerce Wishlist can add a Wishlist page inside My Account.
```

**14 — Can I put the account menu somewhere else on the site?**
```
Yes. Use the [acfw_account_menu] shortcode, the Account Menu block, or the Account Menu widget. Classic themes also get My Account items under Appearance → Menus.
```

**15 — What data does it store?**
```
Only this plugin’s options, related user meta (avatar, addresses, custom fields, offer codes), return requests, and daily Insights totals. Tools never touch other plugins that share an acfw_ prefix. Uninstall removes this plugin’s data; personal-offer coupons are normal WooCommerce coupons and are left in place.
```

**16 — Do I need a developer to set this up?**
```
No. Activate the plugin, open WooCommerce → Account Dashboard, and use the Menu Items canvas and Design Studio. A How to use guide in the header walks through each feature in a few steps. Default WooCommerce endpoints are already loaded so My Account keeps working before you change anything.
```

**17 — Will it work with my theme?**
```
Yes for typical WooCommerce themes (including Storefront and most block themes). The plugin uses WooCommerce’s own My Account hooks rather than copying templates into your theme. Very custom account templates may need a quick check after install.
```

**18 — Can I hide or rename the default WooCommerce account pages?**
```
Yes. On the Menu Items canvas you can rename any item, switch it off, reorder it, or nest it in a group. Switched-off items stay in the builder (dimmed) so you can turn them back on later.
```

**19 — Can I add my own account pages with custom URLs?**
```
Yes. Add an endpoint, set its label and URL slug, and fill it with Classic or block editor content. The slug is checked against WooCommerce endpoints and other menu items so you do not accidentally break the rest of the store.
```

**20 — Can I control who sees each menu item?**
```
Yes. Per item (and per banner) you can limit by user role, date range, products purchased, minimum or maximum orders, total spent, or how long since the last order. The canvas shows a short summary of who can see each item.
```

**21 — Can shop managers use the settings, or only administrators?**
```
Shop managers can open Account Dashboard and save Settings. Capabilities match WooCommerce’s manage_woocommerce access, not only full administrator manage_options.
```

**22 — Can I move my setup from staging to the live site?**
```
Yes. Use Tools → Export to download a JSON file of the configuration, then Import on the other site. There is also a Reset tool that only clears this plugin’s own options.
```

**23 — Does it slow down the storefront?**
```
Admin-heavy screens (Design Studio, Menu Items) load their assets only on those pages. On the front end the plugin uses WooCommerce’s normal My Account flow, ships fonts locally, and makes no external service or telemetry calls.
```

**24 — What if I already use another “customize My Account” plugin?**
```
Use only one My Account menu builder at a time. Two plugins rewriting the same menu will conflict. Deactivate the other customizer before you rely on this one for the live menu.
```

**25 — Does it work on mobile?**
```
Yes. The Design Studio previews phone and tablet widths, and the front-end menu supports horizontal, vertical and tab layouts with a mobile nav pattern. Test once with your theme’s header after you pick a look.
```

**26 — Can I change where customers land after login or logout?**
```
Yes. In Settings you can set the default My Account endpoint customers open first, plus login and logout redirects.
```

**27 — Is there a guide inside the plugin?**
```
Yes. Click How to use in the Account Dashboard header. It opens on the section for the tab you are on, lists each feature in short steps, and links to the screen where you configure it. You can search the guide too.
```

**28 — Who is this for?**
```
Stores that want a branded My Account without hiring a developer: clearer menus, self-service (returns, cancel, addresses), repeat-purchase tools (Buy again, personal offers), and a Design Studio to match the brand. It is a single self-contained plugin — no SaaS account and no extra paid add-ons required for the listed features.
```

---

## Product data → Product Compatibility

Only plugins the code integrates with ( checked in `includes/class-acfw-compat.php`, `functions-acfw.php`, `class-acfw-commerce.php`, `class-acfw-i18n.php` ). One per line:

```
WooCommerce Subscriptions
WooCommerce Memberships
WooCommerce Bookings
WooCommerce Points and Rewards
WooCommerce Shipment Tracking
YITH WooCommerce Wishlist
TI WooCommerce Wishlist
YITH WooCommerce Points and Rewards
myCred
TeraWallet
WPML
Polylang
```

What each integration does ( for review questions ):
- Subscriptions, Memberships, Bookings, TeraWallet: their My Account pages appear in the menu builder with a matching icon, and can be renamed, reordered, grouped or restricted.
- Memberships: the `{membership_plan}` smart tag.
- Points and Rewards, YITH Points and Rewards, myCred: the `{points_balance}` smart tag and the Reward points dashboard stat.
- Shipment Tracking: tracking numbers in Order tracking; a tracked order counts as shipped, so it can no longer be cancelled.
- YITH / TI Wishlist: a Wishlist page inside My Account.
- WPML, Polylang: menu labels, banner text and content are registered for translation.

---

## Product data → General

| Field | Value |
| --- | --- |
| Regular price ($) | `49` (or keep `44` if that tier was agreed) |
| Inconsistency Plugins | leave blank (or `woocommerce` if required) |
| Dependency Themes | leave blank |

**Search Keywords** (if that tab has a field)
```
my account, account dashboard, customize my account, endpoints, design studio, buy again, returns
```

**Product Version**
```
1.0.2
```
(Use **1.0.3** when you Add version with the badge/photo fixes.)

---

## Media (required before Submit for Review)

Ready-to-upload files: `submission/media/` (see that folder’s `README.md`).

Per [Woo product page content and assets](https://developer.woocommerce.com/docs/woo-marketplace/product-page-content-and-assets/):

| Upload | File | Where |
| --- | --- | --- |
| **Product icon** (160×160) | `product-icon-160.png` | Product Data → Woo Product Data → Product Icon |
| **Product image** (featured) | `01-product-image-frontend-dashboard.png` | Set product image |
| **Gallery** (order) | `02` … `07` | Add product gallery images |

Gallery images are **1792×1100** (above the 896×550 minimum). Featured shows the front-end dashboard.

---

## Before Submit for Review

1. Title → My Account Dashboard Builder  
2. Short description + full description + 3 FAQs pasted  
3. Product image + gallery set  
4. Price correct  
5. Preview the public page once  
6. Submit for Review  
