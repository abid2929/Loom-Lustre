<?php
// Loom & Lustre admin panel (Bangla). Products, categories, orders, home slider, settings.
require dirname(__DIR__) . '/inc/core.php';

session_name('lladmin');
session_set_cookie_params(['lifetime' => 0, 'path' => base_url(), 'httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
session_start();
header('X-Frame-Options: DENY');
header('X-Robots-Tag: noindex');

function csrf(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16)); return $_SESSION['csrf']; }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf() . '">'; }
function csrf_ok(): bool { $t = $_POST['csrf'] ?? ''; return is_string($t) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t); }
function aurl(string $q = ''): string {
    if (defined('LL_DEMO')) { // static demo: each view is its own .html file
        parse_str($q, $a); $v = (string)($a['v'] ?? '');
        if ($v === '' || $v === 'home' || isset($a['logout'])) return url('admin/');
        return url('admin/' . $v . (isset($a['id']) ? '-' . (int)$a['id'] : '') . '.html');
    }
    return url('admin/') . ($q !== '' ? '?' . $q : '');
}
function go(string $q = '', string $flash = ''): never { if ($flash) $_SESSION['flash'] = $flash; header('Location: ' . aurl($q)); exit; }
function bn(int|string $n): string { return strtr((string)$n, ['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯']); }
function tk($n): string { return '৳' . bn(number_format((int)$n)); }
function P(string $k, string $d = ''): string { return trim((string)($_POST[$k] ?? $d)); }

/** Save one uploaded image to uploads/, resized to max 1200px webp (and a 400px -s copy). Returns relative path or ''. */
function save_upload(array $f): string {
    if (($f['error'] ?? 4) !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) return '';
    if ($f['size'] > 12 * 1024 * 1024) return '';
    $info = @getimagesize($f['tmp_name']);
    if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF], true)) return '';
    $dir = LL_ROOT . '/uploads/' . date('Y-m');
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $name = date('dHis') . '-' . bin2hex(random_bytes(3));
    $ext = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif'][$info[2]];
    if (function_exists('imagewebp') && function_exists('imagecreatefromstring')) {
        $src = @imagecreatefromstring((string)file_get_contents($f['tmp_name']));
        if ($src) {
            if (function_exists('exif_read_data') && $info[2] === IMAGETYPE_JPEG) {
                $ex = @exif_read_data($f['tmp_name']); $o = (int)($ex['Orientation'] ?? 1);
                if ($o === 3) $src = imagerotate($src, 180, 0); elseif ($o === 6) $src = imagerotate($src, -90, 0); elseif ($o === 8) $src = imagerotate($src, 90, 0);
            }
            foreach (['' => 1400, '-s' => 420] as $suf => $max) {
                $w = imagesx($src); $h = imagesy($src); $r = min(1, $max / max($w, $h));
                $dst = imagecreatetruecolor((int)round($w * $r), (int)round($h * $r));
                imagealphablending($dst, false); imagesavealpha($dst, true);
                imagecopyresampled($dst, $src, 0, 0, 0, 0, imagesx($dst), imagesy($dst), $w, $h);
                imagewebp($dst, "$dir/$name$suf.webp", 82);
                imagedestroy($dst);
            }
            imagedestroy($src);
            return 'uploads/' . date('Y-m') . "/$name.webp";
        }
    }
    if (!move_uploaded_file($f['tmp_name'], "$dir/$name.$ext")) return '';
    return 'uploads/' . date('Y-m') . "/$name.$ext";
}
function uploads_from(string $field): array {
    $out = [];
    if (empty($_FILES[$field])) return $out;
    $F = $_FILES[$field];
    if (is_array($F['name'])) { foreach ($F['name'] as $i => $n) { $p = save_upload(['name' => $n, 'tmp_name' => $F['tmp_name'][$i], 'error' => $F['error'][$i], 'size' => $F['size'][$i]]); if ($p) $out[] = $p; } }
    else { $p = save_upload($F); if ($p) $out[] = $p; }
    return $out;
}

/* ---------- login / first-time setup ---------- */
$hash = S('admin_pass');
$act = (string)($_POST['act'] ?? '');
if (!$hash && $act === 'setup' && csrf_ok()) {
    $p1 = (string)($_POST['p1'] ?? ''); $p2 = (string)($_POST['p2'] ?? '');
    if (mb_strlen($p1) < 6 || $p1 !== $p2) { $_SESSION['flash'] = 'পাসওয়ার্ড কমপক্ষে ৬ অক্ষরের হতে হবে আর দুইবার একই লিখতে হবে।'; }
    else { set_setting('admin_pass', password_hash($p1, PASSWORD_DEFAULT)); session_regenerate_id(true); $_SESSION['admin'] = 1; go('', 'পাসওয়ার্ড সেট হয়েছে। স্বাগতম!'); }
}
if ($hash && $act === 'login' && csrf_ok()) {
    if (!rate_ok('login', 8, 900)) $_SESSION['flash'] = 'অনেকবার ভুল হয়েছে। ১৫ মিনিট পরে চেষ্টা করুন।';
    elseif (password_verify((string)($_POST['pass'] ?? ''), $hash)) { session_regenerate_id(true); $_SESSION['admin'] = 1; go(); }
    else $_SESSION['flash'] = 'পাসওয়ার্ড ভুল।';
}
if (isset($_GET['logout'])) { $_SESSION = []; session_destroy(); header('Location: ' . aurl()); exit; }

$view = (string)($_GET['v'] ?? 'home');
$flash = $_SESSION['flash'] ?? ''; unset($_SESSION['flash']);

function head(string $title, string $view): void {
    $new = (int)db()->query("SELECT COUNT(*) FROM orders WHERE status='new'")->fetchColumn();
    $nav = ['home' => 'ড্যাশবোর্ড', 'orders' => 'অর্ডার', 'products' => 'প্রোডাক্ট', 'cats' => 'ক্যাটাগরি', 'slider' => 'হোম স্লাইডার', 'settings' => 'সেটিংস'];
    ?><!doctype html><html lang="bn"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex">
<title><?= e($title) ?> | অ্যাডমিন</title>
<link rel="icon" href="<?= url('assets/img/favicon-32.png') ?>">
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Great+Vibes&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('admin/admin.css') ?>?v=<?= @filemtime(__DIR__ . '/admin.css') ?>">
</head><body>
<?php if (defined('LL_DEMO')): ?><div class="demo-bar">এটা ডেমো অ্যাডমিন। এখানে কিছু সেভ হবে না। আসল সাইটে পাসওয়ার্ড দিয়ে ঢুকতে হবে।</div><?php endif; ?>
<header class="top"><a class="logo" href="<?= aurl() ?>"><img src="<?= url('assets/img/lady.png') ?>" alt=""><b><?= e(S('shop_name')) ?></b></a>
<a class="site" href="<?= url() ?>" target="_blank">সাইট দেখুন ↗</a><a class="out" href="<?= aurl('logout=1') ?>">লগআউট</a></header>
<nav class="side"><?php foreach ($nav as $k => $n): ?><a href="<?= aurl('v=' . $k) ?>" class="<?= $view === $k || ($k === 'products' && $view === 'edit') || ($k === 'orders' && $view === 'order') ? 'on' : '' ?>"><?= $n ?><?php if ($k === 'orders' && $new): ?><i><?= bn($new) ?></i><?php endif; ?></a><?php endforeach; ?></nav>
<main class="main">
<?php
}
function foot(): void { if (defined('LL_DEMO')) echo '<script>document.addEventListener("submit",function(e){e.preventDefault();alert("ডেমো: এখানে কিছু সেভ হয় না। আসল সাইটে সেভ হবে।")},true)</script>'; echo '</main><script src="' . url('admin/admin.js') . '?v=' . @filemtime(__DIR__ . '/admin.js') . '"></script></body></html>'; }

if (empty($_SESSION['admin']) && !defined('LL_DEMO')) {
    ?><!doctype html><html lang="bn"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>অ্যাডমিন লগইন</title>
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Great+Vibes&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('admin/admin.css') ?>"></head><body class="login">
<form method="post" class="box"><img src="<?= url('assets/img/logo.png') ?>" alt="<?= e(S('shop_name')) ?>" class="login-logo">
<?= csrf_field() ?>
<?php if ($flash): ?><p class="flash err"><?= e($flash) ?></p><?php endif; ?>
<?php if (!$hash): ?>
<h1>প্রথমবার: পাসওয়ার্ড সেট করুন</h1><p class="muted">এই পাসওয়ার্ড দিয়ে পরে অ্যাডমিনে ঢুকবেন। কাউকে বলবেন না।</p>
<input type="hidden" name="act" value="setup"><label>নতুন পাসওয়ার্ড<input type="password" name="p1" required minlength="6" autocomplete="new-password"></label>
<label>আবার লিখুন<input type="password" name="p2" required minlength="6" autocomplete="new-password"></label><button class="btn">সেট করুন</button>
<?php else: ?>
<h1>অ্যাডমিন লগইন</h1><input type="hidden" name="act" value="login"><label>পাসওয়ার্ড<input type="password" name="pass" required autofocus autocomplete="current-password"></label><button class="btn">ঢুকুন</button>
<?php endif; ?></form></body></html><?php
    exit;
}

/* ---------- actions (POST) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !defined('LL_DEMO')) {
    if (!csrf_ok()) go('', 'সেশন শেষ হয়ে গেছে, আবার চেষ্টা করুন।');
    $now = date('Y-m-d H:i:s');
    switch ($act) {
    case 'product':
        $id = (int)($_POST['id'] ?? 0);
        $name = P('name'); if ($name === '') go('v=edit&id=' . $id, 'প্রোডাক্টের নাম লিখুন।');
        $imgs = array_values(array_filter((array)($_POST['keep'] ?? []), fn($x) => is_string($x) && $x !== '' && !str_contains($x, '..')));
        $imgs = array_merge($imgs, uploads_from('photos'));
        $f = [
            'name' => $name, 'category_id' => (int)P('category_id') ?: null, 'brand' => P('brand'), 'price' => max(0, (int)P('price')), 'old_price' => max(0, (int)P('old_price')),
            'images' => json_encode($imgs), 'shades' => P('shades'), 'size' => P('size'), 'stock' => max(0, (int)P('stock')), 'short' => P('short'),
            'description' => P('description'), 'how_to' => P('how_to'), 'tags' => P('tags'), 'featured' => isset($_POST['featured']) ? 1 : 0,
            'bestseller' => isset($_POST['bestseller']) ? 1 : 0, 'is_new' => isset($_POST['is_new']) ? 1 : 0, 'active' => isset($_POST['active']) ? 1 : 0, 'updated' => $now,
        ];
        if ($id) {
            $set = implode(',', array_map(fn($k) => "$k=?", array_keys($f)));
            db()->prepare("UPDATE products SET $set WHERE id=?")->execute([...array_values($f), $id]);
        } else {
            $f['slug'] = unique_slug('products', $name); $f['created'] = $now;
            $f['sort'] = (int)db()->query('SELECT COALESCE(MIN(sort),0)-1 FROM products')->fetchColumn();
            db()->prepare('INSERT INTO products(' . implode(',', array_keys($f)) . ') VALUES(' . rtrim(str_repeat('?,', count($f)), ',') . ')')->execute(array_values($f));
            $id = (int)db()->lastInsertId();
        }
        go('v=edit&id=' . $id, 'সেভ হয়েছে ✓');
    case 'product_del':
        db()->prepare('DELETE FROM products WHERE id=?')->execute([(int)P('id')]);
        go('v=products', 'প্রোডাক্ট মুছে ফেলা হয়েছে।');
    case 'quick':
        $id = (int)P('id');
        db()->prepare('UPDATE products SET price=?, stock=?, active=? WHERE id=?')->execute([(int)P('price'), max(0, (int)P('stock')), isset($_POST['active']) ? 1 : 0, $id]);
        go('v=products' . (P('back') ? '&' . P('back') : ''), 'আপডেট হয়েছে ✓');
    case 'cat':
        $id = (int)P('id'); $name = P('name'); if ($name === '') go('v=cats', 'নাম লিখুন।');
        $up = uploads_from('image'); $img = $up[0] ?? P('image_old');
        if ($id) db()->prepare('UPDATE categories SET name=?, tagline=?, image=?, sort=?, active=? WHERE id=?')->execute([$name, P('tagline'), $img, (int)P('sort'), isset($_POST['active']) ? 1 : 0, $id]);
        else db()->prepare('INSERT INTO categories(name,slug,tagline,image,sort,active) VALUES(?,?,?,?,?,1)')->execute([$name, unique_slug('categories', $name), P('tagline'), $img, (int)P('sort')]);
        go('v=cats', 'সেভ হয়েছে ✓');
    case 'cat_del':
        $id = (int)P('id');
        $n = db()->prepare('SELECT COUNT(*) FROM products WHERE category_id=?'); $n->execute([$id]);
        if ((int)$n->fetchColumn() > 0) go('v=cats', 'এই ক্যাটাগরিতে প্রোডাক্ট আছে। আগে প্রোডাক্টগুলো অন্য ক্যাটাগরিতে নিন।');
        db()->prepare('DELETE FROM categories WHERE id=?')->execute([$id]);
        go('v=cats', 'মুছে ফেলা হয়েছে।');
    case 'order':
        $id = (int)P('id'); $st = P('status');
        if (!isset(ORDER_STATUS[$st])) $st = 'new';
        $o = db()->prepare('SELECT status, items FROM orders WHERE id=?'); $o->execute([$id]); $old = $o->fetch();
        if ($old && in_array($st, ['cancelled', 'returned'], true) && !in_array($old['status'], ['cancelled', 'returned'], true)) {
            $inc = db()->prepare('UPDATE products SET stock=stock+?, sold=MAX(0,sold-?) WHERE id=?');
            foreach (json_decode($old['items'], true) ?: [] as $it) $inc->execute([(int)$it['qty'], (int)$it['qty'], (int)$it['id']]);
        }
        db()->prepare('UPDATE orders SET status=?, admin_note=?, seen=1, updated=? WHERE id=?')->execute([$st, P('admin_note'), $now, $id]);
        go('v=order&id=' . $id, 'অর্ডার আপডেট হয়েছে ✓');
    case 'order_del':
        db()->prepare('DELETE FROM orders WHERE id=?')->execute([(int)P('id')]);
        go('v=orders', 'অর্ডার মুছে ফেলা হয়েছে।');
    case 'slider':
        for ($i = 1; $i <= 4; $i++) {
            foreach (['kicker', 'title', 'text', 'btn', 'link'] as $k) set_setting("s{$i}_$k", P("s{$i}_$k"));
            set_setting("s{$i}_on", isset($_POST["s{$i}_on"]) ? '1' : '0');
            set_setting("s{$i}_dark", isset($_POST["s{$i}_dark"]) ? '1' : '0');
            $up = uploads_from("s{$i}_file"); if ($up) set_setting("s{$i}_img", $up[0]);
        }
        go('v=slider', 'স্লাইডার সেভ হয়েছে ✓');
    case 'settings':
        foreach (['shop_name', 'shop_sub', 'announce', 'phone', 'whatsapp', 'email', 'address', 'facebook', 'instagram', 'tiktok', 'youtube', 'delivery_in', 'delivery_out', 'delivery_in_label', 'delivery_out_label', 'free_over', 'about', 'meta_desc', 'wa_popup', 'pixel_id', 'ga_id', 'notify_email'] as $k) set_setting($k, P($k));
        go('v=settings', 'সেটিংস সেভ হয়েছে ✓');
    case 'password':
        if (!password_verify((string)($_POST['old'] ?? ''), S('admin_pass'))) go('v=settings', 'পুরনো পাসওয়ার্ড ভুল।');
        $p1 = (string)($_POST['p1'] ?? '');
        if (mb_strlen($p1) < 6 || $p1 !== ($_POST['p2'] ?? '')) go('v=settings', 'নতুন পাসওয়ার্ড কমপক্ষে ৬ অক্ষরের আর দুইবার একই হতে হবে।');
        set_setting('admin_pass', password_hash($p1, PASSWORD_DEFAULT));
        go('v=settings', 'পাসওয়ার্ড বদলানো হয়েছে ✓');
    }
    go();
}

/* ---------- views ---------- */
$cats = categories(false);
$catName = array_column($cats, 'name', 'id');

switch ($view) {
case 'orders':
    head('অর্ডার', $view);
    $f = (string)($_GET['s'] ?? '');
    $qq = trim((string)($_GET['q'] ?? ''));
    $w = []; $a = [];
    if (isset(ORDER_STATUS[$f])) { $w[] = 'status=?'; $a[] = $f; }
    if ($qq !== '') { $w[] = '(code LIKE ? OR phone LIKE ? OR name LIKE ?)'; $l = "%$qq%"; array_push($a, $l, $l, $l); }
    $st = db()->prepare('SELECT * FROM orders' . ($w ? ' WHERE ' . implode(' AND ', $w) : '') . ' ORDER BY id DESC LIMIT 300'); $st->execute($a); $list = $st->fetchAll();
    $counts = db()->query('SELECT status, COUNT(*) n FROM orders GROUP BY status')->fetchAll(PDO::FETCH_KEY_PAIR);
    ?>
<h1>অর্ডার</h1>
<div class="tabs"><a href="<?= aurl('v=orders') ?>" class="<?= $f === '' ? 'on' : '' ?>">সব</a><?php foreach (ORDER_STATUS as $k => $n): ?><a href="<?= aurl('v=orders&s=' . $k) ?>" class="<?= $f === $k ? 'on' : '' ?> st-<?= $k ?>"><?= $n ?> <small><?= bn($counts[$k] ?? 0) ?></small></a><?php endforeach; ?></div>
<form class="searchbar"><input type="hidden" name="v" value="orders"><input name="q" value="<?= e($qq) ?>" placeholder="কোড, নাম বা মোবাইল নম্বর দিয়ে খুঁজুন"><button class="btn sm">খুঁজুন</button></form>
<?php if (!$list): ?><p class="empty">কোনো অর্ডার নেই।</p><?php else: ?>
<div class="olist"><?php foreach ($list as $o): $it = json_decode($o['items'], true) ?: []; ?>
<a class="orow<?= $o['status'] === 'new' ? ' is-new' : '' ?>" href="<?= aurl('v=order&id=' . $o['id']) ?>">
  <div><b><?= e($o['name']) ?></b><small><?= e($o['phone']) ?> · <?= e($o['code']) ?></small><small><?= e(implode(', ', array_map(fn($i) => $i['name'] . ' ×' . $i['qty'], $it))) ?></small></div>
  <div class="r"><b><?= tk($o['total']) ?></b><span class="pill st-<?= e($o['status']) ?>"><?= e(ORDER_STATUS[$o['status']] ?? $o['status']) ?></span><small><?= e(date('d M, h:i A', strtotime($o['created']))) ?></small></div>
</a><?php endforeach; ?></div>
<?php endif;
    foot(); break;

case 'order':
    $st = db()->prepare('SELECT * FROM orders WHERE id=?'); $st->execute([(int)($_GET['id'] ?? 0)]); $o = $st->fetch();
    if (!$o) go('v=orders');
    if (!$o['seen'] && !defined('LL_DEMO')) db()->prepare('UPDATE orders SET seen=1 WHERE id=?')->execute([$o['id']]);
    $items = json_decode($o['items'], true) ?: [];
    head('অর্ডার ' . $o['code'], 'order');
    $ph = norm_phone($o['phone']);
    $msg = "আসসালামু আলাইকুম {$o['name']},\n" . S('shop_name') . " থেকে বলছি। আপনার অর্ডার {$o['code']} পেয়েছি।\n" . implode("\n", array_map(fn($i) => "• {$i['name']}" . ($i['shade'] ? " ({$i['shade']})" : '') . " ×{$i['qty']}", $items)) . "\nমোট: ৳{$o['total']} (ক্যাশ অন ডেলিভারি)\nঅর্ডারটি কনফার্ম করতে 'হ্যাঁ' লিখুন।";
    ?>
<?php if ($flash): ?><p class="flash"><?= e($flash) ?></p><?php endif; ?>
<a class="back" href="<?= aurl('v=orders') ?>">← সব অর্ডার</a>
<h1>অর্ডার <?= e($o['code']) ?> <span class="pill st-<?= e($o['status']) ?>"><?= e(ORDER_STATUS[$o['status']]) ?></span></h1>
<div class="two">
<section class="card">
  <h2>কাস্টমার</h2>
  <p class="big"><b><?= e($o['name']) ?></b></p>
  <p><a href="tel:<?= e($ph) ?>"><?= e($o['phone']) ?></a></p>
  <p><?= nl2br(e($o['address'])) ?></p>
  <p class="muted"><?= $o['area'] === 'out' ? e(S('delivery_out_label')) : e(S('delivery_in_label')) ?> · <?= e(date('d M Y, h:i A', strtotime($o['created']))) ?></p>
  <?php if ($o['note'] !== ''): ?><p class="note">নোট: <?= e($o['note']) ?></p><?php endif; ?>
  <div class="acts"><a class="btn wa" href="https://wa.me/88<?= e($ph) ?>?text=<?= rawurlencode($msg) ?>" target="_blank">হোয়াটসঅ্যাপে কনফার্ম করুন</a><a class="btn ghost" href="tel:<?= e($ph) ?>">কল করুন</a></div>
</section>
<section class="card">
  <h2>প্রোডাক্ট</h2>
  <?php foreach ($items as $it): ?><div class="oit"><img src="<?= e(img_small($it['img'] ?? '')) ?>" alt=""><div><b><?= e($it['name']) ?></b><small><?= $it['shade'] ? 'শেড: ' . e($it['shade']) . ' · ' : '' ?><?= bn($it['qty']) ?> × <?= tk($it['price']) ?></small></div><b><?= tk($it['qty'] * $it['price']) ?></b></div><?php endforeach; ?>
  <div class="sumr"><span>সাবটোটাল</span><b><?= tk($o['subtotal']) ?></b></div>
  <div class="sumr"><span>ডেলিভারি</span><b><?= tk($o['delivery']) ?></b></div>
  <div class="sumr tot"><span>মোট (ক্যাশ অন ডেলিভারি)</span><b><?= tk($o['total']) ?></b></div>
</section>
</div>
<form method="post" class="card form">
  <?= csrf_field() ?><input type="hidden" name="act" value="order"><input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
  <h2>স্ট্যাটাস বদলান</h2>
  <div class="stbtns"><?php foreach (ORDER_STATUS as $k => $n): ?><label class="st-<?= $k ?>"><input type="radio" name="status" value="<?= $k ?>" <?= $o['status'] === $k ? 'checked' : '' ?>><span><?= $n ?></span></label><?php endforeach; ?></div>
  <p class="muted">বাতিল বা ফেরত দিলে স্টক আপনাআপনি ফেরত যাবে।</p>
  <label>নিজের নোট (কাস্টমার দেখবে না)<textarea name="admin_note" rows="2"><?= e($o['admin_note']) ?></textarea></label>
  <button class="btn">সেভ করুন</button>
</form>
<form method="post" class="danger" data-confirm="এই অর্ডারটা একদম মুছে ফেলবেন?"><?= csrf_field() ?><input type="hidden" name="act" value="order_del"><input type="hidden" name="id" value="<?= (int)$o['id'] ?>"><button>অর্ডার মুছে ফেলুন</button></form>
<?php
    foot(); break;

case 'products':
    head('প্রোডাক্ট', $view);
    $c = (int)($_GET['c'] ?? 0); $qq = trim((string)($_GET['q'] ?? ''));
    $w = []; $a = [];
    if ($c) { $w[] = 'category_id=?'; $a[] = $c; }
    if ($qq !== '') { $w[] = 'name LIKE ?'; $a[] = "%$qq%"; }
    if (isset($_GET['low'])) $w[] = 'stock<=5';
    $st = db()->prepare('SELECT * FROM products' . ($w ? ' WHERE ' . implode(' AND ', $w) : '') . ' ORDER BY sort, id DESC'); $st->execute($a); $list = $st->fetchAll();
    $back = http_build_query(array_filter(['c' => $c ?: null, 'q' => $qq ?: null]));
    ?>
<?php if ($flash): ?><p class="flash"><?= e($flash) ?></p><?php endif; ?>
<div class="h-row"><h1>প্রোডাক্ট <small><?= bn(count($list)) ?>টি</small></h1><a class="btn" href="<?= aurl('v=edit') ?>">+ নতুন প্রোডাক্ট</a></div>
<div class="tabs"><a href="<?= aurl('v=products') ?>" class="<?= !$c && !isset($_GET['low']) ? 'on' : '' ?>">সব</a><?php foreach ($cats as $ct): ?><a href="<?= aurl('v=products&c=' . $ct['id']) ?>" class="<?= $c === (int)$ct['id'] ? 'on' : '' ?>"><?= e($ct['name']) ?></a><?php endforeach; ?><a href="<?= aurl('v=products&low=1') ?>" class="<?= isset($_GET['low']) ? 'on' : '' ?>">স্টক কম</a></div>
<form class="searchbar"><input type="hidden" name="v" value="products"><input name="q" value="<?= e($qq) ?>" placeholder="প্রোডাক্টের নাম লিখে খুঁজুন"><button class="btn sm">খুঁজুন</button></form>
<div class="plist"><?php foreach ($list as $p): ?>
<form method="post" class="prow<?= $p['active'] ? '' : ' off' ?>">
  <?= csrf_field() ?><input type="hidden" name="act" value="quick"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="back" value="<?= e($back) ?>">
  <a href="<?= aurl('v=edit&id=' . $p['id']) ?>"><img src="<?= e(img_small(p_image($p))) ?>" alt=""></a>
  <div class="pn"><a href="<?= aurl('v=edit&id=' . $p['id']) ?>"><b><?= e($p['name']) ?></b></a><small><?= e($catName[$p['category_id']] ?? '—') ?><?= (int)$p['stock'] <= 5 ? ' · <em>স্টক কম</em>' : '' ?></small></div>
  <label>দাম<input type="number" name="price" value="<?= (int)$p['price'] ?>" min="0"></label>
  <label>স্টক<input type="number" name="stock" value="<?= (int)$p['stock'] ?>" min="0"></label>
  <label class="chk"><input type="checkbox" name="active" <?= $p['active'] ? 'checked' : '' ?>>চালু</label>
  <button class="btn sm ghost">সেভ</button>
</form><?php endforeach; ?></div>
<?php
    foot(); break;

case 'edit':
    $id = (int)($_GET['id'] ?? 0);
    $p = ['id' => 0, 'name' => '', 'category_id' => $cats[0]['id'] ?? 0, 'brand' => S('shop_name'), 'price' => '', 'old_price' => '', 'images' => '[]', 'shades' => '', 'size' => '', 'stock' => 20, 'short' => '', 'description' => '', 'how_to' => '', 'tags' => '', 'featured' => 0, 'bestseller' => 0, 'is_new' => 1, 'active' => 1, 'slug' => ''];
    if ($id) { $st = db()->prepare('SELECT * FROM products WHERE id=?'); $st->execute([$id]); $p = $st->fetch() ?: go('v=products'); }
    head($id ? 'এডিট: ' . $p['name'] : 'নতুন প্রোডাক্ট', 'edit');
    ?>
<?php if ($flash): ?><p class="flash"><?= e($flash) ?></p><?php endif; ?>
<a class="back" href="<?= aurl('v=products') ?>">← সব প্রোডাক্ট</a>
<div class="h-row"><h1><?= $id ? 'প্রোডাক্ট এডিট' : 'নতুন প্রোডাক্ট' ?></h1><?php if ($id): ?><a class="btn ghost sm" href="<?= url('p/' . $p['slug']) ?>" target="_blank">সাইটে দেখুন ↗</a><?php endif; ?></div>
<form method="post" enctype="multipart/form-data" class="card form">
  <?= csrf_field() ?><input type="hidden" name="act" value="product"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
  <label>প্রোডাক্টের নাম *<input name="name" required value="<?= e($p['name']) ?>" placeholder="যেমন: Velvet Red Matte Lipstick"></label>
  <div class="g3">
    <label>ক্যাটাগরি<select name="category_id"><?php foreach ($cats as $ct): ?><option value="<?= $ct['id'] ?>" <?= (int)$p['category_id'] === (int)$ct['id'] ? 'selected' : '' ?>><?= e($ct['name']) ?></option><?php endforeach; ?></select></label>
    <label>বিক্রির দাম (৳) *<input type="number" name="price" required min="0" value="<?= e((string)$p['price']) ?>"></label>
    <label>আগের দাম (৳) <small>ছাড় দেখাতে</small><input type="number" name="old_price" min="0" value="<?= e((string)($p['old_price'] ?: '')) ?>"></label>
  </div>
  <div class="g3">
    <label>স্টক (কয়টা আছে)<input type="number" name="stock" min="0" value="<?= (int)$p['stock'] ?>"></label>
    <label>সাইজ / পরিমাণ<input name="size" value="<?= e($p['size']) ?>" placeholder="যেমন: 30 ml, 3.8 g"></label>
    <label>ব্র্যান্ড<input name="brand" value="<?= e($p['brand']) ?>"></label>
  </div>
  <label>শেড / রং <small>কমা দিয়ে আলাদা করুন। না থাকলে খালি রাখুন।</small><input name="shades" value="<?= e($p['shades']) ?>" placeholder="Classic Red, Ruby, Nude"></label>
  <div class="photos">
    <span class="lbl">ছবি <small>প্রথম ছবিটাই প্রধান ছবি। ছবিতে চাপ দিয়ে টেনে সাজাতে পারবেন।</small></span>
    <div class="ph-list" id="phList"><?php foreach (p_images($p) as $im): ?><div class="ph" draggable="true"><img src="<?= e(img_small($im)) ?>" alt=""><input type="hidden" name="keep[]" value="<?= e($im) ?>"><button type="button" class="ph-x" aria-label="ছবি সরান">×</button></div><?php endforeach; ?>
      <label class="ph-add">+ ছবি যোগ<input type="file" name="photos[]" accept="image/*" multiple hidden></label></div>
    <div class="ph-new" id="phNew"></div>
  </div>
  <label>ছোট বর্ণনা (এক লাইনে)<input name="short" value="<?= e($p['short']) ?>" maxlength="140"></label>
  <label>পুরো বর্ণনা<textarea name="description" rows="5"><?= e($p['description']) ?></textarea></label>
  <label>কীভাবে ব্যবহার করবেন<textarea name="how_to" rows="2"><?= e($p['how_to']) ?></textarea></label>
  <label>সার্চ ট্যাগ <small>কাস্টমার যা লিখে খুঁজতে পারে</small><input name="tags" value="<?= e($p['tags']) ?>" placeholder="lipstick, red, matte, লিপস্টিক"></label>
  <div class="checks">
    <label class="chk"><input type="checkbox" name="active" <?= $p['active'] ? 'checked' : '' ?>>সাইটে দেখাবে</label>
    <label class="chk"><input type="checkbox" name="featured" <?= $p['featured'] ? 'checked' : '' ?>>সামনে রাখুন</label>
    <label class="chk"><input type="checkbox" name="bestseller" <?= $p['bestseller'] ? 'checked' : '' ?>>বেস্টসেলার</label>
    <label class="chk"><input type="checkbox" name="is_new" <?= $p['is_new'] ? 'checked' : '' ?>>নতুন</label>
  </div>
  <button class="btn big">সেভ করুন</button>
</form>
<?php if ($id): ?><form method="post" class="danger" data-confirm="এই প্রোডাক্টটা মুছে ফেলবেন?"><?= csrf_field() ?><input type="hidden" name="act" value="product_del"><input type="hidden" name="id" value="<?= $id ?>"><button>প্রোডাক্ট মুছে ফেলুন</button></form><?php endif; ?>
<?php
    foot(); break;

case 'cats':
    head('ক্যাটাগরি', $view);
    $cnt = db()->query('SELECT category_id, COUNT(*) FROM products GROUP BY category_id')->fetchAll(PDO::FETCH_KEY_PAIR);
    ?>
<?php if ($flash): ?><p class="flash"><?= e($flash) ?></p><?php endif; ?>
<h1>ক্যাটাগরি</h1>
<p class="muted">হোম পেজের গোল ছবিগুলো আর মেনু এখান থেকে আসে। ছোট নম্বর আগে দেখাবে।</p>
<?php foreach ([...$cats, ['id' => 0, 'name' => '', 'tagline' => '', 'image' => '', 'sort' => count($cats) + 1, 'active' => 1, 'slug' => '']] as $ct): ?>
<form method="post" enctype="multipart/form-data" class="card cat-row<?= $ct['id'] ? '' : ' new' ?>">
  <?= csrf_field() ?><input type="hidden" name="act" value="cat"><input type="hidden" name="id" value="<?= (int)$ct['id'] ?>"><input type="hidden" name="image_old" value="<?= e($ct['image']) ?>">
  <label class="cat-img"><?php if ($ct['image']): ?><img src="<?= e(img_small($ct['image'])) ?>" alt=""><?php else: ?><span>+ ছবি</span><?php endif; ?><input type="file" name="image" accept="image/*" hidden></label>
  <div class="g3">
    <label><?= $ct['id'] ? 'নাম' : 'নতুন ক্যাটাগরির নাম' ?><input name="name" value="<?= e($ct['name']) ?>" <?= $ct['id'] ? 'required' : '' ?>></label>
    <label>ছোট লাইন<input name="tagline" value="<?= e($ct['tagline']) ?>"></label>
    <label>ক্রম<input type="number" name="sort" value="<?= (int)$ct['sort'] ?>"></label>
  </div>
  <div class="cat-act"><?php if ($ct['id']): ?><label class="chk"><input type="checkbox" name="active" <?= $ct['active'] ? 'checked' : '' ?>>চালু</label><small><?= bn($cnt[$ct['id']] ?? 0) ?>টি প্রোডাক্ট</small><?php endif; ?><button class="btn sm"><?= $ct['id'] ? 'সেভ' : '+ যোগ করুন' ?></button></div>
</form>
<?php if ($ct['id'] && empty($cnt[$ct['id']])): ?><form method="post" class="danger sm" data-confirm="ক্যাটাগরিটা মুছবেন?"><?= csrf_field() ?><input type="hidden" name="act" value="cat_del"><input type="hidden" name="id" value="<?= (int)$ct['id'] ?>"><button>“<?= e($ct['name']) ?>” মুছুন</button></form><?php endif; ?>
<?php endforeach;
    foot(); break;

case 'slider':
    head('হোম স্লাইডার', $view);
    ?>
<?php if ($flash): ?><p class="flash"><?= e($flash) ?></p><?php endif; ?>
<h1>হোম পেজ স্লাইডার</h1>
<p class="muted">হোম পেজের উপরের বড় ব্যানার। ৫ সেকেন্ড পর পর নিজে নিজে বদলায়। চওড়া ছবি দিন (যেমন 2000×760)।</p>
<form method="post" enctype="multipart/form-data">
<?= csrf_field() ?><input type="hidden" name="act" value="slider">
<?php for ($i = 1; $i <= 4; $i++): ?>
<section class="card form slide-ed">
  <div class="h-row"><h2>স্লাইড <?= bn($i) ?></h2><label class="chk"><input type="checkbox" name="s<?= $i ?>_on" <?= S("s{$i}_on") === '1' ? 'checked' : '' ?>>দেখাবে</label></div>
  <label class="slide-img"><img src="<?= e(img_url(S("s{$i}_img"))) ?>" alt=""><span>ছবি বদলান</span><input type="file" name="s<?= $i ?>_file" accept="image/*" hidden></label>
  <div class="g3">
    <label>ছোট লেখা<input name="s<?= $i ?>_kicker" value="<?= e(S("s{$i}_kicker")) ?>"></label>
    <label>বড় হেডলাইন<input name="s<?= $i ?>_title" value="<?= e(S("s{$i}_title")) ?>"></label>
    <label>বাটনের লেখা<input name="s<?= $i ?>_btn" value="<?= e(S("s{$i}_btn")) ?>"></label>
  </div>
  <label>এক লাইনের বর্ণনা<input name="s<?= $i ?>_text" value="<?= e(S("s{$i}_text")) ?>"></label>
  <div class="g3">
    <label>ক্লিক করলে কোথায় যাবে<select name="s<?= $i ?>_link"><?php $opts = ['shop' => 'সব প্রোডাক্ট', 'sale' => 'সেল', 'new' => 'নতুন']; foreach ($cats as $ct) $opts['c/' . $ct['slug']] = $ct['name']; foreach ($opts as $k => $n): ?><option value="<?= e($k) ?>" <?= S("s{$i}_link") === $k ? 'selected' : '' ?>><?= e($n) ?></option><?php endforeach; ?></select></label>
    <label class="chk"><input type="checkbox" name="s<?= $i ?>_dark" <?= S("s{$i}_dark") === '1' ? 'checked' : '' ?>>কালো লেখার বক্স</label>
  </div>
</section>
<?php endfor; ?>
<button class="btn big">স্লাইডার সেভ করুন</button>
</form>
<?php
    foot(); break;

case 'settings':
    head('সেটিংস', $view);
    $f = function (string $k, string $label, string $type = 'text', string $hint = '') { echo '<label>' . $label . ($hint ? ' <small>' . $hint . '</small>' : '') . ($type === 'area' ? '<textarea name="' . $k . '" rows="3">' . e(S($k)) . '</textarea>' : '<input type="' . $type . '" name="' . $k . '" value="' . e(S($k)) . '">') . '</label>'; };
    ?>
<?php if ($flash): ?><p class="flash"><?= e($flash) ?></p><?php endif; ?>
<h1>সেটিংস</h1>
<form method="post" class="card form">
  <?= csrf_field() ?><input type="hidden" name="act" value="settings">
  <h2>দোকান</h2>
  <div class="g2"><?php $f('shop_name', 'দোকানের নাম'); $f('shop_sub', 'নামের নিচের লেখা'); ?></div>
  <?php $f('announce', 'উপরের লাল লাইনের লেখা', 'text', '• দিয়ে আলাদা করলে একটার পর একটা ঘুরবে'); ?>
  <?php $f('about', 'আমাদের সম্পর্কে', 'area'); ?>
  <h2>যোগাযোগ</h2>
  <div class="g2"><?php $f('phone', 'ফোন নম্বর'); $f('whatsapp', 'হোয়াটসঅ্যাপ নম্বর', 'text', 'খালি রাখলে ফোন নম্বরই ব্যবহার হবে'); $f('email', 'ইমেইল', 'email'); $f('address', 'ঠিকানা'); ?></div>
  <div class="g2"><?php $f('facebook', 'Facebook পেজের লিংক', 'url'); $f('instagram', 'Instagram লিংক', 'url'); $f('tiktok', 'TikTok লিংক', 'url'); $f('youtube', 'YouTube লিংক', 'url'); ?></div>
  <h2>ডেলিভারি চার্জ</h2>
  <div class="g2"><?php $f('delivery_in_label', 'এলাকা ১ এর নাম'); $f('delivery_in', 'এলাকা ১ চার্জ (৳)', 'number'); $f('delivery_out_label', 'এলাকা ২ এর নাম'); $f('delivery_out', 'এলাকা ২ চার্জ (৳)', 'number'); ?></div>
  <?php $f('free_over', 'এত টাকার বেশি অর্ডারে ফ্রি ডেলিভারি (৳)', 'number', '০ দিলে ফ্রি ডেলিভারি বন্ধ'); ?>
  <h2>অন্যান্য</h2>
  <?php $f('wa_popup', 'হোয়াটসঅ্যাপ বাটনের লেখা'); $f('meta_desc', 'গুগলে দেখানোর বর্ণনা', 'area'); ?>
  <div class="g2"><?php $f('notify_email', 'নতুন অর্ডারের ইমেইল যাবে', 'email'); $f('pixel_id', 'Facebook Pixel ID'); $f('ga_id', 'Google Analytics ID'); ?></div>
  <button class="btn big">সেটিংস সেভ করুন</button>
</form>
<form method="post" class="card form">
  <?= csrf_field() ?><input type="hidden" name="act" value="password">
  <h2>পাসওয়ার্ড বদলান</h2>
  <div class="g3"><label>পুরনো পাসওয়ার্ড<input type="password" name="old" required></label><label>নতুন পাসওয়ার্ড<input type="password" name="p1" required minlength="6"></label><label>আবার লিখুন<input type="password" name="p2" required minlength="6"></label></div>
  <button class="btn">পাসওয়ার্ড বদলান</button>
</form>
<p class="muted small">ভার্সন <?= LL_VERSION ?> · ডেটা ফোল্ডার: <?= e(str_replace(LL_ROOT, 'public_html', data_dir())) ?></p>
<?php
    foot(); break;

default:
    head('ড্যাশবোর্ড', 'home');
    $today = date('Y-m-d');
    $T = fn(string $sql, array $a = []) => (function () use ($sql, $a) { $s = db()->prepare($sql); $s->execute($a); return $s->fetchColumn(); })();
    $newO = (int)$T("SELECT COUNT(*) FROM orders WHERE status='new'");
    $todayO = (int)$T('SELECT COUNT(*) FROM orders WHERE substr(created,1,10)=?', [$today]);
    $monthS = (int)$T("SELECT COALESCE(SUM(total),0) FROM orders WHERE status NOT IN ('cancelled','returned') AND substr(created,1,7)=?", [date('Y-m')]);
    $prodN = (int)$T('SELECT COUNT(*) FROM products WHERE active=1');
    $low = db()->query('SELECT * FROM products WHERE stock<=5 ORDER BY stock LIMIT 6')->fetchAll();
    $recent = db()->query('SELECT * FROM orders ORDER BY id DESC LIMIT 6')->fetchAll();
    $days = [];
    for ($i = 6; $i >= 0; $i--) { $d = date('Y-m-d', strtotime("-$i day")); $days[$d] = 0; }
    foreach (db()->query("SELECT substr(created,1,10) d, SUM(total) s FROM orders WHERE status NOT IN ('cancelled','returned') AND created >= '" . array_key_first($days) . "' GROUP BY d") as $r) if (isset($days[$r['d']])) $days[$r['d']] = (int)$r['s'];
    $mx = max(1, ...array_values($days));
    ?>
<?php if ($flash): ?><p class="flash"><?= e($flash) ?></p><?php endif; ?>
<h1>ড্যাশবোর্ড</h1>
<div class="stats">
  <a class="stat hot" href="<?= aurl('v=orders&s=new') ?>"><small>নতুন অর্ডার</small><b><?= bn($newO) ?></b></a>
  <div class="stat"><small>আজকের অর্ডার</small><b><?= bn($todayO) ?></b></div>
  <div class="stat"><small>এই মাসের বিক্রি</small><b><?= tk($monthS) ?></b></div>
  <a class="stat" href="<?= aurl('v=products') ?>"><small>চালু প্রোডাক্ট</small><b><?= bn($prodN) ?></b></a>
</div>
<div class="quick"><a class="btn" href="<?= aurl('v=edit') ?>">+ নতুন প্রোডাক্ট</a><a class="btn ghost" href="<?= aurl('v=slider') ?>">ব্যানার বদলান</a><a class="btn ghost" href="<?= aurl('v=settings') ?>">ফোন / ডেলিভারি চার্জ</a></div>
<div class="two">
<section class="card"><h2>গত ৭ দিনের বিক্রি</h2>
  <div class="chart"><?php foreach ($days as $d => $v): ?><div class="bar"><span style="height:<?= max(3, round($v * 100 / $mx)) ?>%" title="<?= tk($v) ?>"></span><small><?= bn(date('d', strtotime($d))) ?></small></div><?php endforeach; ?></div>
</section>
<section class="card"><h2>স্টক কম</h2>
  <?php if (!$low): ?><p class="muted">সব প্রোডাক্টে যথেষ্ট স্টক আছে।</p><?php endif; ?>
  <?php foreach ($low as $p): ?><a class="oit" href="<?= aurl('v=edit&id=' . $p['id']) ?>"><img src="<?= e(img_small(p_image($p))) ?>" alt=""><div><b><?= e($p['name']) ?></b></div><span class="pill st-cancelled"><?= bn($p['stock']) ?>টি</span></a><?php endforeach; ?>
</section>
</div>
<section class="card"><div class="h-row"><h2>সাম্প্রতিক অর্ডার</h2><a href="<?= aurl('v=orders') ?>">সব দেখুন →</a></div>
<?php if (!$recent): ?><p class="muted">এখনো কোনো অর্ডার আসেনি। সাইট থেকে অর্ডার এলে এখানে দেখাবে।</p><?php endif; ?>
<div class="olist"><?php foreach ($recent as $o): ?><a class="orow<?= $o['status'] === 'new' ? ' is-new' : '' ?>" href="<?= aurl('v=order&id=' . $o['id']) ?>"><div><b><?= e($o['name']) ?></b><small><?= e($o['phone']) ?> · <?= e($o['code']) ?></small></div><div class="r"><b><?= tk($o['total']) ?></b><span class="pill st-<?= e($o['status']) ?>"><?= e(ORDER_STATUS[$o['status']]) ?></span></div></a><?php endforeach; ?></div>
</section>
<?php
    foot();
}
