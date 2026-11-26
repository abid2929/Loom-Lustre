<?php
// v1.4 store layout: departments (Women / Men / Kids / Beauty), Categories page, Luxe store.
const LL_DEPTS = [
    'women' => ['Women', 'Shop Westernwear, Ethnic wear and More', ['westernwear', 'ethnic', 'footwear', 'bags', 'jewellery', 'watches', 'activewear', 'sneakers', 'eyewear'], 'assets/img/p/wst-shirt-dress.webp', '#e2e0dd'],
    'men' => ['Men', 'Shop Formals, Casuals and Sneakers', ['men', 'footwear', 'sneakers', 'watches', 'eyewear', 'bags'], 'assets/img/p/men-white-shirt.webp', '#f3dcc4'],
    'kids' => ['Kids', 'Shop for Boys, Girls and Babies', ['kids'], 'assets/img/p/kd-gent-set.webp', '#d8cbc1'],
    'home' => ['Home', 'Shop Bedding, Cushions, Lamps and Decor', ['home'], 'assets/img/p/hm-blush-bedding.webp', '#e6dfd3'],
    'beauty' => ['Beauty', 'Lipstick, Skincare, Makeup and More', ['lips', 'skincare', 'makeup', 'fragrance', 'nails', 'body'], 'assets/img/p/rose-trio.webp', '#eedfe2'],
];


/** Home page photo-tile rows (label, link, product slug for the photo), like the Nykaa Fashion store. */
const LL_CELEB = [
    ['Drops & Danglers', 'c/jewellery', 'sapphire-drop-earrings'], ['Evening Gowns', 'c/westernwear', 'plum-satin-evening-gown'],
    ['Iconic Sneakers', 'c/sneakers', 'colour-pop-chunky-sneakers'], ['Event-ready Heels', 'c/footwear', 'blue-garden-floral-heels'],
    ['Silk Sarees', 'c/ethnic', 'rose-gold-banarasi-silk-saree'], ['Stylish Handbags', 'c/bags', 'cherry-top-handle-bag'],
    ['Gold Watches', 'c/watches', 'navy-dial-gold-watch'], ['Anarkali Kurtis', 'c/ethnic', 'indigo-paisley-anarkali-kurti'],
    ['Sharp Suits', 'c/men', 'navy-slim-fit-suit'], ['Cosy Bedding', 'c/home', 'blush-pillow-bedding-set'],
];
const LL_EID = [
    ['Festive Salwar Sets', 'c/ethnic', 'red-salwar-kameez-with-dupatta'], ['Golden Classic Watches', 'c/watches', 'rose-gold-link-bracelet-watch'],
    ['Statement Jewellery', 'c/jewellery', 'kundan-gold-bridal-necklace-set'], ['Handwoven Sarees', 'c/ethnic', 'royal-purple-katan-saree'],
    ['Party Heels', 'c/footwear', 'hot-pink-patent-stilettos'], ['Crafty Handbags', 'c/bags', 'tan-wicker-handbag'],
    ['Marigold Kurtis', 'c/ethnic', 'marigold-embroidered-kurti'], ['Kids Festive Sets', 'c/kids', 'little-gent-cardigan-set'],
    ['Festive Home Decor', 'c/home', 'velvet-round-cushions-set-of-3'], ['Eid Glam Lips', 'c/lips', 'velvet-red-matte-lipstick'],
];
/** Sub-types under each category for the PC mega menu: label => search word (searched inside that category). */
const LL_SUBS = [
    'ethnic' => ['Sarees' => 'saree', 'Kurtis & Kurtas' => 'kurti', 'Salwar Suits' => 'salwar', 'Anarkalis' => 'anarkali'],
    'westernwear' => ['Dresses' => 'dress', 'Tops & Blouses' => 'blouse', 'Co-ord Sets' => 'co-ord', 'Gowns' => 'gown', 'Sweaters' => 'sweater'],
    'men' => ['Shirts' => 'shirt', 'T-shirts' => 'tee', 'Suits' => 'suit', 'Jackets' => 'jacket'],
    'footwear' => ['Heels' => 'heels', 'Pumps' => 'pumps', 'Stilettos' => 'stiletto', 'Brogues' => 'brogue', 'Boots' => 'boots', 'Loafers' => 'loafer'],
    'bags' => ['Handbags' => 'handbag', 'Totes' => 'tote', 'Slings' => 'sling', 'Satchels' => 'satchel', 'Duffle Bags' => 'duffle'],
    'jewellery' => ['Necklace Sets' => 'necklace', 'Earrings' => 'earrings', 'Bangles' => 'bangle', 'Pendants' => 'pendant', 'Hoops' => 'hoops'],
    'watches' => ['Leather Strap' => 'leather', 'Bracelet Watches' => 'bracelet', 'Rose Gold' => 'rose gold', 'Gold Watches' => 'gold'],
    'eyewear' => ['Round' => 'round', 'Browline' => 'browline', 'Oversized' => 'oversized'],
    'sneakers' => ['Chunky' => 'chunky', 'Canvas' => 'canvas', 'White Sneakers' => 'white'],
    'activewear' => ['Leggings' => 'leggings', 'Workout Sets' => 'set'],
    'kids' => ['Dresses' => 'dress', 'Sets' => 'set', 'Baby' => 'baby'],
    'home' => ['Bedding' => 'bedding', 'Comforters' => 'comforter', 'Cushions' => 'cushion', 'Lamps' => 'lamp', 'Vases' => 'vase', 'Mirrors' => 'mirror', 'Rugs' => 'rug'],
    'lips' => ['Lipsticks' => 'lipstick', 'Lip Tints' => 'tint', 'Lip Gloss' => 'gloss', 'Lip Balm' => 'balm'],
    'skincare' => ['Serums' => 'serum', 'Creams' => 'cream', 'Face Oils' => 'oil'],
    'makeup' => ['Eyeshadow' => 'eyeshadow', 'Foundation' => 'foundation', 'Blush' => 'blush', 'Mascara' => 'mascara', 'Powder' => 'powder', 'Brushes' => 'brush', 'Kits' => 'kit'],
    'fragrance' => ['Perfumes' => 'parfum'],
    'nails' => ['Nail Polish' => 'nail'],
    'body' => ['Hair Oils' => 'hair', 'Body Butter' => 'butter', 'Lotions' => 'lotion', 'Face Masks' => 'mask'],
];
/** Men-only items inside shared categories (shoes, watches...) and unisex items also shown in Men. */
const LL_MEN_ONLY = ['walnut-leather-brogues', 'tan-brogue-boots', 'black-penny-loafers', 'classic-tan-leather-watch'];
const LL_UNISEX = ['everyday-white-sneakers', 'red-canvas-sneakers', 'colour-pop-chunky-sneakers', 'black-mini-duffle-bag', 'grey-buckle-satchel', 'navy-dial-gold-watch', 'gold-round-sunglasses', 'black-browline-sunglasses', 'clear-frame-sunglasses', 'amber-tint-sunglasses'];
const LL_SHARED = ['footwear', 'sneakers', 'bags', 'watches', 'eyewear'];
/** Products of one category as seen inside a department (Men sees men's shoes, Women does not). */
function nk_dept_products(string $dk, string $slug, int $n): array {
    $w = 'c.slug=?'; $a = [$slug];
    if (in_array($slug, LL_SHARED, true)) {
        if ($dk === 'men') { $l = array_merge(LL_MEN_ONLY, LL_UNISEX); $w .= ' AND p.slug IN (' . implode(',', array_fill(0, count($l), '?')) . ')'; $a = array_merge($a, $l); }
        elseif ($dk === 'women') { $w .= ' AND p.slug NOT IN (' . implode(',', array_fill(0, count(LL_MEN_ONLY), '?')) . ')'; $a = array_merge($a, LL_MEN_ONLY); }
    }
    $o = 'p.featured DESC, p.sold DESC';
    if ($dk === 'men' && in_array($slug, LL_SHARED, true)) $o = "(p.slug IN ('" . implode("','", LL_MEN_ONLY) . "')) DESC, " . $o;
    return q($w, $a, $o, $n);
}

/** Discover posters (Categories page + PC "More" menu): [title, link, image]. */
function nk_discover(): array {
    $disc = [];
    if ($lx = nk_coll('luxe-edit')) $disc[] = ['Luxe Edit', 'luxe', (string)$lx['image']];
    $ni = q("p.is_new=1 AND c.slug IN ('westernwear','ethnic','jewellery','bags')", [], 'p.featured DESC, p.id DESC', 1); if ($ni) $disc[] = ['Hidden Gems', 'new', p_image($ni[0])];
    $fz = nk_cat_imgs('activewear', 1); if ($fz) $disc[] = ['Fit Zone', 'c/activewear', $fz[0]];
    foreach (['eid-edit', 'wedding-season'] as $cs) if ($c = nk_coll($cs)) $disc[] = [$c['name'], 'collection/' . $cs, (string)$c['image']];
    return $disc;
}

/** Department the visitor is in (for the second menu row). */
function nk_cur_dept(string $active): string {
    if (preg_match('~^d/([a-z]+)~', $active, $m) && isset(nk_depts()[$m[1]])) return $m[1];
    if (preg_match('~^c/([a-z0-9-]+)~', $active, $m)) {
        foreach (['women', 'men', 'kids', 'home', 'beauty'] as $k) if (isset(nk_depts()[$k]) && in_array($m[1], LL_DEPTS[$k][2], true)) return $k;
    }
    return isset(nk_depts()['women']) ? 'women' : (string)array_key_first(nk_depts());
}
/** PC second menu row: What's New, Sale, then each category of the department with a hover dropdown. */
function nk_subnav(string $dk): string {
    $dc = nk_dept_cats($dk); if (!$dc) return '';
    $slugs = array_column($dc, 'slug'); $in = implode(',', array_fill(0, count($slugs), '?'));
    $sq = fn(string $c, string $w) => url('search') . '?q=' . rawurlencode($w) . '&c=' . rawurlencode($c);
    $h = '<nav class="nk-sub2 only-d" aria-label="' . e(LL_DEPTS[$dk][0]) . ' categories"><div class="wrap nk-sub2-in">';
    $nw = q("c.slug IN ($in) AND p.is_new=1", $slugs, 'p.featured DESC, p.id DESC', 6);
    if ($nw) {
        $h .= '<div class="nk-dd"><a href="' . url('new') . '">What\'s New ' . icon('chev', 12) . '</a><div class="nk-mm nk-mm-new"><div><h5>Just In</h5><div class="nk-ji">';
        foreach (array_slice($nw, 0, 2) as $p) $h .= '<a href="' . url('p/' . $p['slug']) . '">' . nk_img(p_image($p), $p['name'], 120, 150) . '</a>';
        $h .= '</div></div><div><h5>New Trends</h5><div class="nk-nt">';
        foreach ((array_slice($nw, 2, 4) ?: array_slice($nw, 0, 4)) as $p) $h .= '<a href="' . url('p/' . $p['slug']) . '">' . nk_img(p_image($p), '', 120, 150) . '<span>' . e($p['name']) . '</span></a>';
        $h .= '</div></div></div></div>';
    }
    $h .= '<div class="nk-dd"><a href="' . url('sale') . '">Sale ' . icon('chev', 12) . '</a><div class="nk-mm nk-mm-l"><a class="hl" href="' . url('sale') . '">Shop All</a>';
    foreach ($dc as $c) if ((nk_offs()[$c['slug']]['off'] ?? 0) > 0) $h .= '<a href="' . url('c/' . $c['slug']) . '?sort=off">' . e($c['name']) . ' on Sale</a>';
    $h .= '</div></div>';
    foreach ($dc as $c) {
        $h .= '<div class="nk-dd"><a href="' . url('c/' . $c['slug']) . '">' . e($c['name']) . ' ' . icon('chev', 12) . '</a><div class="nk-mm nk-mm-l"><a class="hl" href="' . url('c/' . $c['slug']) . '">Shop All</a>';
        foreach (LL_SUBS[$c['slug']] ?? [] as $lb => $w) $h .= '<a href="' . e($sq($c['slug'], $w)) . '">' . e($lb) . '</a>';
        $h .= '<a href="' . url('c/' . $c['slug']) . '?sort=new">New Arrivals</a><a href="' . url('c/' . $c['slug']) . '?sort=popular">Best Sellers</a></div></div>';
    }
    return $h . '</div></nav>';
}

/** Resolve a tile list to [label, link, image, offer] for active products only. */
function nk_tiles(array $list): array {
    $st = db()->prepare('SELECT p.images, c.slug cs FROM products p JOIN categories c ON c.id=p.category_id WHERE p.slug=? AND p.active=1 AND c.active=1');
    $out = [];
    foreach ($list as [$label, $link, $slug]) {
        $st->execute([$slug]); $r = $st->fetch(); if (!$r) continue;
        $im = p_images($r)[0] ?? ''; if ($im === '') continue;
        $out[] = [$label, $link, $im, nk_offs()[$r['cs']]['off'] ?? 0];
    }
    return $out;
}

/** Biggest real discount per category slug. */
function nk_offs(): array {
    static $o = null;
    if ($o === null) {
        $o = [];
        foreach (db()->query('SELECT c.slug, MAX(CASE WHEN p.old_price > p.price THEN (p.old_price - p.price) * 100 / p.old_price ELSE 0 END) off, COUNT(p.id) n FROM categories c JOIN products p ON p.category_id=c.id AND p.active=1 WHERE c.active=1 GROUP BY c.id') as $r) $o[$r['slug']] = ['off' => (int)$r['off'], 'n' => (int)$r['n']];
    }
    return $o;
}
function nk_off_text(int $o, string $none = 'New styles inside'): string { return $o >= 5 ? 'Up to ' . $o . '% off' : $none; }
/** Active categories of a department, in shop order. */
function nk_dept_cats(string $d): array {
    $want = LL_DEPTS[$d][2] ?? []; $out = [];
    foreach (db()->query("SELECT * FROM categories WHERE active=1 AND slug NOT LIKE 'lx-%' ORDER BY sort, id") as $c) if (in_array($c['slug'], $want, true)) $out[] = $c;
    return $out;
}
function nk_dept_off(string $d): int { $m = 0; foreach (LL_DEPTS[$d][2] as $s) $m = max($m, nk_offs()[$s]['off'] ?? 0); return $m; }
function nk_depts(): array { return array_filter(LL_DEPTS, fn($k) => nk_dept_cats($k) !== [], ARRAY_FILTER_USE_KEY); }
/** First product photos of a category (featured first). */
function nk_cat_imgs(string $slug, int $n = 2): array {
    $st = db()->prepare("SELECT p.images FROM products p JOIN categories c ON c.id=p.category_id WHERE p.active=1 AND c.slug=? ORDER BY p.featured DESC, p.sold DESC, p.id LIMIT $n");
    $st->execute([$slug]); return array_values(array_filter(array_map(fn($r) => p_images($r)[0] ?? '', $st->fetchAll())));
}
function nk_coll(string $slug): ?array { $st = db()->prepare('SELECT * FROM collections WHERE slug=? AND active=1'); $st->execute([$slug]); return $st->fetch() ?: null; }
function nk_coll_products(int $id, int $n = 8, string $order = 'p.featured DESC, p.sort'): array {
    return q('p.id IN (SELECT product_id FROM col_items WHERE collection_id=?)', [$id], $order, $n);
}
function nk_img(string $src, string $alt = '', int $w = 600, int $h = 750, bool $big = false, string $extra = ''): string {
    return '<img src="' . e($big ? img_url($src) : img_small($src)) . '" alt="' . e($alt) . '" width="' . $w . '" height="' . $h . '" loading="lazy" decoding="async"' . $extra . '>';
}

/** Store switch shown in the header: Fashion | Luxe. */
function nk_switch(bool $luxe): string {
    return '<div class="nk-sw" role="group" aria-label="Store"><a href="' . url() . '" class="' . ($luxe ? '' : 'on') . '">' . icon('spark', 17) . '<span>Fashion</span></a><a href="' . url('luxe') . '" class="' . ($luxe ? 'on' : '') . '">' . icon('gem', 17) . '<span>Luxe</span></a></div>';
}

/** Long SEO text at the bottom of the home page. */
function nk_seo_text(): string {
    $n = e(S('shop_name'));
    $h = '<section class="wrap nk-seo"><h2>' . $n . ': Online Fashion &amp; Beauty Shopping in Bangladesh</h2>';
    $h .= '<p>' . $n . ' brings sarees, kurtis, westernwear, menswear, kidswear, shoes, bags, jewellery, watches, home decor and beauty together in one easy online store. Every product is checked by hand, prices are clear and you can pay cash on delivery anywhere in Bangladesh.</p>';
    foreach (nk_depts() as $k => $d) {
        $links = array_map(fn($c) => '<a href="' . url('c/' . $c['slug']) . '">' . e($c['name']) . '</a>', nk_dept_cats($k));
        $h .= '<h3>' . e($d[0]) . '</h3><p>' . e($d[1]) . '. Explore ' . implode(', ', $links) . ' with new styles added every week. <a href="' . url('d/' . $k) . '">Shop all ' . e(strtolower($d[0])) . '</a>.</p>';
    }
    $h .= '<h3>' . $n . ' Luxe</h3><p>Our <a href="' . url('luxe') . '">Luxe store</a> gathers silk sarees, bridal jewellery, evening gowns and fine leather in one place, for weddings, Eid and every moment that matters.</p>';
    $h .= '<h3>Why shop with us</h3><p>Original products, easy exchange if anything is wrong, fast delivery all over the country and real help on WhatsApp. <a href="' . url('track') . '">Track your order</a> any time with your order code and phone number.</p></section>';
    return $h;
}

/** Phone: tapping a department (Women, Men...) opens its category list in place, like the Nykaa app. */
function nk_dept_subs(): string {
    $h = '';
    foreach (nk_depts() as $k => $d) {
        $h .= '<div class="dsub" id="dsub-' . $k . '" hidden role="dialog" aria-label="' . e($d[0]) . '"><div class="dsub-h"><button type="button" class="dsub-back" data-sub-close aria-label="Back">' . icon('arrow', 22) . '</button><a href="' . url('cart') . '" aria-label="Bag">' . icon('bag', 21) . '</a></div>';
        $h .= '<h2><a href="' . url('d/' . $k) . '">' . e($d[0]) . '</a></h2><ul>' . nk_dept_items($k) . '</ul></div>';
    }
    return $h;
}

/** The list rows (All, What's New, Sale, categories) of one department; used by the phone panel and the PC hover menu. */
function nk_dept_items(string $k): string {
    static $cache = [];
    if (isset($cache[$k])) return $cache[$k];
    $d = nk_depts()[$k]; $h = '';
        $h .= '<li><a href="' . url('new') . '"><span>What\'s New</span><i class="dsub-txt">WHAT\'S<br>NEW</i></a></li>';
        $h .= '<li><a href="' . url('sale') . '"><span>Sale</span><i class="dsub-txt dsub-sale">SALE</i></a></li>';
        $cs = nk_dept_cats($k); $ord = ['ethnic', 'westernwear', 'men', 'kids', 'home', 'footwear', 'bags', 'jewellery', 'activewear', 'watches', 'sneakers', 'eyewear'];
        usort($cs, fn($x, $y) => (array_search($x['slug'], $ord) === false ? 99 : array_search($x['slug'], $ord)) <=> (array_search($y['slug'], $ord) === false ? 99 : array_search($y['slug'], $ord)));
        $lbl = ['ethnic' => 'Indian Wear', 'westernwear' => 'Western Wear', 'activewear' => 'Active & Sports', 'men' => 'Clothing', 'eyewear' => 'Sunglasses'];
        foreach ($cs as $c) {
            if (in_array($c['slug'], ['men', 'kids', 'home'], true) && !empty(LL_SUBS[$c['slug']])) {
                foreach (LL_SUBS[$c['slug']] as $sn => $sw) {
                    $sp = q('c.slug=? AND (p.name LIKE ? OR p.tags LIKE ? OR p.short LIKE ?)', [$c['slug'], "%$sw%", "%$sw%", "%$sw%"], 'p.featured DESC, p.sold DESC', 1);
                    if (!$sp) continue;
                    $h .= '<li><a href="' . url('search') . '?q=' . rawurlencode($sw) . '&amp;c=' . $c['slug'] . '"><span>' . e($sn) . '</span><i><img src="' . e(img_small(p_image($sp[0]))) . '" alt="" width="44" height="44" loading="lazy"></i></a></li>';
                }
                continue;
            }
            $c['name'] = $lbl[$c['slug']] ?? $c['name'];
            $ps = nk_dept_products($k, $c['slug'], 1); $im = $ps ? p_image($ps[0]) : (string)$c['image'];
            $h .= '<li><a href="' . url('c/' . $c['slug']) . '"><span>' . e($c['name']) . '</span><i><img src="' . e(img_small($im)) . '" alt="" width="44" height="44" loading="lazy"></i></a></li>';
        }
    return $cache[$k] = $h;
}

/** Styled photo for a category tile (assets/img/t/<slug>.webp), else the fallback. */
function nk_tile_img(string $slug, string $fallback): string { return is_file(LL_ROOT . '/assets/img/t/' . $slug . '.webp') ? 'assets/img/t/' . $slug . '.webp' : $fallback; }
