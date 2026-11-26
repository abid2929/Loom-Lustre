<div align="center">

<img src="assets/img/logo.png" alt="Loom & Lustre" height="96">

# Loom & Lustre

### Fashion & Beauty online shop for Bangladesh

Sarees and kurtis, westernwear, menswear, footwear, bags, jewellery, sunglasses, kids, activewear and sneakers, plus lipstick, skincare, makeup, fragrance, nails and body care, with cash on delivery all over Bangladesh.

[![Live demo](https://img.shields.io/badge/Live%20demo-open%20shop-9e1b34?style=for-the-badge)](https://abid2929.github.io/Loom-Lustre/)
[![Admin demo](https://img.shields.io/badge/Admin%20panel-demo-17130f?style=for-the-badge)](https://abid2929.github.io/Loom-Lustre/admin/)

![PHP 8](https://img.shields.io/badge/PHP-8.1%2B-777bb4?logo=php&logoColor=white)
![SQLite](https://img.shields.io/badge/SQLite-no%20MySQL%20needed-003b57?logo=sqlite&logoColor=white)
![Version](https://img.shields.io/badge/version-1.5-c9a063)
![Phone ready](https://img.shields.io/badge/phone%20%2B%20PC-ready-2e7d32)

<br>

<img src=".github/screens/pc-home.webp" alt="Loom & Lustre home page" width="900">

</div>

---

## Screens

<table>
<tr>
<td width="68%"><img src=".github/screens/pc-cards.webp" alt="Flash sale product cards"><br><sub>Flash sale with countdown, discount badges and shades</sub></td>
<td width="32%"><img src=".github/screens/ph-home.webp" alt="Home on phone"><br><sub>Home on phone</sub></td>
</tr>
<tr>
<td><img src=".github/screens/pc-product.webp" alt="Product page"><br><sub>Product page: big photo, shade picker, Add to bag, Order on WhatsApp</sub></td>
<td><img src=".github/screens/ph-product.webp" alt="Product page on phone"><br><sub>Product page on phone</sub></td>
</tr>
</table>

### Admin panel (Bangla, with an EN button)

<table>
<tr>
<td><img src=".github/screens/pc-admin.webp" alt="Admin dashboard"><br><sub>Dashboard: sales, orders, visitors, low stock</sub></td>
<td><img src=".github/screens/pc-admin-products.webp" alt="Admin products"><br><sub>Products: search, edit, price and stock</sub></td>
</tr>
</table>

---

## Features

| Shop | Admin |
|---|---|
| Wide auto-sliding offer banner. The "Up to X% off" pill is worked out from real prices | Bangla by default, EN button, the same shared panel as every shop |
| Women, Men, Kids, Beauty menu with a Fashion / Luxe switch; photo category row, In the Spotlight offers, Styles To Celebrate In, Stylists' Picks, Luxe Edit, circles; Categories page and a dark Luxe store | Dashboard with sales chart, live activity and recent orders |
| 18 categories (with Watches and Home) and 112 sample products with colours, shades, sizes and stock | Products: sizes, shades, photo gallery, AI copywriter |
| Product page: shade picker, Add to bag, Buy now, Order on WhatsApp | Categories, collections, inventory and stock |
| Bag, wishlist, checkout with cash on delivery | Orders: status tabs, manual orders, invoice, CSV, courier (Steadfast) |
| Inside / Outside Dhaka delivery charge, optional bKash and Nagad | Reviews, hero banners, homepage sections on/off |
| Order tracking by order number and phone | Admins and roles (Super Admin, Manager, Staff) |
| Search, sort and price filter | Settings: store, delivery, payment, SMS, Meta Pixel + CAPI, SEO |
| Phone layout with a bottom tab bar and smooth entrance animation | Phone layout with an app-style bottom bar |
| SEO: sitemap, robots, canonical, Open Graph, Product structured data | Login lock after wrong passwords, show/hide password |

## Tech

- **PHP 8.1+ and SQLite.** No MySQL, no Node.js, no build step.
- The database creates itself on the first visit, in `~/loomlustre-data` outside the public folder when the host allows it.
- Fonts: Cormorant Garamond and Jost. Photos are sharp 4:5 WebP (1200 px, with 600 px phone copies).
- `docs/` is a static copy for GitHub Pages, built with `php tools/build-demo.php /Loom-Lustre/`. Pages has no PHP, so the admin there runs in demo mode with sample data, and changes stay in your browser only.

## Set up on your own hosting

1. Upload all files (everything except `docs/`) to `public_html` on any cPanel host with PHP 8.1 or newer.
2. Open `yourdomain.com/admin`. The first visit asks you to create the admin username and password.
3. Go to **সাইট সেটিং** (Site settings) and add the shop phone, WhatsApp, Facebook link and delivery charges.
4. Replace the sample products with your own from **প্রোডাক্ট ম্যানেজমেন্ট** (Products).

**Forgot the admin login?** Upload an empty file named `reset-admin.txt` to `public_html`, then open `/admin` and set a new username and password. The file deletes itself.

## Credits

Product photos are from [Unsplash](https://unsplash.com) (free to use). Replace them with real product photos from the admin panel.

<div align="center">

Designed and developed by **Abid Islam** and **[Boostnix Media](https://boostnixmedia.com)**

</div>
