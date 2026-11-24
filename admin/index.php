<?php
// Loom & Lustre admin panel (English). Pages are in inc/admin/views.php, saves in inc/admin/actions.php.
require dirname(__DIR__) . '/inc/core.php';

session_name('lladmin');
session_set_cookie_params(['lifetime' => 0, 'path' => base_url(), 'httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
if (PHP_SAPI !== 'cli') session_start(); else $_SESSION = [];
header('X-Frame-Options: DENY');
header('X-Robots-Tag: noindex');

function csrf(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16)); return $_SESSION['csrf']; }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf() . '">'; }
function csrf_ok(): bool { $t = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF'] ?? ''); return is_string($t) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t); }
function aurl(string $q = ''): string {
    if (defined('LL_DEMO')) { // static demo: each view is its own .html file
        parse_str($q, $a); $v = (string)($a['v'] ?? '');
        if ($v === '' || $v === 'dashboard' || isset($a['logout'])) return url('admin/');
        return url('admin/' . $v . (isset($a['id']) ? '-' . (int)$a['id'] : '') . (isset($a['tab']) ? '-' . preg_replace('/\W/', '', (string)$a['tab']) : '') . '.html');
    }
    return url('admin/') . ($q !== '' ? '?' . $q : '');
}
function go(string $q = '', string $msg = '', string $type = 'ok'): never { if ($msg) $_SESSION['flash'] = [$type, $msg]; header('Location: ' . aurl($q)); exit; }
function P(string $k, string $d = ''): string { return trim((string)($_POST[$k] ?? $d)); }

/** Save one uploaded image to uploads/YYYY-MM, as webp (1400px + 420px -s copy) when GD allows. Returns relative path or ''. */
function save_upload(array $f): string {
    if (($f['error'] ?? 4) !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) return '';
    if ($f['size'] > 12 * 1024 * 1024) return '';
    $info = @getimagesize($f['tmp_name']);
    if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF], true)) return '';
    $dir = LL_ROOT . '/uploads/' . date('Y-m');
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $name = date('dHis') . '-' . bin2hex(random_bytes(3));
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
                imagewebp($dst, "$dir/$name$suf.webp", 82); imagedestroy($dst);
            }
            imagedestroy($src);
            return 'uploads/' . date('Y-m') . "/$name.webp";
        }
    }
    $ext = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif'][$info[2]];
    return move_uploaded_file($f['tmp_name'], "$dir/$name.$ext") ? 'uploads/' . date('Y-m') . "/$name.$ext" : '';
}
/** All files from one input (single or multiple), keyed by their index. */
function uploads_from(string $field): array {
    $out = [];
    if (empty($_FILES[$field])) return $out;
    $F = $_FILES[$field];
    if (is_array($F['name'])) { foreach ($F['name'] as $i => $n) { $p = save_upload(['name' => $n, 'tmp_name' => $F['tmp_name'][$i], 'error' => $F['error'][$i], 'size' => $F['size'][$i]]); if ($p) $out[$i] = $p; } }
    else { $p = save_upload($F); if ($p) $out[0] = $p; }
    return $out;
}

/* ---------- first admin: move the old single password into an "admin" user ---------- */
$pdo = db();
if (!(int)$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() && S('admin_pass') !== '') {
    $pdo->prepare('INSERT INTO admins(username,name,pass,role,created) VALUES(?,?,?,?,?)')->execute(['admin', 'Owner', S('admin_pass'), 'super', date('Y-m-d H:i:s')]);
}
$hasAdmin = (int)$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0;
$act = (string)($_POST['act'] ?? '');

if (!$hasAdmin && $act === 'setup' && csrf_ok()) {
    $u = strtolower(P('username')); $p1 = (string)($_POST['p1'] ?? '');
    if (!preg_match('/^[a-z0-9_.]{3,30}$/', $u)) $_SESSION['flash'] = ['err', 'Username: 3 to 30 letters or numbers, no spaces.'];
    elseif (strlen($p1) < 6 || $p1 !== ($_POST['p2'] ?? '')) $_SESSION['flash'] = ['err', 'Password must be at least 6 characters and both boxes must match.'];
    else {
        $pdo->prepare('INSERT INTO admins(username,name,pass,role,created,last_login) VALUES(?,?,?,?,?,?)')->execute([$u, P('name'), password_hash($p1, PASSWORD_DEFAULT), 'super', date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]);
        session_regenerate_id(true); $_SESSION['aid'] = (int)$pdo->lastInsertId(); go('', 'Welcome! Your admin account is ready.');
    }
}
if ($hasAdmin && $act === 'login' && csrf_ok()) {
    $u = strtolower(P('username'));
    if (!rate_ok('login', 8, 900)) $_SESSION['flash'] = ['err', 'Too many wrong tries. Please wait 15 minutes.'];
    else {
        $st = $pdo->prepare('SELECT * FROM admins WHERE username=? AND active=1'); $st->execute([$u]); $a = $st->fetch();
        $ok = $a && password_verify((string)($_POST['pass'] ?? ''), $a['pass']);
        $pdo->prepare('INSERT INTO logins(username,ok,ip,t) VALUES(?,?,?,?)')->execute([$u, $ok ? 1 : 0, client_ip(), date('Y-m-d H:i:s')]);
        if ($ok) { session_regenerate_id(true); $_SESSION['aid'] = (int)$a['id']; $pdo->prepare('UPDATE admins SET last_login=? WHERE id=?')->execute([date('Y-m-d H:i:s'), $a['id']]); go(); }
        $_SESSION['flash'] = ['err', 'Wrong username or password.'];
    }
}
if (isset($_GET['logout'])) { $_SESSION = []; if (session_status() === PHP_SESSION_ACTIVE) session_destroy(); header('Location: ' . aurl()); exit; }

$ME = null;
if (defined('LL_DEMO')) $ME = ['id' => 0, 'username' => 'demo', 'name' => 'Super Administrator', 'role' => 'super'];
elseif (!empty($_SESSION['aid'])) { $st = $pdo->prepare('SELECT * FROM admins WHERE id=? AND active=1'); $st->execute([(int)$_SESSION['aid']]); $ME = $st->fetch() ?: null; }
$GLOBALS['ME'] = $ME;
$GLOBALS['FLASH'] = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);

if (!$ME) {
    $f = $GLOBALS['FLASH'];
    ?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>Admin login | <?= e(S('shop_name')) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('admin/admin.css') ?>"></head><body class="login">
<form method="post" class="box"><img src="<?= url('assets/img/logo.png') ?>" alt="<?= e(S('shop_name')) ?>" class="login-logo"><?= csrf_field() ?>
<?php if ($f): ?><p class="flash <?= e($f[0]) ?>"><?= e($f[1]) ?></p><?php endif; ?>
<?php if (!$hasAdmin): ?>
<h1>Create your admin account</h1><p class="muted">This is the first visit. Choose the username and password you will use to log in.</p>
<input type="hidden" name="act" value="setup">
<label class="f"><span>Your name</span><input name="name" required></label>
<label class="f"><span>Username</span><input name="username" required pattern="[A-Za-z0-9_.]{3,30}" autocomplete="username"></label>
<label class="f"><span>Password</span><input type="password" name="p1" required minlength="6" autocomplete="new-password"></label>
<label class="f"><span>Password again</span><input type="password" name="p2" required minlength="6" autocomplete="new-password"></label>
<button class="btn">Create account</button>
<?php else: ?>
<h1>Admin login</h1><input type="hidden" name="act" value="login">
<label class="f"><span>Username</span><input name="username" required autofocus autocomplete="username"></label>
<label class="f"><span>Password</span><input type="password" name="pass" required autocomplete="current-password"></label>
<button class="btn">Log in</button>
<?php endif; ?></form></body></html><?php
    exit;
}

require LL_ROOT . '/inc/admin/ui.php';
$view = (string)($_GET['v'] ?? 'dashboard');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !defined('LL_DEMO')) {
    if (!csrf_ok()) go('', 'Your session expired. Please try again.', 'err');
    require LL_ROOT . '/inc/admin/actions.php';
    go();
}
if (!can($view)) $view = 'dashboard';
require LL_ROOT . '/inc/admin/views.php';
