<?php
// Loom & Lustre storefront: one front controller for every page.
require __DIR__ . '/inc/core.php';
require __DIR__ . '/inc/layout.php';
require_once __DIR__ . '/inc/nk.php';

$route = trim((string)($_GET['route'] ?? ''), '/');
if ($route === '' && isset($_SERVER['PATH_INFO'])) $route = trim($_SERVER['PATH_INFO'], '/');
$parts = $route === '' ? [] : explode('/', $route);
$page = $parts[0] ?? '';

const P_SQL = 'SELECT p.*, c.name AS cat_name, c.slug AS cat_slug FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.active=1';

function q(string $where = '', array $args = [], string $order = 'p.sort, p.id DESC', int $limit = 0): array {
    $sql = P_SQL . " AND COALESCE(c.slug,'') " . (ll_lux() ? '' : 'NOT ') . "LIKE 'lx-%'" . ($where ? " AND ($where)" : '') . " ORDER BY $order" . ($limit ? " LIMIT $limit" : '');
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
        $pay = (string)($in['payment'] ?? 'cod'); if (!isset(PAYMENTS[$pay]) || S($pay . '_on') !== '1') $pay = S('cod_on') === '1' ? 'cod' : $pay;
        $trx = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', mb_substr((string)($in['trx'] ?? ''), 0, 30)));
        if ($pay !== 'cod' && strlen($trx) < 6) json_out(['ok' => false, 'error' => 'Please write the Transaction ID from your ' . PAYMENTS[$pay] . ' message.'], 422);
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
            $okv = array_merge(list_csv((string)$p['shades']), list_csv((string)($p['sizes'] ?? '')));
            if ($shade !== '' && array_diff(explode(' · ', $shade), $okv)) $shade = '';
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
        $pdo->prepare('INSERT INTO orders(code,name,phone,address,area,note,items,subtotal,delivery,total,ip,created,updated,payment,trx_id) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
            ->execute([$code, $name, $phone, $addr, $area, $note, json_encode($items, JSON_UNESCAPED_UNICODE), $sub, $del, $sub + $del, client_ip(), $now, $now, $pay, $pay === 'cod' ? '' : $trx]);
        $dec = $pdo->prepare('UPDATE products SET stock=MAX(0,stock-?), sold=sold+? WHERE id=?');
        foreach ($items as $it) $dec->execute([$it['qty'], $it['qty'], $it['id']]);
        $pdo->commit();
        if (S('notify_email') !== '' && function_exists('mail')) {
            $lines = array_map(fn($i) => "- {$i['name']}" . ($i['shade'] ? " ({$i['shade']})" : '') . " x{$i['qty']} = " . $i['qty'] * $i['price'], $items);
            @mail(S('notify_email'), "New order $code - " . S('shop_name'), "Name: $name\nPhone: $phone\nAddress: $addr\n\n" . implode("\n", $lines) . "\n\nTotal: " . ($sub + $del), 'Content-Type: text/plain; charset=UTF-8');
        }
        $o = ['name' => $name, 'code' => $code, 'total' => $sub + $del];
        send_sms($phone, fill_tpl(S('sms_order'), $o));
        $eid = preg_replace('/[^A-Za-z0-9_-]/', '', (string)($in['event_id'] ?? '')) ?: 'order-' . $code;
        capi_event('Purchase', $eid, ['currency' => 'BDT', 'value' => $sub + $del, 'content_ids' => array_map(fn($i) => (string)$i['id'], $items), 'content_type' => 'product', 'num_items' => array_sum(array_column($items, 'qty'))], ['phone' => $phone, 'name' => $name]);
        json_out(['ok' => true, 'value' => $sub + $del, 'code' => $code, 'url' => url('order/' . $code . '?ph=' . substr($phone, -4))]);
    }
    if ($a === 'review' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $in = json_decode((string)file_get_contents('php://input'), true) ?: [];
        if (!empty($in['website'])) json_out(['ok' => false, 'error' => 'Spam'], 400);
        if (!rate_ok('review', 4, 3600)) json_out(['ok' => false, 'error' => 'Too many reviews from this connection. Please try later.'], 429);
        $pid = (int)($in['product'] ?? 0); $name = trim(mb_substr((string)($in['name'] ?? ''), 0, 60)); $body = trim(mb_substr((string)($in['body'] ?? ''), 0, 1000));
        $rating = max(1, min(5, (int)($in['rating'] ?? 5))); $phone = norm_phone((string)($in['phone'] ?? ''));
        $ok = db()->prepare('SELECT id FROM products WHERE id=? AND active=1'); $ok->execute([$pid]);
        if (!$ok->fetch() || $name === '' || mb_strlen($body) < 5) json_out(['ok' => false, 'error' => 'Please write your name and a few words.'], 422);
        db()->prepare('INSERT INTO reviews(product_id,name,phone,rating,body,approved,ip,created) VALUES(?,?,?,?,?,0,?,?)')->execute([$pid, $name, $phone, $rating, $body, client_ip(), date('Y-m-d H:i:s')]);
        json_out(['ok' => true]);
    }
    json_out(['error' => 'Not found'], 404);
}

/* ---------- robots.txt + sitemap.xml ---------- */
if ($route === 'robots.txt') {
    header('Content-Type: text/plain; charset=utf-8');
    echo "User-agent: *\nAllow: /\nDisallow: " . url('admin/') . "\nDisallow: " . url('api/') . "\n\nSitemap: " . abs_url('sitemap.xml') . "\n";
    exit;
}
if ($route === 'sitemap.xml') {
    header('Content-Type: application/xml; charset=utf-8');
    $u = function (string $path, string $mod = '', string $img = '', string $pri = '0.6') {
        $x = '<url><loc>' . e(abs_url($path)) . '</loc>' . ($mod ? '<lastmod>' . substr($mod, 0, 10) . '</lastmod>' : '') . '<priority>' . $pri . '</priority>';
        if ($img !== '') $x .= '<image:image><image:loc>' . e(preg_match('~^https?://~', $img) ? $img : site_origin() . $img) . '</image:loc></image:image>';
        return $x . "</url>\n";
    };
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
    echo $u('', '', '', '1.0');
    foreach (['shop' => '0.9', 'categories' => '0.8', 'luxe' => '0.8', 'new' => '0.7', 'sale' => '0.7', 'about' => '0.4', 'contact' => '0.4'] as $r => $pr) echo $u($r, '', '', $pr);
    foreach (array_keys(nk_depts()) as $dk) echo $u('d/' . $dk, '', '', '0.9');
    foreach (db()->query('SELECT slug FROM categories WHERE active=1') as $c) echo $u('c/' . $c['slug'], '', '', '0.8');
    foreach (db()->query('SELECT slug FROM collections WHERE active=1') as $c) echo $u('collection/' . $c['slug'], '', '', '0.7');
    foreach (db()->query('SELECT * FROM products WHERE active=1') as $pr) echo $u('p/' . $pr['slug'], (string)($pr['updated'] ?? $pr['created'] ?? ''), img_url(p_image($pr)), '0.8');
    echo '</urlset>';
    exit;
}

/* ---------- pages ---------- */
switch ($page) {
case '':
    require_once LL_ROOT . '/inc/admin_data.php';
    $slides = array_values(array_filter(ll_banners(), fn($b) => !empty($b['on']) && ($b['img'] ?? '') !== ''));
    if ($slides) $GLOBALS['ll_preload'] = [img_small($slides[0]['img']), img_url($slides[0]['img'])];
    page_head('', '', '', 'home');
    $cats = categories();
    ?>
<h1 class="sr"><?= e(S('meta_title') !== '' ? S('meta_title') : S('shop_name') . ': fashion and beauty shop in Bangladesh') ?></h1>
<?php
    // biggest real discount behind each banner link (c/<slug>, sale, shop) for the offer pill
    $offFor = function (string $lk): int {
        $w = 'p.active=1 AND p.old_price > p.price'; $a = [];
        if (preg_match('~^c/([a-z0-9-]+)~', ltrim($lk, '#/'), $m)) { $w .= ' AND p.category_id=(SELECT id FROM categories WHERE slug=?)'; $a[] = $m[1]; }
        elseif (preg_match('~^d/([a-z]+)~', ltrim($lk, '#/'), $m) && isset(LL_DEPTS[$m[1]])) { return nk_dept_off($m[1]); }
        elseif (!preg_match('~^(sale|shop)~', ltrim($lk, '#/'))) return 0;
        $st = db()->prepare("SELECT MAX((p.old_price - p.price) * 100 / p.old_price) FROM products p WHERE $w"); $st->execute($a);
        return (int)$st->fetchColumn();
    };
?>
<section class="hero" aria-label="Offers">
  <div class="slides" data-slider>
    <?php foreach ($slides as $n => $sl): $lk = (string)$sl['link']; $off = $offFor($lk); ?>
    <a class="slide g<?= $n % 4 ?><?= $n === 0 ? ' on' : '' ?>" href="<?= e(preg_match('~^https?://~', $lk) ? $lk : url(ltrim($lk, '#/'))) ?>">
      <?php $hi = $sl['img']; $hs = img_small($hi); ?><span class="slide-p"><img src="<?= e($hs) ?>"<?php if ($hs !== img_url($hi)): ?> srcset="<?= e($hs) ?> 900w, <?= e(img_url($hi)) ?> 2000w" sizes="(max-width:860px) 60vw, 60vw"<?php endif; ?> alt="<?= e($sl['title'] ?: S('shop_name')) ?>" width="1600" height="900" <?= $n === 0 ? 'fetchpriority="high"' : 'loading="lazy" decoding="async"' ?>></span>
      <span class="slide-t">
        <?php if (($sl['kicker'] ?? '') !== ''): ?><small><?= e($sl['kicker']) ?></small><?php endif; ?>
        <?php if ($sl['title'] !== ''): ?><b><?= e($sl['title']) ?></b><?php endif; ?>
        <?php if ($off >= 5): ?><em class="pill">Up to <?= $off ?>% off</em><?php endif; ?>
        <?php if (($sl['text'] ?? '') !== ''): ?><span><?= e($sl['text']) ?></span><?php endif; ?>
        <i class="go"><?= e(($sl['btn'] ?? '') !== '' ? $sl['btn'] : 'Shop now') ?> <?= icon('chev', 16) ?></i>
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

<?php $on = fn(string $k) => S("h_{$k}_on") === '1'; $T = fn(string $k) => S("h_{$k}_t"); $Sb = fn(string $k) => S("h_{$k}_s"); $offs = nk_offs(); ?>
<?php if ($on('cats')): ?>
<section class="wrap nk-strip" aria-label="Categories">
  <div class="nk-strip-in">
    <?php $sw = nk_cat_imgs('westernwear', 3)[2] ?? ''; if ($sw !== ''): ?><a href="<?= url('sale') ?>" class="nk-sale only-m"><span><?= nk_img($sw, '', 300, 360) ?><i>SALE</i></span><b>Sale</b></a><?php endif; ?>
    <?php $cby = []; foreach ($cats as $c) $cby[$c['slug']] = $c;
    // Same order and names as the Nykaa Fashion row (lingerie skipped).
    foreach (['westernwear' => ['Westernwear', ''], 'ethnic' => ['Indianwear', ''], 'men' => ['Men', ''], 'footwear' => ['Footwear', 'assets/img/p/fw-pink-stilettos.webp'], 'bags' => ['Bags', 'assets/img/p/bag-woven-tote.webp'], 'kids' => ['Kids', ''], 'activewear' => ['Activewear', ''], 'jewellery' => ['Jewellery', ''], 'sneakers' => ['Sneakers', ''], 'watches' => ['Watches', ''], 'home' => ['Home', '']] as $sl => [$nm, $im]):
        if (!isset($cby[$sl]) || ($offs[$sl]['n'] ?? 0) === 0) continue; $im = nk_tile_img($sl, ($im !== '' && is_file(LL_ROOT . '/' . $im)) ? $im : $cby[$sl]['image']); ?>
    <a href="<?= url('c/' . $sl) ?>"><span><?= nk_img($im, '', 300, 360) ?></span><b><?= e($nm) ?></b></a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($on('celeb')): $tl = nk_tiles(LL_CELEB); if ($tl): ?>
<section class="wrap nk-sec">
  <h2 class="nk-h"><?= e($T('celeb') ?: 'Celebration-ready Categories') ?></h2>
  <div class="nk-spot nk-tiles">
    <?php foreach ($tl as [$lb, $lk, $im, $of]): ?>
    <a href="<?= url($lk) ?>"><span class="nk-spot-p"><?= nk_img($im, $lb) ?><i><?= e($lb) ?></i></span><b><?= e(nk_off_text($of, 'New styles inside')) ?></b><small>Shop now</small></a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; endif; ?>

<?php if ($on('spot')): $sp = array_filter($cats, fn($c) => ($offs[$c['slug']]['n'] ?? 0) > 0); usort($sp, fn($a, $b) => ($offs[$b['slug']]['off'] <=> $offs[$a['slug']]['off']) ?: ($a['sort'] <=> $b['sort'])); $sp = array_slice($sp, 0, 10); if ($sp): ?>
<section class="wrap nk-sec">
  <h2 class="nk-h"><?= e($T('spot') ?: 'Iconic Styles, Unreal Savings') ?></h2>
  <div class="nk-spot">
    <?php foreach ($sp as $c): $im = nk_cat_imgs($c['slug'], 1)[0] ?? $c['image']; ?>
    <a href="<?= url('c/' . $c['slug']) ?>"><span class="nk-spot-p"><?= nk_img($im, $c['name']) ?><i><?= e($c['name']) ?></i></span><b><?= e(nk_off_text($offs[$c['slug']]['off'])) ?></b><small><?= e($c['tagline']) ?></small></a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; endif; ?>

<?php if ($on('eid')): $tl = nk_tiles(LL_EID); if ($tl): ?>
<section class="wrap nk-sec">
  <h2 class="nk-h"><?= e($T('eid') ?: 'Build Your Eid Wardrobe') ?></h2>
  <div class="nk-spot nk-tiles">
    <?php foreach ($tl as [$lb, $lk, $im, $of]): ?>
    <a href="<?= url($lk) ?>"><span class="nk-spot-p"><?= nk_img($im, $lb) ?><i><?= e($lb) ?></i></span><b><?= e(nk_off_text($of, 'New styles inside')) ?></b><small>Shop now</small></a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; endif; ?>

<?php if ($on('styles')): $st = array_values(array_filter($cats, fn($c) => count(nk_cat_imgs($c['slug'], 2)) >= 2)); if ($st): ?>
<section class="wrap nk-sec">
  <h2 class="nk-h"><?= e($T('styles') ?: 'Styles To Celebrate In') ?></h2>
  <div class="nk-row nk-styles">
    <?php foreach ($st as $c): $ims = nk_cat_imgs($c['slug'], 2); ?>
    <a href="<?= url('c/' . $c['slug']) ?>"><span class="nk-sty"><span class="nk-sty-in"><?= nk_img($ims[1], '', 160, 200) ?></span><b><?= e($c['name']) ?></b><span class="nk-sty-p"><?= nk_img($ims[0], $c['name']) ?></span></span><em><?= e(nk_off_text($offs[$c['slug']]['off'] ?? 0)) ?></em></a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; endif; ?>

<?php $pk = $on('picks') ? nk_coll('stylists-picks') : null; $pkp = $pk ? nk_coll_products((int)$pk['id'], 8) : []; if ($pkp): ?>
<section class="wrap nk-sec">
  <h2 class="nk-h"><?= e($T('picks') ?: $pk['name']) ?></h2>
  <div class="nk-row nk-picks">
    <?php foreach ($pkp as $p): ?>
    <a href="<?= url('p/' . $p['slug']) ?>"><?= nk_img(p_image($p), $p['name']) ?><span><b><?= e($p['name']) ?></b><small><?= e($p['short']) ?></small></span></a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php $lx = $on('luxe') ? nk_coll('luxe-edit') : null; if ($lx): $tiles = array_values(array_filter(array_map('nk_coll', ['wedding-season', 'weekend-casual', 'party-night', 'office-ready', 'eid-edit', 'bridal-glow']))); $tiles = array_slice($tiles, 0, 4); ?>
<section class="wrap nk-sec">
  <div class="nk-luxe">
    <a class="nk-luxe-big" href="<?= url('luxe') ?>"><?= nk_img((string)$lx['image'], $lx['name'], 1200, 1500, true) ?><span><b>LUXE EDIT</b><small><?= e($Sb('luxe') ?: $lx['tagline']) ?></small><i>Shop Now <?= icon('arrow', 16) ?></i></span></a>
    <div class="nk-luxe-g">
      <?php foreach ($tiles as $t): ?>
      <a href="<?= url('collection/' . $t['slug']) ?>"><?= nk_img((string)$t['image'], $t['name']) ?><b><?= e($t['name']) ?></b></a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($on('circles')): ?>
<section class="wrap nk-sec">
  <small class="nk-k">Explore More</small>
  <h2 class="nk-h"><?= e($T('circles') ?: 'Find everything for your every need') ?></h2>
  <div class="nk-circ">
    <?php $used = []; foreach (nk_depts() as $k => $d): $used[] = $d[3]; ?><a href="<?= url('d/' . $k) ?>"><span><?= nk_img($d[3], '', 200, 200) ?></span><b><?= e($d[0]) ?></b></a><?php endforeach; ?>
    <?php if ($lx): ?><a href="<?= url('luxe') ?>"><span><?= nk_img((string)$lx['image'], '', 200, 200) ?></span><b>Luxe Store</b></a><?php endif; ?>
    <?php foreach (nk_discover() as [$dt, $dl, $di]): if ($dt !== 'Hidden Gems') continue; ?><a href="<?= url($dl) ?>"><span><?= nk_img($di, '', 200, 200) ?></span><b><?= e($dt) ?></b></a><?php endforeach; ?>
    <?php foreach (collections() as $c): if (in_array($c['slug'], ['luxe-edit', 'stylists-picks'], true)) continue; $ci = (string)$c['image']; if (in_array($ci, $used, true)) { foreach (nk_coll_products((int)$c['id'], 12) as $cp) { if (!in_array(p_image($cp), $used, true)) { $ci = p_image($cp); break; } } } $used[] = $ci; ?><a href="<?= url('collection/' . $c['slug']) ?>"><span><?= nk_img($ci, '', 200, 200) ?></span><b><?= e($c['name']) ?></b></a><?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?= nk_seo_text() ?>
    <?php
    page_foot();
    break;

case 'categories':
    page_head('All Categories', 'Shop women, men, kids and beauty at ' . S('shop_name') . ': sarees, westernwear, shoes, bags, jewellery, lipstick and more.', '', 'categories');
    ?>
<div class="wrap nk-cats-pg">
  <h1>Categories</h1>
  <div class="nk-depts">
    <?php foreach (nk_depts() as $k => $d): ?>
    <a class="nk-dept" href="<?= url('d/' . $k) ?>" data-sub="<?= $k ?>" style="--bg:<?= e($d[4]) ?>"><span class="nk-dept-t"><b><?= e($d[0]) ?></b><small><?= e($d[1]) ?></small></span><?= nk_img($d[3], '', 300, 375) ?></a>
    <?php endforeach; ?>
  </div>
  <h2 class="nk-k2">Discover</h2>
  <div class="nk-disc">
    <?php
    $disc = nk_discover();
    foreach ($disc as [$t, $lk, $im]): ?><a href="<?= url($lk) ?>"><?= nk_img($im, $t, 300, 400) ?><b><?= e($t) ?></b></a><?php endforeach; ?>
  </div>
</div>
    <?php
    page_foot();
    break;

case 'luxe-categories':
    ll_lux(true);
    page_head('Luxe Categories', 'Designer bags, fine jewellery, luxury watches, bridal couture and evening gowns at ' . S('shop_name') . ' Luxe.', '', 'luxe-categories');
    $lxbg = ['#2a2224', '#26231f', '#1f2326', '#2b1c1f', '#231f29', '#2a2422', '#202524'];
    ?>
<div class="wrap nk-cats-pg lx-cats-pg">
  <h1>Luxe Categories</h1>
  <div class="nk-depts">
    <?php foreach (categories() as $i => $c): $lp = q('c.slug=?', [$c['slug']], 'p.featured DESC, p.sold DESC', 1); ?>
    <a class="nk-dept" href="<?= url('c/' . $c['slug']) ?>" style="--bg:<?= $lxbg[$i % 7] ?>"><span class="nk-dept-t"><b><?= e($c['name']) ?></b><small><?= e((string)$c['tagline']) ?></small></span><?= nk_img($lp ? p_image($lp[0]) : (string)$c['image'], '', 300, 375) ?></a>
    <?php endforeach; ?>
  </div>
  <h2 class="nk-k2">Discover</h2>
  <div class="nk-disc">
    <?php foreach ([['Luxe Edit', 'luxe-all', 'assets/img/p/lx-gw-lilac-ball.webp'], ['Bridal Couture', 'c/lx-bridal', 'assets/img/p/lx-br-zardozi-red.webp'], ['Evening Luxe', 'c/lx-gowns', 'assets/img/p/lx-gw-emerald-sequin.webp'], ['Signature Bags', 'c/lx-bags', 'assets/img/p/lx-bag-chestnut-doctor.webp'], ['Fine Jewellery', 'c/lx-jewellery', 'assets/img/p/lx-jw-cascade-drops.webp']] as [$t, $lk, $im]): ?><a href="<?= url($lk) ?>"><?= nk_img($im, $t, 300, 400) ?><b><?= e($t) ?></b></a><?php endforeach; ?>
  </div>
</div>
    <?php
    page_foot();
    break;

case 'd':
    $dk = $parts[1] ?? ''; $dp = nk_depts()[$dk] ?? null;
    if (!$dp) { not_found(); break; }
    $dc = nk_dept_cats($dk); $doff = nk_dept_off($dk); $offs = nk_offs();
    page_head($dp[0], $dp[1] . ' at ' . S('shop_name') . '. Cash on delivery all over Bangladesh.', img_url($dp[3]), 'd/' . $dk);
    ?>
<div class="wrap nk-dpg">
  <nav class="crumbs"><a href="<?= url() ?>">Home</a><?= icon('chev', 14) ?><span><?= e($dp[0]) ?></span></nav>
  <div class="nk-dhero" style="--bg:<?= e($dp[4]) ?>">
    <div class="nk-dhero-t"><small><?= e(S('shop_name')) ?></small><h1><?= e($dp[0]) ?></h1><p><?= e($dp[1]) ?></p><?php if ($doff >= 5): ?><em>UP TO <?= $doff ?>% OFF</em><?php endif; ?><a class="btn" href="#d-all">Shop now</a></div>
    <?php $few = count($dc) < 3; $dpr = $few ? q('c.slug IN (' . implode(',', array_fill(0, count($dc), '?')) . ')', array_column($dc, 'slug'), 'p.featured DESC, p.sold DESC', 10) : []; ?>
    <div class="nk-dhero-p"><?php if ($few): foreach (array_slice($dpr, 0, 3) as $i => $p): ?><span><?= nk_img(p_image($p), $p['name'], 600, 750, $i === 0) ?></span><?php endforeach; else: foreach (array_slice($dc, 0, 3) as $i => $c): $im = p_image(nk_dept_products($dk, $c['slug'], 1)[0] ?? []) ?: $c['image']; ?><span><?= nk_img($im, $c['name'], 600, 750, $i === 0) ?></span><?php endforeach; endif; ?></div>
  </div>
  <div class="nk-sub">
    <?php if ($few): foreach ($dpr as $p): $po = $p['old_price'] > $p['price'] ? (int)round(($p['old_price'] - $p['price']) * 100 / $p['old_price']) : 0; ?><a href="<?= url('p/' . $p['slug']) ?>"><b><?= e(preg_replace('~\s*\(.*\)$~', '', $p['name'])) ?></b><?= nk_img(p_image($p), '', 300, 375) ?><small><?= e(nk_off_text($po, 'Shop now')) ?></small></a><?php endforeach; endif; ?>
    <?php if (!$few): $ord = ['westernwear', 'ethnic', 'men', 'footwear', 'bags', 'kids', 'activewear', 'jewellery', 'sneakers', 'watches', 'eyewear', 'home']; $lbl = ['ethnic' => 'Indianwear', 'eyewear' => 'Sunglasses', 'men' => 'Clothing'];
      $dc2 = $dc; usort($dc2, fn($x, $y) => ((array_search($x['slug'], $ord) === false) ? 99 : array_search($x['slug'], $ord)) <=> ((array_search($y['slug'], $ord) === false) ? 99 : array_search($y['slug'], $ord)));
      foreach ($dc2 as $c): $fb = p_image(nk_dept_products($dk, $c['slug'], 1)[0] ?? []) ?: $c['image']; ?><a href="<?= url('c/' . $c['slug']) ?>"><?= nk_img($dk === 'men' ? $fb : nk_tile_img($c['slug'], $fb), '', 300, 375) ?><b><?= e($lbl[$c['slug']] ?? $c['name']) ?></b><small><?= e(nk_off_text($offs[$c['slug']]['off'] ?? 0, 'Shop now')) ?></small></a><?php endforeach;
      $nw = q('p.is_new=1', [], 'p.featured DESC, p.id DESC', 1); $sl = q('p.old_price > p.price', [], 'p.old_price - p.price DESC', 1); ?>
      <?php if ($nw): ?><a href="<?= url('new') ?>"><?= nk_img(p_image($nw[0]), '', 300, 375) ?><b>New In</b><small>Just landed</small></a><?php endif; ?>
      <?php if ($sl): ?><a href="<?= url('sale') ?>"><?= nk_img(p_image($sl[0]), '', 300, 375) ?><b>Sale</b><small><?= e(nk_off_text($doff, 'Shop now')) ?></small></a><?php endif; ?>
    <?php endif; ?>
  </div>
  <?php foreach ($dc as $i => $c): $pl = nk_dept_products($dk, $c['slug'], 8); if (!$pl) continue; ?>
  <section class="sec"<?= $i === 0 ? ' id="d-all"' : '' ?>>
    <?= section_head($c['name'], $c['tagline'], 'c/' . $c['slug']) ?>
    <?= product_grid($pl, 'grid rail') ?>
  </section>
  <?php endforeach; ?>
</div>
    <?php
    page_foot();
    break;

case 'luxe':
    ll_lux(true);
    page_head('Luxe Store', 'Designer bags, fine jewellery, luxury watches, bridal couture and evening gowns at ' . S('shop_name') . ' Luxe.', img_url('assets/img/b/lx-hero.webp'), 'luxe');
    $lc = categories(); $lby = []; foreach ($lc as $c) $lby[$c['slug']] = $c;
    $offs = nk_offs();
    $ban = function (string $img, string $title, string $sub, string $link, string $btn = 'Shop Now') { ?>
  <a class="lx-ban" href="<?= url($link) ?>" style="--bgi:url('<?= e(img_small($img)) ?>')"><?= nk_img($img, $title, 1200, 1500, true) ?><span><b><?= e($title) ?></b><?php if ($sub !== ''): ?><small><?= e($sub) ?></small><?php endif; ?><i><?= e($btn) ?></i></span></a>
<?php };
    $row = function (string $title, string $slugs, string $cls = 'lx-jw', int $n = 8) {
        $ps = q("c.slug IN ($slugs)", [], 'p.featured DESC, p.price DESC', $n); if (!$ps) return; ?>
  <section class="wrap lx-sec"><h2 class="lx-h"><?= e($title) ?></h2><div class="lx-row <?= $cls ?>">
    <?php foreach ($ps as $p): ?><a href="<?= url('p/' . $p['slug']) ?>"><span><?= nk_img(p_image($p), $p['name']) ?></span><b><?= e($p['name']) ?></b><small><?= money($p['price']) ?></small></a><?php endforeach; ?>
  </div></section>
<?php };
    ?>
<div class="lx">
  <section class="wrap lx-hero"><a href="<?= url('c/lx-gowns') ?>"><img src="<?= url('assets/img/b/lx-hero.webp') ?>" srcset="<?= url('assets/img/b/lx-hero-s.webp') ?> 900w, <?= url('assets/img/b/lx-hero.webp') ?> 2000w" sizes="100vw" alt="Luxe couture gowns and bridal wear" width="2000" height="1125" fetchpriority="high"><span><small>Introducing</small><h1><?= e(S('shop_name')) ?> <em>LUXE</em></h1><span>Designer bags, fine jewellery and couture</span><i>Shop Now</i></span></a></section>

  <section class="wrap lx-cats"><div class="lx-cats-in">
    <a class="lx-cat0" href="<?= url('luxe-all') ?>"><b>LUXE</b><small>EDIT</small></a>
    <?php foreach ($lc as $c): if (($offs[$c['slug']]['n'] ?? 0) === 0) continue; ?><a href="<?= url('c/' . $c['slug']) ?>"><small><?= e(mb_strtoupper(preg_replace('~^(Designer|Fine|Luxury) ~', '', $c['name']))) ?></small><?= nk_img($c['image'], '', 300, 375) ?></a><?php endforeach; ?>
  </div></section>

  <section class="wrap lx-sec"><?php $ban('assets/img/p/lx-br-zardozi-red.webp', 'Bridal Couture', nk_off_text($offs['lx-bridal']['off'] ?? 0, 'Hand zardozi lehengas, made to order'), 'c/lx-bridal'); ?></section>

  <section class="wrap lx-sec"><div class="lx-row lx-occ">
    <?php foreach ([['The Couture Edit', 'c/lx-gowns', 'lx-gw-lilac-ball'], ['Veritable Watches', 'c/lx-watches', 'lx-wt-emerald'], ['Sparkling Jewellery', 'c/lx-jewellery', 'lx-jw-cascade-drops'], ['Bridal Lehengas', 'c/lx-bridal', 'lx-br-crimson-lehenga'], ['Perfect Companion', 'c/lx-bags', 'lx-bag-tan-hobo'], ['Red Carpet Heels', 'c/lx-heels', 'lx-hl-crystal-mesh']] as [$t, $l, $im]): ?>
    <a href="<?= url($l) ?>"><?= nk_img('assets/img/p/' . $im . '.webp', $t) ?><b><?= e($t) ?></b></a>
    <?php endforeach; ?>
  </div></section>

  <section class="wrap lx-sec"><h2 class="lx-h">Luxe Highlights</h2><div class="lx-row lx-labels">
    <?php foreach (['lx-bags' => 'lx-bag-cognac-tote', 'lx-jewellery' => 'lx-jw-laurel-earrings', 'lx-watches' => 'lx-wt-rose-crystal'] as $sl => $im): if (!isset($lby[$sl])) continue; $c = $lby[$sl]; $mx = $offs[$sl]['off'] ?? 0; ?>
    <a href="<?= url('c/' . $sl) ?>"><span class="lx-lt"><b><?= e($c['name']) ?></b><small><?= e($c['tagline']) ?></small><?php if ($mx >= 5): ?><em>Up to <?= $mx ?>% off</em><?php endif; ?><i><?= icon('arrow', 20) ?></i></span><?= nk_img('assets/img/p/' . $im . '.webp', '') ?></a>
    <?php endforeach; ?>
  </div></section>

  <?php $bags = q("c.slug='lx-bags'", [], 'p.featured DESC, p.price DESC', 8); if ($bags): ?>
  <section class="wrap lx-sec"><h2 class="lx-h">Signature Bags</h2>
    <div class="lx-trio"><?php foreach (array_slice($bags, 0, 3) as $p): ?><a href="<?= url('p/' . $p['slug']) ?>"><?= nk_img(p_image($p), $p['name'], 1200, 1500, true) ?></a><?php endforeach; ?></div>
    <div class="lx-row lx-tiles"><?php foreach (array_slice($bags, 3) as $p): ?><a href="<?= url('p/' . $p['slug']) ?>"><?= nk_img(p_image($p), $p['name']) ?><b><?= e($p['name']) ?></b><small><?= money($p['price']) ?></small></a><?php endforeach; ?></div>
  </section>
  <?php endif; ?>

  <?php $ns = q("c.slug IN ('lx-gowns','lx-heels','lx-eyewear')", [], 'p.is_new DESC, p.featured DESC, p.price DESC', 8); if ($ns): ?>
  <section class="wrap lx-sec"><h2 class="lx-h">New Season Styles</h2><div class="lx-row lx-new">
    <?php foreach ($ns as $p): ?><a href="<?= url('p/' . $p['slug']) ?>"><?= nk_img(p_image($p), $p['name']) ?><small><?= e(preg_replace('~^(Designer|Fine|Luxury) ~', '', $p['cat_name'])) ?></small><i>Shop Now</i></a><?php endforeach; ?>
  </div></section>
  <?php endif; ?>

  <section class="wrap lx-sec"><?php $ban('assets/img/p/lx-gw-emerald-sequin.webp', 'Explore Evening Luxe', 'Sequins, tulle and ball gowns', 'c/lx-gowns'); ?>
    <?php $et = q("c.slug IN ('lx-bridal','lx-gowns')", [], 'p.price DESC', 8); ?>
    <div class="lx-row lx-des"><?php foreach ($et as $p): ?><a href="<?= url('p/' . $p['slug']) ?>"><b><?= e($p['name']) ?></b><?= nk_img(p_image($p), $p['name']) ?></a><?php endforeach; ?></div>
  </section>

  <?php $row('Statement Timekeepers', "'lx-watches'", 'lx-jw lx-wt'); ?>
  <?php $row('Fine Jewellery', "'lx-jewellery'"); ?>
  <?php $row('Luxury Sunglasses', "'lx-eyewear'"); ?>
</div>
    <?php
    page_foot();
    break;

case 'shop': case 'c': case 'new': case 'sale': case 'search': case 'collection': case 'luxe-all':
    $where = ''; $args = []; $title = 'All Products'; $sub = 'Every product in our shop'; $active = 'shop'; $banner = '';
    if ($page === 'luxe-all') { ll_lux(true); $title = 'Luxe Edit'; $sub = 'Every piece in our Luxe store'; $active = 'luxe-all'; }
    if ($page === 'c') {
        $st = db()->prepare('SELECT * FROM categories WHERE slug=? AND active=1'); $st->execute([$parts[1] ?? '']); $cat = $st->fetch();
        if (!$cat) { not_found(); break; }
        if (strpos($cat['slug'], 'lx-') === 0) ll_lux(true);
        $where = 'p.category_id=?'; $args = [$cat['id']]; $title = $cat['name']; $sub = $cat['tagline']; $active = 'c/' . $cat['slug']; $banner = $cat['image'];
    } elseif ($page === 'collection') {
        $st = db()->prepare('SELECT * FROM collections WHERE slug=? AND active=1'); $st->execute([$parts[1] ?? '']); $col = $st->fetch();
        if (!$col) { not_found(); break; }
        $where = 'p.id IN (SELECT product_id FROM col_items WHERE collection_id=?)'; $args = [$col['id']]; $title = $col['name']; $sub = $col['tagline']; $active = ''; $banner = $col['image'];
    } elseif ($page === 'new') { $where = 'p.is_new=1'; $title = 'New In'; $sub = 'Fresh arrivals'; $active = 'new'; }
    elseif ($page === 'sale') { $where = 'p.old_price > p.price'; $title = 'Sale'; $sub = 'Beauty at special prices'; $active = 'sale'; }
    elseif ($page === 'search') {
        $qq = trim(mb_substr((string)($_GET['q'] ?? ''), 0, 60));
        $title = $qq === '' ? 'Search' : 'Results for "' . $qq . '"'; $sub = ''; $active = '';
        if ($qq !== '') { $where = 'p.name LIKE ? OR p.short LIKE ? OR p.tags LIKE ? OR c.name LIKE ? OR p.shades LIKE ?'; $l = '%' . $qq . '%'; $args = [$l, $l, $l, $l, $l]; }
        $scat = preg_replace('~[^a-z0-9-]~', '', (string)($_GET['c'] ?? ''));
        if ($qq !== '' && $scat !== '') { $where = "($where) AND c.slug=?"; $args[] = $scat; }
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
    if (ll_lux()) {
        $base = url($active); $qs = fn(array $x) => '?' . http_build_query(array_filter(array_merge(['sort' => $sort, 'max' => $max ?: ''], $x), fn($v) => $v !== '' && $v !== 0));
        ?>
<div class="wrap lxl">
  <div class="lxl-top"><h1><?= e($title) ?> <small>· <?= count($list) ?> item<?= count($list) === 1 ? '' : 's' ?></small></h1>
    <nav class="lxl-crumbs"><a href="<?= url('luxe') ?>">Luxe Home</a><?= icon('chev', 12) ?><span><?= e($title) ?></span></nav></div>
  <div class="lxl-body">
    <aside class="lxl-f">
      <div class="lxl-fh"><b>Filters</b><a href="<?= $base ?>">Reset</a></div>
      <details open><summary>Category</summary><div><?php foreach ($cats as $c): ?><a href="<?= url('c/' . $c['slug']) ?>" class="<?= $active === 'c/' . $c['slug'] ? 'on' : '' ?>"><?= e($c['name']) ?></a><?php endforeach; ?></div></details>
      <details open><summary>Price</summary><div><a href="<?= $base . $qs(['max' => '']) ?>" class="<?= $max ? '' : 'on' ?>">Any price</a><?php foreach ([10000, 20000, 50000] as $m): ?><a href="<?= $base . $qs(['max' => $m]) ?>" class="<?= $max === $m ? 'on' : '' ?>">Under <?= money($m) ?></a><?php endforeach; ?></div></details>
      <details><summary>Discount</summary><div><a href="<?= $base . $qs(['sort' => 'off']) ?>">Biggest discount first</a></div></details>
      <details><summary>Launched In</summary><div><a href="<?= $base . $qs(['sort' => 'new']) ?>">Newest first</a></div></details>
    </aside>
    <div class="lxl-main">
      <form class="lxl-sort" method="get"><?php if ($max): ?><input type="hidden" name="max" value="<?= $max ?>"><?php endif; ?>
        <div class="lxl-chips"><?php foreach ($cats as $c): ?><a href="<?= url('c/' . $c['slug']) ?>" class="<?= $active === 'c/' . $c['slug'] ? 'on' : '' ?>"><?= e(preg_replace('~^(Designer|Fine|Luxury|Evening) | Couture$~', '', $c['name'])) ?></a><?php endforeach; ?></div>
        <label><?= icon('sort', 16) ?><span>Sort by</span><select name="sort" onchange="this.form.submit()"><?php foreach ($orders as $k => $o): ?><option value="<?= $k ?>" <?= $sort === $k ? 'selected' : '' ?>><?= $o[0] ?></option><?php endforeach; ?></select></label>
      </form>
      <?= product_grid($list, 'grid lxl-grid') ?>
    </div>
  </div>
</div>
        <?php
        page_foot();
        break;
    }
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
    <div class="empty-box"><?= icon('search', 40) ?><h2>Nothing found</h2><p>Try another word, like saree, heels or lipstick.</p><a class="btn" href="<?= url('shop') ?>">See all products</a></div>
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
    if (strpos((string)$p['cat_slug'], 'lx-') === 0) ll_lux(true);
    db()->prepare('UPDATE products SET views=views+1 WHERE id=?')->execute([$p['id']]);
    $imgs = p_images($p) ?: [''];
    $off = discount_pct($p);
    $shades = list_csv((string)$p['shades']);
    $sizes = list_csv((string)($p['sizes'] ?? ''));
    $itags = json_decode((string)($p['image_tags'] ?? '{}'), true) ?: [];
    $shadeImg = []; foreach ($itags as $im => $t) $shadeImg[$t] ??= img_url($im);
    $rs = db()->prepare('SELECT * FROM reviews WHERE product_id=? AND approved=1 ORDER BY id DESC LIMIT 30'); $rs->execute([$p['id']]); $reviews = $rs->fetchAll();
    [$ravg, $rn] = review_stats()[(int)$p['id']] ?? [0, 0];
    $out = (int)$p['stock'] <= 0;
    page_head($p['name'], $p['short'] ?: mb_substr($p['description'], 0, 150), img_url($imgs[0]), 'c/' . $p['cat_slug']);
    $wa = wa_link("Hi, I want to order:\n" . $p['name'] . "\nPrice: " . money($p['price']) . "\n" . (isset($_SERVER['HTTP_HOST']) ? 'https://' . $_SERVER['HTTP_HOST'] . url('p/' . $p['slug']) : ''));
    ?>
<div class="wrap pdp">
  <nav class="crumbs"><a href="<?= url() ?>">Home</a><?= icon('chev', 14) ?><a href="<?= url('c/' . $p['cat_slug']) ?>"><?= e($p['cat_name']) ?></a><?= icon('chev', 14) ?><span><?= e($p['name']) ?></span></nav>
  <div class="pdp-in">
    <div class="gallery">
      <div class="g-main"><img id="gMain" src="<?= e(img_url($imgs[0])) ?>"<?php if (img_small($imgs[0]) !== img_url($imgs[0])): ?> srcset="<?= e(img_small($imgs[0])) ?> 600w, <?= e(img_url($imgs[0])) ?> 1200w" sizes="(max-width:860px) 100vw, 50vw"<?php endif; ?> alt="<?= e($p['name']) ?>" width="1200" height="1500" fetchpriority="high"><?php if ($off): ?><span class="badge off"><?= $off ?>% OFF</span><?php endif; ?></div>
      <?php if (count($imgs) > 1): ?><div class="g-thumbs"><?php foreach ($imgs as $i => $im): ?><button class="<?= $i === 0 ? 'on' : '' ?>" data-img="<?= e(img_url($im)) ?>"><img src="<?= e(img_small($im)) ?>" alt="<?= e($p['name']) ?> photo <?= $i + 1 ?>" width="80" height="100" loading="lazy"></button><?php endforeach; ?></div><?php endif; ?>
    </div>
    <div class="info" data-product="<?= (int)$p['id'] ?>">
      <small class="info-brand"><?= e($p['brand'] ?: S('shop_name')) ?></small>
      <h1><?= e($p['name']) ?></h1>
      <?php if ($rn): ?><a class="rate-l" href="#reviews"><span class="stars"><?= str_repeat('★', (int)round($ravg)) ?><i><?= str_repeat('★', 5 - (int)round($ravg)) ?></i></span><b><?= $ravg ?></b><span>(<?= $rn ?> review<?= $rn > 1 ? 's' : '' ?>)</span></a><?php endif; ?>
      <?php if ($p['short'] !== ''): ?><p class="info-short"><?= e($p['short']) ?></p><?php endif; ?>
      <div class="price big"><b><?= money($p['price']) ?></b><?php if ($off): ?><s><?= money($p['old_price']) ?></s><em><?= $off ?>% off</em><?php endif; ?></div>
      <p class="tax">Price includes VAT. <?= $p['size'] !== '' ? 'Size: ' . e($p['size']) . '.' : '' ?></p>
      <?php if ($shades): ?>
      <div class="opt"><span><?= in_array($p['cat_slug'], ['lips', 'skincare', 'makeup', 'fragrance', 'nails', 'body'], true) ? 'Shade' : 'Colour' ?>: <b id="shadeName"><?= e($shades[0]) ?></b></span>
        <div class="shades"><?php foreach ($shades as $i => $s): ?><button type="button" class="<?= $i === 0 ? 'on' : '' ?>" data-shade="<?= e($s) ?>"<?= isset($shadeImg[$s]) ? ' data-img="' . e($shadeImg[$s]) . '"' : '' ?>><?= e($s) ?></button><?php endforeach; ?></div>
      </div>
      <?php endif; ?>
      <?php if ($sizes): ?>
      <div class="opt"><span>Size: <b id="sizeName"><?= e($sizes[0]) ?></b></span>
        <div class="shades sizes"><?php foreach ($sizes as $i => $s): ?><button type="button" class="<?= $i === 0 ? 'on' : '' ?>" data-size="<?= e($s) ?>"><?= e($s) ?></button><?php endforeach; ?></div>
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
  <section class="sec reviews" id="reviews">
    <div class="sec-h"><div><h2>Customer reviews</h2><p><?= $rn ? $ravg . ' out of 5 from ' . $rn . ' review' . ($rn > 1 ? 's' : '') : 'Be the first to review this product' ?></p></div></div>
    <div class="rv-in">
      <div class="rv-list">
        <?php foreach ($reviews as $r): ?>
        <article class="rv"><div class="rv-t"><span class="av"><?= e(mb_strtoupper(mb_substr($r['name'], 0, 1))) ?></span><div><b><?= e($r['name']) ?></b><span class="stars"><?= str_repeat('★', (int)$r['rating']) ?><i><?= str_repeat('★', 5 - (int)$r['rating']) ?></i></span></div><time><?= date('d M Y', strtotime($r['created'])) ?></time></div><p><?= nl2br(e($r['body'])) ?></p></article>
        <?php endforeach; if (!$reviews): ?><p class="muted">No reviews yet. Bought this? Tell others what you think.</p><?php endif; ?>
      </div>
      <form class="rv-form" id="rvForm" data-pid="<?= (int)$p['id'] ?>">
        <h3>Write a review</h3>
        <div class="rate-pick" role="radiogroup" aria-label="Your rating"><?php for ($i = 5; $i >= 1; $i--): ?><input type="radio" name="rating" value="<?= $i ?>" id="r<?= $i ?>" <?= $i === 5 ? 'checked' : '' ?>><label for="r<?= $i ?>" title="<?= $i ?> stars">★</label><?php endfor; ?></div>
        <label>Your name<input name="name" required maxlength="60"></label>
        <label>Mobile (not shown)<input name="phone" inputmode="tel" placeholder="01XXXXXXXXX"></label>
        <label>Your review<textarea name="body" required rows="3" maxlength="1000" placeholder="How was the colour, smell or feel?"></textarea></label>
        <input name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
        <p class="err" role="alert" hidden></p>
        <button class="btn block">Send review</button>
        <small class="muted">Reviews show after the shop checks them.</small>
      </form>
    </div>
  </section>
  <?php $rel = q('p.category_id=? AND p.id<>?', [$p['category_id'], $p['id']], 'p.sold DESC', 8); if ($rel): ?>
  <section class="sec"><?= section_head('You may also like', '', 'c/' . $p['cat_slug']) ?><?= product_grid($rel, 'grid rail') ?></section>
  <?php endif; ?>
</div>
<div class="sticky-buy only-m"><div><b><?= money($p['price']) ?></b><?php if ($off): ?><s><?= money($p['old_price']) ?></s><?php endif; ?></div><?php if (!$out): ?><button class="btn ghost" data-add-pdp>Add to bag</button><button class="btn" data-buy-now>Buy now</button><?php else: ?><button class="btn" disabled>Sold out</button><?php endif; ?></div>
<script>window.LLP={id:'<?= (int)$p['id'] ?>',name:<?= json_encode($p['name']) ?>,price:<?= (int)$p['price'] ?>};try{window.fbq&&fbq('track','ViewContent',{content_ids:[LLP.id],content_type:'product',content_name:LLP.name,value:LLP.price,currency:'BDT'})}catch(e){}</script>
<script type="application/ld+json"><?= json_encode(['@context' => 'https://schema.org', '@graph' => [array_filter(['@type' => 'Product', 'name' => $p['name'], 'url' => abs_url('p/' . $p['slug']), 'sku' => 'LL-' . $p['id'], 'image' => array_map(fn($i) => preg_match('~^https?://~', img_url($i)) ? img_url($i) : site_origin() . img_url($i), $imgs), 'description' => $p['short'] ?: mb_substr(strip_tags((string)$p['description']), 0, 300), 'category' => $p['cat_name'], 'brand' => ['@type' => 'Brand', 'name' => $p['brand'] ?: S('shop_name')], 'aggregateRating' => $rn ? ['@type' => 'AggregateRating', 'ratingValue' => $ravg, 'reviewCount' => $rn] : null, 'review' => $reviews ? array_map(fn($r) => ['@type' => 'Review', 'author' => ['@type' => 'Person', 'name' => $r['name']], 'reviewRating' => ['@type' => 'Rating', 'ratingValue' => (int)$r['rating'], 'bestRating' => 5], 'reviewBody' => $r['body']], array_slice($reviews, 0, 5)) : null, 'offers' => ['@type' => 'Offer', 'url' => abs_url('p/' . $p['slug']), 'priceCurrency' => 'BDT', 'price' => (int)$p['price'], 'itemCondition' => 'https://schema.org/NewCondition', 'availability' => $out ? 'https://schema.org/OutOfStock' : 'https://schema.org/InStock', 'seller' => ['@type' => 'Organization', 'name' => S('shop_name')]]]), ['@type' => 'BreadcrumbList', 'itemListElement' => [['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => abs_url()], ['@type' => 'ListItem', 'position' => 2, 'name' => $p['cat_name'], 'item' => abs_url('c/' . $p['cat_slug'])], ['@type' => 'ListItem', 'position' => 3, 'name' => $p['name']]]]]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
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
      <fieldset class="area paym"><legend>Payment</legend>
        <?php $pm = []; if (S('cod_on') === '1') $pm['cod'] = ['Cash on delivery', 'Pay when you receive your order.'];
        foreach (['bkash', 'nagad'] as $k) if (S($k . '_on') === '1' && S($k . '_number') !== '') $pm[$k] = [PAYMENTS[$k], 'Send money to ' . S($k . '_number') . ' (' . S($k . '_type') . ')'];
        if (!$pm) $pm['cod'] = ['Cash on delivery', 'Pay when you receive your order.'];
        $first = true; foreach ($pm as $k => [$n, $d]): ?>
        <label class="radio"><input type="radio" name="payment" value="<?= $k ?>" <?= $first ? 'checked' : '' ?>><span><b><?= e($n) ?></b><small><?= e($d) ?></small></span></label>
        <?php $first = false; endforeach; ?>
      </fieldset>
      <div class="trx" id="trxBox" hidden><p><?= e(S('pay_note')) ?></p><label>Transaction ID<input name="trx" autocomplete="off" placeholder="e.g. 8N7A6XK2LM"></label></div>
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
    <?php if (has_phone()): ?><a href="tel:<?= e(norm_phone(S('phone'))) ?>"><?= icon('phone', 26) ?><b>Call</b><span><?= e(S('phone')) ?></span></a><?php endif; ?>
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
    http_response_code(404); $GLOBALS['ll_noindex'] = true;
    page_head('Page not found');
    echo '<div class="wrap page narrow"><div class="empty-box">' . icon('search', 40) . '<h1>Page not found</h1><p>The page you are looking for is not here.</p><a class="btn" href="' . url() . '">Go to home</a></div></div>';
    page_foot();
}
