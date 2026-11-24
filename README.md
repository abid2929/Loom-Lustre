# Loom & Lustre: Fashion & Beauty shop

An online cosmetics shop for Bangladesh: lipstick, skincare, makeup, fragrance, nails, body and hair care.

**Live demo:** https://abid2929.github.io/Loom-Lustre/
**Demo admin panel (no password, nothing saves):** https://abid2929.github.io/Loom-Lustre/admin/

## What it has

- Home page with an auto-sliding offer banner, category circles, deals, bestsellers, makeup and skincare sections
- 6 categories and 38 products with shades, sizes, discounts and stock
- Product page with shade picker, quantity, Add to bag, Buy now and Order on WhatsApp
- Shopping bag, wishlist, checkout with cash on delivery (Inside / Outside Dhaka delivery charge), order tracking
- Search, sort and price filter
- Phone layout with a bottom tab bar and a sticky Buy bar
- Admin panel in Bangla at `/admin`: dashboard with sales chart, orders (status, WhatsApp confirm, call), products (photos, shades, price, stock), categories, home slider, shop settings, password

## Tech

PHP 8 + SQLite. No MySQL, no Node.js. Upload the files to `public_html` on any cPanel host and open `/admin` to set a password. The database is created by itself on first visit (in `~/loomlustre-data`, outside the public folder, when the host allows it).

`docs/` is a static copy of the site for GitHub Pages, built with `php tools/build-demo.php /Loom-Lustre/`.

Product photos are from [Unsplash](https://unsplash.com) (free to use). The shop owner should replace them with real product photos from the admin panel.

---

Designed & developed by Abid Islam & [Boostnix Media](https://boostnixmedia.com).
