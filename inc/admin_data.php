<?php
/* Bridge between the shared admin panel (same on every shop, JSON shapes from PetVerse)
   and this shop's SQLite database. ll_get(name) gives the admin its data, ll_put(name, data) saves it back. */
declare(strict_types=1);

const LL_ST_TO_ADMIN = ['new' => 'pending', 'confirmed' => 'processing', 'shipped' => 'shipped', 'delivered' => 'delivered', 'cancelled' => 'cancelled', 'returned' => 'returned'];
const LL_SECTIONS = ['cats' => 'What are you looking for?', 'flash' => 'Flash sale', 'spot' => 'In the spotlight (offers by category)', 'styles' => 'Styles to celebrate (collections)', 'deals' => 'Deals of the week', 'promo' => 'Two promo banners', 'picks' => "Stylists' picks (dark band)", 'best' => 'Bestsellers', 'luxe' => 'Luxe edit', 'band' => 'Why us band', 'makeup' => 'Makeup row', 'skin' => 'Skincare row', 'new' => 'New in', 'circles' => 'Round category circles', 'reviews' => 'Customer reviews', 'help' => 'Shade help box'];

function ll_status_db(string $s): string { $f = array_flip(LL_ST_TO_ADMIN); return $f[$s] ?? 'new'; }
function ll_is_local(string $s): bool { return (bool)preg_match('~^(assets|uploads)/[a-z0-9_\-/.]+\.(jpe?g|png|webp|gif)$~i', $s) && !str_contains($s, '..'); }
function ll_clean($v, int $n = 300): string { return trim(mb_substr((string)$v, 0, $n)); }

/** Hero banners: stored as JSON in settings; first run builds them from the 4 built-in slides. */
function ll_banners(): array {
    $j = json_decode(S('banners'), true);
    if (is_array($j)) return $j;
    $out = [];
    for ($i = 1; $i <= 4; $i++) if (S("s{$i}_img") !== '')
        $out[] = ['img' => S("s{$i}_img"), 'title' => S("s{$i}_title"), 'link' => S("s{$i}_link"), 'on' => (int)(S("s{$i}_on") === '1'), 'kicker' => S("s{$i}_kicker"), 'text' => S("s{$i}_text"), 'btn' => S("s{$i}_btn"), 'dark' => (int)(S("s{$i}_dark") === '1')];
    return $out;
}

function ll_get(string $c) {
    $pdo = db();
    if ($c === 'categories') {
        return array_map(fn($r) => ['k' => $r['slug'], 'name' => $r['name'], 'group' => 'main', 'img' => $r['image'], 'order' => (int)$r['sort'], 'on' => (int)$r['active'], 'tagline' => $r['tagline']], categories(false));
    }
    if ($c === 'products') {
        $cats = []; foreach (categories(false) as $r) $cats[(int)$r['id']] = $r['slug'];
        $out = []; foreach ($cats as $slug) $out[$slug] = [];
        foreach ($pdo->query('SELECT * FROM products ORDER BY sort, id DESC') as $p) {
            $k = $cats[(int)$p['category_id']] ?? (array_values($cats)[0] ?? 'other');
            $imgs = p_images($p); $tags = json_decode((string)($p['image_tags'] ?? '{}'), true) ?: [];
            $x = ['id' => (string)$p['id'], 'n' => $p['name'], 'b' => $p['brand']];
            if ($p['size'] !== '') $x['t'] = $p['size'];
            if ($imgs) { if (preg_match('~^https?://~', $imgs[0])) $x['r'] = $imgs[0]; else $x['i'] = $imgs[0]; }
            foreach (array_slice($imgs, 1) as $g) $x['g'][] = ['s' => $g, 'c' => (string)($tags[$g] ?? '')];
            if ((int)$p['price'] > 0) $x['p'] = (int)$p['price'];
            if ((int)$p['old_price'] > (int)$p['price']) $x['o'] = (int)$p['old_price'];
            $x['st'] = (int)$p['stock'];
            if (($p['sizes'] ?? '') !== '') $x['sz'] = $p['sizes'];
            if ($p['shades'] !== '') $x['cl'] = implode(', ', list_csv($p['shades']));
            if ($p['short'] !== '') $x['sd'] = $p['short'];
            if ($p['description'] !== '') $x['d'] = $p['description'];
            foreach (['ft' => 'featured', 'fs' => 'flash_sale', 'nw' => 'is_new', 'bs' => 'bestseller'] as $f => $col) if (!empty($p[$col])) $x[$f] = 1;
            if (empty($p['active'])) $x['off'] = 1;
            $x['slug'] = $p['slug']; $x['sold'] = (int)$p['sold'];
            $out[$k][] = $x;
        }
        return $out;
    }
    if ($c === 'collections') {
        $out = [];
        foreach (collections(false) as $r) {
            $ids = $pdo->prepare('SELECT product_id FROM col_items WHERE collection_id=?'); $ids->execute([$r['id']]);
            $out[] = ['slug' => $r['slug'], 'name' => $r['name'], 'ids' => array_map('strval', $ids->fetchAll(PDO::FETCH_COLUMN)), 'on' => (int)$r['active'], 'tagline' => $r['tagline'], 'image' => $r['image']];
        }
        return $out;
    }
    if ($c === 'reviews') {
        $out = [];
        foreach ($pdo->query('SELECT r.*, p.name pname FROM reviews r LEFT JOIN products p ON p.id=r.product_id ORDER BY r.approved ASC, r.id DESC') as $r)
            $out[] = ['id' => (int)$r['id'], 'pid' => (int)$r['product_id'], 'name' => $r['name'], 'pet' => (string)($r['pname'] ?? ''), 'text' => $r['body'], 'stars' => (int)$r['rating'], 'on' => (int)$r['approved'], 'phone' => $r['phone'], 't' => strtotime((string)$r['created']) ?: 0];
        return $out;
    }
    if ($c === 'banners') return ll_banners();
    if ($c === 'site') {
        $sections = [];
        foreach (LL_SECTIONS as $k => $label) { $x = ['k' => $k, 'label' => $label, 'on' => (int)(S("h_{$k}_on") === '1'), 't' => S("h_{$k}_t")]; if (array_key_exists("h_{$k}_s", defaults())) $x['s'] = S("h_{$k}_s"); $sections[] = $x; }
        return [
            'store' => ['name' => S('shop_name'), 'tagline' => S('shop_sub'), 'phone' => S('phone'), 'whatsapp' => S('whatsapp'), 'email' => S('email'), 'address' => S('address'), 'facebook' => S('facebook'), 'hours' => S('hours'), 'map' => S('map')],
            'delivery' => ['inside' => (int)S('delivery_in'), 'outside' => (int)S('delivery_out'), 'free_over' => (int)S('free_over'), 'note' => S('delivery_note') !== '' ? S('delivery_note') : 'Inside Dhaka ' . S('delivery_time_in') . ', outside Dhaka ' . S('delivery_time_out') . '.'],
            'payment' => ['cod' => (int)(S('cod_on') === '1'), 'bkash' => S('bkash_on') === '1' ? S('bkash_number') : '', 'nagad' => S('nagad_on') === '1' ? S('nagad_number') : '', 'note' => S('pay_note')],
            'cms' => ['ticker' => list_csv(str_replace('•', ',', S('announce'))), 'about' => S('about'), 'deal' => ['id' => S('deal_id'), 'ends' => S('deal_ends')], 'announce' => S('announce_bar'), 'sections' => $sections],
            'seo' => ['title' => S('meta_title'), 'desc' => S('meta_desc'), 'keys' => S('meta_keywords'), 'ga' => S('ga_id'), 'gsv' => S('gsv')],
            'pixel' => ['id' => S('pixel_id')],
        ];
    }
    if ($c === 'orders') {
        $out = [];
        foreach ($pdo->query('SELECT * FROM orders ORDER BY id DESC') as $o) {
            $items = array_map(fn($i) => ['id' => (string)($i['id'] ?? ''), 'n' => (string)($i['name'] ?? ''), 'v' => (string)($i['shade'] ?? ''), 'q' => (int)($i['qty'] ?? 1), 'p' => (int)($i['price'] ?? 0), 'i' => (string)($i['img'] ?? '')], json_decode((string)$o['items'], true) ?: []);
            $x = ['no' => $o['code'], 't' => strtotime((string)$o['created']) ?: 0, 'name' => $o['name'], 'phone' => $o['phone'], 'address' => $o['address'], 'area' => $o['area'] === 'out' ? 'out' : 'in', 'note' => (string)$o['note'],
                'pay' => $o['payment'] ?: 'cod', 'trx' => (string)$o['trx_id'], 'items' => $items, 'sub' => (int)$o['subtotal'], 'ship' => (int)$o['delivery'], 'total' => (int)$o['total'],
                'status' => LL_ST_TO_ADMIN[$o['status']] ?? 'pending', 'src' => $o['src'] ?: 'website', 'admin_note' => (string)$o['admin_note'], 'paid' => (int)$o['paid'], 'hist' => json_decode((string)$o['hist'], true) ?: []];
            $cj = json_decode((string)$o['courier_json'], true);
            if ($cj) $x['courier'] = $cj; elseif ($o['tracking'] !== '') $x['courier'] = ['by' => 'steadfast', 'cid' => '', 'code' => $o['tracking'], 'st' => 'in_review', 't' => $x['t']];
            $out[] = $x;
        }
        return $out;
    }
    return null;
}

function ll_slug_free(string $table, string $base, int $skipId = 0): string {
    $base = slugify($base) ?: 'item'; $s = $base; $n = 2;
    $st = db()->prepare("SELECT id FROM $table WHERE slug=? AND id<>?");
    while (true) { $st->execute([$s, $skipId]); if (!$st->fetch()) return $s; $s = $base . '-' . $n++; }
}

/** Save what the admin sent. Returns an error string or ''. */
function ll_put(string $c, array $d): string {
    $pdo = db(); $now = date('Y-m-d H:i:s');
    if ($c === 'products') {
        $catId = []; foreach (categories(false) as $r) $catId[$r['slug']] = (int)$r['id'];
        $keep = [];
        $pdo->beginTransaction();
        foreach ($d as $k => $list) {
            if (!isset($catId[$k]) || !is_array($list)) continue;
            foreach (array_values($list) as $j => $x) {
                if (!is_array($x) || ll_clean($x['n'] ?? '') === '') continue;
                $imgs = []; $tags = [];
                foreach (array_merge([['s' => $x['i'] ?? ($x['r'] ?? ''), 'c' => '']], (array)($x['g'] ?? [])) as $g) {
                    $s = (string)($g['s'] ?? ''); if ($s === '') continue;
                    if (!ll_is_local($s) && !preg_match('~^https://[^\s"<>]+$~', $s)) continue;
                    $imgs[] = mb_substr($s, 0, 600); if (($g['c'] ?? '') !== '') $tags[$s] = ll_clean($g['c'], 60);
                }
                $price = (int)($x['p'] ?? 0); $old = (int)($x['o'] ?? 0); if ($old <= $price) $old = 0;
                $row = ['name' => ll_clean($x['n'], 160), 'brand' => ll_clean($x['b'] ?? '', 60), 'size' => ll_clean($x['t'] ?? '', 40), 'category_id' => $catId[$k],
                    'price' => $price, 'old_price' => $old, 'stock' => (isset($x['st']) && $x['st'] !== '' && is_numeric($x['st'])) ? max(0, (int)$x['st']) : 50,
                    'sizes' => ll_clean($x['sz'] ?? '', 200), 'shades' => ll_clean($x['cl'] ?? '', 300), 'short' => ll_clean($x['sd'] ?? '', 600), 'description' => ll_clean($x['d'] ?? '', 6000),
                    'featured' => empty($x['ft']) ? 0 : 1, 'flash_sale' => empty($x['fs']) ? 0 : 1, 'is_new' => empty($x['nw']) ? 0 : 1, 'bestseller' => empty($x['bs']) ? 0 : 1, 'active' => empty($x['off']) ? 1 : 0,
                    'sort' => $j, 'images' => json_encode($imgs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 'updated' => $now];
                $id = (string)($x['id'] ?? '');
                $ex = null;
                if (ctype_digit($id)) { $q = $pdo->prepare('SELECT id, image_tags, images FROM products WHERE id=?'); $q->execute([(int)$id]); $ex = $q->fetch(); }
                if (!$ex && $id !== '') { $q = $pdo->prepare('SELECT id, image_tags, images FROM products WHERE ext_id=?'); $q->execute([$id]); $ex = $q->fetch(); }
                if ($ex) {   // keep the shade tag the main photo already had
                    $old_tags = json_decode((string)$ex['image_tags'], true) ?: [];
                    if ($imgs && isset($old_tags[$imgs[0]])) $tags[$imgs[0]] = $old_tags[$imgs[0]];
                }
                $row['image_tags'] = json_encode((object)$tags, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                if ($ex) {
                    $sets = implode(',', array_map(fn($f) => "$f=?", array_keys($row)));
                    $pdo->prepare("UPDATE products SET $sets WHERE id=?")->execute([...array_values($row), (int)$ex['id']]);
                    $keep[] = (int)$ex['id'];
                } else {
                    $row['slug'] = ll_slug_free('products', $row['name']); $row['created'] = $now; $row['ext_id'] = preg_replace('/[^a-z0-9\-]/i', '', $id);
                    $f = array_keys($row);
                    $pdo->prepare('INSERT INTO products(' . implode(',', $f) . ') VALUES(' . implode(',', array_fill(0, count($f), '?')) . ')')->execute(array_values($row));
                    $keep[] = (int)$pdo->lastInsertId();
                }
            }
        }
        $gone = $keep ? $pdo->query('SELECT id FROM products WHERE id NOT IN (' . implode(',', $keep) . ')')->fetchAll(PDO::FETCH_COLUMN) : [];
        foreach ($gone as $g) { $pdo->prepare('DELETE FROM products WHERE id=?')->execute([$g]); $pdo->prepare('DELETE FROM col_items WHERE product_id=?')->execute([$g]); }
        $pdo->commit();
        return '';
    }
    if ($c === 'categories') {
        $keep = []; $pdo->beginTransaction();
        foreach ($d as $i => $x) {
            $slug = strtolower(preg_replace('/[^a-z0-9\-]/i', '', (string)($x['k'] ?? ''))); $name = ll_clean($x['name'] ?? '', 80);
            if ($slug === '' || $name === '') continue;
            $img = (string)($x['img'] ?? ''); if ($img !== '' && !ll_is_local($img) && !preg_match('~^https://~', $img)) $img = '';
            $q = $pdo->prepare('SELECT id FROM categories WHERE slug=?'); $q->execute([$slug]); $id = $q->fetchColumn();
            $vals = [$name, $img, (int)($x['order'] ?? $i), empty($x['on']) ? 0 : 1, ll_clean($x['tagline'] ?? '', 160)];
            if ($id) { $pdo->prepare('UPDATE categories SET name=?, image=?, sort=?, active=?, tagline=? WHERE id=?')->execute([...$vals, $id]); $keep[] = (int)$id; }
            else { $pdo->prepare('INSERT INTO categories(name,image,sort,active,tagline,slug) VALUES(?,?,?,?,?,?)')->execute([...$vals, $slug]); $keep[] = (int)$pdo->lastInsertId(); }
        }
        if ($keep) {   // a category can be removed only when it has no products
            $gone = $pdo->query('SELECT id FROM categories WHERE id NOT IN (' . implode(',', $keep) . ')')->fetchAll(PDO::FETCH_COLUMN);
            foreach ($gone as $g) { $n = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id=?'); $n->execute([$g]); if (!(int)$n->fetchColumn()) $pdo->prepare('DELETE FROM categories WHERE id=?')->execute([$g]); }
        }
        $pdo->commit(); return '';
    }
    if ($c === 'collections') {
        $keep = []; $pdo->beginTransaction();
        foreach ($d as $i => $x) {
            $name = ll_clean($x['name'] ?? '', 80); if ($name === '') continue;
            $slug = slugify((string)($x['slug'] ?? '')) ?: slugify($name);
            $q = $pdo->prepare('SELECT id FROM collections WHERE slug=?'); $q->execute([$slug]); $id = (int)$q->fetchColumn();
            $img = (string)($x['image'] ?? '');
            if ($id) $pdo->prepare('UPDATE collections SET name=?, active=?, sort=?, tagline=?, image=? WHERE id=?')->execute([$name, empty($x['on']) ? 0 : 1, $i, ll_clean($x['tagline'] ?? '', 160), $img, $id]);
            else { $pdo->prepare('INSERT INTO collections(name,slug,active,sort,tagline,image) VALUES(?,?,?,?,?,?)')->execute([$name, $slug, empty($x['on']) ? 0 : 1, $i, ll_clean($x['tagline'] ?? '', 160), $img]); $id = (int)$pdo->lastInsertId(); }
            $keep[] = $id;
            $pdo->prepare('DELETE FROM col_items WHERE collection_id=?')->execute([$id]);
            $ins = $pdo->prepare('INSERT OR IGNORE INTO col_items(collection_id, product_id) VALUES(?,?)');
            foreach ((array)($x['ids'] ?? []) as $pid) {
                $pid = (string)$pid;
                if (!ctype_digit($pid)) { $q = $pdo->prepare('SELECT id FROM products WHERE ext_id=?'); $q->execute([$pid]); $pid = (string)$q->fetchColumn(); }
                if ($pid !== '' && $pid !== '0') $ins->execute([$id, (int)$pid]);
            }
        }
        $gone = $pdo->query('SELECT id FROM collections' . ($keep ? ' WHERE id NOT IN (' . implode(',', $keep) . ')' : ''))->fetchAll(PDO::FETCH_COLUMN);
        foreach ($gone as $g) { $pdo->prepare('DELETE FROM collections WHERE id=?')->execute([$g]); $pdo->prepare('DELETE FROM col_items WHERE collection_id=?')->execute([$g]); $pdo->prepare('UPDATE products SET collection_id=0 WHERE collection_id=?')->execute([$g]); }
        $pdo->commit(); return '';
    }
    if ($c === 'reviews') {
        $keep = []; $pdo->beginTransaction();
        foreach ($d as $x) {
            $name = ll_clean($x['name'] ?? '', 80); $body = ll_clean($x['text'] ?? '', 1000); if ($name === '' || $body === '') continue;
            $vals = [$name, $body, max(1, min(5, (int)($x['stars'] ?? 5))), empty($x['on']) ? 0 : 1];
            $id = (int)($x['id'] ?? 0);
            if ($id) { $pdo->prepare('UPDATE reviews SET name=?, body=?, rating=?, approved=? WHERE id=?')->execute([...$vals, $id]); $keep[] = $id; }
            else {
                $pid = 0; $pn = ll_clean($x['pet'] ?? '', 160);
                if ($pn !== '') { $q = $pdo->prepare('SELECT id FROM products WHERE name=? COLLATE NOCASE'); $q->execute([$pn]); $pid = (int)$q->fetchColumn(); }
                $pdo->prepare('INSERT INTO reviews(name,body,rating,approved,product_id,created) VALUES(?,?,?,?,?,?)')->execute([...$vals, $pid, $now]); $keep[] = (int)$pdo->lastInsertId();
            }
        }
        $pdo->exec('DELETE FROM reviews' . ($keep ? ' WHERE id NOT IN (' . implode(',', $keep) . ')' : ''));
        $pdo->commit(); return '';
    }
    if ($c === 'banners') {
        $out = [];
        foreach ($d as $b) {
            $img = (string)($b['img'] ?? ''); if (!ll_is_local($img) && !preg_match('~^https://[^\s"<>]+$~', $img)) continue;
            $out[] = ['img' => $img, 'title' => ll_clean($b['title'] ?? '', 100), 'link' => ll_clean($b['link'] ?? '', 300), 'on' => empty($b['on']) ? 0 : 1, 'kicker' => ll_clean($b['kicker'] ?? '', 40), 'text' => ll_clean($b['text'] ?? '', 200), 'btn' => ll_clean($b['btn'] ?? '', 30), 'dark' => empty($b['dark']) ? 0 : 1];
        }
        set_setting('banners', json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); return '';
    }
    if ($c === 'site') {
        $st = (array)($d['store'] ?? []); $dl = (array)($d['delivery'] ?? []); $py = (array)($d['payment'] ?? []); $cm = (array)($d['cms'] ?? []); $seo = (array)($d['seo'] ?? []); $px = (array)($d['pixel'] ?? []);
        $set = [];
        foreach (['name' => 'shop_name', 'tagline' => 'shop_sub', 'phone' => 'phone', 'whatsapp' => 'whatsapp', 'email' => 'email', 'address' => 'address', 'facebook' => 'facebook', 'hours' => 'hours', 'map' => 'map'] as $a => $b) if (array_key_exists($a, $st)) $set[$b] = ll_clean($st[$a], 300);
        if (isset($set['shop_name']) && $set['shop_name'] === '') unset($set['shop_name']);
        foreach (['inside' => 'delivery_in', 'outside' => 'delivery_out', 'free_over' => 'free_over'] as $a => $b) if (array_key_exists($a, $dl)) $set[$b] = (string)max(0, (int)$dl[$a]);
        if (array_key_exists('note', $dl)) $set['delivery_note'] = ll_clean($dl['note'], 300);
        if (array_key_exists('cod', $py)) $set['cod_on'] = empty($py['cod']) ? '0' : '1';
        foreach (['bkash', 'nagad'] as $m) if (array_key_exists($m, $py)) { $n = ll_clean($py[$m], 20); $set["{$m}_number"] = $n; $set["{$m}_on"] = $n === '' ? '0' : '1'; }
        if (array_key_exists('note', $py)) $set['pay_note'] = ll_clean($py['note'], 300);
        if (array_key_exists('ticker', $cm)) $set['announce'] = implode('  •  ', array_map(fn($t) => ll_clean($t, 120), array_filter((array)$cm['ticker'], 'strlen')));
        if (array_key_exists('about', $cm) && trim((string)$cm['about']) !== '') $set['about'] = ll_clean($cm['about'], 3000);
        if (array_key_exists('announce', $cm)) $set['announce_bar'] = ll_clean($cm['announce'], 200);
        if (isset($cm['deal'])) { $set['deal_id'] = preg_replace('/[^a-z0-9\-]/i', '', (string)($cm['deal']['id'] ?? '')); $set['deal_ends'] = ll_clean($cm['deal']['ends'] ?? '', 20); }
        foreach ((array)($cm['sections'] ?? []) as $x) { $k = (string)($x['k'] ?? ''); if (!isset(LL_SECTIONS[$k])) continue; $set["h_{$k}_on"] = empty($x['on']) ? '0' : '1'; if (($x['t'] ?? '') !== '') $set["h_{$k}_t"] = ll_clean($x['t'], 80); if (isset($x['s'])) $set["h_{$k}_s"] = ll_clean($x['s'], 200); }
        foreach (['title' => 'meta_title', 'desc' => 'meta_desc', 'keys' => 'meta_keywords', 'ga' => 'ga_id', 'gsv' => 'gsv'] as $a => $b) if (array_key_exists($a, $seo)) $set[$b] = ll_clean($seo[$a], 300);
        if (array_key_exists('id', $px)) $set['pixel_id'] = preg_replace('/\D/', '', (string)$px['id']);
        foreach ($set as $k => $v) set_setting($k, (string)$v);
        return '';
    }
    return 'Unknown data';
}
