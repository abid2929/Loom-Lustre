<?php
// Shared page parts: <head>, header, footer, product card.

function icon(string $n, int $s = 22): string {
    $p = [
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'bag' => '<path d="M6 7h12l1 13H5L6 7Z"/><path d="M9 7a3 3 0 0 1 6 0"/>',
        'heart' => '<path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10Z"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'close' => '<path d="M6 6l12 12M18 6 6 18"/>',
        'home' => '<path d="M4 11 12 4l8 7v9h-5v-6H9v6H4v-9Z"/>',
        'grid' => '<rect x="4" y="4" width="6.5" height="6.5" rx="1.5"/><rect x="13.5" y="4" width="6.5" height="6.5" rx="1.5"/><rect x="4" y="13.5" width="6.5" height="6.5" rx="1.5"/><rect x="13.5" y="13.5" width="6.5" height="6.5" rx="1.5"/>',
        'truck' => '<path d="M3 6h11v10H3zM14 10h4l3 3v3h-7"/><circle cx="7" cy="17.5" r="1.8"/><circle cx="17" cy="17.5" r="1.8"/>',
        'shield' => '<path d="M12 3 5 6v5c0 4.5 3 8 7 10 4-2 7-5.5 7-10V6l-7-3Z"/><path d="m9 12 2 2 4-4"/>',
        'cash' => '<rect x="3" y="6" width="18" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/>',
        'return' => '<path d="M9 14 4 9l5-5"/><path d="M4 9h10a6 6 0 0 1 0 12h-3"/>',
        'phone' => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z"/>',
        'pin' => '<path d="M12 21s-6-5.6-6-11a6 6 0 0 1 12 0c0 5.4-6 11-6 11Z"/><circle cx="12" cy="10" r="2.2"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'chev' => '<path d="m9 6 6 6-6 6"/>',
        'left' => '<path d="m15 6-6 6 6 6"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'minus' => '<path d="M5 12h14"/>',
        'trash' => '<path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/>',
        'bolt' => '<path d="M13 2 4 14h7l-1 8 9-12h-7l1-8Z"/>',
        'star' => '<path d="m12 3 2.8 5.8 6.2.9-4.5 4.4 1.1 6.2L12 17.3l-5.6 3 1.1-6.2L3 9.7l6.2-.9L12 3Z"/>',
        'spark' => '<path d="M12 3v4M12 17v4M3 12h4M17 12h4M6 6l2.5 2.5M15.5 15.5 18 18M6 18l2.5-2.5M15.5 8.5 18 6"/>',
        'leaf' => '<path d="M5 19c0-9 6-14 15-14 0 9-5 15-14 15"/><path d="M5 19 13 11"/>',
        'check' => '<path d="m5 12 5 5 9-10"/>',
        'fb' => '<path d="M14 8h3V4h-3a4 4 0 0 0-4 4v3H7v4h3v6h4v-6h3l1-4h-4V8Z"/>',
        'ig' => '<rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.3" cy="6.7" r=".6"/>',
        'tt' => '<path d="M14 3v11a3.5 3.5 0 1 1-3.5-3.5"/><path d="M14 3c.5 2.6 2.4 4.5 5 4.8"/>',
        'yt' => '<rect x="3" y="6" width="18" height="12" rx="4"/><path d="m10 9.5 5 2.5-5 2.5Z"/>',
    ];
    if ($n === 'wa') return '<svg class="ic" width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.3-.4.7-1.4.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.8 11.8 0 0 0 4.5 4c1.7.7 2.3.8 3.2.6.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.2-1.2-.1-.1-.3-.2-.5-.3Z"/></svg>';
    return '<svg class="ic" width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($p[$n] ?? '') . '</svg>';
}

function page_head(string $title, string $desc = '', string $image = '', string $active = ''): void {
    $name = S('shop_name');
    $full = $title === '' ? (S('meta_title') !== '' ? S('meta_title') : $name . ' | ' . S('shop_sub') . ' Shop in Bangladesh') : $title . ' | ' . $name;
    track_visit((string)($GLOBALS['route'] ?? ''));
    $desc = $desc !== '' ? $desc : S('meta_desc');
    if (mb_strlen($desc) < 100) $desc = rtrim($desc, '. ') . ($desc !== '' ? '. ' : '') . 'Original products at ' . $name . ', cash on delivery all over Bangladesh.';
    $img = $image !== '' ? $image : url('assets/img/share.jpg');
    if (!preg_match('~^https?://~', $img)) $img = site_origin() . $img;
    $route = (string)($GLOBALS['route'] ?? '');
    $canon = abs_url($GLOBALS['ll_canon'] ?? $route);
    $first = explode('/', $route)[0];
    $noindex = !empty($GLOBALS['ll_noindex']) || in_array($first, ['cart', 'checkout', 'wishlist', 'track', 'order', 'search'], true);
    $GLOBALS['ll_active'] = $active;
    $cssv = @filemtime(LL_ROOT . '/assets/css/site.css') ?: 1;
    ?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($full) ?></title>
<meta name="description" content="<?= e($desc) ?>">
<?php if (S('meta_keywords') !== ''): ?><meta name="keywords" content="<?= e(S('meta_keywords')) ?>">
<?php endif; ?><meta name="theme-color" content="#b31f3a">
<meta property="og:title" content="<?= e($full) ?>">
<meta property="og:description" content="<?= e($desc) ?>">
<meta property="og:image" content="<?= e($img) ?>">
<meta property="og:type" content="<?= $first === 'p' ? 'product' : 'website' ?>">
<meta property="og:url" content="<?= e($canon) ?>">
<meta property="og:site_name" content="<?= e($name) ?>">
<meta property="og:locale" content="en_US">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($full) ?>">
<meta name="twitter:description" content="<?= e($desc) ?>">
<meta name="twitter:image" content="<?= e($img) ?>">
<?php if ($noindex): ?><meta name="robots" content="noindex, follow">
<?php else: ?><link rel="canonical" href="<?= e($canon) ?>">
<meta name="robots" content="index, follow, max-image-preview:large">
<?php endif; if (S('gsv') !== ''): ?><meta name="google-site-verification" content="<?= e(S('gsv')) ?>">
<?php endif; if ($route === ''): ?><script type="application/ld+json"><?= json_encode(['@context' => 'https://schema.org', '@graph' => [
    ['@type' => 'Organization', '@id' => abs_url() . '#org', 'name' => $name, 'url' => abs_url(), 'logo' => site_origin() . url('assets/img/icon-192.png'), 'sameAs' => array_values(array_filter([S('facebook'), S('instagram')])), 'contactPoint' => array_filter(['@type' => 'ContactPoint', 'telephone' => S('phone'), 'contactType' => 'customer service', 'areaServed' => 'BD'])],
    ['@type' => 'WebSite', '@id' => abs_url() . '#web', 'name' => $name, 'url' => abs_url(), 'publisher' => ['@id' => abs_url() . '#org'], 'potentialAction' => ['@type' => 'SearchAction', 'target' => abs_url('search') . '?q={q}', 'query-input' => 'required name=q']],
]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<?php endif; ?><link rel="icon" href="<?= url('assets/img/favicon-32.png') ?>" sizes="32x32">
<link rel="icon" href="<?= url('assets/img/icon-192.png') ?>" sizes="192x192">
<link rel="apple-touch-icon" href="<?= url('assets/img/apple-touch-icon.png') ?>">
<?php if (!empty($GLOBALS['ll_preload'])): [$pa, $pb] = $GLOBALS['ll_preload']; ?><link rel="preload" as="image" href="<?= e($pa) ?>"<?php if ($pb !== $pa): ?> imagesrcset="<?= e($pa) ?> 900w, <?= e($pb) ?> 2000w" imagesizes="(max-width:860px) 75vw, 100vw"<?php endif; ?> fetchpriority="high">
<?php endif; ?><link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500&family=Jost:wght@400;500;600&family=Great+Vibes&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('assets/css/site.css') ?>?v=<?= $cssv ?>">
<?php if (S('pixel_id') !== ''): ?>
<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','<?= e(S('pixel_id')) ?>');fbq('track','PageView');</script>
<?php endif; if (S('ga_id') !== ''): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e(S('ga_id')) ?>"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag('js',new Date());gtag('config','<?= e(S('ga_id')) ?>');</script>
<?php endif; ?>
</head>
<body<?= defined('LL_DEMO') ? ' class="demo"' : '' ?>>
<?php if (defined('LL_DEMO')): ?><div class="demo-bar">Demo preview: products and orders here are samples. <a href="<?= url('admin/') ?>">Open the demo admin panel →</a></div><?php endif; ?>
<?php site_header();
}

function brand_mark(): string {
    return '<a class="brand" href="' . url() . '" aria-label="' . e(S('shop_name')) . ' home"><img src="' . url('assets/img/lady.png') . '" alt="" width="32" height="80"><span class="brand-t"><b>' . e(S('shop_name')) . '</b><small>' . e(S('shop_sub')) . '</small></span></a>';
}

function site_header(): void {
    $a = $GLOBALS['ll_active'] ?? '';
    $cats = categories();
    $msgs = S('announce_bar') !== '' ? [S('announce_bar')] : array_values(array_filter(array_map('trim', explode('•', S('announce')))));
    ?>
<a class="skip" href="#main">Skip to content</a>
<div class="announce" data-msgs="<?= e(json_encode($msgs, JSON_UNESCAPED_UNICODE)) ?>"><span><?= e($msgs[0] ?? '') ?></span></div>
<header class="hdr">
  <div class="hdr-in wrap">
    <button class="icon-btn only-m" data-open="drawer" aria-label="Menu"><?= icon('menu') ?></button>
    <?= brand_mark() ?>
    <form class="search" action="<?= url('search') ?>" role="search">
      <?= icon('search', 19) ?>
      <input type="search" name="q" placeholder="Search lipstick, serum, perfume..." value="<?= e($_GET['q'] ?? '') ?>" aria-label="Search products">
    </form>
    <nav class="hdr-act">
      <a class="icon-btn only-d" href="<?= e(wa_link('Hi ' . S('shop_name') . ', I need help.')) ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><?= icon('wa') ?><span>Help</span></a>
      <a class="icon-btn" href="<?= url('wishlist') ?>" aria-label="Wishlist"><?= icon('heart') ?><span class="only-d">Wishlist</span><i class="cnt" data-wish-count hidden>0</i></a>
      <a class="icon-btn" href="<?= url('cart') ?>" aria-label="Shopping bag"><?= icon('bag') ?><span class="only-d">Bag</span><i class="cnt" data-cart-count hidden>0</i></a>
    </nav>
  </div>
  <nav class="catnav" aria-label="Categories">
    <div class="wrap catnav-in">
      <a href="<?= url('shop') ?>" class="<?= $a === 'shop' ? 'on' : '' ?>">All Products</a>
      <?php foreach ($cats as $c): ?><a href="<?= url('c/' . $c['slug']) ?>" class="<?= $a === 'c/' . $c['slug'] ? 'on' : '' ?>"><?= e($c['name']) ?></a><?php endforeach; ?>
      <a href="<?= url('new') ?>" class="<?= $a === 'new' ? 'on' : '' ?>">New In</a>
      <a href="<?= url('sale') ?>" class="sale <?= $a === 'sale' ? 'on' : '' ?>">Sale</a>
    </div>
  </nav>
</header>
<div class="drawer" id="drawer" hidden>
  <div class="drawer-bg" data-close></div>
  <aside class="drawer-p" role="dialog" aria-label="Menu">
    <div class="drawer-h"><?= brand_mark() ?><button class="icon-btn" data-close aria-label="Close"><?= icon('close') ?></button></div>
    <nav class="drawer-n">
      <a href="<?= url() ?>"><?= icon('home', 20) ?>Home</a>
      <a href="<?= url('shop') ?>"><?= icon('grid', 20) ?>All Products</a>
      <?php foreach ($cats as $c): ?><a href="<?= url('c/' . $c['slug']) ?>"><img src="<?= e(img_small($c['image'])) ?>" alt="" width="28" height="28"><?= e($c['name']) ?></a><?php endforeach; ?>
      <a href="<?= url('new') ?>"><?= icon('spark', 20) ?>New In</a>
      <a href="<?= url('sale') ?>" class="sale"><?= icon('spark', 20) ?>Sale</a>
      <hr>
      <a href="<?= url('track') ?>"><?= icon('truck', 20) ?>Track my order</a>
      <a href="<?= url('about') ?>"><?= icon('leaf', 20) ?>About us</a>
      <a href="<?= url('contact') ?>"><?= icon('phone', 20) ?>Contact</a>
    </nav>
  </aside>
</div>
<main id="main">
<?php
}

function page_foot(): void {
    $cats = categories();
    $social = array_filter(['fb' => S('facebook'), 'ig' => S('instagram'), 'tt' => S('tiktok'), 'yt' => S('youtube')]);
    ?>
</main>
<section class="perks">
  <div class="wrap perks-in">
    <div><?= icon('shield', 28) ?><b>100% Original</b><span>Checked by hand</span></div>
    <div><?= icon('cash', 28) ?><b>Cash on Delivery</b><span>Pay when you get it</span></div>
    <div><?= icon('truck', 28) ?><b>Fast Delivery</b><span>All over Bangladesh</span></div>
    <div><?= icon('return', 28) ?><b>Easy Exchange</b><span>If anything is wrong</span></div>
  </div>
</section>
<footer class="ftr">
  <div class="wrap ftr-in">
    <div class="ftr-brand">
      <img src="<?= url('assets/img/logo.webp') ?>" alt="<?= e(S('shop_name')) ?>" width="200" height="156" loading="lazy">
      <p><?= e(S('about')) ?></p>
      <?php if ($social): ?><div class="social"><?php foreach ($social as $k => $u): ?><a href="<?= e($u) ?>" target="_blank" rel="noopener" aria-label="<?= $k ?>"><?= icon($k, 20) ?></a><?php endforeach; ?></div><?php endif; ?>
    </div>
    <div><h4>Shop</h4><?php foreach ($cats as $c): ?><a href="<?= url('c/' . $c['slug']) ?>"><?= e($c['name']) ?></a><?php endforeach; ?><a href="<?= url('sale') ?>">Sale</a></div>
    <div><h4>Help</h4><a href="<?= url('track') ?>">Track order</a><a href="<?= url('about') ?>#delivery">Delivery &amp; payment</a><a href="<?= url('about') ?>#exchange">Exchange policy</a><a href="<?= url('about') ?>">About us</a><a href="<?= url('contact') ?>">Contact</a></div>
    <div><h4>Contact</h4>
      <a href="tel:<?= e(norm_phone(S('phone'))) ?>"><?= icon('phone', 17) ?><?= e(S('phone')) ?></a>
      <a href="<?= e(wa_link()) ?>" target="_blank" rel="noopener"><?= icon('wa', 17) ?>WhatsApp us</a>
      <?php if (S('email') !== ''): ?><a href="mailto:<?= e(S('email')) ?>"><?= icon('mail', 17) ?><?= e(S('email')) ?></a><?php endif; ?>
      <span><?= icon('pin', 17) ?><?= e(S('address')) ?></span>
    </div>
  </div>
  <div class="ftr-b wrap">
    <span>© <?= date('Y') ?> <?= e(S('shop_name')) ?>. All rights reserved.</span>
    <span>Designed &amp; developed by <a href="https://boostnixmedia.com" target="_blank" rel="noopener">Boostnix Media</a></span>
  </div>
</footer>
<nav class="tabbar" aria-label="Quick links">
  <a href="<?= url() ?>" class="<?= ($GLOBALS['ll_active'] ?? '') === 'home' ? 'on' : '' ?>"><?= icon('home') ?><span>Home</span></a>
  <a href="<?= url('shop') ?>" class="<?= in_array($GLOBALS['ll_active'] ?? '', ['shop'], true) || str_starts_with($GLOBALS['ll_active'] ?? '', 'c/') ? 'on' : '' ?>"><?= icon('grid') ?><span>Shop</span></a>
  <a href="<?= url('wishlist') ?>" class="<?= ($GLOBALS['ll_active'] ?? '') === 'wishlist' ? 'on' : '' ?>"><?= icon('heart') ?><span>Wishlist</span><i class="cnt" data-wish-count hidden>0</i></a>
  <a href="<?= url('cart') ?>" class="<?= ($GLOBALS['ll_active'] ?? '') === 'cart' ? 'on' : '' ?>"><?= icon('bag') ?><span>Bag</span><i class="cnt" data-cart-count hidden>0</i></a>
  <a href="<?= e(wa_link('Hi ' . S('shop_name') . ', I need help.')) ?>" class="wa" target="_blank" rel="noopener"><?= icon('wa') ?><span>WhatsApp</span></a>
</nav>
<a class="wa-float" href="<?= e(wa_link('Hi ' . S('shop_name') . ', I need help.')) ?>" target="_blank" rel="noopener" aria-label="Chat on WhatsApp" title="<?= e(S('wa_popup')) ?>"><?= icon('wa', 30) ?></a>
<div class="toast" id="toast" role="status" aria-live="polite"></div>
<script>window.LL={base:<?= json_encode(base_url()) ?>,cur:<?= json_encode(S('currency'), JSON_UNESCAPED_UNICODE) ?>,demo:<?= defined('LL_DEMO') ? 'true' : 'false' ?>,wa:<?= json_encode(wa_link('')) ?>};</script>
<script src="<?= url('assets/js/site.js') ?>?v=<?= @filemtime(LL_ROOT . '/assets/js/site.js') ?: 1 ?>" defer></script>
</body>
</html>
<?php
}

/** <img> with a sharp large copy for big / high-density screens and a small copy for phones. */
function img_tag(string $path, string $alt, string $sizes, bool $lazy = true, string $cls = ''): string {
    $full = img_url($path); $small = img_small($path);
    $set = $small !== $full ? ' srcset="' . e($small) . ' 600w, ' . e($full) . ' 1200w" sizes="' . $sizes . '"' : '';
    return '<img' . ($cls ? ' class="' . $cls . '"' : '') . ' src="' . e($small) . '"' . $set . ' alt="' . e($alt) . '" width="600" height="750"' . ($lazy ? ' loading="lazy" decoding="async"' : ' fetchpriority="high"') . '>';
}

function product_card(array $p, bool $lazy = true): string {
    $imgs = p_images($p);
    $off = discount_pct($p);
    $out = (int)$p['stock'] <= 0;
    $url = url('p/' . $p['slug']);
    [$ravg, $rn] = review_stats()[(int)$p['id']] ?? [0, 0];
    $h = '<article class="card' . ($out ? ' sold' : '') . '"><div class="card-m">';
    $h .= '<a class="card-img" href="' . $url . '" aria-label="' . e($p['name']) . '">';
    $h .= img_tag($imgs[0] ?? '', $p['name'], '(max-width:860px) 48vw, 300px', $lazy);
    if (isset($imgs[1])) $h .= img_tag($imgs[1], '', '(max-width:860px) 48vw, 300px', true, 'alt-img');
    $h .= '</a>';
    $tags = '';
    if ($off > 0) $tags .= '<span class="badge off">-' . $off . '%</span>';
    if (!empty($p['flash_sale'])) $tags .= '<span class="badge flash">Flash</span>';
    elseif (!empty($p['bestseller'])) $tags .= '<span class="badge best">Bestseller</span>';
    elseif (!empty($p['is_new'])) $tags .= '<span class="badge new">New</span>';
    if ($tags) $h .= '<div class="badges">' . $tags . '</div>';
    if ($out) $h .= '<span class="soldout">Sold out</span>';
    $h .= '<button class="wish" data-wish="' . (int)$p['id'] . '" aria-label="Save ' . e($p['name']) . ' to wishlist">' . icon('heart', 19) . '</button>';
    $shades = list_csv((string)$p['shades']);
    if (!$out) $h .= $shades || list_csv((string)($p['sizes'] ?? ''))
        ? '<a class="quick" href="' . $url . '" aria-label="Choose shade for ' . e($p['name']) . '">' . icon('bag', 18) . '<span>Choose shade</span></a>'
        : '<button class="quick" data-add="' . (int)$p['id'] . '" aria-label="Add ' . e($p['name']) . ' to bag">' . icon('bag', 18) . '<span>Add to bag</span></button>';
    $h .= '</div><div class="card-b">';
    $h .= '<small class="card-cat">' . e($p['brand'] ?: ($p['cat_name'] ?? '')) . '</small>';
    $h .= '<h3><a href="' . $url . '">' . e($p['name']) . '</a></h3>';
    if ($rn) $h .= '<span class="card-r"><span class="stars">' . str_repeat('★', (int)round($ravg)) . '<i>' . str_repeat('★', 5 - (int)round($ravg)) . '</i></span>(' . $rn . ')</span>';
    elseif ($p['size'] !== '') $h .= '<p class="card-s">' . e($p['size']) . ($shades ? ' · ' . count($shades) . ' shades' : '') . '</p>';
    $h .= '<div class="price"><b>' . money($p['price']) . '</b>';
    if ($off > 0) $h .= '<s>' . money($p['old_price']) . '</s>';
    $h .= '</div></div></article>';
    return $h;
}

function product_grid(array $list, string $cls = 'grid'): string {
    if (!$list) return '<p class="empty">No products here yet. Please check back soon.</p>';
    $h = '<div class="' . $cls . '">';
    foreach ($list as $i => $p) $h .= product_card($p, $i > 3);
    return $h . '</div>';
}

function section_head(string $title, string $sub = '', string $link = '', string $linkText = 'View all'): string {
    $h = '<div class="sec-h"><div><h2>' . e($title) . '</h2>' . ($sub !== '' ? '<p>' . e($sub) . '</p>' : '') . '</div>';
    if ($link !== '') $h .= '<a class="more" href="' . url($link) . '">' . e($linkText) . icon('chev', 16) . '</a>';
    return $h . '</div>';
}
