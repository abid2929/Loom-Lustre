<?php
// Loom & Lustre core: database, settings, helpers. PHP 8 + SQLite (no MySQL setup needed).
declare(strict_types=1);
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');
date_default_timezone_set('Asia/Dhaka');
mb_internal_encoding('UTF-8');

define('LL_ROOT', dirname(__DIR__));
define('LL_VERSION', '1.0.0');
if (getenv('LL_DEMO_BUILD')) define('LL_DEMO', true); // only used to build the static GitHub Pages demo

/** Private data folder: ~/loomlustre-data (outside public_html) when possible, else ./data (blocked by .htaccess). */
function data_dir(): string {
    static $d = null;
    if ($d !== null) return $d;
    if (getenv('LL_DATA_DIR')) return $d = getenv('LL_DATA_DIR');
    $home = getenv('HOME') ?: (function_exists('posix_getpwuid') ? (posix_getpwuid(posix_geteuid())['dir'] ?? '') : '');
    $c = [];
    if ($home && is_dir($home) && strpos(LL_ROOT, $home) === 0 && realpath($home) !== realpath(LL_ROOT)) $c[] = rtrim($home, '/') . '/loomlustre-data';
    $c[] = LL_ROOT . '/data';
    foreach ($c as $dir) {
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        if (is_dir($dir) && is_writable($dir)) {
            if (!file_exists("$dir/.htaccess")) @file_put_contents("$dir/.htaccess", "Require all denied\nDeny from all\n");
            return $d = $dir;
        }
    }
    return $d = LL_ROOT . '/data';
}

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $file = data_dir() . '/shop.sqlite';
    $new = !file_exists($file);
    $pdo = new PDO('sqlite:' . $file, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $pdo->exec('PRAGMA journal_mode=WAL; PRAGMA foreign_keys=ON; PRAGMA busy_timeout=4000;');
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS settings (k TEXT PRIMARY KEY, v TEXT);
    CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, slug TEXT UNIQUE NOT NULL,
        tagline TEXT DEFAULT '', image TEXT DEFAULT '', sort INTEGER DEFAULT 0, active INTEGER DEFAULT 1);
    CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, slug TEXT UNIQUE NOT NULL, category_id INTEGER,
        brand TEXT DEFAULT '', price INTEGER DEFAULT 0, old_price INTEGER DEFAULT 0, images TEXT DEFAULT '[]',
        shades TEXT DEFAULT '', size TEXT DEFAULT '', stock INTEGER DEFAULT 20, short TEXT DEFAULT '', description TEXT DEFAULT '',
        how_to TEXT DEFAULT '', tags TEXT DEFAULT '', featured INTEGER DEFAULT 0, bestseller INTEGER DEFAULT 0, is_new INTEGER DEFAULT 1,
        active INTEGER DEFAULT 1, sort INTEGER DEFAULT 0, views INTEGER DEFAULT 0, sold INTEGER DEFAULT 0, created TEXT, updated TEXT);
    CREATE INDEX IF NOT EXISTS ix_prod_cat ON products(category_id, active);
    CREATE TABLE IF NOT EXISTS orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT, code TEXT UNIQUE, name TEXT, phone TEXT, address TEXT, area TEXT, note TEXT,
        items TEXT, subtotal INTEGER, delivery INTEGER, total INTEGER, status TEXT DEFAULT 'new', admin_note TEXT DEFAULT '',
        seen INTEGER DEFAULT 0, ip TEXT, created TEXT, updated TEXT);
    CREATE INDEX IF NOT EXISTS ix_ord_status ON orders(status);
    CREATE TABLE IF NOT EXISTS hits (ip TEXT, kind TEXT, t INTEGER);
    ");
    if ($new) { require_once __DIR__ . '/seed.php'; ll_seed($pdo); }
    return $pdo;
}

/* ---------- settings ---------- */
function defaults(): array {
    return [
        'shop_name' => 'Loom & Lustre',
        'shop_sub' => 'Fashion & Beauty',
        'announce' => 'Cash on delivery all over Bangladesh  •  100% original products  •  Free delivery on orders over ৳3,000',
        'phone' => '01XXXXXXXXX',
        'whatsapp' => '',
        'email' => '',
        'address' => 'Dhaka, Bangladesh',
        'facebook' => '',
        'instagram' => '',
        'tiktok' => '',
        'youtube' => '',
        'delivery_in' => '70',
        'delivery_out' => '130',
        'delivery_in_label' => 'Inside Dhaka',
        'delivery_out_label' => 'Outside Dhaka',
        'free_over' => '3000',
        'currency' => '৳',
        'about' => 'Loom & Lustre brings you hand-picked lipsticks, skincare, fragrance and nail colours that are original, gentle on skin and fairly priced. Every order is checked by hand before it leaves our hands.',
        'meta_desc' => 'Loom & Lustre: original lipstick, skincare, perfume, nail polish and makeup in Bangladesh. Cash on delivery all over the country.',
        'wa_popup' => 'Need help choosing a shade? Ask us on WhatsApp.',
        'pixel_id' => '',
        'ga_id' => '',
        'notify_email' => '',
        // Home slider: 4 slides, each with image, small line, big title, text, button, link
        's1_on' => '1', 's1_img' => 'assets/img/b/owner-lip.webp', 's1_kicker' => 'NEW IN', 's1_title' => 'Moonlight Lip Tint Set', 's1_text' => '8 rich shades in one cute box. Soft matte finish that stays all day.', 's1_btn' => 'Shop lips', 's1_link' => 'c/lips', 's1_dark' => '0',
        's2_on' => '1', 's2_img' => 'assets/img/b/moist-cream.webp', 's2_kicker' => 'SKINCARE EDIT', 's2_title' => 'Glow starts with care', 's2_text' => 'Serums, creams and oils for fresh, healthy looking skin.', 's2_btn' => 'Shop skincare', 's2_link' => 'c/skincare', 's2_dark' => '0',
        's3_on' => '1', 's3_img' => 'assets/img/b/golden-amber.webp', 's3_kicker' => 'FRAGRANCE', 's3_title' => 'Wear your signature', 's3_text' => 'Long lasting perfumes for every mood. Up to 20% off this week.', 's3_btn' => 'Find your scent', 's3_link' => 'c/fragrance', 's3_dark' => '1',
        's4_on' => '1', 's4_img' => 'assets/img/b/swatch.webp', 's4_kicker' => 'BEAUTY SALE', 's4_title' => 'Up to 30% off', 's4_text' => 'Lipsticks, nail colours and makeup kits at special prices.', 's4_btn' => 'Shop the sale', 's4_link' => 'sale', 's4_dark' => '0',
    ];
}
function settings(): array {
    static $s = null;
    if ($s !== null) return $s;
    $s = defaults();
    foreach (db()->query('SELECT k,v FROM settings') as $r) $s[$r['k']] = $r['v'];
    return $s;
}
function set_setting(string $k, string $v): void {
    db()->prepare('INSERT INTO settings(k,v) VALUES(?,?) ON CONFLICT(k) DO UPDATE SET v=excluded.v')->execute([$k, $v]);
}
function S(string $k): string { return (string)(settings()[$k] ?? ''); }

/* ---------- helpers ---------- */
function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function base_url(): string {
    static $b = null;
    if ($b !== null) return $b;
    if (defined('LL_DEMO')) return $b = (getenv('LL_DEMO_BASE') ?: '/');
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    if (basename($dir) === 'admin') $dir = dirname($dir);
    return $b = rtrim($dir, '/') . '/';
}
function url(string $path = ''): string { return base_url() . ltrim($path, '/'); }
function money($n): string { return S('currency') . number_format((int)$n); }
function slugify(string $s): string {
    $s = mb_strtolower(trim($s));
    $s = preg_replace('/[^\p{L}\p{N}]+/u', '-', $s);
    $s = trim((string)$s, '-');
    return $s !== '' ? mb_substr($s, 0, 80) : 'item-' . substr(md5((string)microtime(true)), 0, 6);
}
function unique_slug(string $table, string $base, int $ignoreId = 0): string {
    $slug = slugify($base); $try = $slug; $i = 2;
    $st = db()->prepare("SELECT id FROM $table WHERE slug=? AND id<>?");
    while (true) { $st->execute([$try, $ignoreId]); if (!$st->fetch()) return $try; $try = $slug . '-' . $i++; }
}
function img_url(string $p): string {
    if ($p === '') return url('assets/img/placeholder.svg');
    if (preg_match('~^https?://~', $p)) return $p;
    return url($p);
}
/** Small copy for cards: foo.webp -> foo-s.webp when it exists. */
function img_small(string $p): string {
    if ($p !== '' && !preg_match('~^https?://~', $p)) {
        $s = preg_replace('~\.(webp|jpe?g|png)$~i', '-s.$1', $p);
        if ($s !== $p && is_file(LL_ROOT . '/' . $s)) return url($s);
    }
    return img_url($p);
}
function p_images(array $p): array { $a = json_decode($p['images'] ?: '[]', true); return is_array($a) ? array_values(array_filter($a)) : []; }
function p_image(array $p): string { return p_images($p)[0] ?? ''; }
function list_csv(string $s): array { return array_values(array_filter(array_map('trim', preg_split('/[,\n]+/u', $s)), 'strlen')); }
function discount_pct(array $p): int { return ($p['old_price'] > $p['price'] && $p['old_price'] > 0) ? (int)round(100 - $p['price'] * 100 / $p['old_price']) : 0; }
function client_ip(): string { return substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45); }
function rate_ok(string $kind, int $max, int $secs): bool {
    $pdo = db(); $now = time();
    $pdo->prepare('DELETE FROM hits WHERE t<?')->execute([$now - 86400]);
    $st = $pdo->prepare('SELECT COUNT(*) FROM hits WHERE ip=? AND kind=? AND t>?');
    $st->execute([client_ip(), $kind, $now - $secs]);
    if ((int)$st->fetchColumn() >= $max) return false;
    $pdo->prepare('INSERT INTO hits(ip,kind,t) VALUES(?,?,?)')->execute([client_ip(), $kind, $now]);
    return true;
}
function categories(bool $activeOnly = true): array {
    return db()->query('SELECT * FROM categories' . ($activeOnly ? ' WHERE active=1' : '') . ' ORDER BY sort, id')->fetchAll();
}
function norm_phone(string $p): string {
    $p = strtr($p, ['০'=>'0','১'=>'1','২'=>'2','৩'=>'3','৪'=>'4','৫'=>'5','৬'=>'6','৭'=>'7','৮'=>'8','৯'=>'9']);
    $d = preg_replace('/\D/', '', $p);
    if (strlen($d) === 13 && str_starts_with($d, '880')) $d = substr($d, 2);
    return $d;
}
/** WhatsApp number in 8801XXXXXXXXX form, from the WhatsApp setting or the phone. '' if none is set yet. */
function wa_number(): string {
    $n = norm_phone(S('whatsapp') ?: S('phone'));
    if (strlen($n) === 11 && $n[0] === '0') return '88' . $n;
    return strlen($n) >= 11 ? $n : '';
}
function wa_link(string $text = ''): string {
    $n = wa_number();
    return $n ? 'https://wa.me/' . $n . ($text !== '' ? '?text=' . rawurlencode($text) : '') : url('contact');
}
function json_out($data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
const ORDER_STATUS = [
    'new' => 'নতুন', 'confirmed' => 'কনফার্মড', 'shipped' => 'পাঠানো হয়েছে',
    'delivered' => 'ডেলিভারি সম্পন্ন', 'cancelled' => 'বাতিল', 'returned' => 'ফেরত',
];
const ORDER_STATUS_EN = [
    'new' => 'Received', 'confirmed' => 'Confirmed', 'shipped' => 'On the way',
    'delivered' => 'Delivered', 'cancelled' => 'Cancelled', 'returned' => 'Returned',
];
