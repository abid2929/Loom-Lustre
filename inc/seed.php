<?php
// First-run demo data. The owner edits or deletes all of it from /admin.
function ll_seed(PDO $pdo): void {
    $now = date('Y-m-d H:i:s');
    $cats = [
        ['Lips', 'lips', 'Lipsticks, tints and glosses', 'assets/img/p/rose-trio.webp'],
        ['Skincare', 'skincare', 'Serums, creams and face oils', 'assets/img/p/hydra-serum.webp'],
        ['Makeup', 'makeup', 'Kits, foundation and brushes', 'assets/img/p/makeup-kit.webp'],
        ['Fragrance', 'fragrance', 'Perfumes for every mood', 'assets/img/p/noir-edp.webp'],
        ['Nails', 'nails', 'Nail colours and sets', 'assets/img/p/drip-polish.webp'],
        ['Body & Hair', 'body', 'Lotions, masks and hair oils', 'assets/img/p/body-butter.webp'],
    ];
    $ci = $pdo->prepare('INSERT INTO categories(name,slug,tagline,image,sort) VALUES(?,?,?,?,?)');
    $ids = [];
    foreach ($cats as $i => $c) { $ci->execute([$c[0], $c[1], $c[2], $c[3], $i + 1]); $ids[$c[1]] = (int)$pdo->lastInsertId(); }

    // name, cat, img, price, old, shades, size, short, description, how_to, featured, bestseller
    $P = [
        ['Moonlight Lip Tint Set (8 Shades)', 'lips', 'moon-tint', 1250, 1590, '', '8 x 2.5 ml', 'Eight soft-matte lip tints in a cute gift box.', "Eight lip tints from berry to bright red in one gift box. The light, creamy formula glides on easily, sets to a soft matte finish and does not dry out your lips.\n\nA lovely gift for a sister, friend or yourself.", 'Apply one coat for a soft stain or two coats for full colour. Blot with tissue for a lasting finish.', 1, 1],
        ['Velvet Red Matte Lipstick', 'lips', 'velvet-red', 650, 850, 'Classic Red, Ruby, Brick Red', '3.8 g', 'A bold red that stays put for hours.', "A rich, velvety red with full colour in one swipe. The comfortable matte formula keeps lips soft while the colour lasts through tea, lunch and everything in between.", 'Line your lips first, then fill in from the centre outwards.', 1, 1],
        ['Nude Satin Lipstick', 'lips', 'nude-satin', 590, 790, 'Rosy Nude, Warm Beige, Mocha', '3.8 g', 'Your everyday nude with a soft satin shine.', "A creamy nude that suits every skin tone. Light satin finish, gentle on lips and perfect for office, campus or a day out.", 'Wear alone or over a lip liner one shade deeper.', 1, 0],
        ['Coral Bloom Cream Lipstick', 'lips', 'coral-bloom', 620, 0, 'Coral, Peach, Salmon', '3.8 g', 'A happy coral with a creamy finish.', "Bright, warm coral that lights up your face. Smooth cream texture with light moisture for comfortable all-day wear.", 'Dab on the centre of your lips and press together for a soft look.', 0, 0],
        ['Rose Trio Lipstick Set', 'lips', 'rose-trio', 1450, 1850, '', '3 x 3.8 g', 'Three rosy shades for day, work and night.', "A set of three hand-picked rosy pinks: a soft day pink, a work-ready rose and a deeper evening shade. Comes in a gift-ready box.", 'Mix two shades for your own custom colour.', 1, 0],
        ['Sparkle Lip Gloss', 'lips', 'glitter-gloss', 450, 550, 'Champagne, Pink Shimmer, Clear', '6 ml', 'Glossy shine with fine sparkle, never sticky.', "A non-sticky gloss with fine golden shimmer. Wear it alone for a glassy shine or on top of your lipstick for extra glow.", 'Apply on the centre of the lower lip and press.', 0, 1],
        ['Vitamin C Glow Serum', 'skincare', 'vitc-serum', 890, 1100, '', '30 ml', 'Brightens dull skin and evens out tone.', "A light serum with Vitamin C to help dull, tired skin look brighter and more even. Absorbs fast with no sticky feel, made for humid Bangladesh weather.", 'Use 3 to 4 drops on clean skin every morning, then moisturiser and sunscreen.', 1, 1],
        ['Hyaluronic Hydra Serum', 'skincare', 'hydra-serum', 950, 0, '', '30 ml', 'Deep hydration for plump, soft skin.', "Water-light serum with hyaluronic acid that pulls moisture into the skin. Leaves your face soft, bouncy and fresh.", 'Pat 3 drops on damp skin, morning and night.', 1, 0],
        ['Botanical Face Oil', 'skincare', 'botanical-oil', 780, 950, '', '30 ml', 'A nourishing blend of plant oils.', "A blend of light plant oils to nourish dry skin and give a healthy glow. Great as the last step of your night routine.", 'Warm 2 drops between your palms and press on your face.', 0, 0],
        ['Night Repair Serum', 'skincare', 'night-serum', 990, 1250, '', '30 ml', 'Works while you sleep for fresher mornings.', "A calming night serum that helps skin recover overnight. Wake up to skin that looks rested and smooth.", 'Use 3 drops at night after cleansing.', 0, 1],
        ['Daily Moisture Cream', 'skincare', 'moist-cream', 850, 0, '', '50 g', 'Light, fast-absorbing daily moisturiser.', "A soft, whipped moisturiser that keeps skin hydrated all day without feeling heavy. Suits normal, dry and combination skin.", 'Apply a pea-sized amount on face and neck, morning and night.', 1, 0],
        ['Rose Facial Oil', 'skincare', 'rose-oil', 650, 0, '', '15 ml', 'Rose-scented oil for a soft glow.', "A light facial oil with a gentle rose scent. Adds instant glow and softness to dry or tired skin.", 'Mix one drop into your moisturiser.', 0, 0],
        ['Essential Makeup Kit', 'makeup', 'makeup-kit', 1650, 1990, '', '6 pieces', 'Everything you need for an everyday look.', "Lipstick, mascara, compact powder, blush and two brushes in one kit. Perfect for beginners or as a gift.", 'Start with powder, add blush on the cheeks, mascara and finish with lipstick.', 1, 1],
        ['Pro Makeup Brush Set', 'makeup', 'brush-set', 990, 1290, '', '12 pieces', 'Soft, dense brushes for face and eyes.', "Twelve soft synthetic brushes for foundation, powder, blush, contour and eyes. Easy to wash and long lasting.", 'Wash brushes once a week with mild shampoo and lay flat to dry.', 0, 1],
        ['Silk Skin Foundation', 'makeup', 'silk-foundation', 1150, 1390, 'Ivory, Natural Beige, Warm Sand, Honey', '30 ml', 'Light coverage with a natural finish.', "A lightweight foundation that evens skin tone and lets your skin breathe. Buildable coverage and a natural, skin-like finish.", 'Dot on the face and blend with a brush or damp sponge.', 1, 0],
        ['Skin Care Starter Kit', 'makeup', 'starter-kit', 2490, 2990, '', '8 pieces', 'A complete pink kit for skin and makeup.', "Cleanser, toner, serum, day cream, mask, powder, lip balm and a brush, all in one pretty pink set. A perfect gift box.", 'Use in order: cleanse, tone, serum, cream.', 0, 0],
        ['Rainbow Eyeshadow Palette', 'makeup', 'rainbow-palette', 1290, 1590, '', '35 shades', 'Bright, blendable shades for bold eye looks.', "Thirty-five rich shadows in matte and shimmer finishes, from soft nudes to bright party colours. Smooth, easy to blend and long lasting.", 'Use a flat brush to pat colour on the lid, then blend the edges with a fluffy brush.', 1, 1],
        ['Velvet Nude Eyeshadow Palette', 'makeup', 'velvet-palette', 1490, 1790, '', '12 shades', 'Warm nudes and soft shimmers for every day.', "Twelve warm nude shades that work for office, weddings and evenings. Buttery texture, low fallout and a built-in mirror.", 'Start with the lightest shade all over the lid, then deepen the crease.', 1, 0],
        ['Sunset Eyeshadow Palette', 'makeup', 'sunset-palette', 990, 1250, '', '10 shades', 'Peach, coral and rose shades in one pretty palette.', "Ten sunset-inspired shades in peach, coral, rose and terracotta. Perfect for soft, glowing eye looks.", 'Pat the shimmer shades on the centre of the lid with your finger for extra shine.', 0, 0],
        ['Nude Eyeshadow Quad', 'makeup', 'nude-quad', 690, 0, '', '4 shades', 'Four everyday nudes in a slim travel case.', "Four soft nude shades in a slim case that fits any handbag. Comes with a double-ended applicator.", 'Light shade on the lid, medium in the crease, dark along the lash line.', 0, 0],
        ['Peach Glow Blush', 'makeup', 'peach-blush', 590, 750, 'Peach, Coral, Soft Pink', '6 g', 'A soft flush of colour for fresh cheeks.', "A silky powder blush that gives a natural, healthy flush. Buildable colour that suits every skin tone.", 'Smile and sweep onto the apples of your cheeks, blending up towards the temples.', 1, 1],
        ['Rose Pigment Blush', 'makeup', 'rose-blush', 520, 0, 'Rose, Berry, Terracotta', '5 g', 'Rich pigment blush with a satin finish.', "Highly pigmented blush in deep rose tones. A little goes a long way for a lasting satin flush.", 'Tap off extra powder before applying for a soft look.', 0, 0],
        ['Glow Highlighter Pot', 'makeup', 'glow-highlighter', 650, 790, 'Champagne, Rose Gold', '4 g', 'A soft shimmer for glowing cheekbones.', "A fine shimmer powder that catches the light on cheekbones, nose and brow bone. Never glittery or patchy.", 'Apply with a small brush on the high points of your face.', 0, 1],
        ['Lash Length Mascara', 'makeup', 'lash-mascara', 550, 690, '', '10 ml', 'Long, lifted lashes with no clumps.', "A smudge-proof mascara that lengthens and lifts each lash. The thin wand reaches even the smallest lashes.", 'Wiggle the wand from the root to the tip. Add a second coat for drama.', 1, 1],
        ['Silk Compact Powder', 'makeup', 'silk-compact', 690, 850, 'Fair, Natural, Medium, Tan', '9 g', 'Matte, oil-control powder with a mirror.', "A light pressed powder that controls shine and smooths skin for hours. Gold compact with a mirror and puff.", 'Press over foundation or wear alone on bare skin.', 0, 0],
        ['Loose Setting Powder', 'makeup', 'setting-powder', 750, 0, 'Translucent, Light Beige', '20 g', 'Locks your makeup in place all day.', "A fine loose powder that sets makeup and blurs pores. Comes with a mini kabuki brush.", 'Press a small amount under the eyes and on the T-zone.', 0, 0],
        ['Shea Butter Lip Balm', 'lips', 'lip-balm', 290, 350, '', '10 g', 'Heals dry, cracked lips overnight.', "A rich lip balm with shea butter to soothe dry, chapped lips. Light vanilla scent and no sticky feel.", 'Apply as often as needed, and a thick layer before bed.', 0, 1],
        ['Overnight Renew Cream', 'skincare', 'night-cream', 990, 1190, '', '50 g', 'Rich night cream for soft, rested skin.', "A rich, nourishing night cream that works while you sleep. Wake up to soft, smooth and rested-looking skin.", 'Apply on clean face and neck at night.', 0, 0],
        ['Herbal Hair Oil Trio', 'body', 'herbal-hair-oil', 1150, 1350, '', '3 x 30 ml', 'Three herbal oils for strong, shiny hair.', "Three herbal hair oils for scalp care, shine and less hair fall. Light, non-sticky and easy to wash out.", 'Massage into the scalp, leave for one hour or overnight, then shampoo.', 0, 0],
        ['Argan Hair Serum', 'body', 'argan-oil', 690, 0, '', '50 ml', 'Smooths frizz and adds shine.', "A light argan oil serum that tames frizz and adds a healthy shine without weighing hair down.", 'Rub 2 drops between palms and smooth over damp or dry hair ends.', 0, 0],
        ['Noir Eau de Parfum', 'fragrance', 'noir-edp', 1890, 2290, '', '50 ml', 'Clean, elegant scent for day and night.', "A clean, elegant fragrance with soft musk and fresh notes. Long lasting and perfect for every day.", 'Spray on wrists and neck from a short distance.', 1, 1],
        ['Golden Amber Perfume', 'fragrance', 'golden-amber', 2190, 0, '', '100 ml', 'Warm amber and vanilla for special nights.', "A warm, rich scent with amber and vanilla. Made for weddings, dinners and evenings out.", 'Spray once on each wrist and once on the neck.', 1, 0],
        ['Drip Colour Nail Trio', 'nails', 'drip-polish', 450, 600, '', '3 x 10 ml', 'Three trendy shades, quick dry.', "A trio of quick-dry nail colours in blush pink, burnt orange and mauve. Glossy finish with smooth, even coverage.", 'Apply two thin coats and let each dry for one minute.', 0, 1],
        ['Jewel Tone Nail Set', 'nails', 'jewel-polish', 690, 850, '', '4 x 10 ml', 'Four rich jewel colours for festive nails.', "Ruby, amber, violet and sapphire nail colours. Rich, glossy and long lasting, perfect for Eid and weddings.", 'Use a base coat first for the longest wear.', 0, 0],
        ['Ballet Pink Nail Polish', 'nails', 'ballet-pink', 250, 0, '', '10 ml', 'A soft, clean pink for everyday nails.', "A sheer soft pink that looks neat and clean on every hand. Quick dry and chip resistant.", 'Two coats for a soft colour, three for more.', 0, 0],
        ['Whipped Body Butter', 'body', 'body-butter', 560, 690, '', '200 g', 'Rich butter for very dry skin.', "A thick, whipped body butter that melts into dry skin and keeps it soft for hours. Light, fresh scent.", 'Massage onto damp skin after a shower.', 0, 1],
        ['Soft Hands Lotion', 'body', 'body-lotion', 520, 0, '', '200 ml', 'Non-greasy lotion for soft hands.', "A light hand and body lotion that absorbs fast. Keeps hands soft without a greasy feel.", 'Use as often as you like.', 0, 0],
        ['Peach Clay Face Mask', 'body', 'clay-mask', 690, 850, '', '100 g', 'Gentle clay mask for clean, smooth skin.', "A soft clay mask that lifts dirt and oil while keeping skin comfortable. Leaves your face smooth and fresh.", 'Apply a thin layer, leave for 10 minutes, rinse with warm water. Use twice a week.', 0, 0],
    ];
    $pi = $pdo->prepare('INSERT INTO products(name,slug,category_id,brand,price,old_price,images,shades,size,stock,short,description,how_to,featured,bestseller,is_new,sort,sold,created,updated) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    foreach ($P as $i => $p) {
        $img = json_encode(array_merge(['assets/img/p/' . $p[2] . '.webp'], $p[2] === 'silk-compact' ? ['assets/img/p/silk-compact-2.webp'] : []));
        $pi->execute([$p[0], slugify($p[0]), $ids[$p[1]], 'Loom & Lustre', $p[3], $p[4], $img, $p[5], $p[6], 25, $p[7], $p[8], $p[9], $p[10], $p[11], in_array($p[2], ['moon-tint','rainbow-palette','velvet-palette','lash-mascara','peach-blush','lip-balm','night-cream','argan-oil','sunset-palette','glow-highlighter'], true) ? 1 : 0, $i, $p[11] ? 60 - $i : 5, $now, $now]);
    }
    // catalog collections (themed edits that mix categories)
    $cols = [
        ['Bridal Glow', 'bridal-glow', 'Everything for the wedding day look', 'assets/img/p/velvet-palette.webp', ['velvet-red-matte-lipstick', 'velvet-nude-eyeshadow-palette', 'glow-highlighter-pot', 'silk-skin-foundation', 'golden-amber-perfume', 'jewel-tone-nail-set', 'lash-length-mascara']],
        ['Everyday Essentials', 'everyday-essentials', 'Simple picks for daily use', 'assets/img/p/nude-satin.webp', ['nude-satin-lipstick', 'daily-moisture-cream', 'shea-butter-lip-balm', 'silk-compact-powder', 'ballet-pink-nail-polish', 'soft-hands-lotion', 'vitamin-c-glow-serum']],
        ['Gift Sets', 'gift-sets', 'Pretty boxes ready to gift', 'assets/img/p/rose-trio.webp', ['moonlight-lip-tint-set-8-shades', 'rose-trio-lipstick-set', 'skin-care-starter-kit', 'essential-makeup-kit', 'drip-colour-nail-trio', 'herbal-hair-oil-trio']],
        ['Party Night', 'party-night', 'Bold colour and shine for evenings out', 'assets/img/p/rainbow-palette.webp', ['rainbow-eyeshadow-palette', 'sparkle-lip-gloss', 'noir-eau-de-parfum', 'sunset-eyeshadow-palette', 'coral-bloom-cream-lipstick']],
    ];
    $cc = $pdo->prepare('INSERT INTO collections(name,slug,tagline,image,sort) VALUES(?,?,?,?,?)');
    $up = $pdo->prepare('UPDATE products SET collection_id=? WHERE slug=?');
    foreach ($cols as $i => $c) { $cc->execute([$c[0], $c[1], $c[2], $c[3], $i + 1]); $cid = (int)$pdo->lastInsertId(); foreach ($c[4] as $s) $up->execute([$cid, $s]); }
    $pdo->exec("UPDATE products SET flash_sale=1 WHERE slug IN ('velvet-red-matte-lipstick','peach-glow-blush','vitamin-c-glow-serum','drip-colour-nail-trio','lash-length-mascara','rainbow-eyeshadow-palette')");
    ll_seed_fashion($pdo);
}

// v1.3 fashion range (Nykaa-style category row). Added once to fresh installs and to untouched demo shops.
function ll_seed_fashion(PDO $pdo): void {
    $now = date('Y-m-d H:i:s');
    if ((int)$pdo->query("SELECT COUNT(*) FROM categories WHERE slug='ethnic'")->fetchColumn()) return;
    $cats = [
        ['Ethnic Wear', 'ethnic', 'Sarees, kurtis and salwar kameez', 'eth-red-saree'],
        ['Westernwear', 'westernwear', 'Dresses, tops and co-ord sets', 'wst-shirt-dress'],
        ['Men', 'men', 'Shirts, suits and jackets', 'men-navy-suit'],
        ['Footwear', 'footwear', 'Heels, loafers and boots', 'fw-floral-heels'],
        ['Bags', 'bags', 'Handbags, totes and slings', 'bag-red-top'],
        ['Jewellery', 'jewellery', 'Gold-tone sets, earrings and pendants', 'jw-bridal-set'],
        ['Sunglasses', 'eyewear', 'UV400 frames for sunny days', 'sg-gold-round'],
        ['Kids', 'kids', 'Party dresses and everyday sets', 'kd-unicorn-dress'],
        ['Activewear', 'activewear', 'Leggings and training sets', 'act-lilac-set'],
        ['Sneakers', 'sneakers', 'Everyday and chunky sneakers', 'snk-colour-pop'],
    ];
    // fashion goes first in the menu, beauty after it
    $pdo->exec('UPDATE categories SET sort = sort + 20');
    $ci = $pdo->prepare('INSERT INTO categories(name,slug,tagline,image,sort) VALUES(?,?,?,?,?)');
    $ids = [];
    foreach ($cats as $i => $c) { $ci->execute([$c[0], $c[1], $c[2], 'assets/img/p/' . $c[3] . '.webp', $i + 1]); $ids[$c[1]] = (int)$pdo->lastInsertId(); }
    $W = 'S, M, L, XL'; $F = '36, 37, 38, 39, 40'; $M = '40, 41, 42, 43, 44'; $K = '2-3 Y, 4-5 Y, 6-7 Y';
    // name, cat, img, price, old, colours, sizes, short, description, featured, bestseller, new
    $P = [
        ['Rose Gold Banarasi Silk Saree', 'ethnic', 'eth-banarasi-saree', 6490, 8990, 'Rose Pink, Magenta', '', 'Soft Banarasi silk with a rich zari border.', "A glowing rose-pink Banarasi silk saree with a woven gold zari border and pallu. Light enough for a long wedding day and comes with an unstitched blouse piece.\n\nLength 6.3 m including blouse piece.", 1, 1, 0],
        ['Royal Purple Katan Saree', 'ethnic', 'eth-purple-saree', 5890, 7490, 'Purple, Wine', '', 'A regal katan weave in deep purple and gold.', "Deep purple katan silk with an all-over gold motif and a heavy border. Made for receptions, Eid dinners and family weddings.\n\nLength 6.3 m including blouse piece.", 1, 0, 1],
        ['Sage Green Soft Silk Saree', 'ethnic', 'eth-sage-saree', 4990, 6290, 'Sage Green, Mint', '', 'Calm sage silk that pairs beautifully with gold.', "A soft silk saree in a fresh sage green with a fine woven border. Drapes easily and looks lovely with gold or kundan jewellery.\n\nLength 6.3 m including blouse piece.", 0, 1, 0],
        ['Red Bridal Kanjivaram Saree', 'ethnic', 'eth-red-saree', 8990, 11990, 'Bridal Red', '', 'Classic bridal red with a broad gold border.', "A rich red Kanjivaram-style silk saree with a broad gold temple border. The timeless choice for a bride or the bride's sister.\n\nLength 6.3 m including blouse piece.", 1, 1, 0],
        ['Pink Block Print Kurti Set', 'ethnic', 'eth-pink-kurti', 1890, 2490, 'Pink', $W, 'Breezy cotton kurti with matching pants.', "A soft cotton kurti with hand block print florals, paired with matching straight pants. Cool and comfortable for Bangladesh summers.", 1, 1, 0],
        ['Ivory Floral Kurti Set', 'ethnic', 'eth-white-kurti', 1790, 2290, 'Ivory', $W, 'Fresh ivory kurti with printed florals.', "An ivory cotton kurti set with printed florals and lace detail at the neckline. Easy to wear for office, campus or a day out.", 0, 0, 1],
        ['Marigold Embroidered Kurti', 'ethnic', 'eth-yellow-kurti', 1450, 1890, 'Marigold Yellow', $W, 'Sunny yellow kurti with a colourful yoke.', "A tiered marigold kurti with colourful thread embroidery on the yoke. Perfect for Gaye Holud and Pohela Boishakh.", 0, 1, 0],
        ['Indigo Paisley Anarkali Kurti', 'ethnic', 'eth-navy-kurti', 1690, 2190, 'Indigo', $W, 'Flowing anarkali with a paisley print.', "A flared indigo anarkali kurti with a classic paisley print and three-quarter sleeves. Pairs with leggings or palazzos.", 0, 0, 0],
        ['Red Salwar Kameez with Dupatta', 'ethnic', 'eth-red-salwar', 2650, 3390, 'Red', $W, 'Festive red three-piece with a printed dupatta.', "A three-piece salwar kameez in festive red with a printed dupatta. Ready to wear for Eid and family dawats.", 1, 0, 1],
        ['Terracotta Belted Shirt Dress', 'westernwear', 'wst-shirt-dress', 2450, 3290, 'Terracotta, Olive, Black', $W, 'Easy button-down dress with a tie waist.', "A soft cotton-blend shirt dress with short sleeves, front buttons and a tie belt. Dress it up with heels or down with sneakers.", 1, 1, 0],
        ['Cloud Ruffle Blouse', 'westernwear', 'wst-ruffle-blouse', 1650, 2190, 'White', $W, 'Statement blouse with soft textured ruffles.', "A white blouse with puffed sleeves and a textured ruffle finish. Tuck it into a skirt or jeans for an instant style lift.", 0, 0, 1],
        ['Pink Blossom Co-ord Set', 'westernwear', 'wst-floral-coord', 2850, 3790, 'Blossom Pink', $W, 'Printed shirt and wide pants, ready to go.', "A relaxed shirt and wide-leg pants in a pretty blossom print. Light fabric, easy fit and very photo friendly.", 1, 0, 1],
        ['Plum Satin Evening Gown', 'westernwear', 'wst-satin-gown', 5990, 7990, 'Plum, Emerald', $W, 'A floor-length satin gown for big nights.', "A rich plum satin gown with a fitted bodice and flowing skirt. Made for receptions, parties and award nights.", 0, 0, 0],
        ['Mustard Knit Sweater', 'westernwear', 'wst-mustard-knit', 1390, 1790, 'Mustard, Cream', $W, 'Soft knit sweater for winter evenings.', "A cosy, soft knit sweater in warm mustard. Relaxed fit that looks great with jeans.", 0, 1, 0],
        ['Red Polka Dot Midi Dress', 'westernwear', 'wst-polka-midi', 1990, 2590, 'Red', $W, 'A playful polka dot midi with a V neck.', "A flattering red midi dress with white polka dots and a soft V neckline. Light, breezy and easy to style.", 0, 0, 0],
        ['Indigo Linen Shirt', 'men', 'men-linen-shirt', 1390, 1790, 'Indigo, White', $M, 'Breathable linen shirt for hot days.', "A relaxed linen-blend shirt in washed indigo. Stays cool in humid weather and looks smart with chinos.", 1, 1, 0],
        ['Navy Slim Fit Suit', 'men', 'men-navy-suit', 8990, 11490, 'Navy', $M, 'Two-piece slim fit suit for weddings and office.', "A sharp two-piece navy suit with a slim cut jacket and matching trousers. Wear it to weddings, interviews and formal events.", 1, 0, 0],
        ['Tan Suede Jacket', 'men', 'men-suede-jacket', 4490, 5990, 'Tan', $M, 'Soft faux suede jacket with a classic collar.', "A tan faux suede jacket with a classic collar and front buttons. The easy winter layer over a tee or shirt.", 0, 0, 1],
        ['Crisp White Formal Shirt', 'men', 'men-white-shirt', 1290, 1590, 'White, Sky Blue', $M, 'Wrinkle-resistant shirt for every day.', "A crisp, wrinkle-resistant white shirt with a neat collar. The one shirt every wardrobe needs.", 0, 1, 0],
        ['Mustard Check Overshirt', 'men', 'men-check-shirt', 1490, 1890, 'Mustard Check', $M, 'Brushed cotton check shirt to wear open.', "A brushed cotton overshirt in a mustard and black check. Wear it open over a tee for an easy weekend look.", 0, 0, 0],
        ['Essential White Tee', 'men', 'men-white-tee', 590, 790, 'White, Black, Grey', $M, 'Soft cotton tee with a clean crew neck.', "A soft, thick cotton t-shirt with a clean crew neck. Keeps its shape wash after wash.", 0, 1, 0],
        ['Ivory Pointed Pumps', 'footwear', 'fw-white-pumps', 2290, 2990, 'Ivory', $F, 'Elegant pointed pumps with a slim heel.', "Ivory pointed-toe pumps with a 9 cm slim heel and a cushioned insole. Pairs with sarees and gowns alike.", 1, 0, 0],
        ['Blue Garden Floral Heels', 'footwear', 'fw-floral-heels', 2590, 3290, 'Blue Floral', $F, 'Statement heels in a bright floral print.', "Almond-toe stilettos covered in a blue and pink garden print. The pair everyone asks about.", 1, 1, 1],
        ['Hot Pink Patent Stilettos', 'footwear', 'fw-pink-stilettos', 2490, 3190, 'Hot Pink', $F, 'Glossy pink stilettos for party nights.', "High-shine patent stilettos in hot pink with a pointed toe. Made for parties and photos.", 0, 0, 0],
        ['Classic Black Pumps', 'footwear', 'fw-black-pumps', 1990, 2590, 'Black', $F, 'The everyday black heel, office to evening.', "Simple black pumps with a comfortable 8 cm heel. Smart for the office and right for the evening.", 0, 1, 0],
        ['Walnut Leather Brogues', 'footwear', 'fw-brogues', 3490, 4490, 'Walnut Brown', $M, 'Polished leather brogues for formal days.', "Genuine leather brogues in a polished walnut brown with classic punch detail. The finishing touch for suits.", 1, 0, 0],
        ['Tan Brogue Boots', 'footwear', 'fw-boots', 4290, 5490, 'Tan', $M, 'Lace-up leather boots with brogue detail.', "Lace-up tan leather boots with brogue detailing and a sturdy sole. Warm, smart and long lasting.", 0, 0, 1],
        ['Black Penny Loafers', 'footwear', 'fw-loafers', 2890, 3590, 'Black', $M, 'Glossy slip-on loafers with a chunky sole.', "Shiny black slip-on loafers with a chunky sole. Smart with trousers, cool with jeans.", 0, 1, 0],
        ['Colour Pop Chunky Sneakers', 'sneakers', 'snk-colour-pop', 2990, 3990, 'Multi', $F, 'Bold chunky sneakers with colour blocking.', "Chunky sneakers with mint, purple and red colour blocking and a cushioned sole. Light and comfy for all-day walking.", 1, 1, 1],
        ['Red Canvas Sneakers', 'sneakers', 'snk-red-canvas', 1490, 1990, 'Red, Navy', $F, 'Classic lace-up canvas sneakers.', "Classic low-top canvas sneakers with a white rubber toe cap and cushioned insole. Easy to wear with jeans, dresses or kurtis.", 0, 1, 1],
        ['Everyday White Sneakers', 'sneakers', 'snk-white-classic', 2290, 2890, 'White', $F, 'Minimal leather-look sneakers for daily wear.', "Minimal white sneakers with a leather-look upper and comfortable sole. Wipe clean and go.", 0, 0, 0],
        ['Cherry Top Handle Bag', 'bags', 'bag-red-top', 3290, 4290, 'Cherry Red, Black', '', 'Structured top handle bag with a shoulder strap.', "A structured cherry red bag with a top handle, silver clasp and detachable shoulder strap. Fits a phone, wallet and lipstick.", 1, 1, 0],
        ['Tan Wicker Handbag', 'bags', 'bag-wicker', 2890, 3690, 'Tan', '', 'Woven wicker base with a leather-look top.', "A charming handbag with a woven wicker base and a smooth tan top. Perfect for brunches and holidays.", 0, 0, 1],
        ['Woven Chain Tote', 'bags', 'bag-woven-tote', 3990, 4990, 'Cognac', '', 'Roomy woven tote with a gold chain strap.', "A soft, roomy woven tote in cognac brown with a gold chain detail. Big enough for a laptop.", 1, 0, 0],
        ['Cognac Crossbody Sling', 'bags', 'bag-sling', 1890, 2490, 'Cognac, Black', '', 'A neat everyday sling with an adjustable strap.', "A compact crossbody sling with a fold-over flap and adjustable strap. Hands free and always neat.", 0, 1, 0],
        ['Grey Buckle Satchel', 'bags', 'bag-satchel', 2490, 3190, 'Grey', '', 'Classic satchel with twin buckles.', "A classic satchel with twin buckles, top handle and a shoulder strap. Great for office and campus.", 0, 0, 0],
        ['Black Structured Tote', 'bags', 'bag-black-tote', 3490, 4490, 'Black', '', 'Clean black tote for work days.', "A clean, structured black tote with long handles and a roomy inside. Your everyday work bag.", 0, 1, 0],
        ['Black Mini Duffle Bag', 'bags', 'bag-duffle', 2190, 2790, 'Black', '', 'Compact duffle with buckle straps.', "A compact barrel duffle with buckle straps and twin handles. Cute for weekends and short trips.", 0, 0, 1],
        ['Kundan Gold Bridal Necklace Set', 'jewellery', 'jw-bridal-set', 4990, 6990, '', '', 'Gold-tone necklace with matching earrings.', "A grand gold-tone necklace with ruby-pink stones and matching earrings. Made for brides and wedding guests.\n\nGold-plated brass. Keep away from water and perfume.", 1, 1, 0],
        ['Gold Plated Bangle Set', 'jewellery', 'jw-bangles', 1490, 1990, '', '2.4, 2.6, 2.8', 'Set of six twisted gold-tone bangles.', "Six twisted gold-tone bangles that shine with sarees and kurtis.\n\nGold-plated brass.", 0, 1, 0],
        ['Sapphire Drop Earrings', 'jewellery', 'jw-sapphire-drops', 1290, 1690, '', '', 'Crystal drops with a deep blue stone.', "Statement drop earrings with sparkling crystals around a deep blue stone. Perfect for evenings.", 0, 0, 1],
        ['Twisted Gold Hoops', 'jewellery', 'jw-twist-hoops', 890, 1190, '', '', 'Chunky twisted hoops for every day.', "Chunky twisted hoops in a warm gold tone. Light on the ear and easy to wear daily.", 0, 1, 0],
        ['Crystal Huggie Hoops', 'jewellery', 'jw-huggies', 790, 990, '', '', 'Small hoops lined with crystals.', "Small huggie hoops lined with clear crystals. A tiny sparkle for every day.", 0, 0, 0],
        ['Golden Shell Earrings', 'jewellery', 'jw-shell-earrings', 690, 890, '', '', 'Playful gold-tone seashell drops.', "Playful seashell drop earrings in polished gold tone. A fun summer piece.", 0, 0, 1],
        ['Oval Pendant Chain', 'jewellery', 'jw-oval-pendant', 990, 1290, '', '', 'Minimal oval pendant on a snake chain.', "A minimal open oval pendant on a smooth snake chain. Layers well with other chains.", 1, 0, 0],
        ['Pearl Drop Pendant', 'jewellery', 'jw-pearl-pendant', 1190, 1490, '', '', 'A single pearl in a gold-tone frame.', "A single freshwater-style pearl in a fine gold-tone frame on a delicate chain. Gift ready.", 0, 1, 0],
        ['Crystal Stud Earrings', 'jewellery', 'jw-studs', 590, 790, '', '', 'Round crystal studs set in gold tone.', "Round crystal studs in a gold-tone setting. Simple, bright and right for every day.", 0, 0, 0],


        ['Gold Round Sunglasses', 'eyewear', 'sg-gold-round', 1290, 1690, 'Gold / Green', '', 'Thin gold frame with green lenses.', "Round sunglasses with a thin gold metal frame and green-tinted UV400 lenses. Comes with a case.", 1, 1, 0],
        ['Clear Frame Sunglasses', 'eyewear', 'sg-clear-frame', 1190, 1490, 'Clear / Brown', '', 'Light clear frames with warm brown lenses.', "Lightweight clear frames with warm brown UV400 lenses. Fresh and summery.", 0, 0, 0],
        ['Black Browline Sunglasses', 'eyewear', 'sg-browline', 1090, 1390, 'Black', '', 'Classic browline shape in black.', "A classic browline frame in black with dark UV400 lenses. Suits most face shapes.", 0, 1, 0],
        ['Amber Tint Sunglasses', 'eyewear', 'sg-amber', 1190, 1490, 'Amber', '', 'Oversized frames with amber lenses.', "Oversized frames with soft amber lenses for a retro look. UV400 protection.", 0, 0, 1],
        ['Little Gent Cardigan Set', 'kids', 'kd-gent-set', 1490, 1890, 'Navy / Blue', $K, 'Cardigan, shirt and shorts with a bow tie.', "A smart little set with a navy cardigan, shirt, blue shorts and a bow tie. Made for weddings and birthday parties.", 1, 0, 0],
        ['Unicorn Tulle Party Dress', 'kids', 'kd-unicorn-dress', 1690, 2190, 'Navy Blue', $K, 'Sparkly tulle dress with a unicorn print.', "A party dress with a unicorn print top and layered ombre tulle skirt. Soft cotton lining so it never itches.", 1, 1, 1],
        ['Baby Cotton Tee & Hat Set', 'kids', 'kd-tee-hat', 790, 990, 'White / Olive', '0-6 M, 6-12 M, 1-2 Y', 'Soft tee with a cute knit hat.', "A soft cotton tee with a cosy knit hat. Gentle on baby skin.", 0, 0, 0],
        ['Baby Romper Pack', 'kids', 'kd-romper-pack', 1290, 1690, 'Pastel Mix', '0-6 M, 6-12 M, 1-2 Y', 'Three soft cotton rompers in pastel shades.', "A pack of three soft cotton rompers in pastel colours with snap buttons for easy changes.", 0, 1, 0],
        ['Girls Polka Frill Dress', 'kids', 'kd-polka-frill', 1190, 1490, 'Yellow Polka', $K, 'Twirly frill dress in a happy polka print.', "A twirly cotton frill dress in a bright yellow polka print. Comfortable for play and pretty for photos.", 0, 0, 1],
        ['Sculpt High-Rise Leggings', 'activewear', 'act-leggings', 1290, 1690, 'Lilac, Mustard, Sky, Beige', 'XS, S, M, L, XL', 'Squat-proof leggings with a high waist.', "Soft, stretchy high-rise leggings that stay put during yoga, gym or running. Quick dry and squat proof.", 1, 1, 0],
        ['Lilac Training Set', 'activewear', 'act-lilac-set', 2490, 3190, 'Lilac, Teal, Black', 'XS, S, M, L, XL', 'Matching top and leggings for the gym.', "A matching training set with a supportive top and high-rise leggings. Sweat wicking and super comfy.", 0, 1, 1],
        ['Navy Track Set', 'activewear', 'act-navy-set', 2790, 3490, 'Navy', 'XS, S, M, L, XL', 'Zip jacket and joggers for travel and training.', "A navy zip jacket with matching joggers. Smart enough for travel, comfy enough for training.", 0, 0, 0],
    ];
    $pi = $pdo->prepare('INSERT INTO products(name,slug,category_id,brand,price,old_price,images,shades,sizes,size,stock,short,description,how_to,featured,bestseller,is_new,sort,sold,created,updated) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $base = 100;
    foreach ($P as $i => $p) {
        $pi->execute([$p[0], slugify($p[0]), $ids[$p[1]], 'Loom & Lustre', $p[3], $p[4], json_encode(['assets/img/p/' . $p[2] . '.webp']), $p[5], $p[6], '', 20, $p[7], $p[8], '', $p[9], $p[10], $p[11], $base + $i, $p[10] ? 55 - ($i % 30) : 4, $now, $now]);
    }
    // occasion edits ("Styles to celebrate in"), stylists' picks and the luxe edit
    $cols = [
        ['Eid Edit', 'eid-edit', 'Festive kurtis, salwar and gold for Eid', 'eth-red-salwar', ['red-salwar-kameez-with-dupatta', 'marigold-embroidered-kurti', 'pink-block-print-kurti-set', 'gold-plated-bangle-set', 'golden-amber-perfume', 'velvet-red-matte-lipstick', 'little-gent-cardigan-set']],
        ['Wedding Season', 'wedding-season', 'Silk sarees, heels and bridal sets', 'eth-banarasi-saree', ['red-bridal-kanjivaram-saree', 'rose-gold-banarasi-silk-saree', 'royal-purple-katan-saree', 'kundan-gold-bridal-necklace-set', 'ivory-pointed-pumps', 'navy-slim-fit-suit', 'cherry-top-handle-bag']],
        ['Office Ready', 'office-ready', 'Smart pieces for the work week', 'men-white-shirt', ['crisp-white-formal-shirt', 'indigo-linen-shirt', 'terracotta-belted-shirt-dress', 'classic-black-pumps', 'black-structured-tote', 'grey-buckle-satchel', 'crystal-stud-earrings']],
        ['Weekend Casual', 'weekend-casual', 'Easy outfits for days off', 'wst-floral-coord', ['pink-blossom-co-ord-set', 'colour-pop-chunky-sneakers', 'essential-white-tee', 'gold-round-sunglasses', 'cognac-crossbody-sling', 'red-polka-dot-midi-dress', 'mustard-check-overshirt']],
        ["Stylists' Picks", 'stylists-picks', 'Chosen by our stylists this week', 'eth-navy-kurti', ['indigo-paisley-anarkali-kurti', 'blue-garden-floral-heels', 'woven-chain-tote', 'sapphire-drop-earrings', 'tan-suede-jacket', 'cloud-ruffle-blouse']],
        ['The Luxe Edit', 'luxe-edit', 'Silk, gold and fine finishes for the moments that matter', 'eth-purple-saree', ['royal-purple-katan-saree', 'kundan-gold-bridal-necklace-set', 'rose-gold-banarasi-silk-saree', 'plum-satin-evening-gown', 'walnut-leather-brogues']],
    ];
    $cc = $pdo->prepare('INSERT INTO collections(name,slug,tagline,image,sort) VALUES(?,?,?,?,?)');
    $ix = $pdo->prepare('INSERT OR IGNORE INTO col_items(collection_id, product_id) SELECT ?, id FROM products WHERE slug=?');
    foreach ($cols as $i => $c) { $cc->execute([$c[0], $c[1], $c[2], 'assets/img/p/' . $c[3] . '.webp', $i + 1]); $cid = (int)$pdo->lastInsertId(); foreach ($c[4] as $s) $ix->execute([$cid, $s]); }
    $pdo->exec('UPDATE collections SET sort = sort + 10 WHERE slug IN (\'bridal-glow\',\'everyday-essentials\',\'gift-sets\',\'party-night\')');
    $pdo->exec("UPDATE collections SET image='assets/img/p/eth-sage-saree.webp' WHERE slug='bridal-glow' AND image='assets/img/p/velvet-palette.webp'");
    $pdo->exec("UPDATE collections SET image='assets/img/p/wst-satin-gown.webp' WHERE slug='party-night' AND image='assets/img/p/rainbow-palette.webp'");
    $pdo->exec("UPDATE products SET flash_sale=1 WHERE slug IN ('rose-gold-banarasi-silk-saree','colour-pop-chunky-sneakers','cherry-top-handle-bag','pink-block-print-kurti-set','gold-round-sunglasses','terracotta-belted-shirt-dress')");
    ll_seed_v15($pdo);
}

// v1.5: Watches and Home, like the Nykaa Fashion store. Only added once.
function ll_seed_v15(PDO $pdo): void {
    if ($pdo->query("SELECT 1 FROM categories WHERE slug='watches'")->fetchColumn()) return;
    $now = date('Y-m-d H:i:s');
    $ci = $pdo->prepare('INSERT INTO categories(name,slug,tagline,image,sort) VALUES(?,?,?,?,?)');
    $ids = [];
    foreach ([['Watches', 'watches', 'Gold, rose gold and leather watches', 'wt-rose-link'], ['Home', 'home', 'Bedding, cushions, lamps and decor', 'hm-blush-bedding']] as $c) {
        $ci->execute([$c[0], $c[1], $c[2], 'assets/img/p/' . $c[3] . '.webp', 0]); $ids[$c[1]] = (int)$pdo->lastInsertId();
    }
    // shop order like the Nykaa Fashion photo row
    $order = ['westernwear', 'ethnic', 'men', 'kids', 'home', 'footwear', 'bags', 'activewear', 'jewellery', 'sneakers', 'watches', 'eyewear'];
    $so = $pdo->prepare('UPDATE categories SET sort=? WHERE slug=?');
    foreach ($order as $i => $sl) $so->execute([$i + 1, $sl]);
    $P = [
        ['Rose Gold Minimal Watch', 'watches', 'wt-rose-minimal', 2490, 3290, 'Rose Gold / Taupe', 'Clean white dial with a soft leather strap.', "A slim rose gold watch with a clean white dial and a soft taupe leather strap. Light on the wrist and right for office, campus and dinners.\n\nQuartz movement, 36 mm case, splash resistant.", 1, 1, 1],
        ['Ivory Leather Strap Watch', 'watches', 'wt-ivory-leather', 2290, 2990, 'Ivory / Gold', 'A minimal gold watch on an ivory strap.', "A minimal gold-tone watch with a cream dial and an ivory leather strap. Simple, elegant and easy to match with sarees and westernwear.\n\nQuartz movement, 38 mm case.", 0, 0, 1],
        ['Rose Gold Link Bracelet Watch', 'watches', 'wt-rose-link', 3290, 4290, 'Rose Gold', 'Rose gold bracelet watch with a white dial.', "A rose gold link bracelet watch with a bright white dial and slim markers. A lovely gift for Eid, birthdays and weddings.\n\nQuartz movement, 32 mm case, adjustable links.", 1, 1, 0],
        ['Navy Dial Gold Watch', 'watches', 'wt-navy-gold', 3490, 4490, 'Gold / Navy', 'Deep navy dial on a gold bracelet.', "A gold bracelet watch with a deep navy sunray dial and a small seconds dial. Looks rich with festive and evening outfits.\n\nQuartz movement, 38 mm case.", 1, 0, 0],
        ['Classic Tan Leather Watch', 'watches', 'wt-classic-tan', 2790, 3590, 'Silver / Tan', 'Everyday watch with a tan leather strap.', "A classic everyday watch with a white dial, gold-tone hands and a tan leather strap. Smart with shirts and suits, easy with tees.\n\nQuartz movement, 40 mm case.", 0, 1, 0],
        ['Blush Pillow Bedding Set', 'home', 'hm-blush-bedding', 4490, 5990, 'White / Blush', 'Soft white bedding with blush cushions.', "A soft cotton bedding set with a white duvet cover, two pillow covers and blush embroidered cushion covers. Makes any bedroom look calm and fresh.", 1, 1, 1],
        ['Cloud White Comforter Set', 'home', 'hm-white-comforter', 3990, 4990, 'White', 'Light, airy comforter with pillow covers.', "A light, breathable comforter with two matching pillow covers. Soft enough for summer nights and warm enough for winter.", 0, 1, 0],
        ['Ikat Cushion Covers (Set of 4)', 'home', 'hm-ikat-cushions', 1290, 1690, 'Multi', 'Bright ikat and zigzag cushion covers.', "Four cotton cushion covers in bright ikat, zigzag and dot prints with hidden zips. Instantly cheers up a sofa or bed.\n\nSize 16 x 16 inch, covers only.", 1, 0, 1],
        ['Velvet Round Cushions (Set of 3)', 'home', 'hm-velvet-cushions', 1890, 2490, 'Teal, Navy, Magenta', 'Plush jewel-tone velvet cushions.', "Three round velvet cushions in teal, navy and magenta with a pleated centre. Soft, plush and perfect for festive decor.", 0, 0, 0],
        ['Black Ceramic Table Lamp', 'home', 'hm-pot-lamp', 2490, 3190, 'Black / Amber', 'Glossy ceramic base with a warm shade.', "A rounded black ceramic lamp with an amber fabric shade that gives a warm, cosy glow. Great for bedside tables and living rooms.", 1, 0, 0],
        ['Pineapple Ceramic Lamp', 'home', 'hm-pineapple-lamp', 1990, 2590, 'Ivory', 'Cute pineapple-shaped bedside lamp.', "An ivory ceramic lamp shaped like a pineapple with a pleated shade. A fun, soft light for bedrooms and reading corners.", 0, 1, 0],
        ['Nordic Donut Vase Pair', 'home', 'hm-donut-vases', 1490, 1990, 'Cream', 'Two cream ceramic donut vases.', "A pair of matte cream ceramic vases with a modern donut shape. Lovely with fresh or dried flowers on a shelf or table.", 0, 1, 1],
        ['Copper Ornate Wall Mirror', 'home', 'hm-copper-mirror', 2890, 3790, 'Copper', 'Round mirror with a carved copper frame.', "A round wall mirror with an ornate carved frame in a warm copper finish. Adds light and charm to entrances and bedrooms.\n\nDiameter 60 cm.", 1, 0, 0],
        ['Ivory Gold Area Rug', 'home', 'hm-fringe-rug', 5490, 6990, 'Ivory / Gold', 'Soft rug with a classic gold pattern.', "A soft woven area rug in ivory with a classic gold medallion pattern and fringe ends. Easy to clean and made for living rooms.\n\nSize 5 x 7 ft.", 0, 0, 0],
        ['Smoked Glass Vase with Dried Grass', 'home', 'hm-glass-vase', 1190, 1490, 'Smoke Grey', 'Tall smoked glass vase with pampas grass.', "A tall smoked glass bottle vase with a bunch of natural dried grass. Needs no water and looks calm on any shelf.", 0, 0, 1],
    ];
    $pi = $pdo->prepare('INSERT INTO products(name,slug,category_id,brand,price,old_price,images,shades,sizes,size,stock,short,description,how_to,featured,bestseller,is_new,sort,sold,created,updated) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $sizes = ['hm-blush-bedding' => 'Double, King', 'hm-white-comforter' => 'Double, King', 'hm-fringe-rug' => '4 x 6 ft, 5 x 7 ft'];
    foreach ($P as $i => $p) {
        $pi->execute([$p[0], slugify($p[0]), $ids[$p[1]], 'Loom & Lustre', $p[3], $p[4], json_encode(['assets/img/p/' . $p[2] . '.webp']), $p[5], $sizes[$p[2]] ?? '', '', 20, $p[6], $p[7], '', $p[8], $p[9], $p[10], 200 + $i, $p[9] ? 40 - $i : 3, $now, $now]);
    }
    $ix = $pdo->prepare('INSERT OR IGNORE INTO col_items(collection_id, product_id) SELECT c.id, p.id FROM collections c, products p WHERE c.slug=? AND p.slug=?');
    foreach ([['eid-edit', 'navy-dial-gold-watch'], ['eid-edit', 'velvet-round-cushions-set-of-3'], ['wedding-season', 'rose-gold-link-bracelet-watch'], ['office-ready', 'classic-tan-leather-watch'], ['weekend-casual', 'rose-gold-minimal-watch'], ["stylists-picks", 'rose-gold-minimal-watch'], ['luxe-edit', 'copper-ornate-wall-mirror']] as $x) $ix->execute($x);
    $pdo->exec("UPDATE products SET flash_sale=1 WHERE slug IN ('rose-gold-link-bracelet-watch','blush-pillow-bedding-set')");
    ll_seed_v16($pdo);
    ll_seed_v17($pdo);
}

/** v1.6: the Luxe store has its own categories (slug starts with lx-) and its own products. */
function ll_seed_v16(PDO $pdo): void {
    if ($pdo->query("SELECT 1 FROM categories WHERE slug='lx-bags'")->fetchColumn()) return;
    $now = date('Y-m-d H:i:s');
    $ci = $pdo->prepare('INSERT INTO categories(name,slug,tagline,image,sort) VALUES(?,?,?,?,?)');
    $ids = [];
    foreach ([
        ['Designer Bags', 'lx-bags', 'Totes, clutches and top handles', 'lx-bag-chestnut-doctor'],
        ['Fine Jewellery', 'lx-jewellery', 'Diamonds, gold and statement earrings', 'lx-jw-cascade-drops'],
        ['Luxury Watches', 'lx-watches', 'Crystal, gold and rose gold', 'lx-wt-emerald'],
        ['Bridal Couture', 'lx-bridal', 'Lehengas and silk for the big day', 'lx-br-zardozi-red'],
        ['Evening Gowns', 'lx-gowns', 'Ball gowns, sequins and tulle', 'lx-gw-emerald-sequin'],
        ['Designer Heels', 'lx-heels', 'Glitter, crystal and stilettos', 'lx-hl-crystal-mesh'],
        ['Luxury Sunglasses', 'lx-eyewear', 'Aviators, cat-eyes and navigators', 'lx-sg-gold-aviator'],
    ] as $i => $c) { $ci->execute([$c[0], $c[1], $c[2], 'assets/img/p/' . $c[3] . '.webp', 100 + $i]); $ids[$c[1]] = (int)$pdo->lastInsertId(); }
    $P = [
        ['Chestnut Leather Doctor Bag', 'lx-bags', 'lx-bag-chestnut-doctor', 18900, 22900, 'Chestnut', 'Polished full-grain leather with twin buckles.', "A structured doctor bag in polished chestnut leather with twin buckle straps, a roomy lined inside and a detachable shoulder strap.\n\nFull-grain leather, gold-tone hardware, dust bag included.", 1, 1, 1],
        ['Cognac Hand-stitched Tote', 'lx-bags', 'lx-bag-cognac-tote', 14500, 16900, 'Cognac', 'Soft crazy-horse leather that ages beautifully.', "A large open tote in soft cognac crazy-horse leather with hand-stitched handles. Fits a laptop, a scarf and everything for the day.\n\nFull-grain leather, inner zip pocket.", 1, 0, 1],
        ['Blush Quilted Top Handle Bag', 'lx-bags', 'lx-bag-blush-quilted', 12900, 15900, 'Blush Pink', 'Quilted flap bag with a gold drop clasp.', "A blush pink top handle bag with a quilted flap, a gold drop clasp and a long strap for cross-body wear.\n\nPU-free genuine leather, suede lining.", 1, 1, 0],
        ['Tan Pebble Leather Hobo', 'lx-bags', 'lx-bag-tan-hobo', 16900, 19900, 'Tan / Espresso', 'Slouchy hobo with a sculpted gold clasp.', "A slouchy hobo bag in tan pebble leather with espresso handles and a sculpted gold clasp. Soft, light and made for everyday luxury.", 0, 1, 1],
        ['Powder Blue Saffiano Satchel', 'lx-bags', 'lx-bag-powder-satchel', 13900, 0, 'Powder Blue', 'Saffiano leather with a silver turn lock.', "A neat satchel in powder blue saffiano leather with a silver turn lock and drawstring sides. Smart for brunches and office days.", 0, 0, 1],
        ['Ivory Pleated Pouch Bag', 'lx-bags', 'lx-bag-ivory-pleated', 11500, 0, 'Ivory', 'Sculpted pleats and a curved handle.', "A sculptural pouch bag in ivory with soft pleats and a curved top handle. A modern art piece for weddings and evenings.", 0, 0, 1],
        ['White Quilted Chain Clutch', 'lx-bags', 'lx-clutch-quilted-chain', 9900, 12500, 'White', 'Puffy quilted clutch on a silver chain.', "A puffy white quilted clutch on a silver chain strap. Pairs with red, black and pastels for parties and receptions.", 1, 0, 0],
        ['Pearl Beaded Evening Clutch', 'lx-bags', 'lx-clutch-pearl', 8900, 10900, 'Ivory / Silver', 'Hand-beaded clutch for weddings.', "A hand-beaded ivory clutch with a soft shimmer, a magnetic flap and a hidden chain. Made for walimas, receptions and holud nights.", 0, 1, 0],
        ['Silver Quilted Minaudiere', 'lx-bags', 'lx-clutch-silver', 7900, 9500, 'Silver', 'Hard-case quilted clutch with a kiss lock.', "A silver quilted hard-case clutch with a kiss-lock clasp. Holds your phone, lipstick and keys in style.", 0, 0, 0],
        ['Espresso Leather Clutch', 'lx-bags', 'lx-clutch-espresso', 6900, 0, 'Espresso', 'Slim zip clutch with a wrist loop.', "A slim espresso leather zip clutch with a side wrist loop. Unisex, minimal and perfect for evenings out.", 0, 0, 0],
        ['Cascade Diamond Drop Earrings', 'lx-jewellery', 'lx-jw-cascade-drops', 24900, 29900, 'Silver / Crystal', 'Long cascade of sparkling stones.', "Long chandelier earrings with a cascade of brilliant-cut stones set in rhodium-plated silver. A red-carpet piece for receptions.", 1, 1, 1],
        ['Laurel Crystal Earrings', 'lx-jewellery', 'lx-jw-laurel-earrings', 15900, 18900, 'Silver / Crystal', 'Wreath-shaped earrings in marquise stones.', "Laurel wreath earrings made of marquise-cut stones in silver. Elegant with sarees, gowns and lehengas.", 1, 0, 1],
        ['Hexagon Diamond Studs', 'lx-jewellery', 'lx-jw-hex-studs', 19900, 0, 'Gold / Diamond', 'Hexagon halo studs in gold.', "Hexagon halo studs with a round centre stone in 18k gold-plated silver. Small, brilliant and right for every day.", 0, 1, 0],
        ['Solitaire Pendant and Ring Set', 'lx-jewellery', 'lx-jw-solitaire', 27900, 32900, 'Silver / Diamond', 'A single brilliant stone on a fine chain, with matching ring.', "A fine chain with a single solitaire stone and a matching ring. A timeless gift for anniversaries and engagements.", 1, 1, 0],
        ['Gold Diamond Bar Pendant', 'lx-jewellery', 'lx-jw-bar-pendant', 21500, 0, 'Gold', 'Gold bar pendant set with square stones.', "A gold bar pendant set with square-cut stones on a thick rope chain. A bold, modern statement.", 0, 0, 1],
        ['Gold Chunky Chain Bracelet', 'lx-jewellery', 'lx-jw-chain-bracelet', 12900, 14900, 'Gold', 'Polished chunky links with a toggle clasp.', "A chunky gold chain bracelet with polished links and a toggle clasp. Stacks well with watches.", 0, 0, 0],
        ['Stacked Gold Bangle Trio', 'lx-jewellery', 'lx-jw-gold-bangles', 17900, 20900, 'Gold', 'Three slim gold bangles with a nail detail.', "Three slim gold bangles, one with a nail detail, to wear together or apart. 18k gold plated.", 0, 1, 0],
        ['Emerald Crystal Bezel Watch', 'lx-watches', 'lx-wt-emerald', 32900, 38900, 'Gold / Emerald', 'Emerald dial ringed with crystals.', "A gold bracelet watch with a deep emerald sunray dial and a crystal-set bezel. Roman numerals and a fold-over clasp.\n\nQuartz movement, 38 mm case, water resistant.", 1, 1, 1],
        ['Rose Gold Crystal Watch', 'lx-watches', 'lx-wt-rose-crystal', 29900, 34900, 'Rose Gold', 'A dial filled with tiny crystals.', "A rose gold watch with a dial filled with tiny crystals and a soft sunray centre. Sparkles with every move.\n\nQuartz movement, 32 mm case.", 1, 0, 1],
        ['Pearl Dial Gold Mesh Watch', 'lx-watches', 'lx-wt-pearl-mesh', 24500, 0, 'Gold / Pearl', 'Fluted pearl dial on a gold mesh strap.', "A slim gold watch with a fluted pearl dial, crystal markers and a fine mesh strap.\n\nQuartz movement, 34 mm case.", 0, 1, 0],
        ['Red Zardozi Bridal Lehenga', 'lx-bridal', 'lx-br-zardozi-red', 89000, 99000, 'Red / Gold', 'Hand zardozi work with a heavy dupatta.', "A red bridal lehenga with hand zardozi and sequin work, a fitted blouse and a heavy bordered dupatta. Made to order in 3 weeks.", 1, 1, 1],
        ['Crimson Bridal Lehenga Set', 'lx-bridal', 'lx-br-crimson-lehenga', 78000, 89000, 'Crimson', 'Crimson net lehenga with silver embroidery.', "A crimson lehenga with silver and gold embroidery, a sheer net dupatta and a structured blouse.", 1, 0, 1],
        ['Maroon Velvet Bridal Lehenga', 'lx-bridal', 'lx-br-maroon-velvet', 69000, 79000, 'Maroon', 'Rich velvet with stone and thread work.', "A maroon velvet lehenga with stone and thread work and a matching net dupatta. Warm, regal and perfect for winter weddings.", 0, 1, 0],
        ['Royal Red Lehenga Gown', 'lx-bridal', 'lx-br-royal-gown', 72000, 0, 'Red', 'A full-flare gown with a long trail.', "A full-flare red lehenga gown with all-over embroidery and a long dupatta trail. Made for grand entries.", 0, 0, 1],
        ['Hand-painted Silk Saree', 'lx-bridal', 'lx-br-painted-silk', 38000, 42000, 'Blue / Multi', 'Pure silk with hand-painted motifs.', "A pure silk saree with hand-painted motifs in blue, red and gold, with an unstitched blouse piece.", 0, 0, 0],
        ['Emerald Sequin Gown', 'lx-gowns', 'lx-gw-emerald-sequin', 45000, 52000, 'Emerald', 'All-over sequins with a flowing skirt.', "An emerald gown with all-over sequins, a fitted bodice and a flowing skirt. Ready for receptions and gala nights.", 1, 1, 1],
        ['Lilac Embellished Ball Gown', 'lx-gowns', 'lx-gw-lilac-ball', 58000, 0, 'Lilac', 'Hand-embellished tulle ball gown.', "A lilac tulle ball gown with hand-embellished flowers and sparkle. A true couture moment.", 1, 0, 1],
        ['Wine Tulle Petal Gown', 'lx-gowns', 'lx-gw-wine-tulle', 42000, 48000, 'Wine', 'Strapless tulle with 3D petals.', "A strapless wine tulle gown with 3D petal appliques and a dramatic train.", 0, 1, 0],
        ['Powder Blue Ball Gown', 'lx-gowns', 'lx-gw-powder-ball', 39000, 45000, 'Powder Blue', 'Satin ball gown with a full skirt.', "A powder blue satin ball gown with a full skirt and an off-shoulder neckline. Fairy-tale ready.", 0, 0, 0],
        ['Black Ruffle Couture Dress', 'lx-gowns', 'lx-gw-black-ruffle', 34000, 0, 'Black', 'Tiered ruffles with an off-shoulder neck.', "A black couture dress with tiered organza ruffles and an off-shoulder neckline. Bold for parties and shoots.", 0, 0, 1],
        ['Crystal Mesh Pumps', 'lx-heels', 'lx-hl-crystal-mesh', 16900, 19900, 'Silver', 'Sheer mesh set with crystals.', "Pointed pumps in sheer mesh set with crystals, on a 10 cm stiletto heel. Bridal and party favourite.", 1, 1, 1],
        ['Silver Glitter Peep-toe Heels', 'lx-heels', 'lx-hl-silver-glitter', 12900, 14900, 'Silver', 'Glitter platforms with a peep toe.', "Silver glitter peep-toe platforms with a padded insole. Sparkle from day to night.", 0, 1, 0],
        ['Violet Glitter Stilettos', 'lx-heels', 'lx-hl-violet', 13900, 0, 'Violet', 'Pointed glitter stilettos.', "Pointed violet glitter stilettos with a sleek 9 cm heel. Statement shoes for parties.", 0, 0, 1],
        ['Gold Rimless Aviators', 'lx-eyewear', 'lx-sg-gold-aviator', 11900, 13900, 'Gold / Brown', 'Gold metal frame with gradient lenses.', "Classic aviators with a gold metal frame and brown gradient lenses. UV400 protection, case included.", 1, 1, 0],
        ['Blush Cat-eye Sunglasses', 'lx-eyewear', 'lx-sg-blush-cateye', 9900, 11900, 'Blush / Gold', 'Soft cat-eye frame with gold arms.', "Cat-eye sunglasses with a blush frame, thin gold arms and smoky lenses. UV400 protection.", 0, 1, 1],
        ['Tortoise Navigator Sunglasses', 'lx-eyewear', 'lx-sg-tortoise', 10900, 0, 'Tortoise / Brown', 'Bold navigator with a double bridge.', "A bold tortoise navigator with a double bridge and brown gradient lenses. UV400 protection.", 0, 0, 0],
    ];
    $pi = $pdo->prepare('INSERT INTO products(name,slug,category_id,brand,price,old_price,images,shades,sizes,size,stock,short,description,how_to,featured,bestseller,is_new,sort,sold,created,updated) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $sizes = ['lx-bridal' => 'S, M, L, XL', 'lx-gowns' => 'S, M, L, XL', 'lx-heels' => '36, 37, 38, 39, 40'];
    foreach ($P as $i => $p) {
        $pi->execute([$p[0], slugify($p[0]), $ids[$p[1]], 'Loom & Lustre Luxe', $p[3], $p[4], json_encode(['assets/img/p/' . $p[2] . '.webp']), $p[5], $sizes[$p[1]] ?? '', '', 8, $p[6], str_replace('\n', "\n", $p[7]), '', $p[8], $p[9], $p[10], 300 + $i, $p[9] ? 18 - ($i % 9) : 2, $now, $now]);
    }
}

// v1.7: more fashion products with model photos
function ll_seed_v17(PDO $pdo): void {
    if ($pdo->query("SELECT 1 FROM products WHERE images LIKE '%wst-black-tiered-gown%'")->fetchColumn()) return;
    $now = date('Y-m-d H:i:s');
    $ids = [];
    foreach ($pdo->query('SELECT id, slug FROM categories') as $r) $ids[$r['slug']] = (int)$r['id'];
    $W = 'S, M, L, XL'; $M = '40, 41, 42, 43, 44'; $K = '2-3 Y, 4-5 Y, 6-7 Y'; $F = '36, 37, 38, 39, 40'; $A = 'XS, S, M, L, XL';
    // name, cat, img, price, old, colour, sizes, short, description, featured, bestseller, new
    $P = [
        ['Black Tiered Ruffle Gown', 'westernwear', 'wst-black-tiered-gown', 7490, 8990, 'Black', $W, 'Off-shoulder gown with tiered ruffles.', 'A dramatic black gown with an off-shoulder neckline, a fitted waist and layers of soft ruffles that flow to the floor. Made for receptions and parties.', 1, 1, 1],
        ['Red Drape Slit Dress', 'westernwear', 'wst-red-drape-dress', 4990, 5990, 'Red', $W, 'Long-sleeve dress with a flowing side drape.', 'A red long-sleeve mini dress with a high neck and a long flowing side drape. Bold, elegant and made to be noticed.', 1, 0, 1],
        ['Maroon Embroidered Anarkali Gown', 'ethnic', 'eth-red-anarkali', 6890, 7990, 'Maroon', $W, 'Floor-length flare with gold embroidery.', 'A maroon anarkali gown with a full flare, gold embroidered sleeves and a fitted bodice. Perfect for weddings and Eid.', 1, 1, 1],
        ['Mustard Paisley Lehenga Set', 'ethnic', 'eth-mustard-lehenga', 12900, 14900, 'Mustard / Maroon', $W, 'Paisley print lehenga with a velvet dupatta.', 'A mustard lehenga with rich paisley work, a matching blouse and a maroon velvet dupatta with a golden border.', 1, 1, 0],
        ['Emerald Layered Lehenga', 'ethnic', 'eth-emerald-lehenga', 9890, 11500, 'Emerald', $W, 'Layered flare with an embroidered blouse.', 'An emerald green lehenga with soft layered flare and a hand-embroidered long-sleeve blouse. Light to wear, rich to look at.', 0, 1, 1],
        ['Red Bridal Lehenga with Veil', 'ethnic', 'eth-bridal-red-lehenga', 18900, 22900, 'Red / Gold', $W, 'Heavy zari bridal lehenga with a double dupatta.', 'A red bridal lehenga with heavy zari and thread work, a matching blouse and two dupattas, one for the head and one for the shoulder.', 1, 0, 1],
        ['Ivory Gold Lehenga Set', 'ethnic', 'eth-ivory-lehenga', 10900, 12900, 'Ivory / Gold', $W, 'Ivory silk with gold butti and a sheer dupatta.', 'An ivory lehenga set in soft silk with gold butti, a sweetheart blouse and a sheer dupatta. Perfect for holud and engagement.', 0, 1, 0],
        ['Printed Kurta with Green Sharara', 'ethnic', 'eth-green-sharara', 3490, 3990, 'Multi / Green', $W, 'Printed kurta, flared sharara and dupatta.', 'A colourful printed kurta paired with a bright green flared sharara and a matching dupatta. Fresh for Eid and day functions.', 0, 0, 1],
        ['White Chikan Straight Kurti', 'ethnic', 'eth-white-chikan-kurti', 2290, 2690, 'White', $W, 'Chikan embroidery on soft cotton.', 'A straight white kurti with all-over chikan embroidery on breathable cotton, with a sheer dupatta. Cool and graceful for summer.', 0, 1, 0],
        ['Red Kurta Set with Floral Dupatta', 'ethnic', 'eth-red-kurta-set', 2890, 3390, 'Red', $W, 'Red kurta, pants and a printed dupatta.', 'A red cotton kurta with neck embroidery, straight pants and a soft floral printed dupatta. Easy festive wear.', 0, 0, 1],
        ['White Shirt with Suspender Trousers', 'men', 'men-suspender-shirt', 3990, 4590, 'White / Black', $M, 'Crisp shirt and black trousers with braces.', 'A crisp white shirt with black slim trousers and adjustable braces. A smart vintage look for parties and shoots.', 0, 0, 1],
        ['Black Slim Fit Suit', 'men', 'men-black-suit', 11900, 13900, 'Black', $M, 'Two-piece suit with a slim cut.', 'A black two-piece suit with a slim notch-lapel jacket and flat-front trousers. Ready for weddings, receptions and office events.', 1, 1, 0],
        ['Grey Textured Blazer', 'men', 'men-grey-blazer', 5990, 6990, 'Grey', $M, 'Single-breasted blazer in a soft weave.', 'A grey single-breasted blazer in a soft textured weave. Wear with a white tee or shirt and dark jeans.', 0, 1, 1],
        ['Brown Waistcoat Set', 'men', 'men-brown-waistcoat', 4490, 5290, 'Brown / Grey', $M, 'Waistcoat with check trousers and a tie.', 'A brown waistcoat paired with grey check trousers. Add a white shirt and a paisley tie for a classic gentleman look.', 0, 0, 1],
        ['Black Shirt and Waistcoat', 'men', 'men-black-waistcoat', 3790, 4290, 'Black', $M, 'All-black shirt with a fitted waistcoat.', 'An all-black look with a button-up shirt and a fitted waistcoat. Sharp for evenings and dinners.', 0, 0, 0],
        ['Brown Leather Biker Jacket', 'men', 'men-leather-jacket', 7490, 8990, 'Brown', $M, 'Soft leather jacket with a band collar.', 'A brown leather jacket with a band collar, zip pockets and a slim fit. Pairs with jeans and boots in winter.', 1, 1, 0],
        ['Fruit Print Dress with Sun Hat', 'kids', 'kd-fruit-dress', 1390, 1590, 'White / Multi', $K, 'Sleeveless cotton dress with a straw hat.', 'A sleeveless cotton dress with a happy fruit print and a matching straw sun hat. Cool for summer days out.', 0, 1, 1],
        ['Boys Bomber Jacket Set', 'kids', 'kd-boy-bomber', 1990, 2390, 'Beige / Brown', $K, 'Bomber jacket, stripe tee and joggers.', 'A soft beige bomber jacket with a striped tee and brown joggers. A smart and comfy set for little boys.', 1, 0, 1],
        ['Fox Hood Rain Jacket', 'kids', 'kd-fox-jacket', 1690, 1990, 'Orange', $K, 'Water-resistant jacket with fox ears.', 'A bright orange water-resistant jacket with a fox face hood and reflective stripes. Fun and warm for rainy days.', 0, 0, 1],
        ['Navy Suede Court Heels', 'footwear', 'fw-navy-suede-pumps', 2790, 3290, 'Navy', $F, 'Soft suede pumps with a tan heel.', 'Classic navy suede court shoes with a tan block-stiletto heel and a padded insole. Office to evening.', 0, 1, 1],
        ['Black Patent Stilettos', 'footwear', 'fw-patent-stilettos', 2990, 3490, 'Black', $F, 'Glossy pointed stilettos.', 'Glossy black patent stilettos with a pointed toe and a slim 10 cm heel. A wardrobe essential for parties.', 1, 1, 0],
        ['Gold Cage Heeled Sandals', 'footwear', 'fw-gold-cage-heels', 3290, 3890, 'Gold', $F, 'Metallic lattice straps on a high heel.', 'Gold metallic sandals with a lattice cage upper and a high stiletto heel. Shine at weddings and receptions.', 1, 0, 1],
        ['Nude Bow Strap Heels', 'footwear', 'fw-nude-bow-sandals', 2590, 2990, 'Nude', $F, 'Ankle strap sandals with a jewelled bow.', 'Nude ankle strap sandals with a soft jewelled bow on the toe and a slim heel. Pretty with dresses and sarees.', 0, 1, 0],
        ['Pointed Black Court Heels', 'footwear', 'fw-pointed-court-heels', 2390, 2790, 'Black', $F, 'Matte pointed pumps for every day.', 'Matte black pointed court heels with a cushioned insole. Smart for office, easy for evenings.', 0, 0, 0],
        ['Black Strappy Heeled Sandals', 'footwear', 'fw-black-strappy-sandals', 2190, 2590, 'Black', $F, 'Thin straps and an ankle buckle.', 'Minimal black sandals with thin straps, an ankle buckle and a stiletto heel. Goes with everything.', 0, 0, 1],
        ['White Frame Top Handle Bag', 'bags', 'bag-white-frame', 3490, 3990, 'White', '', 'Structured bag with a gold clasp.', 'A white structured top handle bag with a gold frame clasp and a detachable strap. Fresh and classy.', 0, 1, 1],
        ['Teal Leather Bucket Tote', 'bags', 'bag-teal-tote', 3890, 4490, 'Teal / Tan', '', 'Two-way tote with tan straps.', 'A teal leather tote with contrast tan handles and a long shoulder strap. Carry it by hand or on the shoulder.', 1, 0, 1],
        ['Tan Mini Top Handle Bag', 'bags', 'bag-tan-mini', 2790, 3290, 'Tan', '', 'Small bag with a chain strap.', 'A small tan leather bag with a top handle and a gold chain strap. Holds your phone, cards and lipstick.', 0, 0, 1],
        ['Wine Croc Effect Handbag', 'bags', 'bag-wine-croc', 4290, 4990, 'Wine', '', 'Croc-texture handbag with a gold lock.', 'A wine red handbag with a croc-effect finish, a top handle and a polished gold lock. Rich for evenings and weddings.', 1, 1, 0],
        ['Burgundy Leather Doctor Bag', 'bags', 'bag-brown-doctor', 4990, 5690, 'Burgundy', '', 'Washed leather with a front pocket.', 'A burgundy washed-leather doctor bag with twin handles, a front pocket and a shoulder strap. Roomy for work and travel.', 0, 1, 0],
        ['Sage Sports Bra and Leggings Set', 'activewear', 'act-sage-set', 2690, 3190, 'Sage', $A, 'Soft stretch set for yoga and gym.', 'A sage green sports bra and high-waist leggings in soft, sweat-wicking stretch fabric. For yoga, gym and everyday comfort.', 0, 1, 1],
        ['Black Yoga Tank and Leggings', 'activewear', 'act-black-yoga', 2390, 2790, 'Black', $A, 'Crop tank and full-length leggings.', 'A black crop tank with matching full-length leggings that stretch with every move. Squat-proof and breathable.', 0, 0, 1],
        ['Gemstone Ring Hoop Earrings', 'jewellery', 'jw-gem-hoop', 1490, 1790, 'Gold / Multi', '', 'Gold hoops set with colourful stones.', 'Statement round hoop earrings set with colourful gemstones in gold tone. Brightens any outfit.', 1, 1, 1],
        ['Marble Resin Hoop Earrings', 'jewellery', 'jw-marble-hoops', 790, 990, 'Grey / White', '', 'Light resin hoops with a marble pattern.', 'Light marble-pattern resin hoops on silver posts. Modern, fun and easy to wear all day.', 0, 0, 1],
        ['Gold Ear Stack Set', 'jewellery', 'jw-ear-stack', 1190, 1390, 'Gold', '', 'Studs, huggies and a pearl drop.', 'A set of tiny gold studs, huggies and a pearl drop to stack on one ear. Minimal and chic.', 0, 1, 0],
        ['Black Chain Link Drop Earrings', 'jewellery', 'jw-chain-drops', 990, 1190, 'Black', '', 'Long glossy link drops.', 'Long glossy black chain link earrings that make a strong, modern statement with strapless and one-shoulder looks.', 0, 0, 1],
        ['Black Cat-eye Sunglasses', 'eyewear', 'sg-black-cateye', 1390, 1690, 'Black', '', 'Sharp cat-eye with dark lenses.', 'Black cat-eye sunglasses with dark lenses and UV400 protection. A timeless glam look.', 1, 1, 0],
        ['Bold Oversized Cat-eye', 'eyewear', 'sg-bold-cateye', 1590, 1890, 'Black', '', 'Thick frame with a wing tip.', 'Oversized cat-eye sunglasses with a thick black frame and lifted wing tips. UV400 protection.', 0, 1, 1],
        ['Gold Mirror Half-rim Sunglasses', 'eyewear', 'sg-gold-mirror', 1490, 1790, 'Gold / Mirror', '', 'Half-rim frame with mirror lenses.', 'Gold half-rim sunglasses with a silver mirror finish lens. UV400 protection, case included.', 0, 0, 1],
        ['Retro Oval Sunglasses', 'eyewear', 'sg-retro-oval', 1190, 1390, 'Black', '', 'Slim oval frame with a 90s feel.', 'Slim black oval sunglasses with a 90s retro feel. Light, comfortable and UV400 protected.', 0, 0, 1],
        ['Oversized Square Sunglasses', 'eyewear', 'sg-pink-oversize', 1290, 1490, 'Brown', '', 'Big square frame with brown lenses.', 'Oversized square sunglasses with brown gradient lenses and a slim metal bridge. UV400 protection.', 0, 1, 0],
        ['Navy Canvas Low-top Sneakers', 'sneakers', 'snk-navy-canvas', 1590, 1890, 'Navy', $F, 'Classic canvas with a white sole.', 'Navy canvas low-top sneakers with a white rubber sole and cotton laces. Everyday comfort with jeans.', 0, 1, 1],
        ['Black Canvas Sneakers', 'sneakers', 'snk-black-canvas', 1490, 1790, 'Black', $F, 'Black canvas with a contrast toe cap.', 'Black canvas sneakers with a white toe cap and a cushioned sole. Pair with fun socks for a street style look.', 0, 0, 1],
    ];
    $pi = $pdo->prepare('INSERT INTO products(name,slug,category_id,brand,price,old_price,images,shades,sizes,size,stock,short,description,how_to,featured,bestseller,is_new,sort,sold,created,updated) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    foreach ($P as $i => $p) {
        if (empty($ids[$p[1]])) continue;
        $pi->execute([$p[0], slugify($p[0]), $ids[$p[1]], 'Loom & Lustre', $p[3], $p[4], json_encode(['assets/img/p/' . $p[2] . '.webp']), $p[5], $p[6], '', 15, $p[7], $p[8], '', $p[9], $p[10], $p[11], 5 + $i, $p[10] ? 24 - ($i % 11) : 3, $now, $now]);
    }
}
