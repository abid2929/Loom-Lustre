<?php
// Admin panel: layout, menu, small helpers.

const ROLES = ['super' => 'Super Admin', 'manager' => 'Manager', 'staff' => 'Order Staff'];
/** Which pages each role can open. */
const ROLE_VIEWS = [
    'manager' => ['dashboard', 'products', 'product', 'categories', 'collections', 'inventory', 'orders', 'order', 'invoice', 'courier', 'customers', 'reviews', 'banners', 'homepage', 'reports', 'account'],
    'staff' => ['dashboard', 'inventory', 'orders', 'order', 'invoice', 'courier', 'customers', 'account'],
];
function can(string $view): bool {
    $r = $GLOBALS['ME']['role'] ?? 'staff';
    return $r === 'super' || in_array($view, ROLE_VIEWS[$r] ?? [], true);
}

function ai(string $n, int $s = 18): string {
    $p = [
        'dash' => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
        'box' => '<path d="m21 8-9-5-9 5 9 5 9-5Z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/>',
        'layers' => '<path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 13 9 5 9-5"/>',
        'book' => '<path d="M4 5a2 2 0 0 1 2-2h5v18H6a2 2 0 0 1-2-2V5Z"/><path d="M20 5a2 2 0 0 0-2-2h-5v18h5a2 2 0 0 0 2-2V5Z"/>',
        'stock' => '<path d="M3 21V9l9-6 9 6v12"/><path d="M7 21v-8h10v8"/><path d="M7 17h10"/>',
        'cart' => '<circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.5 12h12L22 7H6"/>',
        'truck' => '<path d="M3 6h11v10H3zM14 10h4l3 3v3h-7"/><circle cx="7" cy="17.5" r="1.8"/><circle cx="17" cy="17.5" r="1.8"/>',
        'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2 20c0-3.5 3-6 7-6s7 2.5 7 6"/><circle cx="17.5" cy="9" r="2.5"/><path d="M17 14c3 0 5 2 5 5"/>',
        'star' => '<path d="m12 3 2.8 5.8 6.2.9-4.5 4.4 1.1 6.2L12 17.3l-5.6 3 1.1-6.2L3 9.7l6.2-.9L12 3Z"/>',
        'image' => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="m21 16-5-5-9 9"/>',
        'home' => '<path d="M4 11 12 4l8 7v9h-5v-6H9v6H4v-9Z"/>',
        'chart' => '<path d="M4 20V4"/><path d="M4 20h16"/><path d="m7 15 4-4 3 3 5-6"/>',
        'shield' => '<path d="M12 3 5 6v5c0 4.5 3 8 7 10 4-2 7-5.5 7-10V6l-7-3Z"/>',
        'gear' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z"/>',
        'ext' => '<path d="M14 4h6v6"/><path d="M20 4 10 14"/><path d="M19 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h5"/>',
        'bell' => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 8 3 8H3s3-1 3-8"/><path d="M10 20a2 2 0 0 0 4 0"/>',
        'out' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'eye' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
        'print' => '<path d="M6 9V3h12v6"/><rect x="3" y="9" width="18" height="8" rx="2"/><path d="M6 14h12v7H6z"/>',
        'edit' => '<path d="M4 20h4L19 9l-4-4L4 16v4Z"/><path d="m13 7 4 4"/>',
        'trash' => '<path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'back' => '<path d="m15 6-6 6 6 6"/>',
        'money' => '<rect x="3" y="6" width="18" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/>',
        'bag' => '<path d="M6 7h12l1 13H5L6 7Z"/><path d="M9 7a3 3 0 0 1 6 0"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'check' => '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>',
        'alert' => '<path d="M12 3 2 20h20L12 3Z"/><path d="M12 10v4M12 17h.01"/>',
        'live' => '<circle cx="12" cy="12" r="3"/><path d="M5.6 5.6a9 9 0 0 0 0 12.8M18.4 5.6a9 9 0 0 1 0 12.8"/>',
        'spark' => '<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3Z"/><path d="M19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8L19 15Z"/>',
        'store' => '<path d="M3 9 5 4h14l2 5"/><path d="M4 9v11h16V9"/><path d="M3 9h18"/><path d="M9 20v-6h6v6"/>',
        'card' => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>',
        'sms' => '<path d="M4 4h16v12H8l-4 4V4Z"/><path d="M8 9h8M8 12h5"/>',
        'target' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/>',
        'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3.5 3 14.5 0 18M12 3c-3 3.5-3 14.5 0 18"/>',
        'lock' => '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
        'chev' => '<path d="m6 9 6 6 6-6"/>',
        'refresh' => '<path d="M20 12a8 8 0 1 1-2.3-5.7L20 8"/><path d="M20 3v5h-5"/>',
        'link' => '<path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/>',
    ];
    return '<svg class="i" width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($p[$n] ?? '') . '</svg>';
}

function tk($n): string { return '৳' . number_format((int)$n); }
function pill(string $status): string { return '<span class="pill st-' . e($status) . '">' . e(ORDER_STATUS_A[$status] ?? $status) . '</span>'; }
const ORDER_STATUS_A = ['new' => 'Pending', 'confirmed' => 'Confirmed', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled', 'returned' => 'Returned'];
const STATUS_COLORS = ['new' => '#f59e0b', 'confirmed' => '#3b82f6', 'shipped' => '#8b5cf6', 'delivered' => '#10b981', 'cancelled' => '#ef4444', 'returned' => '#64748b'];

function menu_items(): array {
    $newO = (int)db()->query("SELECT COUNT(*) FROM orders WHERE status='new'")->fetchColumn();
    $pendR = (int)db()->query('SELECT COUNT(*) FROM reviews WHERE approved=0')->fetchColumn();
    $ship = (int)db()->query("SELECT COUNT(*) FROM orders WHERE status IN ('confirmed','shipped')")->fetchColumn();
    return [
        ['dashboard', 'Dashboard', 'dash', ''],
        ['products', 'Products', 'box', ''],
        ['categories', 'Categories', 'layers', ''],
        ['collections', 'Catalog Collections', 'book', ''],
        ['inventory', 'Inventory & Stock', 'stock', ''],
        ['orders', 'Orders', 'cart', $newO ? '<i class="cnt">' . $newO . '</i>' : ''],
        ['courier', 'Courier Hub', 'truck', $ship ? '<i class="cnt g">' . $ship . '</i>' : '<i class="tag">LIVE</i>'],
        ['customers', 'Customers', 'users', ''],
        ['reviews', 'Customer Reviews', 'star', $pendR ? '<i class="cnt">' . $pendR . '</i>' : ''],
        ['banners', 'Hero Banners', 'image', ''],
        ['homepage', 'Homepage CMS', 'home', ''],
        ['reports', 'Reports & Analytics', 'chart', ''],
        ['admins', 'Admins & Roles', 'shield', ''],
    ];
}
const SETTING_TABS = [
    'store' => ['General Store', 'store'], 'delivery' => ['Delivery & Shipping', 'truck'], 'payment' => ['Payment Settings', 'card'],
    'courier' => ['Courier Automation', 'box'], 'sms' => ['SMS Notifications', 'sms'], 'pixel' => ['Meta Pixel & CAPI', 'target'],
    'seo' => ['Google SEO & AI', 'globe'], 'security' => ['Password & Security', 'lock'],
];

function head(string $title, string $view, string $sub = ''): void {
    $me = $GLOBALS['ME'];
    $tab = (string)($_GET['tab'] ?? 'store');
    $newO = db()->query("SELECT id, code, name, total, created FROM orders WHERE status='new' ORDER BY id DESC LIMIT 5")->fetchAll();
    $pendR = (int)db()->query('SELECT COUNT(*) FROM reviews WHERE approved=0')->fetchColumn();
    $lowS = (int)db()->query('SELECT COUNT(*) FROM products WHERE active=1 AND stock<=5')->fetchColumn();
    $bell = count($newO) + ($pendR ? 1 : 0) + ($lowS ? 1 : 0);
    ?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex">
<title><?= e($title) ?> | <?= e(S('shop_name')) ?> Admin</title>
<link rel="icon" href="<?= url('assets/img/favicon-32.png') ?>">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Great+Vibes&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('admin/admin.css') ?>?v=<?= @filemtime(LL_ROOT . '/admin/admin.css') ?>">
</head><body>
<?php if (defined('LL_DEMO')): ?><div class="demo-bar">Demo admin panel with sample data. Nothing is saved here. The real panel needs a username and password.</div><?php endif; ?>
<aside class="side" id="side">
  <a class="brand" href="<?= aurl() ?>"><span class="logo"><img src="<?= url('assets/img/lady.png') ?>" alt=""></span><span><b><?= e(S('shop_name')) ?></b><small>Beauty Business Panel</small></span></a>
  <nav class="nav">
    <?php foreach (menu_items() as [$k, $n, $ic, $extra]): if (!can($k)) continue; $on = $view === $k || ($k === 'products' && $view === 'product') || ($k === 'orders' && in_array($view, ['order', 'invoice'], true)); ?>
    <a href="<?= aurl('v=' . $k) ?>" class="<?= $on ? 'on' : '' ?>"><?= ai($ic) ?><span><?= $n ?></span><?= $extra ?></a>
    <?php endforeach; ?>
    <?php if (can('settings')): ?>
    <details class="sub" <?= $view === 'settings' ? 'open' : '' ?>><summary class="<?= $view === 'settings' ? 'on' : '' ?>"><?= ai('gear') ?><span>Site Settings</span><?= ai('chev', 16) ?></summary>
      <?php foreach (SETTING_TABS as $k => [$n, $ic]): ?><a href="<?= aurl('v=settings&tab=' . $k) ?>" class="<?= $view === 'settings' && $tab === $k ? 'on' : '' ?>"><?= ai($ic, 16) ?><span><?= $n ?></span><?= $k === 'pixel' ? '<i class="tag">CAPI</i>' : '' ?></a><?php endforeach; ?>
    </details>
    <?php endif; ?>
    <a href="<?= url() ?>" target="_blank" class="live"><?= ai('ext') ?><span>View Live Store</span></a>
  </nav>
  <div class="me"><a class="who" href="<?= aurl('v=account') ?>" title="My password"><span class="av"><?= e(mb_strtoupper(mb_substr($me['name'] ?: $me['username'], 0, 1))) ?></span><span><b><?= e($me['name'] ?: $me['username']) ?></b><small><?= e(ROLES[$me['role']] ?? $me['role']) ?></small></span></a><a href="<?= aurl('logout=1') ?>" title="Log out" aria-label="Log out"><?= ai('out', 20) ?></a></div>
</aside>
<div class="shade" id="shade"></div>
<div class="wrapm">
<header class="top">
  <button class="ib only-m" id="menuBtn" aria-label="Menu"><?= ai('menu', 22) ?></button>
  <div class="tt"><h1><?= e($title) ?></h1><?php if ($sub): ?><small><?= e($sub) ?></small><?php endif; ?></div>
  <?php if (can('product')): ?><a class="ask" href="<?= aurl('v=product') ?>"><?= ai('spark', 16) ?><span>Ask AI</span></a><?php endif; ?>
  <details class="bell"><summary class="ib" aria-label="Notifications"><?= ai('bell', 20) ?><?php if ($bell): ?><i class="cnt"><?= $bell ?></i><?php endif; ?></summary>
    <div class="bell-p"><b>Notifications</b>
      <?php foreach ($newO as $o): ?><a href="<?= aurl('v=order&id=' . $o['id']) ?>"><?= ai('cart', 16) ?><span>New order <?= e($o['code']) ?> from <?= e($o['name']) ?> · <?= tk($o['total']) ?></span></a><?php endforeach; ?>
      <?php if ($pendR): ?><a href="<?= aurl('v=reviews') ?>"><?= ai('star', 16) ?><span><?= $pendR ?> review<?= $pendR > 1 ? 's' : '' ?> waiting for approval</span></a><?php endif; ?>
      <?php if ($lowS): ?><a href="<?= aurl('v=inventory&f=low') ?>"><?= ai('alert', 16) ?><span><?= $lowS ?> product<?= $lowS > 1 ? 's are' : ' is' ?> low on stock</span></a><?php endif; ?>
      <?php if (!$bell): ?><p>You are all caught up.</p><?php endif; ?>
    </div></details>
</header>
<main class="main">
<?php if (!empty($GLOBALS['FLASH'])): [$type, $msg] = $GLOBALS['FLASH']; ?><p class="flash <?= e($type) ?>"><?= e($msg) ?></p><?php endif; ?>
<?php
}
function foot(): void {
    ?>
<footer class="foot"><span>© <?= date('Y') ?> <b><?= e(S('shop_name')) ?> Admin</b>. All rights reserved.</span><span>Developed by <b>Boostnix Media</b></span></footer>
</main></div>
<?php if (defined('LL_DEMO')): ?><script>document.addEventListener('submit',function(e){if(e.target.method&&e.target.method.toLowerCase()==='get')return;e.preventDefault();alert('Demo: changes are not saved here. They save on the real website.')},true)</script><?php endif; ?>
<script>window.LLA={base:<?= json_encode(url('admin/')) ?>,csrf:<?= json_encode(csrf()) ?>,demo:<?= defined('LL_DEMO') ? 'true' : 'false' ?>};</script>
<script src="<?= url('admin/admin.js') ?>?v=<?= @filemtime(LL_ROOT . '/admin/admin.js') ?>"></script>
</body></html>
<?php
}

/** Card header with title, subtitle and optional right side html. */
function card_h(string $t, string $s = '', string $right = ''): string {
    return '<div class="card-h"><div><h2>' . e($t) . '</h2>' . ($s !== '' ? '<p>' . e($s) . '</p>' : '') . '</div>' . $right . '</div>';
}
function field(string $name, string $label, string $value = '', string $type = 'text', string $hint = '', string $attrs = ''): string {
    $in = $type === 'area' ? '<textarea name="' . $name . '" rows="3" ' . $attrs . '>' . e($value) . '</textarea>'
        : '<input type="' . $type . '" name="' . $name . '" value="' . e($value) . '" ' . $attrs . '>';
    return '<label class="f"><span>' . $label . '</span>' . $in . ($hint !== '' ? '<small>' . $hint . '</small>' : '') . '</label>';
}
function sfield(string $k, string $label, string $type = 'text', string $hint = '', string $attrs = ''): string { return field($k, $label, S($k), $type, $hint, $attrs); }
function toggle(string $name, string $label, bool $on, string $hint = ''): string {
    return '<label class="tg"><input type="checkbox" name="' . $name . '" value="1" ' . ($on ? 'checked' : '') . '><i></i><span>' . $label . ($hint ? '<small>' . $hint . '</small>' : '') . '</span></label>';
}
