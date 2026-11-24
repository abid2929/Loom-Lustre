<?php
// Admin pages (GET). $view is already checked with can(). Uses head()/foot() from ui.php.
$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$today = date('Y-m-d');
$cats = categories(false);
$catName = array_column($cats, 'name', 'id');
$cols = collections(false);
$colName = array_column($cols, 'name', 'id');

function thumb(string $src, string $cls = 'th'): string { return '<img class="' . $cls . '" src="' . e($src) . '" alt="" loading="lazy">'; }
function stars(float $r): string { $n = (int)round($r); return '<span class="stars">' . str_repeat('★', $n) . '<i>' . str_repeat('★', 5 - $n) . '</i></span>'; }
function del_btn(string $act, int $id, string $what, array $extra = []): string {
    $h = '<form method="post" class="inl" data-confirm="Delete ' . e($what) . '? This cannot be undone.">' . csrf_field() . '<input type="hidden" name="act" value="' . $act . '"><input type="hidden" name="id" value="' . $id . '">';
    foreach ($extra as $k => $v) $h .= '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">';
    return $h . '<button class="icb red" title="Delete" aria-label="Delete">' . ai('trash', 16) . '</button></form>';
}
function ago(string $t): string {
    $s = time() - strtotime($t);
    if ($s < 60) return 'just now';
    if ($s < 3600) return floor($s / 60) . ' min ago';
    if ($s < 86400) return floor($s / 3600) . ' h ago';
    return floor($s / 86400) . ' d ago';
}
function sold_ok(): string { return "status NOT IN ('cancelled','returned')"; }
function stat_card(string $label, string $value, string $icon, string $color, string $note = '', string $href = ''): string {
    $tag = $href ? 'a href="' . $href . '"' : 'div';
    return '<' . $tag . ' class="stat" style="--c:' . $color . '"><span class="si">' . ai($icon, 22) . '</span><div><small>' . e($label) . '</small><b>' . $value . '</b>' . ($note ? '<em>' . $note . '</em>' : '') . '</div></' . ($href ? 'a' : 'div') . '>';
}
function order_items(array $o): array { return json_decode($o['items'] ?? '[]', true) ?: []; }
function pay_label(array $o): string { return PAYMENTS[$o['payment'] ?? 'cod'] ?? 'Cash on delivery'; }
function area_label(array $o): string { return ($o['area'] ?? 'in') === 'out' ? S('delivery_out_label') : S('delivery_in_label'); }

/* ================= invoice: a page of its own, made for printing ================= */
if ($view === 'invoice') {
    $st = $pdo->prepare('SELECT * FROM orders WHERE id=?'); $st->execute([$id]); $o = $st->fetch();
    if (!$o) go('v=orders', 'Order not found.', 'err');
    ?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>Invoice <?= e($o['code']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Great+Vibes&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('admin/admin.css') ?>"></head><body class="inv-page">
<div class="inv-bar"><a class="btn ghost" href="<?= aurl('v=order&id=' . $o['id']) ?>"><?= ai('back', 16) ?> Back</a><button class="btn" onclick="print()"><?= ai('print', 16) ?> Print invoice</button></div>
<div class="inv">
  <div class="inv-h"><div><img src="<?= url('assets/img/logo.png') ?>" alt="<?= e(S('shop_name')) ?>" class="inv-logo"><p><?= e(S('address')) ?><br><?= e(S('phone')) ?><?= S('email') ? ' · ' . e(S('email')) : '' ?></p></div>
    <div class="r"><h1>INVOICE</h1><p><b><?= e($o['code']) ?></b><br><?= date('d M Y, h:i A', strtotime($o['created'])) ?></p><?= pill($o['status']) ?></div></div>
  <div class="inv-to"><div><small>BILL TO</small><b><?= e($o['name']) ?></b><p><?= e($o['phone']) ?><br><?= nl2br(e($o['address'])) ?></p></div>
    <div><small>PAYMENT</small><b><?= e(pay_label($o)) ?></b><p><?= $o['trx_id'] ? 'TrxID: ' . e($o['trx_id']) . '<br>' : '' ?>Delivery: <?= e(area_label($o)) ?><?= $o['tracking'] ? '<br>Tracking: ' . e($o['tracking']) : '' ?></p></div></div>
  <table class="inv-t"><thead><tr><th>#</th><th>Item</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead><tbody>
  <?php foreach (order_items($o) as $i => $it): ?><tr><td><?= $i + 1 ?></td><td><?= e($it['name']) ?><?= !empty($it['shade']) ? '<small>Shade: ' . e($it['shade']) . '</small>' : '' ?></td><td><?= (int)$it['qty'] ?></td><td><?= tk($it['price']) ?></td><td><?= tk($it['qty'] * $it['price']) ?></td></tr><?php endforeach; ?>
  </tbody></table>
  <div class="inv-sum"><p><span>Subtotal</span><b><?= tk($o['subtotal']) ?></b></p><p><span>Delivery</span><b><?= tk($o['delivery']) ?></b></p><p class="g"><span><?= ($o['payment'] ?? 'cod') === 'cod' ? 'Cash to collect' : 'Total' ?></span><b><?= tk($o['total']) ?></b></p></div>
  <?php if ($o['note']): ?><p class="inv-note"><b>Customer note:</b> <?= e($o['note']) ?></p><?php endif; ?>
  <p class="inv-thanks">Thank you for shopping with <?= e(S('shop_name')) ?>!</p>
</div></body></html><?php
    exit;
}

/* ================= dashboard ================= */
if ($view === 'dashboard') {
    head('Dashboard', $view, 'Welcome back, ' . ($ME['name'] ?: $ME['username']) . '. Here is your shop today.');
    $q = fn(string $sql) => $pdo->query($sql)->fetchColumn();
    $visToday = (int)$q("SELECT COUNT(DISTINCT vid) FROM visits WHERE day='$today'");
    $viewsToday = (int)$q("SELECT COUNT(*) FROM visits WHERE day='$today'");
    $online = (int)$q('SELECT COUNT(DISTINCT vid) FROM visits WHERE t>' . (time() - 300));
    $repeat = (int)$q('SELECT COUNT(*) FROM (SELECT phone FROM orders GROUP BY phone HAVING COUNT(*)>1)');
    $sales = (int)$q('SELECT COALESCE(SUM(total),0) FROM orders WHERE ' . sold_ok());
    $salesToday = (int)$q("SELECT COALESCE(SUM(total),0) FROM orders WHERE substr(created,1,10)='$today' AND " . sold_ok());
    $orders = (int)$q('SELECT COUNT(*) FROM orders');
    $ordToday = (int)$q("SELECT COUNT(*) FROM orders WHERE substr(created,1,10)='$today'");
    $pending = (int)$q("SELECT COUNT(*) FROM orders WHERE status='new'");
    $delivered = (int)$q("SELECT COUNT(*) FROM orders WHERE status='delivered'");
    $prods = (int)$q('SELECT COUNT(*) FROM products');
    $inStock = (int)$q('SELECT COUNT(*) FROM products WHERE stock>0');
    $low = (int)$q('SELECT COUNT(*) FROM products WHERE active=1 AND stock<=5');
    $custs = (int)$q('SELECT COUNT(DISTINCT phone) FROM orders');
    ?>
<section class="hero-row">
  <div class="card vis"><?= card_h('Visitors right now', 'Live numbers from your store', '<span class="livedot">LIVE</span>') ?>
    <div class="vis-g"><div><b><?= $online ?></b><small>Online now</small></div><div><b><?= $visToday ?></b><small>Visitors today</small></div><div><b><?= $viewsToday ?></b><small>Page views today</small></div><div><b><?= $repeat ?></b><small>Repeat buyers</small></div></div>
    <?php $vd = []; for ($i = 6; $i >= 0; $i--) $vd[date('Y-m-d', strtotime("-$i day"))] = 0;
    foreach ($pdo->query("SELECT day, COUNT(DISTINCT vid) n FROM visits WHERE day>='" . array_key_first($vd) . "' GROUP BY day") as $r) if (isset($vd[$r['day']])) $vd[$r['day']] = (int)$r['n'];
    $vm = max(1, max($vd)); $src = $pdo->query("SELECT ref, COUNT(DISTINCT vid) n FROM visits WHERE ref<>'' AND day>='" . array_key_first($vd) . "' GROUP BY ref ORDER BY n DESC LIMIT 4")->fetchAll(); ?>
    <div class="vbars" aria-label="Visitors per day, last 7 days"><?php foreach ($vd as $d => $n): ?><span title="<?= date('D d M', strtotime($d)) ?>: <?= $n ?> visitors"><i style="height:<?= max(4, round($n * 100 / $vm)) ?>%"></i><small><?= date('D', strtotime($d)) ?></small></span><?php endforeach; ?></div>
    <?php if ($src): ?><p class="vsrc">Top sources this week: <?php foreach ($src as $r): ?><b><?= e($r['ref']) ?> <?= (int)$r['n'] ?></b><?php endforeach; ?></p><?php endif; ?>
  </div>
  <div class="card feed"><?= card_h('Live activity', 'What is happening in your shop', '<a class="lnk" href="' . aurl('v=reports') . '">Reports</a>') ?>
    <ul class="feed-l">
    <?php
    $ev = [];
    foreach ($pdo->query('SELECT id, code, name, total, status, created FROM orders ORDER BY created DESC LIMIT 6') as $o) $ev[] = [strtotime($o['created']), 'cart', 'New order <b>' . e($o['code']) . '</b> from ' . e($o['name']) . ' · ' . tk($o['total']), aurl('v=order&id=' . $o['id'])];
    foreach ($pdo->query('SELECT r.name, r.rating, r.created, p.name pn FROM reviews r LEFT JOIN products p ON p.id=r.product_id ORDER BY r.id DESC LIMIT 4') as $r) $ev[] = [strtotime($r['created']), 'star', e($r['name']) . ' gave <b>' . (int)$r['rating'] . '★</b> to ' . e($r['pn']), aurl('v=reviews')];
    foreach ($pdo->query("SELECT path, t FROM visits WHERE path LIKE 'p/%' ORDER BY t DESC LIMIT 6") as $v) {
        $s = $pdo->prepare('SELECT name FROM products WHERE slug=?'); $s->execute([substr($v['path'], 2)]); $n = $s->fetchColumn();
        if ($n) $ev[] = [(int)$v['t'], 'eye', 'Someone is looking at <b>' . e($n) . '</b>', ''];
    }
    usort($ev, fn($a, $b) => $b[0] <=> $a[0]);
    foreach (array_slice($ev, 0, 8) as [$t, $ic, $txt, $href]): ?>
      <li><span class="fi"><?= ai($ic, 16) ?></span><?= $href ? '<a href="' . $href . '">' . $txt . '</a>' : '<span>' . $txt . '</span>' ?><time><?= ago(date('Y-m-d H:i:s', $t)) ?></time></li>
    <?php endforeach; if (!$ev): ?><li class="empty">No activity yet. Orders, reviews and visits will show here.</li><?php endif; ?>
    </ul>
  </div>
</section>

<section class="stats">
  <?= stat_card('Total sales', tk($sales), 'money', '#b31f3a', tk($salesToday) . ' today', aurl('v=reports')) ?>
  <?= stat_card('Total orders', (string)$orders, 'bag', '#3b82f6', $ordToday . ' today', aurl('v=orders')) ?>
  <?= stat_card('Pending orders', (string)$pending, 'clock', '#f59e0b', 'Need a call', aurl('v=orders&s=new')) ?>
  <?= stat_card('Delivered', (string)$delivered, 'check', '#10b981', $orders ? round($delivered * 100 / $orders) . '% of orders' : '', aurl('v=orders&s=delivered')) ?>
  <?= stat_card('Products', (string)$prods, 'box', '#8b5cf6', count($cats) . ' categories', aurl('v=products')) ?>
  <?= stat_card('In stock', (string)$inStock, 'stock', '#0ea5e9', ($prods - $inStock) . ' sold out', aurl('v=inventory')) ?>
  <?= stat_card('Low stock', (string)$low, 'alert', '#ef4444', '5 or fewer left', aurl('v=inventory&f=low')) ?>
  <?= stat_card('Customers', (string)$custs, 'users', '#14b8a6', $repeat . ' came back', aurl('v=customers')) ?>
</section>

<section class="charts">
  <div class="card"><?php
    $days = []; for ($i = 6; $i >= 0; $i--) $days[date('Y-m-d', strtotime("-$i day"))] = [0, 0];
    $st = $pdo->query("SELECT substr(created,1,10) d, SUM(total) s, COUNT(*) n FROM orders WHERE created>='" . array_key_first($days) . "' AND " . sold_ok() . ' GROUP BY d');
    foreach ($st as $r) if (isset($days[$r['d']])) $days[$r['d']] = [(int)$r['s'], (int)$r['n']];
    $week = array_sum(array_column($days, 0));
    echo card_h('Sales, last 7 days', tk($week) . ' from ' . array_sum(array_column($days, 1)) . ' orders');
    $W = 640; $H = 220; $pad = 34; $max = max(1, max(array_column($days, 0))); $n = count($days);
    $pts = []; $i = 0;
    foreach ($days as $d => [$s, $c]) { $pts[] = [$pad + $i * ($W - 2 * $pad) / ($n - 1), $H - 30 - ($s / $max) * ($H - 60), $s, $d, $c]; $i++; }
    $line = implode(' ', array_map(fn($p) => round($p[0], 1) . ',' . round($p[1], 1), $pts));
    $area = $line . ' ' . round(end($pts)[0], 1) . ',' . ($H - 30) . ' ' . $pad . ',' . ($H - 30);
    ?>
    <svg class="chart" viewBox="0 0 <?= $W ?> <?= $H ?>" role="img" aria-label="Sales for the last 7 days">
      <defs><linearGradient id="gA" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#b31f3a" stop-opacity=".28"/><stop offset="1" stop-color="#b31f3a" stop-opacity="0"/></linearGradient></defs>
      <?php for ($g = 0; $g <= 3; $g++): $y = 30 + $g * ($H - 60) / 3; ?><line x1="<?= $pad ?>" x2="<?= $W - $pad ?>" y1="<?= $y ?>" y2="<?= $y ?>" class="grid"/><text x="<?= $pad - 6 ?>" y="<?= $y + 4 ?>" class="ax" text-anchor="end"><?= $max >= 1000 ? round($max * (3 - $g) / 3000, 1) . 'k' : round($max * (3 - $g) / 3) ?></text><?php endfor; ?>
      <polygon points="<?= $area ?>" fill="url(#gA)"/>
      <polyline points="<?= $line ?>" fill="none" stroke="#b31f3a" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>
      <?php foreach ($pts as [$x, $y, $s, $d, $c]): ?><g class="pt"><circle cx="<?= round($x, 1) ?>" cy="<?= round($y, 1) ?>" r="4.5"/><title><?= date('D d M', strtotime($d)) ?>: <?= tk($s) ?> (<?= $c ?> orders)</title></g><text x="<?= round($x, 1) ?>" y="<?= $H - 8 ?>" class="ax" text-anchor="middle"><?= date('D', strtotime($d)) ?></text><?php endforeach; ?>
    </svg>
  </div>
  <div class="card"><?php
    $byS = array_fill_keys(array_keys(ORDER_STATUS_A), 0);
    foreach ($pdo->query('SELECT status, COUNT(*) n FROM orders GROUP BY status') as $r) if (isset($byS[$r['status']])) $byS[$r['status']] = (int)$r['n'];
    $tot = array_sum($byS);
    echo card_h('Order status', $tot . ' orders in total');
    $R = 70; $C = 2 * M_PI * $R; $off = 0; ?>
    <div class="donut-w"><svg class="donut" viewBox="0 0 200 200" role="img" aria-label="Orders by status"><circle cx="100" cy="100" r="<?= $R ?>" fill="none" stroke="#eef0f4" stroke-width="26"/>
      <?php if ($tot) foreach ($byS as $k => $n): if (!$n) continue; $len = $C * $n / $tot; ?><circle cx="100" cy="100" r="<?= $R ?>" fill="none" stroke="<?= STATUS_COLORS[$k] ?>" stroke-width="26" stroke-dasharray="<?= round($len, 2) ?> <?= round($C - $len, 2) ?>" stroke-dashoffset="<?= round(-$off, 2) ?>" transform="rotate(-90 100 100)"><title><?= ORDER_STATUS_A[$k] ?>: <?= $n ?></title></circle><?php $off += $len; endforeach; ?>
      <text x="100" y="96" text-anchor="middle" class="dn"><?= $tot ?></text><text x="100" y="118" text-anchor="middle" class="dl">orders</text></svg>
      <ul class="legend"><?php foreach ($byS as $k => $n): ?><li><i style="background:<?= STATUS_COLORS[$k] ?>"></i><?= ORDER_STATUS_A[$k] ?><b><?= $n ?></b></li><?php endforeach; ?></ul></div>
  </div>
</section>

<section class="card"><?= card_h('Recent orders', 'The latest orders from your store', '<a class="btn sm" href="' . aurl('v=orders') . '">All orders</a>') ?>
  <?php $rows = $pdo->query('SELECT * FROM orders ORDER BY created DESC, id DESC LIMIT 8')->fetchAll(); ?>
  <div class="tw"><table class="t mc"><thead><tr><th>Order</th><th>Customer</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $o): ?><tr><td><a class="b" href="<?= aurl('v=order&id=' . $o['id']) ?>"><?= e($o['code']) ?></a></td><td><b><?= e($o['name']) ?></b><small><?= e($o['phone']) ?></small></td><td><?= array_sum(array_column(order_items($o), 'qty')) ?></td><td class="b"><?= tk($o['total']) ?></td><td><?= e(pay_label($o)) ?></td><td><?= pill($o['status']) ?></td><td><small><?= date('d M, h:i A', strtotime($o['created'])) ?></small></td>
    <td class="acts"><a class="icb" href="<?= aurl('v=order&id=' . $o['id']) ?>" title="View"><?= ai('eye', 16) ?></a><a class="icb" href="<?= aurl('v=invoice&id=' . $o['id']) ?>" title="Print invoice"><?= ai('print', 16) ?></a></td></tr><?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="8" class="empty">No orders yet. When a customer orders, it shows here.</td></tr><?php endif; ?>
  </tbody></table></div>
</section>
<?php
    foot(); exit;
}

/* ================= products ================= */
if ($view === 'products') {
    $f = ['q' => trim((string)($_GET['q'] ?? '')), 'cat' => (int)($_GET['cat'] ?? 0), 'col' => (int)($_GET['col'] ?? 0), 'stock' => (string)($_GET['stock'] ?? ''), 'badge' => (string)($_GET['badge'] ?? '')];
    $w = []; $a = [];
    if ($f['q'] !== '') { $w[] = '(name LIKE ? OR brand LIKE ? OR tags LIKE ?)'; array_push($a, "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%"); }
    if ($f['cat']) { $w[] = 'category_id=?'; $a[] = $f['cat']; }
    if ($f['col']) { $w[] = 'collection_id=?'; $a[] = $f['col']; }
    $w[] = ['in' => 'stock>5', 'low' => 'stock BETWEEN 1 AND 5', 'out' => 'stock<=0'][$f['stock']] ?? '1=1';
    $w[] = ['featured' => 'featured=1', 'flash' => 'flash_sale=1', 'new' => 'is_new=1', 'best' => 'bestseller=1', 'sale' => 'old_price>price', 'hidden' => 'active=0'][$f['badge']] ?? '1=1';
    $st = $pdo->prepare('SELECT * FROM products WHERE ' . implode(' AND ', $w) . ' ORDER BY sort, id DESC'); $st->execute($a); $rows = $st->fetchAll();
    $total = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    head('Products', $view, count($rows) . ' of ' . $total . ' products');
    ?>
<section class="card">
  <?= card_h('Product management', 'Edit prices, photos, shades and badges', '<a class="btn" href="' . aurl('v=product') . '">' . ai('plus', 16) . ' Add product</a>') ?>
  <form class="filters" method="get" action="<?= url('admin/') ?>"><input type="hidden" name="v" value="products">
    <input type="search" name="q" value="<?= e($f['q']) ?>" placeholder="Search name, brand or tag" data-filter="#ptable">
    <select name="cat"><option value="0">All categories</option><?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= $f['cat'] == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select>
    <select name="col"><option value="0">All collections</option><?php foreach ($cols as $c): ?><option value="<?= $c['id'] ?>" <?= $f['col'] == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select>
    <select name="stock"><option value="">Any stock</option><?php foreach (['in' => 'In stock', 'low' => 'Low stock (1-5)', 'out' => 'Sold out'] as $k => $n): ?><option value="<?= $k ?>" <?= $f['stock'] === $k ? 'selected' : '' ?>><?= $n ?></option><?php endforeach; ?></select>
    <select name="badge"><option value="">Any badge</option><?php foreach (['featured' => 'Featured', 'flash' => 'Flash sale', 'new' => 'New arrival', 'best' => 'Best seller', 'sale' => 'On discount', 'hidden' => 'Hidden'] as $k => $n): ?><option value="<?= $k ?>" <?= $f['badge'] === $k ? 'selected' : '' ?>><?= $n ?></option><?php endforeach; ?></select>
    <button class="btn ghost">Filter</button><?php if (array_filter($f)): ?><a class="lnk" href="<?= aurl('v=products') ?>">Clear</a><?php endif; ?>
  </form>
  <div class="tw"><table class="t mc" id="ptable"><thead><tr><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Badges</th><th>Status</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $p): $d = discount_pct($p); ?>
    <tr data-s="<?= e(mb_strtolower($p['name'] . ' ' . $p['brand'] . ' ' . $p['tags'])) ?>">
      <td><div class="pc"><?= thumb(img_small(p_image($p))) ?><div><a class="b" href="<?= aurl('v=product&id=' . $p['id']) ?>"><?= e($p['name']) ?></a><small><?= e($p['brand'] ?: '—') ?><?= $p['size'] ? ' · ' . e($p['size']) : '' ?><?= $p['collection_id'] && isset($colName[$p['collection_id']]) ? ' · ' . e($colName[$p['collection_id']]) : '' ?></small></div></div></td>
      <td><?= e($catName[$p['category_id']] ?? '—') ?></td>
      <td><b><?= tk($p['price']) ?></b><?php if ($d): ?><small><s><?= tk($p['old_price']) ?></s> <i class="disc">-<?= $d ?>%</i></small><?php endif; ?></td>
      <td><span class="stk <?= $p['stock'] <= 0 ? 'out' : ($p['stock'] <= 5 ? 'low' : '') ?>"><?= (int)$p['stock'] ?></span></td>
      <td class="badges"><?= $p['featured'] ? '<i class="bd f">Featured</i>' : '' ?><?= $p['flash_sale'] ? '<i class="bd fl">Flash</i>' : '' ?><?= $p['is_new'] ? '<i class="bd n">New</i>' : '' ?><?= $p['bestseller'] ? '<i class="bd b">Best</i>' : '' ?></td>
      <td><?= $p['active'] ? '<span class="pill st-delivered">Live</span>' : '<span class="pill st-returned">Hidden</span>' ?></td>
      <td class="acts"><a class="icb" href="<?= aurl('v=product&id=' . $p['id']) ?>" title="Edit"><?= ai('edit', 16) ?></a><a class="icb" href="<?= url('p/' . $p['slug']) ?>" target="_blank" title="See on website"><?= ai('ext', 16) ?></a><?= del_btn('product_del', (int)$p['id'], $p['name']) ?></td>
    </tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="7" class="empty">No products match. <a href="<?= aurl('v=products') ?>">Show all</a></td></tr><?php endif; ?>
  </tbody></table></div>
</section>
<?php
    foot(); exit;
}

/* ================= add / edit product ================= */
if ($view === 'product') {
    $p = null;
    if ($id) { $st = $pdo->prepare('SELECT * FROM products WHERE id=?'); $st->execute([$id]); $p = $st->fetch() ?: null; }
    $p ??= ['id' => 0, 'name' => '', 'brand' => '', 'category_id' => $cats[0]['id'] ?? 0, 'collection_id' => 0, 'price' => 0, 'old_price' => 0, 'stock' => 20, 'size' => '', 'sizes' => '', 'shades' => '', 'images' => '[]', 'image_tags' => '{}', 'short' => '', 'description' => '', 'how_to' => '', 'tags' => '', 'featured' => 0, 'flash_sale' => 0, 'is_new' => 1, 'bestseller' => 0, 'active' => 1, 'slug' => '', 'sold' => 0, 'views' => 0];
    $regular = $p['old_price'] > $p['price'] ? $p['old_price'] : $p['price']; $sale = $p['old_price'] > $p['price'] ? $p['price'] : 0;
    $imgs = p_images($p); $tags = json_decode($p['image_tags'] ?: '{}', true) ?: [];
    $shadeList = list_csv((string)$p['shades']);
    head($p['id'] ? 'Edit product' : 'Add product', $view, $p['id'] ? $p['name'] : 'Fill in the details. Fields with * are needed.');
    ?>
<form method="post" enctype="multipart/form-data" class="pform" id="pform"><?= csrf_field() ?><input type="hidden" name="act" value="product"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
<div class="pf-top"><a class="lnk" href="<?= aurl('v=products') ?>"><?= ai('back', 16) ?> All products</a>
  <?php if ($p['id']): ?><span class="muted"><?= (int)$p['views'] ?> views · <?= (int)$p['sold'] ?> sold</span><a class="btn ghost sm" target="_blank" href="<?= url('p/' . $p['slug']) ?>"><?= ai('ext', 16) ?> View on site</a><?php endif; ?></div>
<div class="pf-grid">
<div class="pf-main">
  <section class="card"><?= card_h('Basic info') ?>
    <?= field('name', 'Product name *', $p['name'], 'text', '', 'required id="pName" placeholder="e.g. Velvet Matte Lipstick"') ?>
    <div class="g2"><?= field('brand', 'Brand', $p['brand'], 'text', '', 'placeholder="e.g. Loom & Lustre"') ?><?= field('size', 'Size / volume', $p['size'], 'text', 'Shown under the name, like 3.5 g or 50 ml', 'id="pSize"') ?></div>
    <div class="place"><span class="lb">Where does it show?</span>
      <div class="seg"><label><input type="radio" name="place" value="cat" checked><span><?= ai('layers', 16) ?> Category</span></label><label><input type="radio" name="place" value="col" <?= $p['collection_id'] ? 'checked' : '' ?>><span><?= ai('book', 16) ?> Category + Catalog collection</span></label></div>
      <div class="g2"><label class="f"><span>Category *</span><select name="category_id" id="pCat"><?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= $p['category_id'] == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></label>
      <label class="f col-only"><span>Catalog collection</span><select name="collection_id"><option value="0">None</option><?php foreach ($cols as $c): ?><option value="<?= $c['id'] ?>" <?= $p['collection_id'] == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select><small>Groups like Bridal Glow or Gift Sets. <a href="<?= aurl('v=collections') ?>">Manage</a></small></label></div>
    </div>
  </section>

  <section class="card"><?= card_h('Price & stock', 'Put a sale price to show a discount badge') ?>
    <div class="g3"><?= field('regular', 'Regular price (৳) *', (string)$regular, 'number', '', 'min="0" required id="pReg"') ?><?= field('sale', 'Sale price (৳)', $sale ? (string)$sale : '', 'number', '<span id="pDisc"></span>', 'min="0" id="pSale" placeholder="Leave empty if no sale"') ?><?= field('stock', 'Stock *', (string)$p['stock'], 'number', 'How many you have', 'min="0" required') ?></div>
  </section>

  <section class="card"><?= card_h('Shades / colours & sizes', 'Customers pick one of these on the product page') ?>
    <div class="chips-f" data-name="shades"><span class="lb">Shades / colours</span>
      <input type="hidden" name="shades" value="<?= e($p['shades']) ?>">
      <div class="chips"></div>
      <div class="chip-add"><input type="text" placeholder="Type a shade and press Enter, e.g. Ruby Red"><button type="button" class="btn ghost sm">Add</button></div>
      <div class="quick"><small>Quick add:</small><?php foreach (['Ruby Red', 'Nude Pink', 'Coral', 'Berry', 'Mauve', 'Rose', 'Peach', 'Brown', 'Plum', 'Fair', 'Light', 'Medium', 'Tan', 'Deep'] as $q): ?><button type="button" data-v="<?= $q ?>"><?= $q ?></button><?php endforeach; ?></div>
    </div>
    <div class="chips-f" data-name="sizes"><span class="lb">Size options (optional)</span>
      <input type="hidden" name="sizes" value="<?= e($p['sizes']) ?>">
      <div class="chips"></div>
      <div class="chip-add"><input type="text" placeholder="e.g. 30 ml"><button type="button" class="btn ghost sm">Add</button></div>
      <div class="quick"><small>Quick add:</small><?php foreach (['Mini', 'Regular', 'Jumbo', '10 ml', '30 ml', '50 ml', '100 ml', '3.5 g', 'Set of 3'] as $q): ?><button type="button" data-v="<?= $q ?>"><?= $q ?></button><?php endforeach; ?></div>
    </div>
  </section>

  <section class="card"><?= card_h('Photo gallery', 'First photo is the main one. Drag to reorder. Tag a photo with a shade to show it when that shade is picked.') ?>
    <datalist id="shadeList"><?php foreach ($shadeList as $s): ?><option value="<?= e($s) ?>"><?php endforeach; ?></datalist>
    <div class="gal" id="gal">
    <?php foreach ($imgs as $im): ?>
      <div class="gi" draggable="true"><img src="<?= e(img_small($im)) ?>" alt=""><input type="hidden" name="keep[]" value="<?= e($im) ?>"><input name="keep_tag[]" value="<?= e($tags[$im] ?? '') ?>" placeholder="Shade tag" list="shadeList"><button type="button" class="icb red rm" title="Remove photo"><?= ai('trash', 14) ?></button></div>
    <?php endforeach; ?>
    </div>
    <div id="newImgs"></div>
    <template id="imgRow"><div class="nrow"><label class="up"><?= ai('image', 18) ?><span>Choose file</span><input type="file" name="gfile[]" accept="image/*"></label><span class="or">or</span><input type="url" name="gurl[]" placeholder="Paste image link (https://...)"><input name="gtag[]" placeholder="Shade tag" list="shadeList"><button type="button" class="icb red rm" title="Remove row"><?= ai('trash', 14) ?></button></div></template>
    <button type="button" class="btn ghost sm" id="addImg"><?= ai('plus', 16) ?> Add another image</button>
  </section>

  <section class="card"><?= card_h('Description', '', '<button type="button" class="btn ai-b" id="aiBtn">' . ai('spark', 16) . ' AI copywriter</button>') ?>
    <p class="muted sm" id="aiNote">Write the name and pick a category, then press AI copywriter. It fills the boxes below. You can edit everything.</p>
    <?= field('short', 'Short description', $p['short'], 'text', 'One line under the price', 'id="fShort" maxlength="160"') ?>
    <?= field('description', 'Full description', $p['description'], 'area', '', 'id="fDesc" rows="6"') ?>
    <?= field('how_to', 'How to use', $p['how_to'], 'area', '', 'id="fHow" rows="2"') ?>
    <?= field('tags', 'Search words', $p['tags'], 'text', 'Comma separated. Helps customers find it in search.', 'id="fTags"') ?>
  </section>
</div>
<aside class="pf-side">
  <section class="card"><?= card_h('Publish') ?>
    <?= toggle('active', 'Show on website', (bool)$p['active'], 'Turn off to hide it') ?>
    <button class="btn wide"><?= $p['id'] ? 'Save changes' : 'Add product' ?></button>
  </section>
  <section class="card"><?= card_h('Badges & sections', 'Where it shows on the homepage') ?>
    <?= toggle('featured', 'Featured', (bool)$p['featured'], 'Deals of the week') ?>
    <?= toggle('flash_sale', 'Flash sale', (bool)$p['flash_sale'], 'Flash sale row with timer') ?>
    <?= toggle('is_new', 'New arrival', (bool)$p['is_new'], 'New in row + NEW tag') ?>
    <?= toggle('bestseller', 'Best seller', (bool)$p['bestseller'], 'Bestsellers row') ?>
  </section>
  <?php if ($p['id']): ?>
  <section class="card"><?= card_h('More') ?>
    <button class="btn ghost wide" form="fCopy"><?= ai('layers', 16) ?> Make a copy</button>
    <button class="btn ghost wide red" form="fDel"><?= ai('trash', 16) ?> Delete product</button>
  </section>
  <?php endif; ?>
</aside>
</div>
</form>
<?php if ($p['id']): ?>
<form method="post" id="fCopy"><?= csrf_field() ?><input type="hidden" name="act" value="product_copy"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"></form>
<form method="post" id="fDel" data-confirm="Delete this product? This cannot be undone."><?= csrf_field() ?><input type="hidden" name="act" value="product_del"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"></form>
<?php endif;
    foot(); exit;
}

/* ================= categories & collections ================= */
if ($view === 'categories' || $view === 'collections') {
    $isCat = $view === 'categories';
    $t = $isCat ? 'categories' : 'collections'; $act = $isCat ? 'cat' : 'collection'; $fk = $isCat ? 'category_id' : 'collection_id';
    $rows = $pdo->query("SELECT x.*, (SELECT COUNT(*) FROM products p WHERE p.$fk=x.id) n FROM $t x ORDER BY sort, id")->fetchAll();
    $ed = null; foreach ($rows as $r) if ((int)$r['id'] === $id) $ed = $r;
    head($isCat ? 'Categories' : 'Catalog Collections', $view, $isCat ? 'The main groups in your shop menu' : 'Themed groups like Bridal Glow or Gift Sets. A product can be in one collection.');
    ?>
<div class="split">
<section class="card"><?= card_h($isCat ? 'All categories' : 'All collections', count($rows) . ' in total') ?>
  <div class="tw"><table class="t"><thead><tr><th>Image</th><th>Name</th><th>Slug</th><th>Products</th><th>Order</th><th>Status</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?><tr><td><?= thumb(img_small((string)$r['image'])) ?></td><td><b><?= e($r['name']) ?></b><small><?= e($r['tagline']) ?></small></td><td><code><?= e($r['slug']) ?></code></td><td><a href="<?= aurl('v=products&' . ($isCat ? 'cat' : 'col') . '=' . $r['id']) ?>"><?= (int)$r['n'] ?></a></td><td><?= (int)$r['sort'] ?></td>
    <td><?= $r['active'] ? '<span class="pill st-delivered">Active</span>' : '<span class="pill st-returned">Hidden</span>' ?></td>
    <td class="acts"><a class="icb" href="<?= aurl('v=' . $view . '&id=' . $r['id']) ?>" title="Edit"><?= ai('edit', 16) ?></a><a class="icb" target="_blank" href="<?= url(($isCat ? 'c/' : 'collection/') . $r['slug']) ?>" title="See on website"><?= ai('ext', 16) ?></a><?= del_btn($act . '_del', (int)$r['id'], $r['name']) ?></td></tr><?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="7" class="empty">Nothing here yet. Add one on the right.</td></tr><?php endif; ?>
  </tbody></table></div>
</section>
<section class="card"><?= card_h($ed ? 'Edit ' . $ed['name'] : 'Add new', $ed ? '' : 'Name, short line and a photo', $ed ? '<a class="lnk" href="' . aurl('v=' . $view) . '">+ New</a>' : '') ?>
  <form method="post" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="act" value="<?= $act ?>"><input type="hidden" name="id" value="<?= (int)($ed['id'] ?? 0) ?>"><input type="hidden" name="image_old" value="<?= e($ed['image'] ?? '') ?>">
    <?= field('name', 'Name *', $ed['name'] ?? '', 'text', '', 'required') ?>
    <?= field('tagline', 'Short line', $ed['tagline'] ?? '', 'text', 'Shown under the name') ?>
    <?php if (!empty($ed['image'])): ?><img class="prev" src="<?= e(img_small($ed['image'])) ?>" alt=""><?php endif; ?>
    <label class="f"><span>Photo</span><input type="file" name="image" accept="image/*"></label>
    <?= field('image_url', 'Or photo link', '', 'url', '', 'placeholder="https://..."') ?>
    <?= field('sort', 'Order', (string)($ed['sort'] ?? count($rows) + 1), 'number', 'Smaller numbers come first') ?>
    <?php if ($ed): ?><?= toggle('active', 'Active', (bool)$ed['active']) ?><?php endif; ?>
    <button class="btn wide"><?= $ed ? 'Save' : 'Add' ?></button>
  </form>
</section>
</div>
<?php
    foot(); exit;
}

/* ================= inventory ================= */
if ($view === 'inventory') {
    $fl = (string)($_GET['f'] ?? '');
    $w = ['low' => 'stock BETWEEN 1 AND 5', 'out' => 'stock<=0'][$fl] ?? '1=1';
    $rows = $pdo->query("SELECT * FROM products WHERE $w ORDER BY stock, name")->fetchAll();
    $value = (int)$pdo->query('SELECT COALESCE(SUM(stock*price),0) FROM products WHERE active=1')->fetchColumn();
    $units = (int)$pdo->query('SELECT COALESCE(SUM(stock),0) FROM products WHERE active=1')->fetchColumn();
    head('Inventory & Stock', $view, 'Change stock and price for many products at once');
    ?>
<section class="stats s4">
  <?= stat_card('Units in stock', (string)$units, 'stock', '#0ea5e9') ?>
  <?= stat_card('Stock value', tk($value), 'money', '#b31f3a', 'At selling price') ?>
  <?= stat_card('Low stock', (string)$pdo->query('SELECT COUNT(*) FROM products WHERE stock BETWEEN 1 AND 5')->fetchColumn(), 'alert', '#f59e0b', '', aurl('v=inventory&f=low')) ?>
  <?= stat_card('Sold out', (string)$pdo->query('SELECT COUNT(*) FROM products WHERE stock<=0')->fetchColumn(), 'box', '#ef4444', '', aurl('v=inventory&f=out')) ?>
</section>
<section class="card">
  <?= card_h('Stock list', count($rows) . ' products', '<div class="tabs">' . implode('', array_map(fn($k, $n) => '<a href="' . aurl('v=inventory' . ($k ? '&f=' . $k : '')) . '" class="' . ($fl === $k ? 'on' : '') . '">' . $n . '</a>', ['', 'low', 'out'], ['All', 'Low', 'Sold out'])) . '</div>') ?>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="stock"><input type="hidden" name="f" value="<?= e($fl) ?>">
  <input type="search" class="qf" placeholder="Quick search" data-filter="#itable">
  <div class="tw"><table class="t" id="itable"><thead><tr><th>Product</th><th>Category</th><th>Sold</th><th>Price (৳)</th><th>Stock</th></tr></thead><tbody>
  <?php foreach ($rows as $p): ?><tr data-s="<?= e(mb_strtolower($p['name'])) ?>"><td><div class="pc"><?= thumb(img_small(p_image($p))) ?><div><a class="b" href="<?= aurl('v=product&id=' . $p['id']) ?>"><?= e($p['name']) ?></a><small><?= e($p['size']) ?></small></div></div></td><td><?= e($catName[$p['category_id']] ?? '—') ?></td><td><?= (int)$p['sold'] ?></td>
    <td><input class="num" type="number" min="0" name="price[<?= $p['id'] ?>]" value="<?= (int)$p['price'] ?>"></td>
    <td><div class="stepper"><button type="button" data-d="-1">−</button><input class="num stk <?= $p['stock'] <= 0 ? 'out' : ($p['stock'] <= 5 ? 'low' : '') ?>" type="number" min="0" name="stock[<?= $p['id'] ?>]" value="<?= (int)$p['stock'] ?>"><button type="button" data-d="1">+</button></div></td></tr><?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="5" class="empty">Nothing here. Good job!</td></tr><?php endif; ?>
  </tbody></table></div>
  <div class="sticky-save"><button class="btn">Save all changes</button></div>
  </form>
</section>
<?php
    foot(); exit;
}

/* ================= orders ================= */
if ($view === 'orders') {
    $s = (string)($_GET['s'] ?? ''); $q = trim((string)($_GET['q'] ?? ''));
    $w = []; $a = [];
    if (isset(ORDER_STATUS_A[$s])) { $w[] = 'status=?'; $a[] = $s; }
    if ($q !== '') { $w[] = '(code LIKE ? OR name LIKE ? OR phone LIKE ?)'; array_push($a, "%$q%", "%$q%", "%$q%"); }
    $st = $pdo->prepare('SELECT * FROM orders' . ($w ? ' WHERE ' . implode(' AND ', $w) : '') . ' ORDER BY created DESC, id DESC LIMIT 300'); $st->execute($a); $rows = $st->fetchAll();
    $cnt = array_fill_keys(array_keys(ORDER_STATUS_A), 0); foreach ($pdo->query('SELECT status, COUNT(*) n FROM orders GROUP BY status') as $r) $cnt[$r['status']] = (int)$r['n'];
    head('Orders', $view, array_sum($cnt) . ' orders in total');
    ?>
<section class="card">
  <div class="stabs"><a href="<?= aurl('v=orders') ?>" class="<?= $s === '' ? 'on' : '' ?>">All <i><?= array_sum($cnt) ?></i></a><?php foreach (ORDER_STATUS_A as $k => $n): ?><a href="<?= aurl('v=orders&s=' . $k) ?>" class="<?= $s === $k ? 'on' : '' ?>" style="--c:<?= STATUS_COLORS[$k] ?>"><?= $n ?> <i><?= $cnt[$k] ?></i></a><?php endforeach; ?></div>
  <form class="filters" method="get" action="<?= url('admin/') ?>"><input type="hidden" name="v" value="orders"><?php if ($s): ?><input type="hidden" name="s" value="<?= e($s) ?>"><?php endif; ?>
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search order no, name or phone" data-filter="#otable"><button class="btn ghost">Search</button></form>
  <form method="post" id="bulk"><?= csrf_field() ?><input type="hidden" name="act" value="orders_bulk">
  <div class="bulk-bar"><label class="ck"><input type="checkbox" id="ckAll"> Select all</label><select name="status"><option value="">Mark selected as...</option><?php foreach (ORDER_STATUS_A as $k => $n): ?><option value="<?= $k ?>"><?= $n ?></option><?php endforeach; ?></select><button class="btn sm">Apply</button><span class="muted" id="selN"></span></div>
  <div class="tw"><table class="t mc" id="otable"><thead><tr><th></th><th>Order</th><th>Customer</th><th>Area</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $o): ?><tr data-s="<?= e(mb_strtolower($o['code'] . ' ' . $o['name'] . ' ' . $o['phone'])) ?>" class="<?= !$o['seen'] ? 'unseen' : '' ?>"><td><input type="checkbox" name="ids[]" value="<?= $o['id'] ?>" class="ck1"></td>
    <td><a class="b" href="<?= aurl('v=order&id=' . $o['id']) ?>"><?= e($o['code']) ?></a><?= !$o['seen'] ? '<i class="bd fl">NEW</i>' : '' ?></td><td><b><?= e($o['name']) ?></b><small><a href="tel:<?= e($o['phone']) ?>"><?= e($o['phone']) ?></a></small></td><td><?= e(area_label($o)) ?></td>
    <td><?= array_sum(array_column(order_items($o), 'qty')) ?></td><td class="b"><?= tk($o['total']) ?></td><td><?= e(pay_label($o)) ?><?= $o['trx_id'] ? '<small>' . e($o['trx_id']) . '</small>' : '' ?></td><td><?= pill($o['status']) ?></td><td><small><?= date('d M, h:i A', strtotime($o['created'])) ?></small></td>
    <td class="acts"><a class="icb" href="<?= aurl('v=order&id=' . $o['id']) ?>" title="View"><?= ai('eye', 16) ?></a><a class="icb" href="<?= aurl('v=invoice&id=' . $o['id']) ?>" title="Print invoice"><?= ai('print', 16) ?></a></td></tr><?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="10" class="empty">No orders here.</td></tr><?php endif; ?>
  </tbody></table></div></form>
</section>
<?php
    foot(); exit;
}

/* ================= one order ================= */
if ($view === 'order') {
    $st = $pdo->prepare('SELECT * FROM orders WHERE id=?'); $st->execute([$id]); $o = $st->fetch();
    if (!$o) go('v=orders', 'Order not found.', 'err');
    if (!$o['seen'] && !defined('LL_DEMO')) $pdo->prepare('UPDATE orders SET seen=1 WHERE id=?')->execute([$id]);
    $prev = $pdo->prepare('SELECT id, code, total, status, created FROM orders WHERE phone=? AND id<>? ORDER BY id DESC LIMIT 5'); $prev->execute([$o['phone'], $id]); $prev = $prev->fetchAll();
    $items = order_items($o);
    $waText = "Hello {$o['name']}, this is " . S('shop_name') . ". We got your order {$o['code']} of Tk {$o['total']}. Please reply YES to confirm. Thank you!";
    $waTo = '88' . norm_phone($o['phone']);
    head('Order ' . $o['code'], $view, 'Placed ' . date('d M Y, h:i A', strtotime($o['created'])));
    ?>
<div class="pf-top"><a class="lnk" href="<?= aurl('v=orders') ?>"><?= ai('back', 16) ?> All orders</a><?= pill($o['status']) ?><a class="btn ghost sm" href="<?= aurl('v=invoice&id=' . $o['id']) ?>"><?= ai('print', 16) ?> Print invoice</a></div>
<div class="pf-grid">
<div class="pf-main">
  <section class="card"><?= card_h('Items', count($items) . ' product' . (count($items) > 1 ? 's' : '')) ?>
    <div class="oitems"><?php foreach ($items as $it): ?><div class="oi"><?= thumb(img_small((string)($it['img'] ?? ''))) ?><div><b><?= e($it['name']) ?></b><?= !empty($it['shade']) ? '<small>Shade: ' . e($it['shade']) . '</small>' : '' ?></div><span><?= (int)$it['qty'] ?> × <?= tk($it['price']) ?></span><b><?= tk($it['qty'] * $it['price']) ?></b></div><?php endforeach; ?></div>
    <div class="osum"><p><span>Subtotal</span><b><?= tk($o['subtotal']) ?></b></p><p><span>Delivery (<?= e(area_label($o)) ?>)</span><b><?= tk($o['delivery']) ?></b></p><p class="g"><span>Total</span><b><?= tk($o['total']) ?></b></p></div>
  </section>
  <section class="card"><?= card_h('Update order') ?>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="order"><input type="hidden" name="id" value="<?= $o['id'] ?>">
      <div class="g2"><label class="f"><span>Status</span><select name="status"><?php foreach (ORDER_STATUS_A as $k => $n): ?><option value="<?= $k ?>" <?= $o['status'] === $k ? 'selected' : '' ?>><?= $n ?></option><?php endforeach; ?></select><small>Cancelled or Returned puts the stock back.</small></label>
      <?= field('tracking', 'Courier tracking number', (string)$o['tracking']) ?></div>
      <?= field('admin_note', 'Private note (only admins see this)', (string)$o['admin_note'], 'area') ?>
      <button class="btn">Save order</button>
    </form>
  </section>
  <?php if ($prev): ?><section class="card"><?= card_h('Earlier orders from this customer', count($prev) . ' before') ?>
    <div class="tw"><table class="t"><tbody><?php foreach ($prev as $r): ?><tr><td><a class="b" href="<?= aurl('v=order&id=' . $r['id']) ?>"><?= e($r['code']) ?></a></td><td><?= tk($r['total']) ?></td><td><?= pill($r['status']) ?></td><td><small><?= date('d M Y', strtotime($r['created'])) ?></small></td></tr><?php endforeach; ?></tbody></table></div></section><?php endif; ?>
</div>
<aside class="pf-side">
  <section class="card"><?= card_h('Customer') ?>
    <p class="cust"><b><?= e($o['name']) ?></b><a href="tel:<?= e($o['phone']) ?>"><?= e($o['phone']) ?></a><span><?= nl2br(e($o['address'])) ?></span></p>
    <?php if ($o['note']): ?><p class="note"><b>Note:</b> <?= e($o['note']) ?></p><?php endif; ?>
    <div class="btns"><a class="btn wa" target="_blank" href="https://wa.me/<?= $waTo ?>?text=<?= rawurlencode($waText) ?>">WhatsApp confirm</a><a class="btn ghost" href="tel:<?= e($o['phone']) ?>">Call</a></div>
  </section>
  <section class="card"><?= card_h('Payment') ?>
    <p class="kv"><span>Method</span><b><?= e(pay_label($o)) ?></b></p>
    <?php if ($o['trx_id']): ?><p class="kv"><span>Transaction ID</span><b><?= e($o['trx_id']) ?></b></p><?php endif; ?>
    <p class="kv"><span><?= ($o['payment'] ?? 'cod') === 'cod' ? 'Collect on delivery' : 'Amount' ?></span><b><?= tk($o['total']) ?></b></p>
  </section>
  <?php if (can('courier')): ?>
  <section class="card"><?= card_h('Courier', S('courier') === 'steadfast' ? 'Steadfast is connected' : 'No courier connected') ?>
    <?php if ($o['tracking']): ?><p class="kv"><span>Tracking</span><b><?= e($o['tracking']) ?></b></p><?php endif; ?>
    <?php if (in_array($o['status'], ['new', 'confirmed'], true)): ?>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="courier_send"><input type="hidden" name="id" value="<?= $o['id'] ?>"><input type="hidden" name="back" value="order"><button class="btn ghost wide"><?= ai('truck', 16) ?> Send to Steadfast</button></form>
    <?php endif; if (S('courier') !== 'steadfast' && can('settings')): ?><small class="muted">Add your Steadfast API keys in <a href="<?= aurl('v=settings&tab=courier') ?>">Courier Automation</a>.</small><?php endif; ?>
  </section>
  <?php endif; if (($ME['role'] ?? '') === 'super'): ?>
  <section class="card"><form method="post" data-confirm="Delete this order for good?"><?= csrf_field() ?><input type="hidden" name="act" value="order_del"><input type="hidden" name="id" value="<?= $o['id'] ?>"><button class="btn ghost wide red"><?= ai('trash', 16) ?> Delete order</button></form></section>
  <?php endif; ?>
</aside>
</div>
<?php
    foot(); exit;
}

/* ================= courier hub ================= */
if ($view === 'courier') {
    $ready = $pdo->query("SELECT * FROM orders WHERE status='confirmed' ORDER BY id")->fetchAll();
    $moving = $pdo->query("SELECT * FROM orders WHERE status='shipped' ORDER BY updated DESC LIMIT 100")->fetchAll();
    $doneM = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status='delivered' AND substr(updated,1,7)='" . date('Y-m') . "'")->fetchColumn();
    $codDue = array_sum(array_map(fn($o) => ($o['payment'] ?? 'cod') === 'cod' ? (int)$o['total'] : 0, $moving));
    $on = S('courier') === 'steadfast' && S('steadfast_key') !== '';
    head('Courier Hub', $view, 'Send confirmed orders to the courier and follow parcels');
    ?>
<section class="stats s4">
  <?= stat_card('Ready to send', (string)count($ready), 'box', '#3b82f6', 'Confirmed orders') ?>
  <?= stat_card('On the way', (string)count($moving), 'truck', '#8b5cf6', 'With the courier') ?>
  <?= stat_card('Cash to collect', tk($codDue), 'money', '#f59e0b', 'From parcels on the way') ?>
  <?= stat_card('Delivered this month', (string)$doneM, 'check', '#10b981') ?>
</section>
<section class="card cstat <?= $on ? 'ok' : '' ?>"><div><span class="livedot <?= $on ? '' : 'off' ?>"><?= $on ? 'CONNECTED' : 'NOT CONNECTED' ?></span><b>Steadfast Courier</b><p class="muted"><?= $on ? 'Press Send on an order. The parcel is booked, the tracking number is saved and the order becomes Shipped.' : 'Add your Steadfast API key and secret to book parcels from here with one click.' ?></p></div><?php if (can('settings')): ?><a class="btn ghost" href="<?= aurl('v=settings&tab=courier') ?>"><?= ai('gear', 16) ?> Courier settings</a><?php endif; ?></section>
<section class="card"><?= card_h('Ready to send', 'Confirmed orders waiting for the courier') ?>
  <div class="tw"><table class="t"><thead><tr><th>Order</th><th>Customer</th><th>Address</th><th>COD</th><th></th></tr></thead><tbody>
  <?php foreach ($ready as $o): ?><tr><td><a class="b" href="<?= aurl('v=order&id=' . $o['id']) ?>"><?= e($o['code']) ?></a></td><td><b><?= e($o['name']) ?></b><small><?= e($o['phone']) ?></small></td><td><small><?= e(mb_strimwidth($o['address'], 0, 70, '...')) ?></small></td><td class="b"><?= ($o['payment'] ?? 'cod') === 'cod' ? tk($o['total']) : 'Paid' ?></td>
    <td class="acts"><form method="post" class="inl"><?= csrf_field() ?><input type="hidden" name="act" value="courier_send"><input type="hidden" name="id" value="<?= $o['id'] ?>"><button class="btn sm"><?= ai('truck', 14) ?> Send</button></form></td></tr><?php endforeach; ?>
  <?php if (!$ready): ?><tr><td colspan="5" class="empty">No confirmed orders waiting. Confirm new orders first.</td></tr><?php endif; ?>
  </tbody></table></div>
</section>
<section class="card"><?= card_h('On the way', 'Parcels with the courier') ?>
  <div class="tw"><table class="t"><thead><tr><th>Order</th><th>Customer</th><th>Courier</th><th>Tracking</th><th>COD</th><th>Sent</th><th></th></tr></thead><tbody>
  <?php foreach ($moving as $o): ?><tr><td><a class="b" href="<?= aurl('v=order&id=' . $o['id']) ?>"><?= e($o['code']) ?></a></td><td><b><?= e($o['name']) ?></b><small><?= e($o['phone']) ?></small></td><td><?= e($o['courier'] ? ucfirst($o['courier']) : 'Manual') ?></td><td><code><?= e($o['tracking'] ?: '—') ?></code></td><td><?= ($o['payment'] ?? 'cod') === 'cod' ? tk($o['total']) : 'Paid' ?></td><td><small><?= ago($o['updated']) ?></small></td>
    <td class="acts"><form method="post" class="inl"><?= csrf_field() ?><input type="hidden" name="act" value="order"><input type="hidden" name="id" value="<?= $o['id'] ?>"><input type="hidden" name="status" value="delivered"><input type="hidden" name="tracking" value="<?= e($o['tracking']) ?>"><input type="hidden" name="admin_note" value="<?= e($o['admin_note']) ?>"><input type="hidden" name="back" value="list"><button class="btn ghost sm" title="Mark delivered"><?= ai('check', 14) ?> Delivered</button></form></td></tr><?php endforeach; ?>
  <?php if (!$moving): ?><tr><td colspan="7" class="empty">No parcels on the way.</td></tr><?php endif; ?>
  </tbody></table></div>
</section>
<?php
    foot(); exit;
}

/* ================= customers ================= */
if ($view === 'customers') {
    $q = trim((string)($_GET['q'] ?? ''));
    $rows = $pdo->query("SELECT phone, MAX(name) name, MAX(address) address, MAX(area) area, COUNT(*) n, SUM(CASE WHEN " . sold_ok() . " THEN total ELSE 0 END) spent, MAX(created) last, MIN(created) first FROM orders GROUP BY phone ORDER BY last DESC")->fetchAll();
    head('Customers', $view, count($rows) . ' people have ordered');
    $rep = count(array_filter($rows, fn($r) => $r['n'] > 1));
    ?>
<section class="stats s4">
  <?= stat_card('Customers', (string)count($rows), 'users', '#14b8a6') ?>
  <?= stat_card('Repeat buyers', (string)$rep, 'refresh', '#8b5cf6', count($rows) ? round($rep * 100 / count($rows)) . '% came back' : '') ?>
  <?= stat_card('Average spend', tk(count($rows) ? array_sum(array_column($rows, 'spent')) / count($rows) : 0), 'money', '#b31f3a', 'Per customer') ?>
  <?= stat_card('New this month', (string)count(array_filter($rows, fn($r) => substr($r['first'], 0, 7) === date('Y-m'))), 'plus', '#3b82f6') ?>
</section>
<section class="card"><?= card_h('All customers', 'Made from orders. Same phone number = same customer.') ?>
  <input type="search" class="qf" placeholder="Search name or phone" value="<?= e($q) ?>" data-filter="#ctable">
  <div class="tw"><table class="t mc" id="ctable"><thead><tr><th>Customer</th><th>Area</th><th>Orders</th><th>Spent</th><th>Last order</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?><tr data-s="<?= e(mb_strtolower($r['name'] . ' ' . $r['phone'])) ?>"><td><div class="pc"><span class="av"><?= e(mb_strtoupper(mb_substr($r['name'], 0, 1))) ?></span><div><b><?= e($r['name']) ?></b><small><?= e($r['phone']) ?> · <?= e(mb_strimwidth($r['address'], 0, 40, '...')) ?></small></div></div></td>
    <td><?= e($r['area'] === 'out' ? S('delivery_out_label') : S('delivery_in_label')) ?></td><td><?= (int)$r['n'] ?><?= $r['n'] > 1 ? ' <i class="bd b">Repeat</i>' : '' ?></td><td class="b"><?= tk($r['spent']) ?></td><td><small><?= date('d M Y', strtotime($r['last'])) ?></small></td>
    <td class="acts"><a class="icb" href="<?= aurl('v=orders&q=' . rawurlencode($r['phone'])) ?>" title="Orders"><?= ai('cart', 16) ?></a><a class="icb" href="tel:<?= e($r['phone']) ?>" title="Call"><?= ai('sms', 16) ?></a><a class="icb wa" target="_blank" href="https://wa.me/88<?= norm_phone($r['phone']) ?>" title="WhatsApp">WA</a></td></tr><?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="6" class="empty">No customers yet.</td></tr><?php endif; ?>
  </tbody></table></div>
</section>
<?php
    foot(); exit;
}

/* ================= reviews ================= */
if ($view === 'reviews') {
    $fl = (string)($_GET['f'] ?? 'pending');
    $w = ['pending' => 'r.approved=0', 'approved' => 'r.approved=1'][$fl] ?? '1=1';
    $rows = $pdo->query("SELECT r.*, p.name pn, p.slug, p.images FROM reviews r LEFT JOIN products p ON p.id=r.product_id WHERE $w ORDER BY r.id DESC LIMIT 200")->fetchAll();
    $nP = (int)$pdo->query('SELECT COUNT(*) FROM reviews WHERE approved=0')->fetchColumn(); $nA = (int)$pdo->query('SELECT COUNT(*) FROM reviews WHERE approved=1')->fetchColumn();
    $avg = (float)$pdo->query('SELECT COALESCE(AVG(rating),0) FROM reviews WHERE approved=1')->fetchColumn();
    head('Customer Reviews', $view, 'New reviews wait here until you approve them');
    ?>
<section class="stats s4">
  <?= stat_card('Average rating', number_format($avg, 1) . ' ★', 'star', '#f59e0b', 'From approved reviews') ?>
  <?= stat_card('Approved', (string)$nA, 'check', '#10b981', 'Showing on the website') ?>
  <?= stat_card('Waiting', (string)$nP, 'clock', '#ef4444', 'Need your OK') ?>
  <?= stat_card('Reviewed products', (string)$pdo->query('SELECT COUNT(DISTINCT product_id) FROM reviews WHERE approved=1')->fetchColumn(), 'box', '#8b5cf6') ?>
</section>
<section class="card"><?= card_h('Reviews', '', '<div class="tabs">' . implode('', array_map(fn($k, $n) => '<a href="' . aurl('v=reviews&f=' . $k) . '" class="' . ($fl === $k ? 'on' : '') . '">' . $n . '</a>', ['pending', 'approved', 'all'], ["Waiting ($nP)", "Approved ($nA)", 'All'])) . '</div>') ?>
  <div class="revs">
  <?php foreach ($rows as $r): ?><div class="rev"><?= thumb(img_small((string)(json_decode((string)$r['images'], true)[0] ?? ''))) ?><div class="rb"><div class="rt"><?= stars((float)$r['rating']) ?><b><?= e($r['name']) ?></b><small><?= e($r['phone']) ?> · <?= date('d M Y', strtotime($r['created'])) ?></small><?= $r['approved'] ? '<span class="pill st-delivered">Live</span>' : '<span class="pill st-new">Waiting</span>' ?></div>
    <p><?= nl2br(e($r['body'])) ?></p><small>on <a href="<?= url('p/' . $r['slug']) ?>" target="_blank"><?= e($r['pn'] ?? 'deleted product') ?></a></small></div>
    <div class="ra"><?php foreach (($r['approved'] ? ['hide' => 'Hide'] : ['approve' => 'Approve']) + ['delete' => 'Delete'] as $do => $lbl): ?><form method="post" class="inl" <?= $do === 'delete' ? 'data-confirm="Delete this review?"' : '' ?>><?= csrf_field() ?><input type="hidden" name="act" value="review"><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="do" value="<?= $do ?>"><input type="hidden" name="f" value="<?= e($fl) ?>"><button class="btn sm <?= $do === 'approve' ? '' : 'ghost' ?> <?= $do === 'delete' ? 'red' : '' ?>"><?= $lbl ?></button></form><?php endforeach; ?></div></div>
  <?php endforeach; if (!$rows): ?><p class="empty">No reviews here.</p><?php endif; ?>
  </div>
</section>
<?php
    foot(); exit;
}

/* ================= hero banners ================= */
if ($view === 'banners') {
    head('Hero Banners', $view, 'The big sliding pictures at the top of your homepage');
    ?>
<form method="post" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="act" value="banners">
<div class="bgrid">
<?php for ($i = 1; $i <= 4; $i++): $k = "s$i"; ?>
<section class="card banner"><?= card_h('Slide ' . $i, '', toggle($k . '_on', 'Show', S($k . '_on') === '1')) ?>
  <div class="bprev" style="background-image:url('<?= e(img_url(S($k . '_img'))) ?>')"><div class="<?= S($k . '_dark') === '1' ? 'dk' : '' ?>"><small><?= e(S($k . '_kicker')) ?></small><b><?= e(S($k . '_title')) ?></b></div></div>
  <div class="g2"><label class="f"><span>New picture</span><input type="file" name="<?= $k ?>_file" accept="image/*"><small>Wide photo, about 1600 × 700</small></label><?= field($k . '_url', 'Or picture link', '', 'url', '', 'placeholder="https://..."') ?></div>
  <div class="g2"><?= field($k . '_kicker', 'Small line', S($k . '_kicker')) ?><?= field($k . '_title', 'Big title', S($k . '_title')) ?></div>
  <?= field($k . '_text', 'Text', S($k . '_text')) ?>
  <div class="g2"><?= field($k . '_btn', 'Button text', S($k . '_btn')) ?><?= field($k . '_link', 'Button link', S($k . '_link'), 'text', 'Like c/lips, sale or a full https:// link') ?></div>
  <?= toggle($k . '_dark', 'Dark picture', S($k . '_dark') === '1', 'Makes the text white') ?>
</section>
<?php endfor; ?>
</div>
<div class="sticky-save"><button class="btn">Save banners</button></div>
</form>
<?php
    foot(); exit;
}

/* ================= homepage CMS ================= */
if ($view === 'homepage') {
    head('Homepage CMS', $view, 'Turn homepage sections on or off and change their titles');
    $secs = [
        'cats' => ['Shop by category', 'Round category pictures'], 'flash' => ['Flash sale', 'Products marked Flash sale, with a timer'], 'deals' => ['Deals of the week', 'Products marked Featured'],
        'promo' => ['Two promo banners', 'Lips and skincare picture boxes'], 'best' => ['Bestsellers', 'Products marked Best seller'], 'band' => ['Trust band', 'Wide strip with your promise'],
        'makeup' => ['Makeup must-haves', 'Products from Makeup'], 'skin' => ['Skincare favourites', 'Products from Skincare'], 'new' => ['New in', 'Products marked New arrival'],
        'reviews' => ['Customer reviews', 'Approved reviews from customers'], 'help' => ['Shade help box', 'WhatsApp help strip near the bottom'],
    ];
    $d = defaults();
    ?>
<form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="homepage">
<section class="card"><?= card_h('Homepage sections', 'From top to bottom, after the hero banners', '<a class="btn ghost sm" target="_blank" href="' . url() . '">' . ai('ext', 16) . ' Open homepage</a>') ?>
  <div class="cms">
  <?php $n = 0; foreach ($secs as $k => [$t, $s]): $n++; ?>
    <div class="cms-r"><span class="no"><?= $n ?></span><div class="cms-h"><?= toggle("h_{$k}_on", $t, S("h_{$k}_on") === '1', $s) ?></div>
      <div class="g2"><?= isset($d["h_{$k}_t"]) ? field("h_{$k}_t", 'Title', S("h_{$k}_t")) : '' ?><?= isset($d["h_{$k}_s"]) ? field("h_{$k}_s", 'Subtitle', S("h_{$k}_s")) : '' ?></div></div>
  <?php endforeach; ?>
  </div>
</section>
<div class="sticky-save"><button class="btn">Save homepage</button></div>
</form>
<?php
    foot(); exit;
}

/* ================= reports ================= */
if ($view === 'reports') {
    $from = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($_GET['from'] ?? '')) ? $_GET['from'] : date('Y-m-d', strtotime('-29 day'));
    $to = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($_GET['to'] ?? '')) ? $_GET['to'] : $today;
    $st = $pdo->prepare('SELECT * FROM orders WHERE substr(created,1,10) BETWEEN ? AND ? ORDER BY id'); $st->execute([$from, $to]); $all = $st->fetchAll();
    $ok = array_filter($all, fn($o) => !in_array($o['status'], ['cancelled', 'returned'], true));
    $rev = array_sum(array_column($ok, 'total')); $units = 0; $top = []; $byDay = []; $byArea = []; $byPay = []; $byS = array_fill_keys(array_keys(ORDER_STATUS_A), 0);
    foreach ($all as $o) $byS[$o['status']] = ($byS[$o['status']] ?? 0) + 1;
    foreach ($ok as $o) {
        $d = substr($o['created'], 0, 10); $byDay[$d] = ($byDay[$d] ?? 0) + $o['total'];
        $a = area_label($o); $byArea[$a] = ($byArea[$a] ?? 0) + 1;
        $pl = pay_label($o); $byPay[$pl] = ($byPay[$pl] ?? 0) + $o['total'];
        foreach (order_items($o) as $it) { $units += $it['qty']; $top[$it['name']] ??= [0, 0]; $top[$it['name']][0] += $it['qty']; $top[$it['name']][1] += $it['qty'] * $it['price']; }
    }
    uasort($top, fn($a, $b) => $b[1] <=> $a[1]);
    $vs = $pdo->prepare('SELECT COUNT(*) v, COUNT(DISTINCT vid) u FROM visits WHERE day BETWEEN ? AND ?'); $vs->execute([$from, $to]); [$views, $uniq] = array_values($vs->fetch());
    $tp = $pdo->prepare('SELECT path, COUNT(*) n FROM visits WHERE day BETWEEN ? AND ? GROUP BY path ORDER BY n DESC LIMIT 8'); $tp->execute([$from, $to]); $tp = $tp->fetchAll();
    $rf = $pdo->prepare("SELECT ref, COUNT(DISTINCT vid) n FROM visits WHERE ref<>'' AND day BETWEEN ? AND ? GROUP BY ref ORDER BY n DESC LIMIT 8"); $rf->execute([$from, $to]); $rf = $rf->fetchAll();
    head('Reports & Analytics', $view, date('d M Y', strtotime($from)) . ' to ' . date('d M Y', strtotime($to)));
    $bar = function (array $rows, bool $money = false) { $m = max(1, max($rows ?: [1])); $h = '<ul class="bars">'; foreach ($rows as $k => $v) $h .= '<li><span>' . e((string)$k) . '</span><i style="width:' . round($v * 100 / $m) . '%"></i><b>' . ($money ? tk($v) : $v) . '</b></li>'; return $h . ($rows ? '' : '<li class="empty">No data yet</li>') . '</ul>'; };
    ?>
<form class="filters card" method="get" action="<?= url('admin/') ?>"><input type="hidden" name="v" value="reports">
  <label>From <input type="date" name="from" value="<?= e($from) ?>"></label><label>To <input type="date" name="to" value="<?= e($to) ?>"></label><button class="btn ghost">Show</button>
  <?php foreach (['Today' => [$today, $today], '7 days' => [date('Y-m-d', strtotime('-6 day')), $today], '30 days' => [date('Y-m-d', strtotime('-29 day')), $today], 'This month' => [date('Y-m-01'), $today]] as $n => [$a, $b]): ?><a class="chip-l" href="<?= aurl("v=reports&from=$a&to=$b") ?>"><?= $n ?></a><?php endforeach; ?>
</form>
<section class="stats">
  <?= stat_card('Revenue', tk($rev), 'money', '#b31f3a', 'Cancelled & returned not counted') ?>
  <?= stat_card('Orders', (string)count($all), 'bag', '#3b82f6', count($ok) . ' valid') ?>
  <?= stat_card('Average order', tk(count($ok) ? $rev / count($ok) : 0), 'chart', '#8b5cf6') ?>
  <?= stat_card('Items sold', (string)$units, 'box', '#14b8a6') ?>
  <?= stat_card('Delivered', (string)$byS['delivered'], 'check', '#10b981', count($all) ? round($byS['delivered'] * 100 / count($all)) . '% success' : '') ?>
  <?= stat_card('Cancelled / returned', (string)($byS['cancelled'] + $byS['returned']), 'alert', '#ef4444') ?>
  <?= stat_card('Visitors', (string)$uniq, 'users', '#0ea5e9', $views . ' page views') ?>
  <?= stat_card('Conversion', ($uniq ? round(count($all) * 100 / $uniq, 1) : 0) . '%', 'target', '#f59e0b', 'Orders per visitor') ?>
</section>
<div class="split even">
  <section class="card"><?= card_h('Sales by day') ?><?= $bar($byDay, true) ?></section>
  <section class="card"><?= card_h('Top products', 'By money earned') ?>
    <div class="tw"><table class="t"><thead><tr><th>Product</th><th>Sold</th><th>Revenue</th></tr></thead><tbody><?php foreach (array_slice($top, 0, 10, true) as $n => [$q, $m]): ?><tr><td><?= e($n) ?></td><td><?= $q ?></td><td class="b"><?= tk($m) ?></td></tr><?php endforeach; if (!$top): ?><tr><td colspan="3" class="empty">No sales in this time.</td></tr><?php endif; ?></tbody></table></div></section>
  <section class="card"><?= card_h('Orders by status') ?><?= $bar(array_combine(array_map(fn($k) => ORDER_STATUS_A[$k], array_keys($byS)), $byS)) ?></section>
  <section class="card"><?= card_h('Orders by area') ?><?= $bar($byArea) ?><?= card_h('Money by payment method') ?><?= $bar($byPay, true) ?></section>
  <section class="card"><?= card_h('Most visited pages') ?><?= $bar(array_column(array_map(fn($r) => ['/' . $r['path'], $r['n']], $tp), 1, 0)) ?></section>
  <section class="card"><?= card_h('Where visitors came from', 'Other websites and apps') ?><?= $bar(array_column($rf, 'n', 'ref')) ?></section>
</div>
<?php
    foot(); exit;
}

/* ================= admins & roles ================= */
if ($view === 'admins') {
    $rows = $pdo->query('SELECT * FROM admins ORDER BY id')->fetchAll();
    $ed = null; foreach ($rows as $r) if ((int)$r['id'] === $id) $ed = $r;
    $logs = $pdo->query('SELECT * FROM logins ORDER BY rowid DESC LIMIT 15')->fetchAll();
    head('Admins & Roles', $view, 'Give your team their own login with only the pages they need');
    ?>
<div class="split">
<section class="card"><?= card_h('Team', count($rows) . ' admin' . (count($rows) > 1 ? 's' : '')) ?>
  <div class="tw"><table class="t"><thead><tr><th>Name</th><th>Role</th><th>Last login</th><th>Status</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?><tr><td><div class="pc"><span class="av"><?= e(mb_strtoupper(mb_substr($r['name'] ?: $r['username'], 0, 1))) ?></span><div><b><?= e($r['name'] ?: $r['username']) ?></b><small>@<?= e($r['username']) ?></small></div></div></td><td><span class="role r-<?= e($r['role']) ?>"><?= e(ROLES[$r['role']] ?? $r['role']) ?></span></td><td><small><?= $r['last_login'] ? ago($r['last_login']) : 'Never' ?></small></td>
    <td><?= $r['active'] ? '<span class="pill st-delivered">Active</span>' : '<span class="pill st-returned">Blocked</span>' ?></td>
    <td class="acts"><a class="icb" href="<?= aurl('v=admins&id=' . $r['id']) ?>" title="Edit"><?= ai('edit', 16) ?></a><?= (int)$r['id'] !== (int)$ME['id'] ? del_btn('admin_del', (int)$r['id'], $r['username']) : '' ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
  <div class="roles"><?php foreach (['super' => 'Everything, including settings, team and deleting orders.', 'manager' => 'Products, orders, customers, reviews, banners, homepage and reports. No settings or team.', 'staff' => 'Orders, courier, customers and stock only. Good for packing and calling staff.'] as $k => $t): ?><p><span class="role r-<?= $k ?>"><?= ROLES[$k] ?></span><?= $t ?></p><?php endforeach; ?></div>
</section>
<section class="card"><?= card_h($ed ? 'Edit ' . ($ed['name'] ?: $ed['username']) : 'Add team member', '', $ed ? '<a class="lnk" href="' . aurl('v=admins') . '">+ New</a>' : '') ?>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="admin_user"><input type="hidden" name="id" value="<?= (int)($ed['id'] ?? 0) ?>">
    <?= field('name', 'Full name', $ed['name'] ?? '') ?>
    <?= field('username', 'Username *', $ed['username'] ?? '', 'text', 'Letters, numbers, dot or _', 'required pattern="[A-Za-z0-9_.]{3,30}" autocomplete="off"') ?>
    <label class="f"><span>Role</span><select name="role"><?php foreach (ROLES as $k => $n): ?><option value="<?= $k ?>" <?= ($ed['role'] ?? 'staff') === $k ? 'selected' : '' ?>><?= $n ?></option><?php endforeach; ?></select></label>
    <?= field('pass', $ed ? 'New password' : 'Password *', '', 'password', $ed ? 'Leave empty to keep the old one' : 'At least 6 characters', 'autocomplete="new-password" minlength="6"' . ($ed ? '' : ' required')) ?>
    <?php if ($ed): ?><?= toggle('active', 'Can log in', (bool)$ed['active']) ?><?php endif; ?>
    <button class="btn wide"><?= $ed ? 'Save' : 'Add admin' ?></button>
  </form>
</section>
</div>
<section class="card"><?= card_h('Recent logins', 'Last 15 tries. Many wrong tries? Change your password.') ?>
  <div class="tw"><table class="t"><thead><tr><th>Username</th><th>Result</th><th>IP</th><th>Time</th></tr></thead><tbody>
  <?php foreach ($logs as $l): ?><tr><td><?= e($l['username']) ?></td><td><?= $l['ok'] ? '<span class="pill st-delivered">OK</span>' : '<span class="pill st-cancelled">Wrong password</span>' ?></td><td><code><?= e($l['ip']) ?></code></td><td><small><?= date('d M Y, h:i A', strtotime($l['t'])) ?></small></td></tr><?php endforeach; ?>
  <?php if (!$logs): ?><tr><td colspan="4" class="empty">No logins recorded yet.</td></tr><?php endif; ?>
  </tbody></table></div>
</section>
<?php
    foot(); exit;
}

/* ================= settings (and "account" = own password for every role) ================= */
if ($view === 'settings' || $view === 'account') {
    $tab = $view === 'account' ? 'security' : (string)($_GET['tab'] ?? 'store');
    if (!isset(SETTING_TABS[$tab])) $tab = 'store';
    [$tname, $tic] = SETTING_TABS[$tab];
    head($tname, $view === 'account' ? 'account' : 'settings', 'Site Settings');
    $saved = fn(string $k) => S($k) !== '' ? 'placeholder="Saved. Leave empty to keep it."' : '';
    ?>
<?php if ($view === 'settings'): ?><nav class="stabs set-tabs"><?php foreach (SETTING_TABS as $k => [$n, $ic]): ?><a href="<?= aurl('v=settings&tab=' . $k) ?>" class="<?= $tab === $k ? 'on' : '' ?>"><?= ai($ic, 16) ?> <?= $n ?></a><?php endforeach; ?></nav><?php endif; ?>
<form method="post" class="set-f"><?= csrf_field() ?><input type="hidden" name="act" value="settings"><input type="hidden" name="tab" value="<?= $tab ?>">
<?php switch ($tab):
case 'store': ?>
  <section class="card"><?= card_h('Store details', 'Shown in the header, footer and contact page') ?>
    <div class="g2"><?= sfield('shop_name', 'Shop name') ?><?= sfield('shop_sub', 'Tagline under the name') ?></div>
    <?= sfield('announce', 'Top announcement bar', 'text', 'Use • between messages. They rotate.') ?>
    <?= sfield('about', 'About the shop', 'area') ?>
  </section>
  <section class="card"><?= card_h('Contact') ?>
    <div class="g2"><?= sfield('phone', 'Phone') ?><?= sfield('whatsapp', 'WhatsApp number', 'text', 'Empty = same as phone') ?><?= sfield('email', 'Email', 'email') ?><?= sfield('notify_email', 'Send new order emails to', 'email') ?></div>
    <?= sfield('address', 'Address') ?>
    <?= sfield('wa_popup', 'WhatsApp help message') ?>
  </section>
  <section class="card"><?= card_h('Social links') ?>
    <div class="g2"><?= sfield('facebook', 'Facebook page link', 'url') ?><?= sfield('instagram', 'Instagram link', 'url') ?><?= sfield('tiktok', 'TikTok link', 'url') ?><?= sfield('youtube', 'YouTube link', 'url') ?></div>
  </section>
<?php break; case 'delivery': ?>
  <section class="card"><?= card_h('Delivery charges', 'Customers pick an area at checkout') ?>
    <div class="g3"><?= sfield('delivery_in_label', 'Area 1 name') ?><?= sfield('delivery_in', 'Charge (৳)', 'number') ?><?= sfield('delivery_time_in', 'Delivery time') ?></div>
    <div class="g3"><?= sfield('delivery_out_label', 'Area 2 name') ?><?= sfield('delivery_out', 'Charge (৳)', 'number') ?><?= sfield('delivery_time_out', 'Delivery time') ?></div>
    <?= sfield('free_over', 'Free delivery when the bag is over (৳)', 'number', '0 = never free') ?>
  </section>
<?php break; case 'payment': ?>
  <section class="card"><?= card_h('Payment methods', 'Turn on the ways customers can pay') ?>
    <?= toggle('cod_on', 'Cash on delivery', S('cod_on') === '1', 'Customer pays the delivery man') ?>
    <div class="pm"><?= toggle('bkash_on', 'bKash', S('bkash_on') === '1', 'Customer sends money and writes the TrxID') ?><div class="g2"><?= sfield('bkash_number', 'bKash number') ?><label class="f"><span>Account type</span><select name="bkash_type"><?php foreach (['Personal', 'Agent', 'Merchant'] as $t): ?><option <?= S('bkash_type') === $t ? 'selected' : '' ?>><?= $t ?></option><?php endforeach; ?></select></label></div></div>
    <div class="pm"><?= toggle('nagad_on', 'Nagad', S('nagad_on') === '1') ?><div class="g2"><?= sfield('nagad_number', 'Nagad number') ?><label class="f"><span>Account type</span><select name="nagad_type"><?php foreach (['Personal', 'Agent', 'Merchant'] as $t): ?><option <?= S('nagad_type') === $t ? 'selected' : '' ?>><?= $t ?></option><?php endforeach; ?></select></label></div></div>
    <?= sfield('pay_note', 'Note shown at checkout for bKash / Nagad') ?>
  </section>
<?php break; case 'courier': ?>
  <section class="card"><?= card_h('Courier automation', 'Book parcels from the Courier Hub with one click') ?>
    <label class="f"><span>Courier</span><select name="courier"><option value="none" <?= S('courier') !== 'steadfast' ? 'selected' : '' ?>>None (I book parcels myself)</option><option value="steadfast" <?= S('courier') === 'steadfast' ? 'selected' : '' ?>>Steadfast Courier</option></select></label>
    <div class="g2"><?= sfield('steadfast_key', 'Steadfast API key', 'text', '', 'autocomplete="off"') ?><?= field('steadfast_secret', 'Steadfast secret key', '', 'password', 'Find both in your Steadfast panel under API.', 'autocomplete="off" ' . $saved('steadfast_secret')) ?></div>
    <p class="muted sm">When you press Send, the order code, name, phone, address and cash to collect go to Steadfast. The tracking number comes back and is saved on the order.</p>
  </section>
<?php break; case 'sms': ?>
  <section class="card"><?= card_h('SMS notifications', 'Text customers when they order and when the parcel ships') ?>
    <?= toggle('sms_on', 'Send SMS', S('sms_on') === '1', 'Needs an SMS account, like BulkSMSBD') ?>
    <?= sfield('sms_url', 'SMS API link') ?>
    <div class="g2"><?= field('sms_api_key', 'API key', '', 'password', '', 'autocomplete="off" ' . $saved('sms_api_key')) ?><?= sfield('sms_sender', 'Sender ID') ?></div>
    <?= sfield('sms_order', 'Message after an order', 'area', 'You can use {name} {code} {total}') ?>
    <?= sfield('sms_shipped', 'Message when shipped', 'area', 'You can use {name} {code} {tracking}') ?>
    <?= field('test_phone', 'Send a test SMS to (optional)', '', 'tel', '', 'placeholder="01XXXXXXXXX"') ?>
  </section>
<?php break; case 'pixel': ?>
  <section class="card"><?= card_h('Meta Pixel & Conversions API', 'Track visits, add to bag and purchases for Facebook ads') ?>
    <div class="g2"><?= sfield('pixel_id', 'Meta Pixel ID', 'text', 'Numbers only, from Events Manager') ?><?= sfield('ga_id', 'Google Analytics ID', 'text', 'Like G-XXXXXXX (optional)') ?></div>
    <?= field('capi_token', 'Conversions API access token', '', 'password', 'Events Manager > Settings > Conversions API > Generate access token', 'autocomplete="off" ' . $saved('capi_token')) ?>
    <?php if (S('capi_token') !== ''): ?><?= toggle('capi_clear', 'Remove the saved token', false) ?><?php endif; ?>
    <?= sfield('capi_test', 'Test event code (optional)', 'text', 'Only while testing in Events Manager. Clear it after.') ?>
    <p class="cstat-l"><span class="livedot <?= S('pixel_id') !== '' && S('capi_token') !== '' ? '' : 'off' ?>"><?= S('pixel_id') !== '' && S('capi_token') !== '' ? 'CAPI ON' : 'CAPI OFF' ?></span> <?= (int)S('capi_count') ?> server events sent so far. Browser and server events share one event ID, so Meta counts each purchase once.</p>
  </section>
<?php break; case 'seo': ?>
  <section class="card"><?= card_h('Google SEO', 'How your shop looks in Google search') ?>
    <?= sfield('meta_title', 'Homepage title', 'text', 'About 60 characters') ?>
    <?= sfield('meta_desc', 'Description', 'area', 'About 155 characters') ?>
    <?= sfield('meta_keywords', 'Keywords', 'text', 'Comma separated') ?>
    <div class="gprev"><small><?= e(preg_replace('~^https?://~', '', url())) ?></small><b><?= e(S('meta_title')) ?></b><p><?= e(S('meta_desc')) ?></p></div>
  </section>
  <section class="card"><?= card_h('AI copywriter', 'Writes product descriptions for you') ?>
    <?= field('gemini_key', 'Google Gemini API key (free)', '', 'password', 'Get one free at aistudio.google.com. Without a key, a simple built-in writer is used.', 'autocomplete="off" ' . $saved('gemini_key')) ?>
  </section>
<?php break; case 'security': ?>
  <section class="card narrow"><?= card_h('Change your password', 'Signed in as @' . $ME['username']) ?>
    <?= field('old', 'Current password', '', 'password', '', 'required autocomplete="current-password"') ?>
    <?= field('p1', 'New password', '', 'password', 'At least 6 characters', 'required minlength="6" autocomplete="new-password"') ?>
    <?= field('p2', 'New password again', '', 'password', '', 'required minlength="6" autocomplete="new-password"') ?>
  </section>
<?php break; endswitch; ?>
<div class="sticky-save"><button class="btn"><?= $tab === 'security' ? 'Change password' : 'Save settings' ?></button></div>
</form>
<?php
    foot(); exit;
}

go();
