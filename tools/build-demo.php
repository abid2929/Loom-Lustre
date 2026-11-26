<?php
// Builds the static GitHub Pages demo into docs/ (sample data; the shared admin runs in demo mode, saving only in the browser).
// Usage: php tools/build-demo.php /Loom-Lustre/
$root = dirname(__DIR__);
$base = $argv[1] ?? '/';
$out = $root . '/docs';
$data = (getenv('LL_TMP') ?: sys_get_temp_dir()) . '/ll-demo-' . getmypid() . '-' . time();
@mkdir($data, 0755, true);
putenv("LL_DATA_DIR=$data"); putenv('LL_DEMO_BUILD=1'); putenv("LL_DEMO_BASE=$base"); putenv('LL_DEMO_HOST=abid2929.github.io');
require $root . '/inc/core.php';
$pdo = db();
// sample data (fake people, demo only) so the demo admin has something to show
$P = $pdo->query('SELECT * FROM products ORDER BY id')->fetchAll();
mt_srand(7);
$fake = [['Nusrat Jahan', '01711000001', 'House 12, Road 5, Dhanmondi, Dhaka', 'in'], ['Tasnim Akter', '01811000002', 'Agrabad, Chattogram', 'out'], ['Farhana Rahman', '01911000003', 'Mirpur 10, Dhaka', 'in'], ['Sadia Islam', '01611000004', 'Zindabazar, Sylhet', 'out'], ['Mim Chowdhury', '01511000005', 'Uttara Sector 7, Dhaka', 'in'], ['Raisa Karim', '01711000006', 'Banani, Dhaka', 'in'], ['Jannatul Ferdous', '01811000007', 'Shaheb Bazar, Rajshahi', 'out'], ['Lamia Hossain', '01911000008', 'Bashundhara R/A, Dhaka', 'in'], ['Anika Sultana', '01611000009', 'Khulna Sadar, Khulna', 'out']];
$plan = [[0, 'new', 0, 'cod'], [4, 'new', 0, 'bkash'], [7, 'new', 0, 'cod'], [1, 'confirmed', 0, 'cod'], [5, 'confirmed', 1, 'nagad'], [2, 'shipped', 1, 'cod'], [8, 'shipped', 2, 'cod'], [3, 'delivered', 2, 'cod'], [0, 'delivered', 3, 'bkash'], [6, 'delivered', 3, 'cod'], [2, 'delivered', 4, 'cod'], [5, 'cancelled', 4, 'cod'], [7, 'delivered', 5, 'nagad'], [1, 'delivered', 6, 'cod'], [3, 'returned', 6, 'cod'], [4, 'delivered', 6, 'cod']];
usort($plan, fn($a, $b) => $b[2] <=> $a[2]);
foreach ($plan as $i => [$who, $status, $daysAgo, $pay]) {
    $f = $fake[$who]; $its = []; $sub = 0;
    foreach ([$P[($i * 5) % count($P)], $P[($i * 7 + 3) % count($P)]] as $k => $p) { $q = ($i + $k) % 3 === 0 ? 2 : 1; $sh = explode(',', (string)$p['shades'])[0]; $its[] = ['id' => (int)$p['id'], 'name' => $p['name'], 'shade' => trim($sh), 'qty' => $q, 'price' => (int)$p['price'], 'img' => json_decode($p['images'], true)[0]]; $sub += $q * $p['price']; }
    $del = $f[3] === 'out' ? 130 : 70; $t = date('Y-m-d H:i:s', strtotime("-$daysAgo day -" . ($i % 5 * 2 + 1) . ' hour'));
    $trk = in_array($status, ['shipped', 'delivered'], true) ? 'SF' . (88231400 + $i * 37) : '';
    $pdo->prepare('INSERT INTO orders(code,name,phone,address,area,note,items,subtotal,delivery,total,status,seen,ip,created,updated,payment,trx_id,courier,tracking) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
        ->execute(['LL' . date('ymd', strtotime($t)) . strtoupper(substr(md5((string)$i), 0, 4)), $f[0], $f[1], $f[2], $f[3], $i === 0 ? 'Please call before delivery.' : '', json_encode($its, JSON_UNESCAPED_UNICODE), $sub, $del, $sub + $del, $status, $status === 'new' ? 0 : 1, '', $t, $t, $pay, $pay === 'cod' ? '' : 'TX' . strtoupper(substr(md5("t$i"), 0, 8)), $trk ? 'steadfast' : '', $trk]);
}
$pdo->exec('UPDATE products SET stock=3 WHERE id IN (4,9,17)'); $pdo->exec('UPDATE products SET stock=0 WHERE id IN (12,25)');
$revs = [['Nusrat J.', 5, 'The colour is exactly like the photo and it stays on for hours. Will buy again!'], ['Tasnim A.', 5, 'Fast delivery to Chattogram and the packing was really nice.'], ['Farhana R.', 4, 'Soft on the skin and smells lovely. A little pricey but worth it.'], ['Sadia I.', 5, 'Original product, I checked the batch code. Thank you!'], ['Mim C.', 4, 'Nice shade for everyday office use.'], ['Raisa K.', 5, 'My skin feels so fresh. Perfect for summer.'], ['Lamia H.', 5, 'Bought as a gift for my sister and she loved it.'], ['Anika S.', 4, 'Good quality. Delivery took 3 days to Khulna.'], ['Jannat F.', 5, 'Best lipstick I have tried this year.'], ['Sumaiya', 3, 'Nice, but I expected a little more shine.'], ['Rumana', 5, 'Please bring more shades of this!']];
foreach ($revs as $i => [$n, $r, $b]) $pdo->prepare('INSERT INTO reviews(product_id,name,phone,rating,body,approved,created) VALUES(?,?,?,?,?,?,?)')->execute([(int)$P[($i * 3) % 12]['id'], $n, '', $r, $b, $i < 9 ? 1 : 0, date('Y-m-d H:i:s', strtotime('-' . ($i + 1) * 5 . ' hour'))]);
$now = date('Y-m-d H:i:s');
foreach (['bkash_on' => '1', 'bkash_number' => '01XXXXXXXXX', 'nagad_on' => '1', 'nagad_number' => '01XXXXXXXXX'] as $k => $v) set_setting($k, $v);
foreach ([['owner', 'Shop Owner', 'super', 0], ['manager', 'Store Manager', 'manager', 3], ['packing', 'Packing Staff', 'staff', 26]] as [$u, $n, $r, $h]) $pdo->prepare('INSERT INTO admins(username,name,pass,role,active,last_login,created) VALUES(?,?,?,?,1,?,?)')->execute([$u, $n, 'demo-no-login', $r, date('Y-m-d H:i:s', strtotime("-$h hour")), $now]);
foreach ([['owner', 1, 0], ['manager', 1, 3], ['packing', 0, 26], ['packing', 1, 26]] as [$u, $ok, $h]) $pdo->prepare('INSERT INTO logins(username,ok,ip,t) VALUES(?,?,?,?)')->execute([$u, $ok, '103.120.0.' . (10 + $h), date('Y-m-d H:i:s', strtotime("-$h hour"))]);
$paths = ['', 'shop', 'c/lips', 'c/skincare', 'sale', 'new', 'cart', 'checkout']; foreach ($P as $p) $paths[] = 'p/' . $p['slug'];
$refs = ['', '', '', 'facebook.com', 'facebook.com', 'm.facebook.com', 'google.com', 'instagram.com', 'l.messenger.com'];
$vi = $pdo->prepare('INSERT INTO visits(vid,ip,path,kind,ref,day,t) VALUES(?,?,?,?,?,?,?)');
$pdo->beginTransaction();
for ($d = 6; $d >= 0; $d--) {
    $nv = 40 + mt_rand(0, 35);
    for ($v = 0; $v < $nv; $v++) {
        $vid = substr(md5("v$d-$v"), 0, 16); $ref = $refs[mt_rand(0, count($refs) - 1)];
        $base = strtotime("-$d day") - mt_rand(0, $d ? 80000 : 30000);
        if ($d === 0 && $v < 7) $base = time() - mt_rand(10, 240);
        for ($k = 0, $n = mt_rand(1, 5); $k < $n; $k++) { $t = $base + $k * 40; $vi->execute([$vid, '', $paths[mt_rand(0, count($paths) - 1)], 'view', $k ? '' : $ref, date('Y-m-d', $t), $t]); }
    }
}
$pdo->commit();
function render(string $kind, string $arg): string {
    global $root;
    return (string)shell_exec('php ' . escapeshellarg($root . '/tools/render.php') . ' ' . escapeshellarg($kind) . ' ' . escapeshellarg($arg) . ' 2>/dev/null');
}
function put(string $file, string $html): void { @mkdir(dirname($file), 0755, true); file_put_contents($file, $html); }
if (is_dir($out)) { fwrite(STDERR, "docs/ already exists. Move or delete it first, then run again.\n"); exit(1); }
@mkdir($out);
$routes = ['', 'shop', 'categories', 'luxe', 'luxe-all', 'd/women', 'd/men', 'd/kids', 'd/home', 'd/beauty', 'new', 'sale', 'search', 'cart', 'checkout', 'wishlist', 'about', 'contact', 'track'];
foreach ($pdo->query('SELECT slug FROM categories WHERE active=1') as $c) $routes[] = 'c/' . $c['slug'];
foreach ($pdo->query('SELECT slug FROM collections WHERE active=1') as $c) $routes[] = 'collection/' . $c['slug'];
foreach ($pdo->query('SELECT slug FROM products WHERE active=1') as $p) $routes[] = 'p/' . $p['slug'];
foreach ($routes as $r) put($out . '/' . ($r === '' ? '' : $r . '/') . 'index.html', render('site', $r));
put($out . '/404.html', render('site', 'missing-page'));
// shared admin: static app + the shop data as JSON (no api.php on GitHub Pages, so it runs in demo mode)
require $root . '/inc/admin_data.php';
foreach (['products', 'categories', 'collections', 'reviews', 'banners', 'site', 'orders'] as $c) put($out . '/data/' . $c . '.json', json_encode(ll_get($c), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
foreach (['index.html', 'admin.css', 'admin.js', 'admin2.js', 'bn.js', 'pwd-eye.js'] as $f) put($out . '/admin/' . $f, file_get_contents($root . '/admin/' . $f));
put($out . '/robots.txt', render('site', 'robots.txt'));
put($out . '/sitemap.xml', render('site', 'sitemap.xml'));
$views = glob($out . '/admin/*') ?: [];
// product data for bag / wishlist in the demo
$rows = [];
foreach ($pdo->query('SELECT * FROM products WHERE active=1') as $r) $rows[] = ['id' => (int)$r['id'], 'name' => $r['name'], 'url' => url('p/' . $r['slug']), 'img' => img_small(p_image($r)), 'price' => (int)$r['price'], 'old' => (int)$r['old_price'], 'size' => $r['size'], 'stock' => (int)$r['stock'], 'shades' => list_csv((string)$r['shades'])];
put($out . '/api/products.json', json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
exec('cp -r ' . escapeshellarg($root . '/assets') . ' ' . escapeshellarg($out . '/assets'));
copy($root . '/favicon.ico', $out . '/favicon.ico');
touch($out . '/.nojekyll');
echo "Demo built in docs/ (" . count($routes) . " pages, " . count($views) . " admin pages)\n";
