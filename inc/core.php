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
    CREATE TABLE IF NOT EXISTS reviews (
        id INTEGER PRIMARY KEY AUTOINCREMENT, product_id INTEGER, name TEXT, phone TEXT DEFAULT '', rating INTEGER DEFAULT 5,
        body TEXT DEFAULT '', approved INTEGER DEFAULT 0, ip TEXT DEFAULT '', created TEXT);
    CREATE INDEX IF NOT EXISTS ix_rev_prod ON reviews(product_id, approved);
    CREATE TABLE IF NOT EXISTS admins (
        id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT UNIQUE NOT NULL, name TEXT DEFAULT '', pass TEXT NOT NULL,
        role TEXT DEFAULT 'staff', active INTEGER DEFAULT 1, last_login TEXT, created TEXT);
    CREATE TABLE IF NOT EXISTS logins (username TEXT, ok INTEGER, ip TEXT, t TEXT);
    CREATE TABLE IF NOT EXISTS collections (
        id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, slug TEXT UNIQUE NOT NULL, tagline TEXT DEFAULT '',
        image TEXT DEFAULT '', sort INTEGER DEFAULT 0, active INTEGER DEFAULT 1);
    CREATE TABLE IF NOT EXISTS visits (vid TEXT, ip TEXT, path TEXT, kind TEXT DEFAULT 'view', ref TEXT DEFAULT '', day TEXT, t INTEGER);
    CREATE INDEX IF NOT EXISTS ix_vis_day ON visits(day);
    CREATE INDEX IF NOT EXISTS ix_vis_t ON visits(t);
    ");
    $pc0 = array_column($pdo->query('PRAGMA table_info(products)')->fetchAll(), 'name');
    foreach (['collection_id' => 'INTEGER DEFAULT 0', 'sizes' => "TEXT DEFAULT ''", 'image_tags' => "TEXT DEFAULT '{}'"] as $c => $t)
        if (!in_array($c, $pc0, true)) $pdo->exec("ALTER TABLE products ADD COLUMN $c $t");
    // v1.1: flash sale flag, payment method + courier fields on orders
    $pc = array_column($pdo->query('PRAGMA table_info(products)')->fetchAll(), 'name');
    if (!in_array('flash_sale', $pc, true)) $pdo->exec('ALTER TABLE products ADD COLUMN flash_sale INTEGER DEFAULT 0');
    $oc = array_column($pdo->query('PRAGMA table_info(orders)')->fetchAll(), 'name');
    foreach (['payment' => "TEXT DEFAULT 'cod'", 'trx_id' => "TEXT DEFAULT ''", 'courier' => "TEXT DEFAULT ''", 'tracking' => "TEXT DEFAULT ''"] as $c => $t)
        if (!in_array($c, $oc, true)) $pdo->exec("ALTER TABLE orders ADD COLUMN $c $t");
    // v1.2: shared admin (same on every shop). Extra columns + tables it needs.
    $pc = array_column($pdo->query('PRAGMA table_info(products)')->fetchAll(), 'name');
    if (!in_array('ext_id', $pc, true)) $pdo->exec("ALTER TABLE products ADD COLUMN ext_id TEXT DEFAULT ''");
    $oc = array_column($pdo->query('PRAGMA table_info(orders)')->fetchAll(), 'name');
    foreach (['paid' => 'INTEGER DEFAULT 0', 'hist' => "TEXT DEFAULT '[]'", 'courier_json' => "TEXT DEFAULT ''", 'src' => "TEXT DEFAULT 'website'"] as $c => $t)
        if (!in_array($c, $oc, true)) $pdo->exec("ALTER TABLE orders ADD COLUMN $c $t");
    $pdo->exec("CREATE TABLE IF NOT EXISTS col_items (collection_id INTEGER, product_id INTEGER, PRIMARY KEY(collection_id, product_id));
        CREATE TABLE IF NOT EXISTS audit (t INTEGER, u TEXT, w TEXT);
        CREATE TABLE IF NOT EXISTS capi_log (t INTEGER, code INTEGER);");
    if (!$new && (int)$pdo->query('SELECT COUNT(*) FROM col_items')->fetchColumn() === 0)
        $pdo->exec('INSERT OR IGNORE INTO col_items(collection_id, product_id) SELECT collection_id, id FROM products WHERE collection_id > 0');
    $pdo->exec("UPDATE settings SET v='What are you looking for?' WHERE k='h_cats_t' AND v='Shop by category'");
    // v1.3: fashion categories. Added once, and only to a shop that still has the demo products.
    if (!$pdo->query("SELECT 1 FROM settings WHERE k='v13_fashion'")->fetchColumn()) {
        $pdo->exec("INSERT OR REPLACE INTO settings(k,v) VALUES('v13_fashion','1')");
        if (!$new && $pdo->query("SELECT 1 FROM products WHERE slug='velvet-red-matte-lipstick'")->fetchColumn()) { require_once __DIR__ . '/seed.php'; ll_seed_fashion($pdo); }
    }
    // v1.5: Watches + Home departments and Nykaa-style home sections
    if (!$pdo->query("SELECT 1 FROM settings WHERE k='v15_home'")->fetchColumn()) {
        $pdo->exec("INSERT OR REPLACE INTO settings(k,v) VALUES('v15_home','1')");
        $pdo->exec("UPDATE settings SET v='Iconic Styles, Unreal Savings' WHERE k='h_spot_t' AND lower(v)='in the spotlight'");
        if (!$new && $pdo->query("SELECT 1 FROM categories WHERE slug='ethnic'")->fetchColumn()) { require_once __DIR__ . '/seed.php'; ll_seed_v15($pdo); }
    }
    // v1.6: separate Luxe store (own lx- categories and products)
    if (!$pdo->query("SELECT 1 FROM settings WHERE k='v16_luxe'")->fetchColumn()) {
        $pdo->exec("INSERT OR REPLACE INTO settings(k,v) VALUES('v16_luxe','1')");
        if (!$new && $pdo->query("SELECT 1 FROM categories WHERE slug='watches'")->fetchColumn()) { require_once __DIR__ . '/seed.php'; ll_seed_v16($pdo); }
    }
    // v1.7: more fashion products
    if (!$pdo->query("SELECT 1 FROM settings WHERE k='v17_more'")->fetchColumn()) {
        $pdo->exec("INSERT OR REPLACE INTO settings(k,v) VALUES('v17_more','1')");
        if (!$new && $pdo->query("SELECT 1 FROM categories WHERE slug='westernwear'")->fetchColumn()) { require_once __DIR__ . '/seed.php'; ll_seed_v17($pdo); }
    }
    if ($new) { require_once __DIR__ . '/seed.php'; ll_seed($pdo); $pdo->exec('INSERT OR IGNORE INTO col_items(collection_id, product_id) SELECT collection_id, id FROM products WHERE collection_id > 0'); }
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
        'about' => 'Loom & Lustre brings you hand-picked sarees, kurtis, westernwear, shoes, bags, jewellery and beauty that are original, well made and fairly priced. Every order is checked by hand before it leaves our hands.',
        'meta_desc' => 'Loom & Lustre: sarees, kurtis, westernwear, shoes, bags, jewellery, lipstick and skincare in Bangladesh. Cash on delivery all over the country.',
        'wa_popup' => 'Need help choosing a shade? Ask us on WhatsApp.',
        'pixel_id' => '',
        'ga_id' => '',
        'notify_email' => '',
        // Home slider: 4 slides, each with image, small line, big title, text, button, link
        's1_on' => '1', 's1_img' => 'assets/img/b/ethnic-festive.webp', 's1_kicker' => 'ETHNIC WEAR FESTIVAL', 's1_title' => 'Sarees & kurtis for every celebration', 's1_text' => 'Banarasi, katan and soft silk sarees, block print kurtis and festive salwar sets.', 's1_btn' => 'Shop ethnic wear', 's1_link' => 'c/ethnic', 's1_dark' => '0',
        's2_on' => '1', 's2_img' => 'assets/img/b/owner-lip.webp', 's2_kicker' => 'NEW IN', 's2_title' => 'Moonlight Lip Tint Set', 's2_text' => '8 rich shades in one cute box. Soft matte finish that stays all day.', 's2_btn' => 'Shop lips', 's2_link' => 'c/lips', 's2_dark' => '0',
        's3_on' => '1', 's3_img' => 'assets/img/b/western-edit.webp', 's3_kicker' => 'WESTERNWEAR', 's3_title' => 'Fresh styles, easy days', 's3_text' => 'Shirt dresses, co-ord sets and party gowns.', 's3_btn' => 'Shop westernwear', 's3_link' => 'c/westernwear', 's3_dark' => '0',
        's4_on' => '1', 's4_img' => 'assets/img/b/men-edit.webp', 's4_kicker' => 'MEN', 's4_title' => 'Sharp suits & easy shirts', 's4_text' => 'Formals, casuals, jackets and shoes for him.', 's4_btn' => 'Shop men', 's4_link' => 'd/men', 's4_dark' => '0',
        // delivery
        'delivery_time_in' => '1 to 2 days', 'delivery_time_out' => '2 to 4 days',
        // payment
        'cod_on' => '1', 'bkash_on' => '0', 'bkash_number' => '', 'bkash_type' => 'Personal', 'nagad_on' => '0', 'nagad_number' => '', 'nagad_type' => 'Personal',
        'pay_note' => 'Send the total amount, then write your Transaction ID below.',
        // courier
        'courier' => 'none', 'steadfast_key' => '', 'steadfast_secret' => '',
        // SMS (bulksmsbd.net style API)
        'sms_on' => '0', 'sms_url' => 'http://bulksmsbd.net/api/smsapi', 'sms_api_key' => '', 'sms_sender' => '',
        'sms_order' => 'Dear {name}, your order {code} of Tk {total} is received. We will call you soon. - Loom & Lustre',
        'sms_shipped' => 'Dear {name}, your order {code} is on the way. - Loom & Lustre',
        // Meta pixel + Conversions API
        'capi_token' => '', 'capi_test' => '',
        // SEO
        'meta_title' => 'Loom & Lustre | Fashion & Beauty Shop in Bangladesh',
        'meta_keywords' => 'lipstick bangladesh, skincare, makeup, perfume, nail polish, cosmetics shop, loom and lustre',
        // homepage sections (Homepage CMS)
        'h_cats_on' => '1', 'h_cats_t' => 'What are you looking for?', 'h_cats_s' => '',
        'h_flash_on' => '1', 'h_flash_t' => 'Flash sale', 'h_flash_s' => 'Hurry, these prices end tonight',
        'h_celeb_on' => '1', 'h_celeb_t' => 'Celebration-ready Categories',
        'h_eid_on' => '1', 'h_eid_t' => 'Build Your Eid Wardrobe',
        'h_spot_on' => '1', 'h_spot_t' => 'Iconic Styles, Unreal Savings', 'h_spot_s' => 'The biggest offers in every department',
        'h_styles_on' => '1', 'h_styles_t' => 'Styles To Celebrate In', 'h_styles_s' => 'Ready-made edits for every occasion',
        'h_picks_on' => '1', 'h_picks_t' => "Stylists' Picks", 'h_picks_s' => 'CHOSEN BY OUR STYLISTS THIS WEEK',
        'h_luxe_on' => '1', 'h_luxe_t' => 'The Luxe Edit', 'h_luxe_s' => 'Silk, gold and fine finishes for the moments that matter.',
        'h_circles_on' => '1', 'h_circles_t' => 'Find everything for your every need', 'h_circles_s' => 'Fashion, beauty and accessories in one place',
        'h_deals_on' => '1', 'h_deals_t' => 'Deals of the week', 'h_deals_s' => 'Special prices for a short time',
        'h_promo_on' => '0',
        'h_best_on' => '1', 'h_best_t' => 'Bestsellers', 'h_best_s' => 'What our customers love most',
        'h_band_on' => '1', 'h_band_t' => 'Fashion & beauty you can trust',
        'h_makeup_on' => '1', 'h_makeup_t' => 'Makeup must-haves', 'h_makeup_s' => 'Palettes, blush, mascara and more',
        'h_skin_on' => '1', 'h_skin_t' => 'Skincare favourites', 'h_skin_s' => 'Serums and creams for a healthy glow',
        'h_new_on' => '1', 'h_new_t' => 'New in', 'h_new_s' => 'Fresh arrivals this week',
        'h_reviews_on' => '1', 'h_reviews_t' => 'Loved by our customers',
        'h_help_on' => '1', 'h_help_t' => 'Not sure which shade?', 'h_help_s' => 'Send us a selfie or tell us your skin tone. We will suggest the right lipstick or foundation shade, free.',
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
function site_origin(): string {
    if (defined('LL_DEMO')) return getenv('LL_DEMO_ORIGIN') ?: 'https://abid2929.github.io';
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    return ($https ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}
function abs_url(string $path = ''): string {
    $u = url($path);
    if (defined('LL_DEMO') && $path !== '' && !str_contains(basename($path), '.')) $u .= '/';
    return site_origin() . $u;
}
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
function p_images(array $p): array { $a = json_decode(($p['images'] ?? '') ?: '[]', true); return is_array($a) ? array_values(array_filter($a)) : []; }
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
    // Storefront: the Fashion store and the Luxe store (slug lx-...) each see only their own categories.
    $w = $activeOnly ? " WHERE active=1 AND slug " . (ll_lux() ? '' : 'NOT ') . "LIKE 'lx-%'" : '';
    return db()->query('SELECT * FROM categories' . $w . ' ORDER BY sort, id')->fetchAll();
}
/** True on Luxe store pages (luxe home, lx- categories and their products). */
function ll_lux(?bool $set = null): bool {
    if ($set !== null) { $GLOBALS['ll_lux'] = $set; if ($set) $GLOBALS['ll_body'] = 'lxm'; }
    return !empty($GLOBALS['ll_lux']);
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
const PAYMENTS = ['cod' => 'Cash on delivery', 'bkash' => 'bKash', 'nagad' => 'Nagad'];

/* ---------- outside services (all optional, switched on from Admin > Site settings) ---------- */
function http_call(string $method, string $url, array $headers = [], ?string $body = null, int $timeout = 12): array {
    if (!function_exists('curl_init')) return [0, ''];
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $timeout, CURLOPT_CONNECTTIMEOUT => 6, CURLOPT_CUSTOMREQUEST => $method, CURLOPT_HTTPHEADER => $headers]);
    if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    $res = (string)curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return [$code, $res];
}
function fill_tpl(string $t, array $o): string {
    return strtr($t, ['{name}' => $o['name'] ?? '', '{code}' => $o['code'] ?? '', '{total}' => (string)($o['total'] ?? ''), '{tracking}' => $o['tracking'] ?? '']);
}
/** SMS through a bulksmsbd.net style GET API. Returns true when the gateway answered OK. */
function send_sms(string $phone, string $text, bool $force = false): bool {
    if (defined('LL_DEMO') || (!$force && S('sms_on') !== '1')) return false;
    $tpl = S('sms_tpl');   // any gateway: a link with {number} and {message}
    if ($tpl !== '' && preg_match('~^https?://~', $tpl)) {
        $num = norm_phone($phone); if (strlen($num) === 11) $num = '88' . $num;
        [$code] = http_call('GET', str_replace(['{number}', '{message}'], [rawurlencode($num), rawurlencode($text)], $tpl));
        return $code >= 200 && $code < 300;
    }
    if (S('sms_api_key') === '') return false;
    $q = http_build_query(['api_key' => S('sms_api_key'), 'type' => 'text', 'number' => '88' . norm_phone($phone), 'senderid' => S('sms_sender'), 'message' => $text]);
    [$code, $res] = http_call('GET', S('sms_url') . '?' . $q);
    return $code >= 200 && $code < 300;
}
/** Meta Conversions API: server copy of a browser event (same event_id, so Meta counts it once). */
function capi_event(string $name, string $eventId, array $custom = [], array $user = []): void {
    if (S('pixel_id') === '' || S('capi_token') === '' || defined('LL_DEMO')) return;
    $ud = ['client_ip_address' => client_ip(), 'client_user_agent' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 300)];
    if (!empty($user['phone'])) $ud['ph'] = [hash('sha256', '880' . substr(norm_phone($user['phone']), 1))];
    if (!empty($user['name'])) $ud['fn'] = [hash('sha256', mb_strtolower(trim(explode(' ', $user['name'])[0])))];
    foreach (['fbp' => '_fbp', 'fbc' => '_fbc'] as $k => $c) if (!empty($_COOKIE[$c])) $ud[$k] = $_COOKIE[$c];
    $ev = ['event_name' => $name, 'event_time' => time(), 'event_id' => $eventId, 'action_source' => 'website', 'event_source_url' => (string)($_SERVER['HTTP_REFERER'] ?? ''), 'user_data' => $ud, 'custom_data' => $custom];
    $body = ['data' => [$ev]];
    if (S('capi_test') !== '') $body['test_event_code'] = S('capi_test');
    [$code] = http_call('POST', 'https://graph.facebook.com/v19.0/' . rawurlencode(S('pixel_id')) . '/events?access_token=' . rawurlencode(S('capi_token')), ['Content-Type: application/json'], json_encode($body), 6);
    try { db()->prepare('INSERT INTO capi_log(t,code) VALUES(?,?)')->execute([time(), $code]); } catch (Throwable $e) {}
    $n = (int)S('capi_count'); set_setting('capi_count', (string)($n + 1));
}
/** Steadfast courier: create a parcel for an order. Returns [ok, message, tracking]. */
function steadfast_create(array $o): array {
    if (S('steadfast_key') === '' || S('steadfast_secret') === '') return [false, 'Steadfast API keys are missing (Settings > Courier Automation).', ''];
    $cod = ($o['payment'] ?? 'cod') === 'cod' ? (int)$o['total'] : 0;
    $body = json_encode(['invoice' => $o['code'], 'recipient_name' => $o['name'], 'recipient_phone' => norm_phone($o['phone']), 'recipient_address' => mb_substr($o['address'], 0, 250), 'cod_amount' => $cod, 'note' => $o['note'] ?? '']);
    [$code, $res] = http_call('POST', rtrim(S('sf_base') ?: 'https://portal.packzy.com/api/v1', '/') . '/create_order', ['Api-Key: ' . S('steadfast_key'), 'Secret-Key: ' . S('steadfast_secret'), 'Content-Type: application/json'], $body);
    $j = json_decode($res, true) ?: [];
    if ($code === 200 && !empty($j['consignment']['tracking_code'])) return [true, 'Sent to Steadfast.', (string)$j['consignment']['tracking_code']];
    return [false, 'Steadfast error: ' . mb_substr((string)($j['message'] ?? $res ?: 'no answer'), 0, 160), ''];
}
/** Average rating and count of approved reviews for each product id. */
function review_stats(): array {
    static $r = null;
    if ($r !== null) return $r;
    $r = [];
    foreach (db()->query('SELECT product_id, AVG(rating) a, COUNT(*) n FROM reviews WHERE approved=1 GROUP BY product_id') as $x) $r[(int)$x['product_id']] = [round((float)$x['a'], 1), (int)$x['n']];
    return $r;
}
function collections(bool $activeOnly = true): array {
    return db()->query('SELECT * FROM collections' . ($activeOnly ? ' WHERE active=1' : '') . ' ORDER BY sort, id')->fetchAll();
}
/** Count a storefront page view (bots skipped). Kept 90 days. */
function track_visit(string $path, string $kind = 'view'): void {
    if (defined('LL_DEMO') || PHP_SAPI === 'cli') return;
    $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
    if ($ua === '' || preg_match('~bot|crawl|spider|slurp|facebookexternalhit|preview|monitor|curl|wget|python|headless~i', $ua)) return;
    $vid = (string)($_COOKIE['llv'] ?? '');
    if (!preg_match('/^[a-f0-9]{16}$/', $vid)) { $vid = bin2hex(random_bytes(8)); if (!headers_sent()) setcookie('llv', $vid, ['expires' => time() + 86400 * 365, 'path' => '/', 'samesite' => 'Lax']); }
    $ref = (string)($_SERVER['HTTP_REFERER'] ?? '');
    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    $ref = ($ref !== '' && $host !== '' && stripos($ref, $host) === false) ? mb_substr((string)parse_url($ref, PHP_URL_HOST), 0, 80) : '';
    try {
        db()->prepare('INSERT INTO visits(vid,ip,path,kind,ref,day,t) VALUES(?,?,?,?,?,?,?)')->execute([$vid, client_ip(), mb_substr($path, 0, 160), $kind, $ref, date('Y-m-d'), time()]);
        if (random_int(1, 200) === 1) db()->prepare('DELETE FROM visits WHERE t<?')->execute([time() - 86400 * 90]);
    } catch (Throwable $e) {}
}
