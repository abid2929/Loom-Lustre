<?php
// Admin form saves. Runs only on POST with a valid CSRF token. Every branch ends with go() or json_out().
$now = date('Y-m-d H:i:s');
$pdo = db();
$need = function (string $view) { if (!can($view)) go('', 'Your role cannot do that.', 'err'); };

switch ($act) {
case 'product':
    $need('product');
    $id = (int)P('id');
    $name = P('name'); if ($name === '') go('v=product&id=' . $id, 'Please write the product name.', 'err');
    $regular = max(0, (int)P('regular')); $sale = max(0, (int)P('sale'));
    if ($sale > 0 && $sale < $regular) { $price = $sale; $old = $regular; } else { $price = $regular ?: $sale; $old = 0; }
    // gallery: kept images in their new order, then new rows (file or web link), each with an optional shade tag
    $imgs = []; $tags = [];
    foreach ((array)($_POST['keep'] ?? []) as $i => $k) {
        if (!is_string($k) || $k === '' || str_contains($k, '..')) continue;
        $imgs[] = $k; $t = trim((string)($_POST['keep_tag'][$i] ?? '')); if ($t !== '') $tags[$k] = $t;
    }
    $files = uploads_from('gfile');
    foreach ((array)($_POST['gurl'] ?? []) as $i => $u) {
        $p = $files[$i] ?? '';
        if ($p === '') { $u = trim((string)$u); if (preg_match('~^https?://\S+$~i', $u)) $p = $u; }
        if ($p === '') continue;
        $imgs[] = $p; $t = trim((string)($_POST['gtag'][$i] ?? '')); if ($t !== '') $tags[$p] = $t;
    }
    foreach ($files as $i => $p) if (!in_array($p, $imgs, true)) $imgs[] = $p; // files with no matching url row
    $f = [
        'name' => $name, 'brand' => P('brand'), 'category_id' => (int)P('category_id') ?: null, 'collection_id' => (int)P('collection_id'),
        'price' => $price, 'old_price' => $old, 'stock' => max(0, (int)P('stock', '0')), 'size' => P('size'), 'sizes' => P('sizes'), 'shades' => P('shades'),
        'images' => json_encode(array_values(array_unique($imgs))), 'image_tags' => json_encode($tags, JSON_UNESCAPED_UNICODE),
        'short' => P('short'), 'description' => P('description'), 'how_to' => P('how_to'), 'tags' => P('tags'),
        'featured' => isset($_POST['featured']) ? 1 : 0, 'flash_sale' => isset($_POST['flash_sale']) ? 1 : 0, 'is_new' => isset($_POST['is_new']) ? 1 : 0,
        'bestseller' => isset($_POST['bestseller']) ? 1 : 0, 'active' => isset($_POST['active']) ? 1 : 0, 'updated' => $now,
    ];
    if ($id) {
        $pdo->prepare('UPDATE products SET ' . implode(',', array_map(fn($k) => "$k=?", array_keys($f))) . ' WHERE id=?')->execute([...array_values($f), $id]);
    } else {
        $f['slug'] = unique_slug('products', $name); $f['created'] = $now;
        $f['sort'] = (int)$pdo->query('SELECT COALESCE(MIN(sort),0)-1 FROM products')->fetchColumn();
        $pdo->prepare('INSERT INTO products(' . implode(',', array_keys($f)) . ') VALUES(' . rtrim(str_repeat('?,', count($f)), ',') . ')')->execute(array_values($f));
        $id = (int)$pdo->lastInsertId();
    }
    go('v=product&id=' . $id, 'Product saved.');

case 'product_del':
    $need('product');
    $pdo->prepare('DELETE FROM products WHERE id=?')->execute([(int)P('id')]);
    go('v=products', 'Product deleted.');

case 'product_copy':
    $need('product');
    $st = $pdo->prepare('SELECT * FROM products WHERE id=?'); $st->execute([(int)P('id')]); $p = $st->fetch();
    if (!$p) go('v=products');
    unset($p['id']); $p['name'] .= ' (copy)'; $p['slug'] = unique_slug('products', $p['name']); $p['active'] = 0; $p['sold'] = 0; $p['views'] = 0; $p['created'] = $p['updated'] = $now;
    $pdo->prepare('INSERT INTO products(' . implode(',', array_keys($p)) . ') VALUES(' . rtrim(str_repeat('?,', count($p)), ',') . ')')->execute(array_values($p));
    go('v=product&id=' . $pdo->lastInsertId(), 'Copy made. It is hidden until you turn it on.');

case 'stock':
    $need('inventory');
    $u = $pdo->prepare('UPDATE products SET stock=?, price=CASE WHEN ?>0 THEN ? ELSE price END, updated=? WHERE id=?');
    foreach ((array)($_POST['stock'] ?? []) as $pid => $s) $u->execute([max(0, (int)$s), (int)($_POST['price'][$pid] ?? 0), (int)($_POST['price'][$pid] ?? 0), $now, (int)$pid]);
    go('v=inventory' . (P('f') ? '&f=' . P('f') : ''), 'Stock updated.');

case 'cat': case 'collection':
    $need($act === 'cat' ? 'categories' : 'collections');
    $t = $act === 'cat' ? 'categories' : 'collections'; $back = $act === 'cat' ? 'categories' : 'collections';
    $id = (int)P('id'); $name = P('name'); if ($name === '') go('v=' . $back, 'Please write a name.', 'err');
    $up = uploads_from('image'); $img = $up[0] ?? (P('image_url') ?: P('image_old'));
    if ($id) $pdo->prepare("UPDATE $t SET name=?, tagline=?, image=?, sort=?, active=? WHERE id=?")->execute([$name, P('tagline'), $img, (int)P('sort'), isset($_POST['active']) ? 1 : 0, $id]);
    else $pdo->prepare("INSERT INTO $t(name,slug,tagline,image,sort,active) VALUES(?,?,?,?,?,1)")->execute([$name, unique_slug($t, $name), P('tagline'), $img, (int)P('sort')]);
    go('v=' . $back, 'Saved.');

case 'cat_del': case 'collection_del':
    $isCat = $act === 'cat_del'; $need($isCat ? 'categories' : 'collections');
    $id = (int)P('id');
    if ($isCat) {
        $n = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id=?'); $n->execute([$id]);
        if ((int)$n->fetchColumn() > 0) go('v=categories', 'This category still has products. Move them to another category first.', 'err');
        $pdo->prepare('DELETE FROM categories WHERE id=?')->execute([$id]);
        go('v=categories', 'Category deleted.');
    }
    $pdo->prepare('UPDATE products SET collection_id=0 WHERE collection_id=?')->execute([$id]);
    $pdo->prepare('DELETE FROM collections WHERE id=?')->execute([$id]);
    go('v=collections', 'Collection deleted. Its products stay in the shop.');

case 'order':
    $need('order');
    $id = (int)P('id'); $st = P('status'); if (!isset(ORDER_STATUS[$st])) $st = 'new';
    $o = $pdo->prepare('SELECT * FROM orders WHERE id=?'); $o->execute([$id]); $old = $o->fetch();
    if (!$old) go('v=orders');
    $out = ['cancelled', 'returned'];
    if (in_array($st, $out, true) !== in_array($old['status'], $out, true)) { // stock goes back on cancel/return, out again if re-opened
        $sign = in_array($st, $out, true) ? 1 : -1;
        $u = $pdo->prepare('UPDATE products SET stock=MAX(0,stock+?), sold=MAX(0,sold-?) WHERE id=?');
        foreach (json_decode($old['items'], true) ?: [] as $it) $u->execute([$sign * (int)$it['qty'], $sign * (int)$it['qty'], (int)$it['id']]);
    }
    $pdo->prepare('UPDATE orders SET status=?, admin_note=?, tracking=?, seen=1, updated=? WHERE id=?')->execute([$st, P('admin_note'), P('tracking', $old['tracking']), $now, $id]);
    $msg = 'Order updated.';
    if ($st === 'shipped' && $old['status'] !== 'shipped' && send_sms($old['phone'], fill_tpl(S('sms_shipped'), $old + ['tracking' => P('tracking')]))) $msg .= ' SMS sent to the customer.';
    go(P('back') === 'list' ? 'v=orders' : 'v=order&id=' . $id, $msg);

case 'orders_bulk':
    $need('orders');
    $st = P('status'); $ids = array_map('intval', (array)($_POST['ids'] ?? []));
    if (!isset(ORDER_STATUS[$st]) || !$ids) go('v=orders', 'Pick orders and a status first.', 'err');
    $pdo->prepare('UPDATE orders SET status=?, seen=1, updated=? WHERE id IN (' . implode(',', $ids) . ')')->execute([$st, $now]);
    go('v=orders', count($ids) . ' orders marked ' . ORDER_STATUS_A[$st] . '.');

case 'order_del':
    $need('order');
    if (($GLOBALS['ME']['role'] ?? '') !== 'super') go('v=orders', 'Only a Super Admin can delete orders.', 'err');
    $pdo->prepare('DELETE FROM orders WHERE id=?')->execute([(int)P('id')]);
    go('v=orders', 'Order deleted.');

case 'courier_send':
    $need('courier');
    $id = (int)P('id'); $o = $pdo->prepare('SELECT * FROM orders WHERE id=?'); $o->execute([$id]); $o = $o->fetch();
    if (!$o) go('v=courier');
    if (S('courier') !== 'steadfast') go('v=settings&tab=courier', 'Choose Steadfast and add its API keys first.', 'err');
    [$ok, $m, $trk] = steadfast_create($o);
    if ($ok) {
        $pdo->prepare("UPDATE orders SET courier='steadfast', tracking=?, status='shipped', updated=? WHERE id=?")->execute([$trk, $now, $id]);
        send_sms($o['phone'], fill_tpl(S('sms_shipped'), $o + ['tracking' => $trk]));
    }
    go(P('back') === 'order' ? 'v=order&id=' . $id : 'v=courier', $m . ($trk ? ' Tracking: ' . $trk : ''), $ok ? 'ok' : 'err');

case 'review':
    $need('reviews');
    $id = (int)P('id'); $do = P('do');
    if ($do === 'delete') $pdo->prepare('DELETE FROM reviews WHERE id=?')->execute([$id]);
    else $pdo->prepare('UPDATE reviews SET approved=? WHERE id=?')->execute([$do === 'approve' ? 1 : 0, $id]);
    go('v=reviews' . (P('f') ? '&f=' . P('f') : ''), $do === 'delete' ? 'Review deleted.' : ($do === 'approve' ? 'Review is now on the website.' : 'Review hidden.'));

case 'banners':
    $need('banners');
    for ($i = 1; $i <= 4; $i++) {
        foreach (['kicker', 'title', 'text', 'btn', 'link'] as $k) set_setting("s{$i}_$k", P("s{$i}_$k"));
        set_setting("s{$i}_on", isset($_POST["s{$i}_on"]) ? '1' : '0');
        set_setting("s{$i}_dark", isset($_POST["s{$i}_dark"]) ? '1' : '0');
        $up = uploads_from("s{$i}_file");
        if ($up) set_setting("s{$i}_img", reset($up));
        elseif (preg_match('~^https?://\S+$~i', P("s{$i}_url"))) set_setting("s{$i}_img", P("s{$i}_url"));
    }
    go('v=banners', 'Banners saved.');

case 'homepage':
    $need('homepage');
    foreach (array_keys(defaults()) as $k) {
        if (!str_starts_with($k, 'h_')) continue;
        if (str_ends_with($k, '_on')) set_setting($k, isset($_POST[$k]) ? '1' : '0');
        else set_setting($k, P($k));
    }
    go('v=homepage', 'Homepage saved.');

case 'admin_user':
    $need('admins');
    $id = (int)P('id'); $u = strtolower(P('username')); $role = isset(ROLES[P('role')]) ? P('role') : 'staff'; $pw = (string)($_POST['pass'] ?? '');
    if (!preg_match('/^[a-z0-9_.]{3,30}$/', $u)) go('v=admins', 'Username: 3 to 30 letters or numbers, no spaces.', 'err');
    $dupe = $pdo->prepare('SELECT id FROM admins WHERE username=? AND id<>?'); $dupe->execute([$u, $id]);
    if ($dupe->fetch()) go('v=admins', 'That username is already taken.', 'err');
    if ($id === (int)$GLOBALS['ME']['id'] && $role !== 'super') go('v=admins', 'You cannot remove your own Super Admin role.', 'err');
    if ($id) {
        $pdo->prepare('UPDATE admins SET username=?, name=?, role=?, active=? WHERE id=?')->execute([$u, P('name'), $role, ($id === (int)$GLOBALS['ME']['id'] || isset($_POST['active'])) ? 1 : 0, $id]);
        if ($pw !== '') { if (strlen($pw) < 6) go('v=admins', 'Password must be at least 6 characters.', 'err'); $pdo->prepare('UPDATE admins SET pass=? WHERE id=?')->execute([password_hash($pw, PASSWORD_DEFAULT), $id]); }
    } else {
        if (strlen($pw) < 6) go('v=admins', 'Give the new admin a password of at least 6 characters.', 'err');
        $pdo->prepare('INSERT INTO admins(username,name,pass,role,created) VALUES(?,?,?,?,?)')->execute([$u, P('name'), password_hash($pw, PASSWORD_DEFAULT), $role, $now]);
    }
    go('v=admins', 'Admin saved.');

case 'admin_del':
    $need('admins');
    $id = (int)P('id');
    if ($id === (int)$GLOBALS['ME']['id']) go('v=admins', 'You cannot delete yourself.', 'err');
    $pdo->prepare('DELETE FROM admins WHERE id=?')->execute([$id]);
    go('v=admins', 'Admin removed.');

case 'settings':
    $tab = P('tab');
    if ($tab === 'security') { // any role may change their own password
        $me = $GLOBALS['ME'];
        if (!password_verify((string)($_POST['old'] ?? ''), $me['pass'])) go('v=settings&tab=security', 'Current password is wrong.', 'err');
        $p1 = (string)($_POST['p1'] ?? '');
        if (strlen($p1) < 6 || $p1 !== ($_POST['p2'] ?? '')) go('v=settings&tab=security', 'New password must be at least 6 characters and match.', 'err');
        $pdo->prepare('UPDATE admins SET pass=? WHERE id=?')->execute([password_hash($p1, PASSWORD_DEFAULT), $me['id']]);
        go('v=settings&tab=security', 'Password changed.');
    }
    $need('settings');
    $keys = [
        'store' => ['shop_name', 'shop_sub', 'announce', 'about', 'phone', 'whatsapp', 'email', 'address', 'facebook', 'instagram', 'tiktok', 'youtube', 'wa_popup', 'notify_email'],
        'delivery' => ['delivery_in_label', 'delivery_in', 'delivery_time_in', 'delivery_out_label', 'delivery_out', 'delivery_time_out', 'free_over'],
        'payment' => ['bkash_number', 'bkash_type', 'nagad_number', 'nagad_type', 'pay_note'],
        'courier' => ['courier', 'steadfast_key', 'steadfast_secret'],
        'sms' => ['sms_url', 'sms_api_key', 'sms_sender', 'sms_order', 'sms_shipped'],
        'pixel' => ['pixel_id', 'capi_test', 'ga_id'],
        'seo' => ['meta_title', 'meta_desc', 'meta_keywords', 'gemini_key'],
    ][$tab] ?? [];
    $toggles = ['payment' => ['cod_on', 'bkash_on', 'nagad_on'], 'sms' => ['sms_on']][$tab] ?? [];
    foreach ($keys as $k) {
        if (in_array($k, ['steadfast_secret', 'gemini_key', 'sms_api_key'], true) && P($k) === '') continue; // blank keeps the saved secret
        set_setting($k, P($k));
    }
    if ($tab === 'pixel' && P('capi_token') !== '') set_setting('capi_token', P('capi_token'));
    if ($tab === 'pixel' && isset($_POST['capi_clear'])) set_setting('capi_token', '');
    foreach ($toggles as $k) set_setting($k, isset($_POST[$k]) ? '1' : '0');
    if ($tab === 'payment' && !isset($_POST['cod_on']) && !isset($_POST['bkash_on']) && !isset($_POST['nagad_on'])) set_setting('cod_on', '1');
    if ($tab === 'sms' && P('test_phone') !== '') {
        $ok = send_sms(P('test_phone'), 'Test SMS from ' . S('shop_name') . ' admin panel.');
        go('v=settings&tab=sms', $ok ? 'Saved. Test SMS sent.' : 'Saved, but the test SMS failed. Check the API key and that SMS is turned on.', $ok ? 'ok' : 'err');
    }
    go('v=settings&tab=' . $tab, 'Settings saved.');

case 'ai_copy': // product text writer: Gemini when a key is set, else a built-in template
    $need('product');
    $name = P('name'); $cat = P('cat'); $shades = P('shades'); $size = P('size');
    if ($name === '') json_out(['ok' => false, 'error' => 'Write the product name first.']);
    $key = S('gemini_key');
    if ($key !== '') {
        $prompt = "Write e-commerce copy in simple, warm English for a cosmetics shop in Bangladesh called " . S('shop_name') . ".\nProduct: $name\nCategory: $cat\n" . ($shades ? "Shades: $shades\n" : '') . ($size ? "Size: $size\n" : '') .
            "Return JSON only: {\"short\": one line under 110 characters, \"description\": 2 short paragraphs, \"how_to\": 1 to 2 sentences, \"tags\": 8 comma separated search words}. Do not invent ingredients, certifications or medical claims.";
        [$code, $res] = http_call('POST', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . rawurlencode($key), ['Content-Type: application/json'], json_encode(['contents' => [['parts' => [['text' => $prompt]]]], 'generationConfig' => ['responseMimeType' => 'application/json']]), 25);
        $txt = json_decode($res, true)['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $j = json_decode((string)$txt, true);
        if ($code === 200 && is_array($j) && !empty($j['description'])) json_out(['ok' => true, 'src' => 'Gemini'] + $j);
    }
    $c = mb_strtolower($cat);
    $what = str_contains($c, 'lip') ? 'lips' : (str_contains($c, 'skin') ? 'skin' : (str_contains($c, 'nail') ? 'nails' : (str_contains($c, 'fragr') ? 'scent' : 'look')));
    $short = [
        'lips' => "Rich colour and soft comfort for your lips, all day long.", 'skin' => "Gentle daily care for fresh, healthy looking skin.",
        'nails' => "Glossy, even colour that lasts and dries fast.", 'scent' => "A long lasting fragrance you will love to wear every day.", 'look' => "Easy to use beauty for a polished everyday look.",
    ][$what];
    $desc = "$name is picked by " . S('shop_name') . " for its quality and value. " . [
        'lips' => 'It glides on smoothly, gives even colour in one or two swipes and keeps lips comfortable for hours.',
        'skin' => 'The light texture absorbs quickly with no sticky feel, which makes it great for the humid weather in Bangladesh.',
        'nails' => 'The smooth formula spreads evenly, dries quickly and gives a glossy salon finish at home.',
        'scent' => 'The fragrance opens fresh and settles into a soft, lasting scent that suits work days and evenings out.',
        'look' => 'It is easy to apply, blends well and suits beginners as well as makeup lovers.',
    ][$what] . ($shades ? " Choose from $shades." : '') . "\n\nEvery order is checked by hand before it leaves our shop. Pay cash on delivery anywhere in Bangladesh.";
    $how = ['lips' => 'Apply from the centre of the lips outwards. Add a second coat for deeper colour.', 'skin' => 'Use on clean skin, morning and night. Follow with sunscreen in the day.', 'nails' => 'Apply two thin coats and let each dry for one minute.', 'scent' => 'Spray on wrists and neck from a short distance.', 'look' => 'Apply a small amount and build up as you like.'][$what];
    $tags = implode(', ', array_unique(array_filter([mb_strtolower($name), $c, $what === 'look' ? 'makeup' : $what, 'cosmetics bangladesh', 'original', 'cash on delivery', mb_strtolower(S('shop_name'))])));
    json_out(['ok' => true, 'src' => 'built-in writer', 'short' => $short, 'description' => $desc, 'how_to' => $how, 'tags' => $tags]);
}
