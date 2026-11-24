<?php
// Builds the static GitHub Pages demo into docs/ (sample data, admin without password, nothing saves).
// Usage: php tools/build-demo.php /Loom-Lustre/
$root = dirname(__DIR__);
$base = $argv[1] ?? '/';
$out = $root . '/docs';
$data = (getenv('LL_TMP') ?: sys_get_temp_dir()) . '/ll-demo-' . getmypid() . '-' . time();
@mkdir($data, 0755, true);
putenv("LL_DATA_DIR=$data"); putenv('LL_DEMO_BUILD=1'); putenv("LL_DEMO_BASE=$base"); putenv('LL_DEMO_HOST=abid2929.github.io');
require $root . '/inc/core.php';
$pdo = db();
// sample orders (fake people) so the demo admin has something to show
$P = $pdo->query('SELECT * FROM products ORDER BY id')->fetchAll();
$fake = [['Nusrat Jahan', '01711000001', 'House 12, Road 5, Dhanmondi, Dhaka', 'in', 'new', 0], ['Tasnim Akter', '01811000002', 'Agrabad, Chattogram', 'out', 'confirmed', 1], ['Farhana Rahman', '01911000003', 'Mirpur 10, Dhaka', 'in', 'shipped', 2], ['Sadia Islam', '01611000004', 'Zindabazar, Sylhet', 'out', 'delivered', 3], ['Mim Chowdhury', '01511000005', 'Uttara Sector 7, Dhaka', 'in', 'new', 0]];
foreach ($fake as $i => $f) {
    $its = []; $sub = 0;
    foreach ([$P[($i * 5) % count($P)], $P[($i * 7 + 3) % count($P)]] as $k => $p) { $q = $k + 1; $sh = explode(',', (string)$p['shades'])[0]; $its[] = ['id' => (int)$p['id'], 'name' => $p['name'], 'shade' => trim($sh), 'qty' => $q, 'price' => (int)$p['price'], 'img' => json_decode($p['images'], true)[0]]; $sub += $q * $p['price']; }
    $del = $f[3] === 'out' ? 130 : 70; $t = date('Y-m-d H:i:s', strtotime("-{$f[5]} day -" . ($i * 2) . ' hour'));
    $pdo->prepare('INSERT INTO orders(code,name,phone,address,area,note,items,subtotal,delivery,total,status,seen,ip,created,updated) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
        ->execute(['LL' . date('ymd', strtotime($t)) . strtoupper(substr(md5((string)$i), 0, 4)), $f[0], $f[1], $f[2], $f[3], '', json_encode($its, JSON_UNESCAPED_UNICODE), $sub, $del, $sub + $del, $f[4], $f[4] === 'new' ? 0 : 1, '', $t, $t]);
}
function render(string $kind, string $arg): string {
    global $root;
    return (string)shell_exec('php ' . escapeshellarg($root . '/tools/render.php') . ' ' . escapeshellarg($kind) . ' ' . escapeshellarg($arg) . ' 2>/dev/null');
}
function put(string $file, string $html): void { @mkdir(dirname($file), 0755, true); file_put_contents($file, $html); }
if (is_dir($out)) { fwrite(STDERR, "docs/ already exists. Move or delete it first, then run again.\n"); exit(1); }
@mkdir($out);
$routes = ['', 'shop', 'new', 'sale', 'search', 'cart', 'checkout', 'wishlist', 'about', 'contact', 'track'];
foreach ($pdo->query('SELECT slug FROM categories WHERE active=1') as $c) $routes[] = 'c/' . $c['slug'];
foreach ($pdo->query('SELECT slug FROM products WHERE active=1') as $p) $routes[] = 'p/' . $p['slug'];
foreach ($routes as $r) put($out . '/' . ($r === '' ? '' : $r . '/') . 'index.html', render('site', $r));
put($out . '/404.html', render('site', 'missing-page'));
// admin views
$views = ['' => 'index.html', 'v=orders' => 'orders.html', 'v=products' => 'products.html', 'v=edit' => 'edit.html', 'v=cats' => 'cats.html', 'v=slider' => 'slider.html', 'v=settings' => 'settings.html'];
foreach ($pdo->query('SELECT id FROM orders') as $o) $views['v=order&id=' . $o['id']] = 'order-' . $o['id'] . '.html';
foreach ($pdo->query('SELECT id FROM products') as $p) $views['v=edit&id=' . $p['id']] = 'edit-' . $p['id'] . '.html';
foreach ($views as $q => $f) put($out . '/admin/' . $f, render('admin', $q));
// product data for bag / wishlist in the demo
$rows = [];
foreach ($pdo->query('SELECT * FROM products WHERE active=1') as $r) $rows[] = ['id' => (int)$r['id'], 'name' => $r['name'], 'url' => url('p/' . $r['slug']), 'img' => img_small(p_image($r)), 'price' => (int)$r['price'], 'old' => (int)$r['old_price'], 'size' => $r['size'], 'stock' => (int)$r['stock'], 'shades' => list_csv((string)$r['shades'])];
put($out . '/api/products.json', json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
exec('cp -r ' . escapeshellarg($root . '/assets') . ' ' . escapeshellarg($out . '/assets'));
@mkdir($out . '/admin'); copy($root . '/admin/admin.css', $out . '/admin/admin.css'); copy($root . '/admin/admin.js', $out . '/admin/admin.js');
copy($root . '/favicon.ico', $out . '/favicon.ico');
touch($out . '/.nojekyll');
echo "Demo built in docs/ (" . count($routes) . " pages, " . count($views) . " admin pages)\n";
