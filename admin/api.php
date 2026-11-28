<?php
/* Loom & Lustre admin API (JSON). Same actions as the shared admin (PetVerse base);
   data lives in this shop's SQLite database, see inc/admin_data.php. */
declare(strict_types=1);
require dirname(__DIR__) . '/inc/core.php';
require dirname(__DIR__) . '/inc/admin_data.php';
session_name('lladmin');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Strict', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

function out($a, int $code = 200): never { http_response_code($code); echo json_encode($a, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit; }
function body(): array { static $b = null; if ($b === null) { $j = json_decode((string)file_get_contents('php://input'), true); $b = is_array($j) ? $j : []; } return $b; }
function me(): ?array { return $_SESSION['u'] ?? null; }
const ROLES = [
  'super'   => ['*'],
  'manager' => ['dashboard', 'products', 'inventory', 'categories', 'collections', 'orders', 'courier', 'customers', 'reviews', 'banners', 'cms', 'reports', 'settings'],
  'staff'   => ['dashboard', 'products', 'inventory', 'orders', 'courier', 'customers'],
];
function can(string $perm): bool { $u = me(); if (!$u) return false; $p = ROLES[$u['role']] ?? []; return in_array('*', $p, true) || in_array($perm, $p, true); }
function need(string $perm): void { if (!me()) out(['error' => 'login'], 401); if (!can($perm)) out(['error' => 'You do not have permission for this.'], 403); }
function audit(string $what): void { db()->prepare('INSERT INTO audit(t,u,w) VALUES(?,?,?)')->execute([time(), me()['u'] ?? '?', mb_substr($what, 0, 200)]); }
function admins(): array { return db()->query('SELECT * FROM admins ORDER BY id')->fetchAll(); }
function find_order(string $no): ?array { $q = db()->prepare('SELECT * FROM orders WHERE code=?'); $q->execute([$no]); return $q->fetch() ?: null; }

$a = (string)($_GET['a'] ?? '');
$pdo = db();

// Forgot the password: upload an empty file named reset-admin.txt next to index.php; the next visit removes all admins and the file, so a new first admin can be made.
foreach (['reset-admin.txt', 'RESET-ADMIN'] as $rf) if (is_file(LL_ROOT . '/' . $rf)) { $pdo->exec('DELETE FROM admins'); $pdo->exec('DELETE FROM logins WHERE ok=0'); @unlink(LL_ROOT . '/' . $rf); $_SESSION = []; }
if ($a === 'state') out(['setup' => !admins(), 'me' => me(), 'csrf' => me() ? $_SESSION['csrf'] : null, 'roles' => array_keys(ROLES)]);

if ($a === 'login') {
  if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') out(['error' => 'POST only'], 405);
  $b = body(); $un = strtolower(ll_clean($b['user'] ?? '', 40)); $pw = (string)($b['pass'] ?? '');
  $ip = client_ip();
  $q = $pdo->prepare('SELECT COUNT(*) FROM logins WHERE ip=? AND ok=0 AND t>?'); $q->execute([$ip, date('Y-m-d H:i:s', time() - 900)]);
  if ((int)$q->fetchColumn() >= 8) out(['error' => 'Too many tries. Close the browser and try again later.'], 429);
  if (!admins() && $un === '') $un = 'admin';   // password-only sign in: the first admin is "admin"
  if (!admins()) {   // first run: create the super admin
    if (!preg_match('/^[a-z0-9_.]{3,30}$/', $un) || strlen($pw) < 8) out(['error' => 'Password must be 8+ characters.'], 400);
    $pdo->prepare('INSERT INTO admins(username,name,pass,role,active,created) VALUES(?,?,?,?,1,?)')->execute([$un, ll_clean($b['name'] ?? 'Super Admin', 60) ?: 'Super Admin', password_hash($pw, PASSWORD_DEFAULT), 'super', date('Y-m-d H:i:s')]);
  }
  $u = null;
  if ($un === '') { foreach ($pdo->query('SELECT * FROM admins WHERE active=1') as $x) if (password_verify($pw, (string)$x['pass'])) { $u = $x; break; } }   // password only
  else { $q = $pdo->prepare('SELECT * FROM admins WHERE username=? AND active=1'); $q->execute([$un]); $u = $q->fetch() ?: null; if ($u && !password_verify($pw, (string)$u['pass'])) $u = null; }
  $ok = (bool)$u; if ($u) $un = $u['username'];
  $pdo->prepare('INSERT INTO logins(username,ok,ip,t) VALUES(?,?,?,?)')->execute([$un, $ok ? 1 : 0, $ip, date('Y-m-d H:i:s')]);
  if (!$ok) { usleep(700000); out(['error' => 'Wrong password.'], 403); }
  session_regenerate_id(true);
  $_SESSION['u'] = ['u' => $u['username'], 'name' => $u['name'] ?: $u['username'], 'role' => isset(ROLES[$u['role']]) ? $u['role'] : 'staff'];
  $_SESSION['csrf'] = bin2hex(random_bytes(16));
  $pdo->prepare('UPDATE admins SET last_login=? WHERE id=?')->execute([date('Y-m-d H:i:s'), $u['id']]);
  out(['ok' => 1, 'me' => $_SESSION['u'], 'csrf' => $_SESSION['csrf']]);
}
if ($a === 'logout') { $_SESSION = []; session_destroy(); out(['ok' => 1]); }

if (!me()) out(['error' => 'login'], 401);
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !hash_equals($_SESSION['csrf'] ?? '', (string)($_SERVER['HTTP_X_PV'] ?? ''))) out(['error' => 'Session expired. Please log in again.'], 403);
// a removed or switched-off admin loses access at once
$q = $pdo->prepare('SELECT role, active FROM admins WHERE username=?'); $q->execute([me()['u']]); $cur = $q->fetch();
if (!$cur || !(int)$cur['active']) { $_SESSION = []; out(['error' => 'login'], 401); }
$_SESSION['u']['role'] = isset(ROLES[$cur['role']]) ? $cur['role'] : 'staff';

$READ = ['products' => 'products', 'categories' => 'products', 'collections' => 'products', 'reviews' => 'reviews', 'banners' => 'banners', 'site' => 'dashboard', 'orders' => 'orders'];
$WRITE = ['products' => 'products', 'categories' => 'categories', 'collections' => 'collections', 'reviews' => 'reviews', 'banners' => 'banners', 'site' => 'settings'];

if ($a === 'get') {
  $c = (string)($_GET['c'] ?? '');
  if (!isset($READ[$c])) out(['error' => 'Unknown data'], 404);
  if ($c === 'site' && !can('dashboard') && !can('cms')) out(['data' => []]);
  need($READ[$c]);
  out(['data' => ll_get($c)]);
}
if ($a === 'put') {
  $c = (string)($_GET['c'] ?? ''); $d = body()['data'] ?? null;
  if (!isset($WRITE[$c]) || !is_array($d)) out(['error' => 'Bad data'], 400);
  if ($c === 'site') { if (!can('settings') && !can('cms')) out(['error' => 'You do not have permission for this.'], 403); }
  else need($WRITE[$c]);
  if (strlen((string)json_encode($d)) > 3000000) out(['error' => 'Too big'], 413);
  try { $err = ll_put($c, $d); } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); out(['error' => 'Could not save: ' . $e->getMessage()], 500); }
  if ($err !== '') out(['error' => $err], 400);
  audit("saved $c"); out(['ok' => 1]);
}

/* Download all data: one zip with the database (orders, products, settings, customers), uploaded photos and,
   with full=1, every website file too. Restore = upload the zip in cPanel and Extract. */
if ($a === 'backup') {
  need('backup'); @set_time_limit(600); @ini_set('memory_limit', '512M');
  if (!class_exists('ZipArchive')) out(['error' => 'This server has no ZIP support. Ask hosting to enable the PHP zip extension.'], 500);
  $full = ($_GET['full'] ?? '') === '1';
  $tmpDir = sys_get_temp_dir(); $zf = tempnam($tmpDir, 'llbk'); $db = $tmpDir . '/llbk-' . bin2hex(random_bytes(4)) . '.sqlite';
  try { $bk = new PDO('sqlite:' . data_dir() . '/shop.sqlite'); $bk->exec('VACUUM INTO ' . $bk->quote($db)); $bk = null; }   // safe copy of the live database
  catch (Throwable $e) { copy(data_dir() . '/shop.sqlite', $db); }
  $z = new ZipArchive(); $z->open($zf, ZipArchive::OVERWRITE);
  $z->addFile($db, 'database/shop.sqlite');
  $root = realpath(LL_ROOT); $dataReal = realpath(data_dir());
  $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
  foreach ($it as $f) {
    $fp = $f->getPathname(); $rel = ltrim(substr($fp, strlen($root)), '/');
    if ($dataReal && str_starts_with(realpath($fp) ?: $fp, $dataReal)) continue;      // the live db is added above
    if (preg_match('~^(docs|\.git|data)/|\.sqlite~', $rel)) continue;
    if (!$full && !str_starts_with($rel, 'uploads/')) continue;
    $z->addFile($fp, ($full ? 'website/' : '') . $rel);
  }
  $info = "Loom & Lustre backup " . date('Y-m-d H:i') . "\n\ndatabase/shop.sqlite = orders, products, categories, customers, settings, admin login.\n"
    . ($full ? "website/ = every website file (put these in the shop folder in cPanel).\n" : "uploads/ = photos uploaded in the admin.\n")
    . "\nTo restore the data: put shop.sqlite in the private data folder (" . basename((string)$dataReal) . ") on the server.\n";
  $z->addFromString('READ-ME.txt', $info); $z->close(); @unlink($db);
  audit('downloaded ' . ($full ? 'full' : 'data') . ' backup');
  header_remove('Content-Type'); header('Content-Type: application/zip');
  header('Content-Disposition: attachment; filename="loomlustre-' . ($full ? 'full' : 'data') . '-backup-' . date('Ymd-Hi') . '.zip"');
  header('Content-Length: ' . filesize($zf)); readfile($zf); @unlink($zf); exit;
}

if ($a === 'upload') {
  need('products');
  $f = $_FILES['photo'] ?? null;
  if (!$f || $f['error'] !== UPLOAD_ERR_OK || $f['size'] > 6000000) out(['error' => 'Upload failed (max 6 MB).'], 400);
  $info = @getimagesize($f['tmp_name']);
  $ext = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif'][$info[2] ?? 0] ?? null;
  if (!$ext) out(['error' => 'Only JPG, PNG, WEBP or GIF images.'], 400);
  $dir = LL_ROOT . '/uploads'; if (!is_dir($dir)) mkdir($dir, 0755, true);
  $name = 'up-' . date('ymd-His') . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
  if (!move_uploaded_file($f['tmp_name'], "$dir/$name")) out(['error' => 'Could not save the image.'], 500);
  out(['ok' => 1, 'i' => $ext === 'gif' ? "uploads/$name" : ll_make_sizes("uploads/$name")]);   // fast WebP + phone/card copies
}

if ($a === 'pull') {   // copy online product photos to this server
  need('products'); @set_time_limit(300);
  $ok = 0; $fail = 0; $have = 0; $dir = LL_ROOT . '/uploads'; if (!is_dir($dir)) mkdir($dir, 0755, true);
  foreach ($pdo->query('SELECT id, images, image_tags FROM products')->fetchAll() as $p) {
    $imgs = json_decode((string)$p['images'], true) ?: []; $tags = json_decode((string)$p['image_tags'], true) ?: []; $changed = false;
    foreach ($imgs as $n => $u) {
      if (!preg_match('~^https://~', $u)) { $have++; continue; }
      [$code, $bin] = http_call('GET', $u, ['User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120 Safari/537.36'], null, 25);
      $info = ($code === 200 && strlen($bin) > 1000 && strlen($bin) < 8000000) ? @getimagesizefromstring($bin) : false;
      $ext = $info ? ([IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif'][$info[2]] ?? null) : null;
      if (!$ext) { $fail++; continue; }
      $name = 'web-' . $p['id'] . '-' . substr(md5($u), 0, 8) . '.' . $ext; file_put_contents("$dir/$name", $bin);
      $rel = $ext === 'gif' ? "uploads/$name" : ll_make_sizes("uploads/$name");
      if (isset($tags[$u])) { $tags[$rel] = $tags[$u]; unset($tags[$u]); }
      $imgs[$n] = $rel; $changed = true; $ok++;
    }
    if ($changed) $pdo->prepare('UPDATE products SET images=?, image_tags=? WHERE id=?')->execute([json_encode(array_values($imgs), JSON_UNESCAPED_SLASHES), json_encode((object)$tags, JSON_UNESCAPED_SLASHES), $p['id']]);
  }
  $fast = 0;   // older uploads (banners, categories, products): add the small phone/card copies once
  foreach (glob("$dir/*.{jpg,jpeg,png,webp}", GLOB_BRACE) ?: [] as $fp) {
    $rel = 'uploads/' . basename($fp);
    if (preg_match('~-(s|t|x)\.webp$~', $rel) || is_file(LL_ROOT . '/' . preg_replace('~\.\w+$~', '-t.webp', $rel))) continue;
    if (@getimagesize($fp)[0] > 360) { ll_make_sizes($rel, true); $fast++; }
  }
  out(['ok' => 1, 'new' => $ok, 'have' => $have, 'fail' => $fail, 'fast' => $fast]);
}

const STATUSES = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'returned'];
if ($a === 'order_add') {   // manual order (phone / Facebook)
  need('orders'); $b = body()['order'] ?? [];
  $phone = norm_phone((string)($b['phone'] ?? ''));
  if (ll_clean($b['name'] ?? '') === '' || !preg_match('/^01[3-9]\d{8}$/', $phone)) out(['error' => 'Name and an 11-digit phone are needed.'], 400);
  $items = []; $sub = 0;
  foreach ((array)($b['items'] ?? []) as $it) {
    $qn = max(1, (int)($it['q'] ?? 1)); $pr = max(0, (int)($it['p'] ?? 0)); $pid = (string)($it['id'] ?? ''); $img = '';
    if (ctype_digit($pid)) { $q = $pdo->prepare('SELECT * FROM products WHERE id=?'); $q->execute([(int)$pid]); if ($row = $q->fetch()) { $img = p_image($row); $pdo->prepare('UPDATE products SET stock=MAX(0,stock-?), sold=sold+? WHERE id=?')->execute([$qn, $qn, $row['id']]); } }
    $items[] = ['id' => (int)$pid, 'name' => ll_clean($it['n'] ?? '', 160), 'shade' => ll_clean($it['v'] ?? '', 60), 'qty' => $qn, 'price' => $pr, 'img' => $img]; $sub += $qn * $pr;
  }
  if (!$items) out(['error' => 'Add at least one product.'], 400);
  $ship = max(0, (int)($b['ship'] ?? 0)); $now = date('Y-m-d H:i:s');
  do { $code = 'LL' . random_int(100000, 999999); } while (find_order($code));
  $pay = in_array($b['pay'] ?? '', ['cod', 'bkash', 'nagad'], true) ? $b['pay'] : 'cod';
  $pdo->prepare('INSERT INTO orders(code,name,phone,address,area,note,items,subtotal,delivery,total,status,ip,created,updated,payment,src,hist) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
    ->execute([$code, ll_clean($b['name'], 80), $phone, ll_clean($b['address'] ?? ''), ($b['area'] ?? '') === 'out' ? 'out' : 'in', ll_clean($b['note'] ?? ''), json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $sub, $ship, $sub + $ship, 'new', client_ip(), $now, $now, $pay, ll_clean($b['src'] ?? 'manual', 20), '[]']);
  audit("added order $code"); out(['ok' => 1, 'no' => $code]);
}
if ($a === 'order_update') {
  need('orders'); $b = body(); $no = (string)($b['no'] ?? ''); $o = find_order($no);
  if (!$o) out(['error' => 'Order not found'], 404);
  $hist = json_decode((string)$o['hist'], true) ?: []; $status = $o['status'];
  if (isset($b['status']) && in_array($b['status'], STATUSES, true) && ll_status_db($b['status']) !== $o['status']) {
    $status = ll_status_db($b['status']); $hist[] = ['t' => time(), 's' => $b['status'], 'u' => me()['u']];
    if (in_array($status, ['cancelled', 'returned'], true) && !in_array($o['status'], ['cancelled', 'returned'], true))   // put the stock back
      foreach (json_decode((string)$o['items'], true) ?: [] as $it) $pdo->prepare('UPDATE products SET stock=stock+?, sold=MAX(0,sold-?) WHERE id=?')->execute([(int)$it['qty'], (int)$it['qty'], (int)$it['id']]);
  }
  $pdo->prepare('UPDATE orders SET status=?, admin_note=?, paid=?, hist=?, seen=1, updated=? WHERE id=?')->execute([$status, isset($b['admin_note']) ? ll_clean($b['admin_note'], 500) : $o['admin_note'], isset($b['paid']) ? ($b['paid'] ? 1 : 0) : (int)$o['paid'], json_encode($hist), date('Y-m-d H:i:s'), $o['id']]);
  if ($status !== $o['status'] && S('sms_on_status') === '1') send_sms($o['phone'], 'Dear ' . $o['name'] . ', your ' . S('shop_name') . ' order ' . $no . ' is now ' . strtoupper((string)$b['status']) . '.', true);
  audit("order $no updated"); out(['ok' => 1]);
}
if ($a === 'order_del') {
  need('settings'); $no = (string)(body()['no'] ?? '');
  $pdo->prepare('DELETE FROM orders WHERE code=?')->execute([$no]); audit("deleted order $no"); out(['ok' => 1]);
}

/* Steadfast courier */
function sf(string $path, ?array $post = null): array {
  $base = rtrim(S('sf_base') ?: 'https://portal.packzy.com/api/v1', '/');
  if (S('steadfast_key') === '' || S('steadfast_secret') === '') return ['error' => 'Add your Steadfast API key and secret in Site Settings > Courier.'];
  [$code, $res] = http_call($post ? 'POST' : 'GET', $base . $path, ['Api-Key: ' . S('steadfast_key'), 'Secret-Key: ' . S('steadfast_secret'), 'Content-Type: application/json'], $post ? json_encode($post) : null, 20);
  $j = json_decode($res, true);
  return is_array($j) ? $j : ['error' => 'Courier did not answer (' . ($code ?: 'no connection') . ')'];
}
if ($a === 'courier_send') {
  need('courier'); $no = (string)(body()['no'] ?? ''); $o = find_order($no);
  if (!$o) out(['error' => 'Order not found'], 404);
  if (json_decode((string)$o['courier_json'], true) || $o['tracking'] !== '') out(['error' => 'Already sent to courier.'], 400);
  $cod = ((int)$o['paid'] || ($o['payment'] ?: 'cod') !== 'cod') ? 0 : (int)$o['total'];
  $r = sf('/create_order', ['invoice' => $o['code'], 'recipient_name' => $o['name'], 'recipient_phone' => norm_phone($o['phone']), 'recipient_address' => mb_substr($o['address'], 0, 250), 'cod_amount' => $cod, 'note' => (string)$o['note']]);
  $c = $r['consignment'] ?? null;
  if (!$c) out(['error' => $r['error'] ?? ($r['message'] ?? 'Courier error'), 'raw' => $r], 400);
  $cj = ['by' => 'steadfast', 'cid' => (string)($c['consignment_id'] ?? ''), 'code' => (string)($c['tracking_code'] ?? ''), 'st' => (string)($c['status'] ?? 'in_review'), 't' => time()];
  $st = in_array($o['status'], ['new', 'confirmed'], true) ? 'shipped' : $o['status'];
  $pdo->prepare('UPDATE orders SET courier_json=?, courier=?, tracking=?, status=?, updated=? WHERE id=?')->execute([json_encode($cj), 'steadfast', $cj['code'], $st, date('Y-m-d H:i:s'), $o['id']]);
  if ($st === 'shipped' && S('sms_on') === '1' && S('sms_shipped') !== '') send_sms($o['phone'], fill_tpl(S('sms_shipped'), $o + ['tracking' => $cj['code']]));
  audit("sent $no to courier"); out(['ok' => 1, 'courier' => $c]);
}
if ($a === 'courier_status') {
  need('courier'); $no = (string)(body()['no'] ?? ''); $o = find_order($no); if (!$o) out(['error' => 'Order not found'], 404);
  $r = sf('/status_by_invoice/' . rawurlencode($no)); $st = $r['delivery_status'] ?? null;
  if (!$st) out(['error' => $r['error'] ?? 'No status yet', 'raw' => $r], 400);
  $cj = json_decode((string)$o['courier_json'], true) ?: ['by' => 'steadfast', 'cid' => '', 'code' => $o['tracking'], 't' => time()]; $cj['st'] = $st;
  $os = $st === 'delivered' ? 'delivered' : ($st === 'cancelled' ? 'returned' : $o['status']);
  $pdo->prepare('UPDATE orders SET courier_json=?, status=? WHERE id=?')->execute([json_encode($cj), $os, $o['id']]);
  out(['ok' => 1, 'status' => $st]);
}
if ($a === 'courier_balance') { need('courier'); $r = sf('/get_balance'); out(isset($r['current_balance']) ? ['ok' => 1, 'balance' => $r['current_balance']] : ['error' => $r['error'] ?? 'Could not read balance']); }

if ($a === 'stats') {
  need('dashboard');
  $days = []; foreach ($pdo->query('SELECT day, COUNT(DISTINCT vid) n FROM visits WHERE t>' . (time() - 30 * 86400) . ' GROUP BY day') as $r) $days[$r['day']] = (int)$r['n'];
  $live = (int)$pdo->query('SELECT COUNT(DISTINCT vid) FROM visits WHERE t>' . (time() - 300))->fetchColumn();
  $pages = []; foreach ($pdo->query('SELECT path, COUNT(*) n FROM visits WHERE t>' . (time() - 7 * 86400) . ' GROUP BY path ORDER BY n DESC LIMIT 10') as $r) $pages['/' . $r['path']] = (int)$r['n'];
  $cap = $pdo->query('SELECT COUNT(*) n, SUM(code=200) ok FROM capi_log WHERE t>' . (time() - 30 * 86400))->fetch();
  $audit = array_map(fn($r) => ['t' => (int)$r['t'], 'u' => $r['u'], 'w' => $r['w']], $pdo->query('SELECT * FROM audit ORDER BY t DESC LIMIT 30')->fetchAll());
  out(['days' => $days, 'live' => $live, 'pages' => $pages, 'capi' => ['events' => (int)$cap['n'], 'ok' => (int)$cap['ok']], 'audit' => $audit]);
}

/* secrets: tokens and keys, never sent back in full. Admin name => settings key */
const SECRET_MAP = ['capi_token' => 'capi_token', 'capi_test' => 'capi_test', 'sf_key' => 'steadfast_key', 'sf_secret' => 'steadfast_secret', 'sf_base' => 'sf_base', 'sms_url' => 'sms_tpl', 'sms_admin' => 'sms_admin', 'sms_on_order' => 'sms_on', 'sms_on_status' => 'sms_on_status', 'gemini_key' => 'gemini_key'];
const SECRET_HIDDEN = ['capi_token', 'sf_secret', 'gemini_key', 'sms_url'];
if ($a === 'secrets_get') {
  need('settings'); $o = [];
  foreach (SECRET_MAP as $k => $sk) $o[$k] = in_array($k, SECRET_HIDDEN, true) ? (S($sk) === '' ? '' : '__set__') : S($sk);
  if ($o['sf_base'] === '') $o['sf_base'] = 'https://portal.packzy.com/api/v1';
  out(['data' => $o]);
}
if ($a === 'secrets_put') {
  need('settings'); if (me()['role'] !== 'super') out(['error' => 'Only the super admin can change keys.'], 403);
  foreach ((array)(body()['data'] ?? []) as $k => $v) {
    if (!isset(SECRET_MAP[$k])) continue;
    $v = is_bool($v) || is_int($v) ? ($v ? '1' : '0') : ll_clean($v, 600);
    if ($v === '__set__' || (in_array($k, SECRET_HIDDEN, true) && $v === '')) continue;
    set_setting(SECRET_MAP[$k], $v);
  }
  audit('changed keys'); out(['ok' => 1]);
}
if ($a === 'sms_test') {
  need('settings'); $n = norm_phone((string)(body()['to'] ?? ''));
  if (!preg_match('/^01[3-9]\d{8}$/', $n)) out(['error' => 'Give an 11-digit number'], 400);
  $ok = send_sms($n, S('shop_name') . ' test SMS. Your SMS settings work.', true);
  out(['ok' => $ok, 'msg' => $ok ? 'sent' : 'The gateway did not accept it. Check the link.']);
}

/* admins & roles */
if ($a === 'users') { need('users'); out(['data' => array_map(fn($u) => ['u' => $u['username'], 'name' => $u['name'], 'role' => $u['role'], 'on' => (int)$u['active'], 'last' => $u['last_login'] ? strtotime($u['last_login']) : null], admins())]); }
if ($a === 'user_save') {
  need('users'); $b = body(); $un = strtolower(ll_clean($b['u'] ?? '', 30)); $role = (string)($b['role'] ?? 'staff'); $pw = (string)($b['pass'] ?? '');
  if (!preg_match('/^[a-z0-9_.]{3,30}$/', $un) || !isset(ROLES[$role])) out(['error' => 'Username 3+ letters (a-z, 0-9) and a role.'], 400);
  $q = $pdo->prepare('SELECT * FROM admins WHERE username=?'); $q->execute([$un]); $ex = $q->fetch();
  if (!$ex && strlen($pw) < 8) out(['error' => 'New admin needs an 8+ character password.'], 400);
  if ($pw !== '') foreach (admins() as $o) if ($o['username'] !== $un && password_verify($pw, (string)$o['pass'])) out(['error' => 'Another admin already uses this password. Please choose a different one.'], 400);
  if ($pw !== '' && strlen($pw) < 8) out(['error' => 'Password must be 8+ characters.'], 400);
  $name = ll_clean($b['name'] ?? $un, 60) ?: $un;
  if (!$ex) $pdo->prepare('INSERT INTO admins(username,name,pass,role,active,created) VALUES(?,?,?,?,?,?)')->execute([$un, $name, password_hash($pw, PASSWORD_DEFAULT), $role, empty($b['on']) ? 0 : 1, date('Y-m-d H:i:s')]);
  else {
    $self = $un === me()['u'];
    $pdo->prepare('UPDATE admins SET name=?, role=?, active=? WHERE id=?')->execute([$name, $self ? $ex['role'] : $role, $self ? 1 : (empty($b['on']) ? 0 : 1), $ex['id']]);
    if ($pw !== '') $pdo->prepare('UPDATE admins SET pass=? WHERE id=?')->execute([password_hash($pw, PASSWORD_DEFAULT), $ex['id']]);
  }
  audit("saved admin $un"); out(['ok' => 1]);
}
if ($a === 'user_del') {
  need('users'); $un = (string)(body()['u'] ?? '');
  if ($un === me()['u']) out(['error' => 'You cannot delete yourself.'], 400);
  $pdo->prepare('DELETE FROM admins WHERE username=?')->execute([$un]); audit("deleted admin $un"); out(['ok' => 1]);
}
if ($a === 'pass') {
  $b = body(); $old = (string)($b['old'] ?? ''); $new = (string)($b['new'] ?? '');
  $q = $pdo->prepare('SELECT * FROM admins WHERE username=?'); $q->execute([me()['u']]); $mine = $q->fetch();
  if (!$mine || !password_verify($old, (string)$mine['pass'])) out(['error' => 'Current password is wrong.'], 403);
  if (strlen($new) < 8) out(['error' => 'New password must be 8+ characters.'], 400);
  foreach (admins() as $o) if ($o['username'] !== me()['u'] && password_verify($new, (string)$o['pass'])) out(['error' => 'Another admin already uses this password. Please choose a different one.'], 400);
  $pdo->prepare('UPDATE admins SET pass=? WHERE id=?')->execute([password_hash($new, PASSWORD_DEFAULT), $mine['id']]);
  audit('changed own password'); out(['ok' => 1]);
}

/* AI writer: Google Gemini (free key) if set, otherwise the admin uses its built-in writer */
if ($a === 'ai') {
  need('products'); $key = S('gemini_key');
  if ($key === '') out(['error' => 'nokey']);
  $prompt = ll_clean(body()['prompt'] ?? '', 2000);
  [, $res] = http_call('POST', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . rawurlencode($key), ['Content-Type: application/json'], json_encode(['contents' => [['parts' => [['text' => $prompt]]]]]), 30);
  $j = json_decode($res, true); $t = $j['candidates'][0]['content']['parts'][0]['text'] ?? null;
  out($t ? ['ok' => 1, 'text' => $t] : ['error' => 'AI did not answer. Check the Gemini key.']);
}

if ($a === 'export') {
  need('reports'); header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="loom-lustre-orders-' . date('Ymd') . '.csv"');
  $f = fopen('php://output', 'w'); fwrite($f, "\xEF\xBB\xBF");
  fputcsv($f, ['Order', 'Date', 'Name', 'Phone', 'Address', 'Area', 'Items', 'Subtotal', 'Delivery', 'Total', 'Payment', 'TrxID', 'Status']);
  foreach (ll_get('orders') as $o) fputcsv($f, [$o['no'], date('Y-m-d H:i', $o['t']), $o['name'], $o['phone'], $o['address'], $o['area'], implode('; ', array_map(fn($i) => $i['n'] . ($i['v'] ? ' (' . $i['v'] . ')' : '') . ' x' . $i['q'], $o['items'])), $o['sub'], $o['ship'], $o['total'], $o['pay'], $o['trx'], $o['status']]);
  exit;
}
out(['error' => 'Unknown action'], 404);
