<?php
// v1.4 store layout: departments (Women / Men / Kids / Beauty), Categories page, Luxe store.
const LL_DEPTS = [
    'women' => ['Women', 'Shop Westernwear, Ethnic wear and More', ['ethnic', 'westernwear', 'footwear', 'bags', 'jewellery', 'eyewear', 'activewear'], 'assets/img/p/wst-shirt-dress.webp', '#e2e0dd'],
    'men' => ['Men', 'Shop Formals, Casuals and Sneakers', ['men', 'footwear', 'sneakers', 'eyewear'], 'assets/img/p/men-white-shirt.webp', '#f3dcc4'],
    'kids' => ['Kids', 'Shop for Boys, Girls and Babies', ['kids'], 'assets/img/p/kd-gent-set.webp', '#d8cbc1'],
    'beauty' => ['Beauty', 'Lipstick, Skincare, Makeup and More', ['lips', 'skincare', 'makeup', 'fragrance', 'nails', 'body'], 'assets/img/p/rose-trio.webp', '#eedfe2'],
];

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
    foreach (categories() as $c) if (in_array($c['slug'], $want, true)) $out[] = $c;
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
    return '<div class="nk-sw" role="group" aria-label="Store"><a href="' . url() . '" class="' . ($luxe ? '' : 'on') . '">' . icon('spark', 15) . '<span>Fashion</span></a><a href="' . url('luxe') . '" class="' . ($luxe ? 'on' : '') . '">' . icon('gem', 15) . '<span>Luxe</span></a></div>';
}

/** Long SEO text at the bottom of the home page. */
function nk_seo_text(): string {
    $n = e(S('shop_name'));
    $h = '<section class="wrap nk-seo"><h2>' . $n . ': Online Fashion &amp; Beauty Shopping in Bangladesh</h2>';
    $h .= '<p>' . $n . ' brings sarees, kurtis, westernwear, menswear, kidswear, shoes, bags, jewellery and beauty together in one easy online store. Every product is checked by hand, prices are clear and you can pay cash on delivery anywhere in Bangladesh.</p>';
    foreach (nk_depts() as $k => $d) {
        $links = array_map(fn($c) => '<a href="' . url('c/' . $c['slug']) . '">' . e($c['name']) . '</a>', nk_dept_cats($k));
        $h .= '<h3>' . e($d[0]) . '</h3><p>' . e($d[1]) . '. Explore ' . implode(', ', $links) . ' with new styles added every week. <a href="' . url('d/' . $k) . '">Shop all ' . e(strtolower($d[0])) . '</a>.</p>';
    }
    $h .= '<h3>' . $n . ' Luxe</h3><p>Our <a href="' . url('luxe') . '">Luxe store</a> gathers silk sarees, bridal jewellery, evening gowns and fine leather in one place, for weddings, Eid and every moment that matters.</p>';
    $h .= '<h3>Why shop with us</h3><p>Original products, easy exchange if anything is wrong, fast delivery all over the country and real help on WhatsApp. <a href="' . url('track') . '">Track your order</a> any time with your order code and phone number.</p></section>';
    return $h;
}
