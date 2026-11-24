# Loom & Lustre: Fashion & Beauty shop

An online cosmetics shop for Bangladesh: lipstick, skincare, makeup, fragrance, nails, body and hair care.

**Live demo:** https://abid2929.github.io/Loom-Lustre/
**Demo admin panel (no password, changes stay only in your browser):** https://abid2929.github.io/Loom-Lustre/admin/

## What it has

- Home page with an auto-sliding offer banner, category circles, deals, bestsellers, makeup and skincare sections
- 6 categories and 38 products with shades, sizes, discounts and stock
- Product page with shade picker, quantity, Add to bag, Buy now and Order on WhatsApp
- Shopping bag, wishlist, checkout with cash on delivery (Inside / Outside Dhaka delivery charge), order tracking
- Search, sort and price filter
- Phone layout with a bottom tab bar and a sticky Buy bar
- Premium look: Cormorant Garamond + Jost type, full-width photo banners, arch category tiles, sharp 4:5 product photos (1200 px with 600 px phone copies)
- Admin panel at `/admin`, the same shared panel used by every shop (base: PetVerse). **Bangla by default** with an EN button:
  - Dashboard: welcome card, quick buttons, visitors, live activity, sales chart, status donut, recent orders
  - Products (sizes, shades, gallery with a shade tag per photo, AI copywriter), Categories, Collections, Inventory & Stock
  - Orders (status tabs, manual orders, invoice, CSV), Courier Hub (Steadfast), Customers, Reviews, Hero Banners, Homepage CMS (top bar, deal, sections on/off), Reports
  - Admins & Roles (Super Admin, Manager, Staff), Site Settings (store, delivery, payment, courier, SMS, Meta Pixel + CAPI, SEO + Gemini key, password)
  - Phone: app-style bottom tab bar
- SEO: sitemap.xml, robots.txt, canonical links, Open Graph and Twitter cards, Product + Breadcrumb + Organization structured data
- Storefront extras: flash sale with countdown, collections, product reviews, size options, bKash/Nagad with Transaction ID

## Tech

PHP 8 + SQLite. No MySQL, no Node.js. Upload the files to `public_html` on any cPanel host and open `/admin` to create the first admin username and password. The database is created by itself on first visit (in `~/loomlustre-data`, outside the public folder, when the host allows it).

`docs/` is a static copy of the site for GitHub Pages, built with `php tools/build-demo.php /Loom-Lustre/`. On GitHub Pages there is no PHP, so the admin runs in demo mode with the shop's sample data.

Product photos are from [Unsplash](https://unsplash.com) (free to use). The shop owner should replace them with real product photos from the admin panel.

---

Designed & developed by Abid Islam & [Boostnix Media](https://boostnixmedia.com).
