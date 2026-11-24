<?php
// Loom & Lustre storefront: one front controller for every page.
require __DIR__ . '/inc/core.php';
require __DIR__ . '/inc/layout.php';

$route = trim((string)($_GET['route'] ?? ''), '/');
if ($route === '' && isset($_SERVER['PATH_INFO'])) $route = trim($_SERVER['PATH_INFO'], '/');
$parts = $route === '' ? [] : explode('/', $route);
$page = $parts[0] ?? '';

const P_SQL = 'SELECT p.*, c.name AS cat_name, c.slug AS cat_slug FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.active=1';

function q(string $where = '', array $args = [], string $order = 'p.sort, p.id DESC', int $limit = 0): array {
    $sql = P_SQL . ($where ? " AND ($where)" : '') . " ORDER BY $order" . ($limit ? " LIMIT $limit" : '');
    $st = db()->prepare($sql); $st->execute($args); return $st->fetchAll();
}

/* ---------- JSON API ---------- */
if ($page === 'api') {
    $a = $parts[1] ?? '';
    if ($a === 'products') {
        $ids = array_slice(array_filter(array_map('intval', explode(',', (string)($_GET['ids'] ?? '')))), 0, 60);
        if (!$ids) json_out([]);
        $in = implode(',', $ids);
        $rows = db()->query("SELECT id,name,slug,price,old_price,images,shades,size,stock FROM products WHERE active=1 AND id IN ($in)")->fetchAll();
        $out = [];
        foreach ($rows as $r) $out[] = ['id' => (int)$r['id'], 'name' => $r['name'], 'url' => url('p/' . $r['slug']), 'img' => img_small(p_image($r)), 'price' => (int)$r['price'], 'old' => (int)$r['old_price'], 'size' => $r['size'], 'stock' => (int)$r['stock'], 'shades' => list_csv((string)$r['shades'])];
        json_out($out);
    }
    if ($a === 'order' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $in = json_decode((string)file_get_contents('php://input'), true) ?: [];
        if (!empty($in['website'])) json_out(['ok' => false, 'error' => 'Spam'], 400); // honeypot
        if (!rate_ok('order', 6, 3600)) json_out(['ok' => false, 'error' => 'Too many orders from this connection. Please call or WhatsApp us.'], 429);
        $name = trim(mb_substr((string)($in['name'] ?? ''), 0, 80));
        $phone = norm_phone((string)($in['phone'] ?? ''));
        $addr = trim(mb_substr((string)($in['address'] ?? ''), 0, 400));
        $area = ($in['area'] ?? '') === 'out' ? 'out' : 'in';
        $note = trim(mb_substr((string)($in['note'] ?? ''), 0, 400));
        if ($name === '' || $addr === '') json_out(['ok' => false, 'error' => 'Please write your name and full address.'], 422);
        if (!preg_match('/^01[3-9]\d{8}$/', $phone)) json_out(['ok' => false, 'error' => 'Please give a valid 11 digit mobile number (01XXXXXXXXX).'], 422);
        $items = []; $sub = 0;
        $st = db()->prepare('SELECT * FROM products WHERE id=? AND active=1');
        foreach (array_slice((array)($in['items'] ?? []), 0, 40) as $it) {
            $id = (int)($it['id'] ?? 0); $qty = max(1, min(20, (int)($it['qty'] ?? 1)));
            $st->execute([$id]); $p = $st->fetch();
            if (!$p || (int)$p['stock'] <= 0) continue;
            $qty = min($qty, (int)$p['stock']);
            $shade = trim(mb_substr((string)($it['shade'] ?? ''), 0, 40));
            if ($shade !== '' && !in_array($shade, list_csv((string)$p['shades']), true)) $shade = '';
            $items[] = ['id' => (int)$p['id'], 'name' => $p['name'], 'shade' => $shade, 'qty' => $qty, 'price' => (int)$p['price'], 'img' => p_image($p)];
            $sub += $qty * (int)$p['price'];
        }
        if (!$items) json_out(['ok' => false, 'error' => 'Your bag is empty or the items are sold out.'], 422);
        $free = (int)S('free_over');
        $del = ($free > 0 && $sub >= $free) ? 0 : (int)S($area === 'out' ? 'delivery_out' : 'delivery_in');
        $code = 'LL' . date('ymd') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
        $now = date('Y-m-d H:i:s');
        $pdo = db();
        $pdo->beginTransaction();
        $pdo->prepare('INSERT INTO orders(code,name,phone,address,area,note,items,subtotal,delivery,total,ip,created,updated) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)')
            ->execute([$code, $name, $phone, $addr, $area, $note, json_encode($items, JSON_UNESCAPED_UNICODE), $sub, $del, $sub + $del, client_ip(), $now, $now]);
        $dec = $pdo->prepare('UPDATE products SET stock=MAX(0,stock-?), sold=sold+? WHERE id=?');
        foreach ($items as $it) $dec->execute([$it['qty'], $it['qty'], $it['id']]);
        $pdo->commit();
        if (S('notify_email') !== '' && function_exists('mail')) {
            $lines = array_map(fn($i) => "- {$i['name']}" . ($i['shade'] ? " ({$i['shade']})" : '') . " x{$i['qty']} = " . $i['qty'] * $i['price'], $items);
            @mail(S('notify_email'), "New order $code - " . S('shop_name'), "Name: $name\nPhone: $phone\nAddress: $addr\n\n" . implode("\n", $lines) . "\n\nTotal: " . ($sub + $del), 'Content-Type: text/plain; charset=UTF-8');
        }
        json_out(['ok' => true, 'code' => $code, 'url' => url('order/' . $code . '?ph=' . substr($phone, -4))]);
    }
    json_out(['error' => 'Not found'], 404);
}

/* ---------- pages ---------- */
switch ($page) {
case '':
    page_head('', '', '', 'home');
    $slides = [];
    for ($i = 1; $i <= 4; $i++) if (S("s{$i}_on") === '1' && S("s{$i}_img") !== '') $slides[] = $i;
    $cats = categories();
    ?>
<section class="hero" aria-label="Offers">
  <div class="slides" data-slider>
    <?php foreach ($slides as $n => $i): ?>
    <a class="slide<?= $n === 0 ? ' on' : '' ?><?= S("s{$i}_dark") === '1' ? ' dark' : '' ?>" href="<?= url(S("s{$i}_link")) ?>">
      <img src="<?= e(img_url(S("s{$i}_img"))) ?>" alt="<?= e(S("s{$i}_title")) ?>" <?= $n === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?>>
      <span class="slide-t">
        <?php if (S("s{$i}_kicker") !== ''): ?><small><?= e(S("s{$i}_kicker")) ?></small><?php endif; ?>
        <b><?= e(S("s{$i}_title")) ?></b>
        <?php if (S("s{$i}_text") !== ''): ?><span><?= e(S("s{$i}_text")) ?></span><?php endif; ?>
        <?php if (S("s{$i}_btn") !== ''): ?><i class="btn"><?= e(S("s{$i}_btn")) ?></i><?php endif; ?>
      </span>
    </a>
    <?php endforeach; ?>
  </div>
  <?php if (count($slides) > 1): ?>
  <button class="sl-btn prev" aria-label="Previous slide"><?= icon('left') ?></button>
  <button class="sl-btn next" aria-label="Next slide"><?= icon('chev') ?></button>
  <div class="dots"><?php foreach ($slides as $n => $i): ?><button aria-label="Slide <?= $n + 1 ?>" class="<?= $n === 0 ? 'on' : '' ?>"></button><?php endforeach; ?></div>
  <?php endif; ?>
</section>

<section class="wrap sec">
  <?= section_head('Shop by category', 'Find exactly what your beauty routine needs') ?>
  <div class="cats">
    <?php foreach ($cats as $c): ?>
    <a class="cat" href="<?= url('c/' . $c['slug']) ?>"><span class="cat-img"><img src="<?= e(img_small($c['image'])) ?>" alt="" width="200" height="200" loading="lazy"></span><b><?= e($c['name']) ?></b></a>
    <?php endforeach; ?>
  </div>
</section>

<section class="wrap sec">
  <?= section_head('Deals of the week', 'Special prices for a short time', 'sale') ?>
  <?= product_grid(q('p.old_price > p.price', [], '(p.old_price - p.price) * 1.0 / p.old_price DESC', 8), 'grid rail') ?>
</section>

<section class="wrap duo">
  <a class="promo pink" href="<?= url('c/lips') ?>">
    <img src="<?= url('assets/img/b/rose-trio.webp') ?>" alt="" loading="lazy">
    <span><small>THE LIP EDIT</small><b>Find your perfect shade</b><i class="btn">Shop lips</i></span>
  </a>
  <a class="promo dark" href="<?= url('c/nails') ?>">
    <img src="<?= url('assets/img/b/drip-polish.webp') ?>" alt="" loading="lazy">
    <span><small>NAIL STUDIO</small><b>Colours that pop</b><i class="btn">Shop nails</i></span>
  </a>
</section>

<section class="wrap sec">
  <?= section_head('Bestsellers', 'What our customers love most', 'shop?sort=popular') ?>
  <?= product_grid(q('p.bestseller=1', [], 'p.sold DESC', 8)) ?>
</section>

<section class="band">
  <div class="wrap band-in">
    <div class="band-txt">
      <small>WHY LOOM &amp; LUSTRE</small>
      <h2>Beauty you can trust</h2>
      <p><?= e(S('about')) ?></p>
      <ul>
        <li><?= icon('check', 18) ?>Only original, sealed products</li>
        <li><?= icon('check', 18) ?>Shade help on WhatsApp before you buy</li>
        <li><?= icon('check', 18) ?>Cash on delivery in all 64 districts</li>
      </ul>
      <a class="btn" href="<?= url('shop') ?>">Start shopping</a>
    </div>
    <img src="<?= url('assets/img/b/pink-flat.webp') ?>" alt="Pink skincare and makeup set" loading="lazy">
  </div>
</section>

<section class="wrap sec">
  <?= section_head('Makeup must-haves', 'Palettes, blush, mascara and more', 'c/makeup') ?>
  <?= product_grid(q("c.slug='makeup'", [], 'p.featured DESC, p.sold DESC', 8), 'grid rail') ?>
</section>

<section class="wrap sec">
  <?= section_head('Skincare favourites', 'Serums and creams for a healthy glow', 'c/skincare') ?>
  <?= product_grid(q("c.slug='skincare'", [], 'p.featured DESC, p.sort', 4)) ?>
</section>

<section class="wrap sec">
  <?= section_head('New in', 'Fresh arrivals this week', 'new') ?>
  <?= product_grid(q('', [], 'p.is_new DESC, p.id DESC', 8), 'grid rail') ?>
</section>

<section class="wrap help-w"><div class="help">
  <div>
    <h2>Not sure which shade?</h2>
    <p>Send us a selfie or tell us your skin tone. We will suggest the right lipstick or foundation shade, free.</p>
  </div>
  <a class="btn wa-btn" href="<?= e(wa_link('Hi, can you help me choose a shade?')) ?>" target="_blank" rel="noopener"><?= icon('wa', 20) ?>Ask on WhatsApp</a>
</div></section>
    <?php
    page_foot();
    break;

case 'shop': case 'c': case 'new': case 'sale': case 'search':
    $where = ''; $args = []; $title = 'All Products'; $sub = 'Every product in our shop'; $active = 'shop'; $banner = '';
    if ($page === 'c') {
        $st = db()->prepare('SELECT * FROM categories WHERE slug=? AND active=1'); $st->execute([$parts[1] ?? '']); $cat = $st->fetch();
        if (!$cat) { not_found(); break; }
        $where = 'p.category_id=?'; $args = [$cat['id']]; $title = $cat['name']; $sub = $cat['tagline']; $active = 'c/' . $cat['slug']; $banner = $cat['image'];
    } elseif ($page === 'new') { $where = 'p.is_new=1'; $title = 'New In'; $sub = 'Fresh arrivals'; $active = 'new'; }
    elseif ($page === 'sale') { $where = 'p.old_price > p.price'; $title = 'Sale'; $sub = 'Beauty at special prices'; $active = 'sale'; }
    elseif ($page === 'search') {
        $qq = trim(mb_substr((string)($_GET['q'] ?? ''), 0, 60));
        $title = $qq === '' ? 'Search' : 'Results for "' . $qq . '"'; $sub = ''; $active = '';
        if ($qq !== '') { $where = 'p.name LIKE ? OR p.short LIKE ? OR p.tags LIKE ? OR c.name LIKE ? OR p.shades LIKE ?'; $l = '%' . $qq . '%'; $args = [$l, $l, $l, $l, $l]; }
        elseif (!defined('LL_DEMO')) $where = '0';
    }
    $sort = (string)($_GET['sort'] ?? '');
    $orders = ['' => ['Recommended', 'p.featured DESC, p.sort, p.id DESC'], 'popular' => ['Most popular', 'p.sold DESC'], 'low' => ['Price: low to high', 'p.price ASC'], 'high' => ['Price: high to low', 'p.price DESC'], 'new' => ['Newest first', 'p.id DESC'], 'off' => ['Biggest discount', '(p.old_price - p.price) * 1.0 / MAX(p.old_price,1) DESC']];
    if (!isset($orders[$sort])) $sort = '';
    $max = (int)($_GET['max'] ?? 0);
    if ($max > 0) { $where = ($where ? "($where) AND " : '') . 'p.price <= ?'; $args[] = $max; }
    $list = q($where, $args, $orders[$sort][1]);
    page_head($title, $sub, '', $active);
    $cats = categories();
    ?>
<section class="list-h<?= $banner ? ' has-img' : '' ?>">
  <div class="wrap">
    <nav class="crumbs"><a href="<?= url() ?>">Home</a><?= icon('chev', 14) ?><span><?= e($title) ?></span></nav>
    <h1><?= e($title) ?></h1>
    <?php if ($sub !== ''): ?><p><?= e($sub) ?></p><?php endif; ?>
  </div>
</section>
<div class="wrap">
  <div class="chips" role="list">
    <a role="listitem" href="<?= url('shop') ?>" class="<?= $active === 'shop' ? 'on' : '' ?>">All</a>
    <?php foreach ($cats as $c): ?><a role="listitem" href="<?= url('c/' . $c['slug']) ?>" class="<?= $active === 'c/' . $c['slug'] ? 'on' : '' ?>"><?= e($c['name']) ?></a><?php endforeach; ?>
    <a role="listitem" href="<?= url('sale') ?>" class="<?= $active === 'sale' ? 'on' : '' ?>">Sale</a>
  </div>
  <form class="tools" method="get">
    <?php if ($page === 'search'): ?><input type="hidden" name="q" value="<?= e($_GET['q'] ?? '') ?>"><?php endif; ?>
    <span class="count"><?= count($list) ?> product<?= count($list) === 1 ? '' : 's' ?></span>
    <label>Price
      <select name="max" onchange="this.form.submit()">
        <option value="0">Any</option>
        <?php foreach ([500, 1000, 2000] as $m): ?><option value="<?= $m ?>" <?= $max === $m ? 'selected' : '' ?>>Under <?= money($m) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>Sort
      <select name="sort" onchange="this.form.submit()">
        <?php foreach ($orders as $k => $o): ?><option value="<?= $k ?>" <?= $sort === $k ? 'selected' : '' ?>><?= $o[0] ?></option><?php endforeach; ?>
      </select>
    </label>
  </form>
  <?php if ($page === 'search' && !$list): ?>
    <div class="empty-box"><?= icon('search', 40) ?><h2>Nothing found</h2><p>Try another word, like lipstick, serum or perfume.</p><a class="btn" href="<?= url('shop') ?>">See all products</a></div>
  <?php else: ?>
    <?= product_grid($list) ?>
  <?php endif; ?>
</div>
    <?php
    page_foot();
    break;

case 'p':
    $st = db()->prepare(P_SQL . ' AND p.slug=?'); $st->execute([$parts[1] ?? '']); $p = $st->fetch();
    if (!$p) { not_found(); break; }
    db()->prepare('UPDATE products SET views=views+1 WHERE id=?')->execute([$p['id']]);
    $imgs = p_images($p) ?: [''];
    $off = discount_pct($p);
    $shades = list_csv((string)$p['shades']);
    $out = (int)$p['stock'] <= 0;
    page_head($p['name'], $p['short'] ?: mb_substr($p['description'], 0, 150), img_url($imgs[0]), 'c/' . $p['cat_slug']);
    $wa = wa_link("Hi, I want to order:\n" . $p['name'] . "\nPrice: " . money($p['price']) . "\n" . (isset($_SERVER['HTTP_HOST']) ? 'https://' . $_SERVER['HTTP_HOST'] . url('p/' . $p['slug']) : ''));
    ?>
<div class="wrap pdp">
  <nav class="crumbs"><a href="<?= url() ?>">Home</a><?= icon('chev', 14) ?><a href="<?= url('c/' . $p['cat_slug']) ?>"><?= e($p['cat_name']) ?></a><?= icon('chev', 14) ?><span><?= e($p['name']) ?></span></nav>
  <div class="pdp-in">
    <div class="gallery">
      <div class="g-main"><img id="gMain" src="<?= e(img_url($imgs[0])) ?>" alt="<?= e($p['name']) ?>" width="800" height="800" fetchpriority="high"><?php if ($off): ?><span class="badge off"><?= $off ?>% OFF</span><?php endif; ?></div>
      <?php if (count($imgs) > 1): ?><div class="g-thumbs"><?php foreach ($imgs as $i => $im): ?><button class="<?= $i === 0 ? 'on' : '' ?>" data-img="<?= e(img_url($im)) ?>"><img src="<?= e(img_small($im)) ?>" alt="" width="80" height="80"></button><?php endforeach; ?></div><?php endif; ?>
    </div>
    <div class="info" data-product="<?= (int)$p['id'] ?>">
      <small class="info-brand"><?= e($p['brand'] ?: S('shop_name')) ?></small>
      <h1><?= e($p['name']) ?></h1>
      <?php if ($p['short'] !== ''): ?><p class="info-short"><?= e($p['short']) ?></p><?php endif; ?>
      <div class="price big"><b><?= money($p['price']) ?></b><?php if ($off): ?><s><?= money($p['old_price']) ?></s><em><?= $off ?>% off</em><?php endif; ?></div>
      <p class="tax">Price includes VAT. <?= $p['size'] !== '' ? 'Size: ' . e($p['size']) . '.' : '' ?></p>
      <?php if ($shades): ?>
      <div class="opt"><span>Shade: <b id="shadeName"><?= e($shades[0]) ?></b></span>
        <div class="shades"><?php foreach ($shades as $i => $s): ?><button type="button" class="<?= $i === 0 ? 'on' : '' ?>" data-shade="<?= e($s) ?>"><?= e($s) ?></button><?php endforeach; ?></div>
      </div>
      <?php endif; ?>
      <div class="opt"><span>Quantity</span>
        <div class="qty"><button type="button" data-q="-1" aria-label="Less"><?= icon('minus', 18) ?></button><input id="qty" type="number" value="1" min="1" max="<?= max(1, min(20, (int)$p['stock'])) ?>" aria-label="Quantity"><button type="button" data-q="1" aria-label="More"><?= icon('plus', 18) ?></button></div>
        <?php if (!$out && (int)$p['stock'] <= 5): ?><em class="low">Only <?= (int)$p['stock'] ?> left</em><?php endif; ?>
      </div>
      <?php if ($out): ?>
        <button class="btn block" disabled>Sold out</button>
        <a class="btn ghost block" href="<?= e($wa) ?>" target="_blank" rel="noopener">Ask when it is back</a>
      <?php else: ?>
      <div class="buy">
        <button class="btn ghost" data-add-pdp><?= icon('bag', 19) ?>Add to bag</button>
        <button class="btn" data-buy-now>Buy now</button>
      </div>
      <a class="btn wa-btn block" href="<?= e($wa) ?>" target="_blank" rel="noopener"><?= icon('wa', 20) ?>Order on WhatsApp</a>
      <?php endif; ?>
      <button class="wish-line" data-wish="<?= (int)$p['id'] ?>"><?= icon('heart', 19) ?><span>Add to wishlist</span></button>
      <ul class="mini-perks">
        <li><?= icon('cash', 20) ?>Cash on delivery</li>
        <li><?= icon('truck', 20) ?>Dhaka <?= money(S('delivery_in')) ?>, outside <?= money(S('delivery_out')) ?></li>
        <li><?= icon('shield', 20) ?>100% original</li>
      </ul>
      <div class="tabs">
        <details open><summary>Description</summary><div><?= nl2br(e($p['description'])) ?></div></details>
        <?php if ($p['how_to'] !== ''): ?><details><summary>How to use</summary><div><?= nl2br(e($p['how_to'])) ?></div></details><?php endif; ?>
        <details><summary>Delivery &amp; exchange</summary><div>Inside Dhaka: <?= money(S('delivery_in')) ?>, 1 to 2 days. Outside Dhaka: <?= money(S('delivery_out')) ?>, 2 to 4 days.<?= (int)S('free_over') > 0 ? ' Free delivery on orders over ' . money(S('free_over')) . '.' : '' ?> Pay cash when you receive. If the product is damaged or wrong, tell the delivery person or call us the same day and we will replace it.</div></details>
      </div>
    </div>
  </div>
  <?php $rel = q('p.category_id=? AND p.id<>?', [$p['category_id'], $p['id']], 'p.sold DESC', 8); if ($rel): ?>
  <section class="sec"><?= section_head('You may also like', '', 'c/' . $p['cat_slug']) ?><?= product_grid($rel, 'grid rail') ?></section>
  <?php endif; ?>
</div>
<div class="sticky-buy only-m"><div><b><?= money($p['price']) ?></b><?php if ($off): ?><s><?= money($p['old_price']) ?></s><?php endif; ?></div><?php if (!$out): ?><button class="btn ghost" data-add-pdp>Add to bag</button><button class="btn" data-buy-now>Buy now</button><?php else: ?><button class="btn" disabled>Sold out</button><?php endif; ?></div>
<script type="application/ld+json"><?= json_encode(['@context' => 'https://schema.org', '@type' => 'Product', 'name' => $p['name'], 'image' => img_url($imgs[0]), 'description' => $p['short'], 'brand' => ['@type' => 'Brand', 'name' => $p['brand'] ?: S('shop_name')], 'offers' => ['@type' => 'Offer', 'priceCurrency' => 'BDT', 'price' => (int)$p['price'], 'availability' => $out ? 'https://schema.org/OutOfStock' : 'https://schema.org/InStock']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
    <?php
    page_foot();
    break;

case 'cart':
    page_head('Shopping bag', '', '', 'cart');
    ?>
<div class="wrap page">
  <h1 class="pg-t">Shopping bag</h1>
  <div class="cart-in" id="cartPage">
    <div class="cart-list" id="cartList"><p class="loading">Loading your bag...</p></div>
    <aside class="summary" id="cartSum" hidden>
      <h2>Order summary</h2>
      <div class="row"><span>Subtotal</span><b data-sub></b></div>
      <div class="row muted"><span>Delivery</span><span>Added at checkout</span></div>
      <?php if ((int)S('free_over') > 0): ?><p class="free-note" data-free data-over="<?= (int)S('free_over') ?>"></p><?php endif; ?>
      <a class="btn block" href="<?= url('checkout') ?>">Checkout</a>
      <a class="link" href="<?= url('shop') ?>">Continue shopping</a>
    </aside>
  </div>
</div>
    <?php
    page_foot();
    break;

case 'checkout':
    page_head('Checkout', '', '', 'cart');
    ?>
<div class="wrap page">
  <h1 class="pg-t">Checkout</h1>
  <div class="cart-in">
    <form class="co-form" id="coForm" novalidate data-in="<?= (int)S('delivery_in') ?>" data-out="<?= (int)S('delivery_out') ?>" data-free="<?= (int)S('free_over') ?>">
      <h2>Delivery details</h2>
      <label>Your name<input name="name" required autocomplete="name" placeholder="Full name"></label>
      <label>Mobile number<input name="phone" required inputmode="tel" autocomplete="tel" placeholder="01XXXXXXXXX"></label>
      <label>Full address<textarea name="address" required rows="3" placeholder="House, road, area, thana, district"></textarea></label>
      <fieldset class="area"><legend>Delivery area</legend>
        <label class="radio"><input type="radio" name="area" value="in" checked><span><b><?= e(S('delivery_in_label')) ?></b><small><?= money(S('delivery_in')) ?></small></span></label>
        <label class="radio"><input type="radio" name="area" value="out"><span><b><?= e(S('delivery_out_label')) ?></b><small><?= money(S('delivery_out')) ?></small></span></label>
      </fieldset>
      <label>Note (optional)<textarea name="note" rows="2" placeholder="Shade, gift wrap or delivery time"></textarea></label>
      <input name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
      <div class="pay"><?= icon('cash', 22) ?><div><b>Cash on delivery</b><small>Pay when you receive your order.</small></div></div>
      <p class="err" id="coErr" role="alert" hidden></p>
      <button class="btn block" type="submit" id="coBtn">Confirm order</button>
    </form>
    <aside class="summary" id="coSum">
      <h2>Your order</h2>
      <div class="co-items" id="coItems"><p class="loading">Loading...</p></div>
      <div class="row"><span>Subtotal</span><b data-sub></b></div>
      <div class="row"><span>Delivery</span><b data-del></b></div>
      <div class="row total"><span>Total</span><b data-total></b></div>
    </aside>
  </div>
</div>
    <?php
    page_foot();
    break;

case 'order':
    $st = db()->prepare('SELECT * FROM orders WHERE code=?'); $st->execute([$parts[1] ?? '']); $o = $st->fetch();
    if (!$o || substr($o['phone'], -4) !== (string)($_GET['ph'] ?? '')) { not_found(); break; }
    $items = json_decode($o['items'], true) ?: [];
    page_head('Order ' . $o['code'], '', '', '');
    $msg = "Hi, I placed order {$o['code']}.\nName: {$o['name']}\nTotal: " . money($o['total']);
    ?>
<div class="wrap page narrow">
  <div class="thanks">
    <span class="tick"><?= icon('check', 34) ?></span>
    <h1>Thank you, <?= e($o['name']) ?>!</h1>
    <p>Your order <b><?= e($o['code']) ?></b> is placed. We will call you on <b><?= e($o['phone']) ?></b> to confirm it soon.</p>
    <p class="status">Status: <b><?= e(ORDER_STATUS_EN[$o['status']] ?? $o['status']) ?></b></p>
  </div>
  <div class="summary">
    <?php foreach ($items as $it): ?>
    <div class="co-it"><img src="<?= e(img_small($it['img'] ?? '')) ?>" alt="" width="56" height="56"><div><b><?= e($it['name']) ?></b><small><?= $it['shade'] ? e($it['shade']) . ' · ' : '' ?>Qty <?= (int)$it['qty'] ?></small></div><span><?= money($it['qty'] * $it['price']) ?></span></div>
    <?php endforeach; ?>
    <div class="row"><span>Subtotal</span><b><?= money($o['subtotal']) ?></b></div>
    <div class="row"><span>Delivery</span><b><?= $o['delivery'] ? money($o['delivery']) : 'Free' ?></b></div>
    <div class="row total"><span>Total (cash on delivery)</span><b><?= money($o['total']) ?></b></div>
    <p class="addr"><?= icon('pin', 17) ?><?= e($o['address']) ?></p>
  </div>
  <div class="thanks-act">
    <a class="btn wa-btn" href="<?= e(wa_link($msg)) ?>" target="_blank" rel="noopener"><?= icon('wa', 20) ?>Send order on WhatsApp</a>
    <a class="btn ghost" href="<?= url('shop') ?>">Continue shopping</a>
  </div>
</div>
<script>try{localStorage.removeItem('ll_cart')}catch(e){}</script>
    <?php
    page_foot();
    break;

case 'track':
    $o = null; $err = '';
    if (isset($_GET['code'])) {
        $st = db()->prepare('SELECT * FROM orders WHERE code=?'); $st->execute([strtoupper(trim((string)$_GET['code']))]); $o = $st->fetch();
        if (!$o || $o['phone'] !== norm_phone((string)($_GET['phone'] ?? ''))) { $o = null; $err = 'We could not find that order. Please check the order code and mobile number.'; }
    }
    page_head('Track order', '', '', '');
    ?>
<div class="wrap page narrow">
  <h1 class="pg-t">Track your order</h1>
  <form class="co-form" method="get">
    <label>Order code<input name="code" required placeholder="LL..." value="<?= e($_GET['code'] ?? '') ?>"></label>
    <label>Mobile number<input name="phone" required inputmode="tel" placeholder="01XXXXXXXXX" value="<?= e($_GET['phone'] ?? '') ?>"></label>
    <?php if ($err): ?><p class="err"><?= e($err) ?></p><?php endif; ?>
    <button class="btn block">Track</button>
  </form>
  <?php if ($o): $steps = ['new', 'confirmed', 'shipped', 'delivered']; $at = array_search($o['status'], $steps, true); ?>
  <div class="summary track">
    <h2>Order <?= e($o['code']) ?></h2>
    <?php if ($at === false): ?><p class="status">Status: <b><?= e(ORDER_STATUS_EN[$o['status']] ?? $o['status']) ?></b></p>
    <?php else: ?><ol class="steps"><?php foreach ($steps as $i => $s): ?><li class="<?= $i <= $at ? 'done' : '' ?>"><?= e(ORDER_STATUS_EN[$s]) ?></li><?php endforeach; ?></ol><?php endif; ?>
    <div class="row total"><span>Total</span><b><?= money($o['total']) ?></b></div>
  </div>
  <?php endif; ?>
</div>
    <?php
    page_foot();
    break;

case 'wishlist':
    page_head('Wishlist', '', '', 'wishlist');
    ?>
<div class="wrap page">
  <h1 class="pg-t">My wishlist</h1>
  <div id="wishList"><p class="loading">Loading...</p></div>
</div>
    <?php
    page_foot();
    break;

case 'about':
    page_head('About us', '', '', '');
    ?>
<div class="wrap page narrow prose">
  <h1 class="pg-t">About <?= e(S('shop_name')) ?></h1>
  <p class="lead"><?= e(S('about')) ?></p>
  <h2 id="delivery">Delivery &amp; payment</h2>
  <p><?= e(S('delivery_in_label')) ?>: <?= money(S('delivery_in')) ?> (1 to 2 days). <?= e(S('delivery_out_label')) ?>: <?= money(S('delivery_out')) ?> (2 to 4 days).<?= (int)S('free_over') > 0 ? ' Orders over ' . money(S('free_over')) . ' get free delivery.' : '' ?></p>
  <p>You pay cash to the delivery person when you receive your parcel. We call every customer to confirm the order before we send it.</p>
  <h2 id="exchange">Exchange policy</h2>
  <p>Please check your parcel in front of the delivery person. If a product is damaged, leaking or not what you ordered, tell us the same day and we will replace it. For hygiene reasons, opened or used beauty products cannot be returned.</p>
  <h2>Original products</h2>
  <p>We only sell sealed, original products. Each order is checked by hand and packed with care.</p>
</div>
    <?php
    page_foot();
    break;

case 'contact':
    page_head('Contact', '', '', '');
    ?>
<div class="wrap page narrow">
  <h1 class="pg-t">Contact us</h1>
  <div class="contact">
    <a href="tel:<?= e(norm_phone(S('phone'))) ?>"><?= icon('phone', 26) ?><b>Call</b><span><?= e(S('phone')) ?></span></a>
    <a href="<?= e(wa_link('Hi ' . S('shop_name'))) ?>" target="_blank" rel="noopener" class="wa"><?= icon('wa', 26) ?><b>WhatsApp</b><span>Chat with us</span></a>
    <?php if (S('facebook') !== ''): ?><a href="<?= e(S('facebook')) ?>" target="_blank" rel="noopener"><?= icon('fb', 26) ?><b>Facebook</b><span>Message our page</span></a><?php endif; ?>
    <?php if (S('email') !== ''): ?><a href="mailto:<?= e(S('email')) ?>"><?= icon('mail', 26) ?><b>Email</b><span><?= e(S('email')) ?></span></a><?php endif; ?>
    <div><?= icon('pin', 26) ?><b>Address</b><span><?= e(S('address')) ?></span></div>
  </div>
</div>
    <?php
    page_foot();
    break;

default:
    not_found();
}

function not_found(): void {
    http_response_code(404);
    page_head('Page not found');
    echo '<div class="wrap page narrow"><div class="empty-box">' . icon('search', 40) . '<h1>Page not found</h1><p>The page you are looking for is not here.</p><a class="btn" href="' . url() . '">Go to home</a></div></div>';
    page_foot();
}
