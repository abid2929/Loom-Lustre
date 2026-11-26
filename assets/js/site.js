/* Loom & Lustre storefront script: slider, bag, wishlist, checkout */
(function () {
  'use strict';
  var B = (window.LL && LL.base) || '/', CUR = (window.LL && LL.cur) || '৳';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var money = function (n) { return CUR + Number(n || 0).toLocaleString('en-US'); };
  var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
  function load(k) { try { return JSON.parse(localStorage.getItem(k) || '[]') || []; } catch (e) { return []; } }
  function save(k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) {} }

  /* ---------- toast ---------- */
  var tt;
  function toast(html) {
    var t = $('#toast'); if (!t) return;
    t.innerHTML = html; t.classList.add('show');
    clearTimeout(tt); tt = setTimeout(function () { t.classList.remove('show'); }, 3200);
  }

  /* ---------- bag ---------- */
  function cart() { return load('ll_cart'); }
  function setCart(c) { save('ll_cart', c); counts(); }
  function px(ev, d, o) { try { if (window.fbq) fbq('track', ev, d || {}, o || {}); } catch (e) {} }
  function addToCart(id, qty, shade) {
    px('AddToCart', { content_ids: [String(id)], content_type: 'product', currency: 'BDT' });
    var c = cart(); id = +id; qty = Math.max(1, +qty || 1); shade = shade || '';
    var f = c.filter(function (i) { return i.id === id && (i.shade || '') === shade; })[0];
    if (f) f.qty = Math.min(20, f.qty + qty); else c.push({ id: id, qty: Math.min(20, qty), shade: shade });
    setCart(c);
  }
  function counts() {
    var n = cart().reduce(function (a, i) { return a + i.qty; }, 0), w = load('ll_wish').length;
    $$('[data-cart-count]').forEach(function (e) { e.textContent = n; e.hidden = !n; });
    $$('[data-wish-count]').forEach(function (e) { e.textContent = w; e.hidden = !w; });
    var wl = load('ll_wish');
    $$('[data-wish]').forEach(function (b) {
      var on = wl.indexOf(+b.getAttribute('data-wish')) > -1;
      b.classList.toggle('on', on);
      var s = b.querySelector('span'); if (s) s.textContent = on ? 'Saved to wishlist' : 'Add to wishlist';
    });
  }
  function fetchProducts(ids) {
    if (!ids.length) return Promise.resolve([]);
    if (LL.demo) return fetch(B + 'api/products.json').then(function (r) { return r.json(); }).then(function (all) { return all.filter(function (p) { return ids.indexOf(p.id) > -1; }); });
    return fetch(B + 'api/products?ids=' + ids.join(','), { headers: { Accept: 'application/json' } }).then(function (r) { return r.json(); });
  }

  /* ---------- announcement rotation ---------- */
  var an = $('.announce');
  if (an) {
    var msgs = []; try { msgs = JSON.parse(an.getAttribute('data-msgs') || '[]'); } catch (e) {}
    if (msgs.length > 1) {
      var ai = 0, sp = an.querySelector('span');
      setInterval(function () { ai = (ai + 1) % msgs.length; sp.style.opacity = 0; setTimeout(function () { sp.textContent = msgs[ai]; sp.style.opacity = 1; }, 400); }, 3800);
    }
  }

  /* ---------- drawer ---------- */
  $$('[data-open]').forEach(function (b) {
    b.addEventListener('click', function () { var d = document.getElementById(b.getAttribute('data-open')); d.hidden = false; document.body.style.overflow = 'hidden'; });
  });
  $$('[data-close]').forEach(function (b) {
    b.addEventListener('click', function () { var d = b.closest('.drawer'); d.hidden = true; document.body.style.overflow = ''; });
  });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { var d = $('.drawer:not([hidden])'); if (d) { d.hidden = true; document.body.style.overflow = ''; } } });

  /* ---------- hero slider (auto, swipe, dots) ---------- */
  var sl = $('[data-slider]');
  if (sl) {
    var ss = $$('.slide', sl), dots = $$('.dots button'), cur = 0, timer;
    var go = function (n) {
      cur = (n + ss.length) % ss.length;
      ss.forEach(function (s, i) { s.classList.toggle('on', i === cur); });
      dots.forEach(function (d, i) { d.classList.toggle('on', i === cur); });
    };
    var play = function () { clearInterval(timer); if (ss.length > 1) timer = setInterval(function () { go(cur + 1); }, 5000); };
    dots.forEach(function (d, i) { d.addEventListener('click', function () { go(i); play(); }); });
    var pv = $('.sl-btn.prev'), nx = $('.sl-btn.next');
    if (pv) pv.addEventListener('click', function () { go(cur - 1); play(); });
    if (nx) nx.addEventListener('click', function () { go(cur + 1); play(); });
    var x0 = null, moved = false;
    sl.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; moved = false; }, { passive: true });
    sl.addEventListener('touchend', function (e) {
      if (x0 === null) return; var dx = e.changedTouches[0].clientX - x0; x0 = null;
      if (Math.abs(dx) > 40) { moved = true; go(cur + (dx < 0 ? 1 : -1)); play(); }
    });
    sl.addEventListener('click', function (e) { if (moved) { e.preventDefault(); moved = false; } }, true);
    play();
  }

  /* ---------- card buttons ---------- */
  document.addEventListener('click', function (e) {
    var a = e.target.closest('[data-add]');
    if (a) { addToCart(a.getAttribute('data-add'), 1, ''); toast('Added to your bag <a href="' + B + 'cart">View bag</a>'); return; }
    var w = e.target.closest('[data-wish]');
    if (w) {
      var id = +w.getAttribute('data-wish'), wl = load('ll_wish'), i = wl.indexOf(id);
      if (i > -1) { wl.splice(i, 1); toast('Removed from wishlist'); } else { wl.push(id); toast('Saved to wishlist <a href="' + B + 'wishlist">View</a>'); }
      save('ll_wish', wl); counts();
      if (document.getElementById('wishList') && i > -1) renderWish();
    }
  });

  /* ---------- product page ---------- */
  var info = $('[data-product]');
  if (info) {
    var pid = info.getAttribute('data-product'), qty = $('#qty'), shade = '', pick = { s: '', z: '' };
    function variant() { shade = [pick.s, pick.z].filter(Boolean).join(' · '); }
    var sb = $('.shades button.on[data-shade]'); if (sb) pick.s = sb.getAttribute('data-shade');
    var zb = $('.sizes button.on'); if (zb) pick.z = zb.getAttribute('data-size');
    variant();
    $$('.shades button[data-shade]').forEach(function (b) {
      b.addEventListener('click', function () {
        $$('.shades button[data-shade]').forEach(function (x) { x.classList.remove('on'); }); b.classList.add('on');
        pick.s = b.getAttribute('data-shade'); $('#shadeName').textContent = pick.s; variant();
        var im = b.getAttribute('data-img'); if (im) { $('#gMain').src = im; $$('.g-thumbs button').forEach(function (x) { x.classList.toggle('on', x.getAttribute('data-img') === im); }); }
      });
    });
    $$('.sizes button').forEach(function (b) {
      b.addEventListener('click', function () {
        $$('.sizes button').forEach(function (x) { x.classList.remove('on'); }); b.classList.add('on');
        pick.z = b.getAttribute('data-size'); $('#sizeName').textContent = pick.z; variant();
      });
    });
    $$('[data-q]').forEach(function (b) {
      b.addEventListener('click', function () { var v = (+qty.value || 1) + (+b.getAttribute('data-q')); qty.value = Math.max(1, Math.min(+qty.max || 20, v)); });
    });
    $$('[data-add-pdp]').forEach(function (b) { b.addEventListener('click', function () { addToCart(pid, qty.value, shade); toast('Added to your bag <a href="' + B + 'cart">View bag</a>'); }); });
    $$('[data-buy-now]').forEach(function (b) { b.addEventListener('click', function () { addToCart(pid, qty.value, shade); location.href = B + 'checkout'; }); });
    $$('.g-thumbs button').forEach(function (b) {
      b.addEventListener('click', function () { $$('.g-thumbs button').forEach(function (x) { x.classList.remove('on'); }); b.classList.add('on'); $('#gMain').src = b.getAttribute('data-img'); });
    });
  }

  /* ---------- bag page ---------- */
  function lineHtml(it, p, i) {
    return '<div class="ci"><a href="' + p.url + '"><img src="' + esc(p.img) + '" alt="" width="96" height="96"></a>' +
      '<div><h3><a href="' + p.url + '">' + esc(p.name) + '</a></h3><small>' + (it.shade ? 'Shade: ' + esc(it.shade) + ' · ' : '') + money(p.price) + '</small>' +
      '<div class="qty"><button data-ci="' + i + '" data-d="-1" aria-label="Less">−</button><input value="' + it.qty + '" readonly aria-label="Quantity"><button data-ci="' + i + '" data-d="1" aria-label="More">+</button></div></div>' +
      '<div class="ci-r"><b>' + money(p.price * it.qty) + '</b><button class="rm" data-rm="' + i + '">Remove</button></div></div>';
  }
  function emptyBag(el) {
    el.innerHTML = '<div class="empty-box"><svg class="ic" width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M6 7h12l1 13H5L6 7Z"/><path d="M9 7a3 3 0 0 1 6 0"/></svg><h2>Your bag is empty</h2><p>Find something you will love.</p><a class="btn" href="' + B + 'shop">Start shopping</a></div>';
  }
  function joinCart() {
    var c = cart();
    return fetchProducts(c.map(function (i) { return i.id; })).then(function (ps) {
      var m = {}; ps.forEach(function (p) { m[p.id] = p; });
      var keep = c.filter(function (i) { return m[i.id] && m[i.id].stock > 0; });
      if (keep.length !== c.length) setCart(keep);
      return { c: keep, m: m };
    });
  }
  function renderCart() {
    var el = $('#cartList'); if (!el) return;
    joinCart().then(function (d) {
      if (!d.c.length) { emptyBag(el); $('#cartSum').hidden = true; return; }
      var sub = 0;
      el.innerHTML = d.c.map(function (it, i) { var p = d.m[it.id]; sub += p.price * it.qty; return lineHtml(it, p, i); }).join('');
      var s = $('#cartSum'); s.hidden = false; $('[data-sub]', s).textContent = money(sub);
      var fr = $('[data-free]', s);
      if (fr) { var over = +fr.getAttribute('data-over'); fr.textContent = sub >= over ? 'You get free delivery!' : 'Add ' + money(over - sub) + ' more for free delivery.'; }
    }).catch(function () { el.innerHTML = '<p class="err">Could not load your bag. Please refresh the page.</p>'; });
  }
  var cl = $('#cartList');
  if (cl) {
    renderCart();
    cl.addEventListener('click', function (e) {
      var c = cart(), b = e.target.closest('[data-ci]'), r = e.target.closest('[data-rm]');
      if (b) { var i = +b.getAttribute('data-ci'); c[i].qty = Math.max(1, Math.min(20, c[i].qty + (+b.getAttribute('data-d')))); setCart(c); renderCart(); }
      if (r) { c.splice(+r.getAttribute('data-rm'), 1); setCart(c); renderCart(); }
    });
  }

  /* ---------- wishlist page ---------- */
  function renderWish() {
    var el = $('#wishList'); if (!el) return;
    var wl = load('ll_wish');
    fetchProducts(wl).then(function (ps) {
      if (!ps.length) { el.innerHTML = '<div class="empty-box"><svg class="ic" width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10Z"/></svg><h2>No saved items yet</h2><p>Tap the heart on any product to save it here.</p><a class="btn" href="' + B + 'shop">Browse products</a></div>'; return; }
      el.innerHTML = '<div class="grid">' + ps.map(function (p) {
        var off = p.old > p.price ? Math.round(100 - p.price * 100 / p.old) : 0;
        var bag = '<svg class="ic" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M6 7h12l1 13H5L6 7Z"/><path d="M9 7a3 3 0 0 1 6 0"/></svg>';
        return '<article class="card' + (p.stock < 1 ? ' sold' : '') + '"><div class="card-m"><a class="card-img" href="' + p.url + '"><img src="' + esc(p.img) + '" alt="' + esc(p.name) + '" width="600" height="750"></a>' +
          (off ? '<div class="badges"><span class="badge off">-' + off + '%</span></div>' : '') + (p.stock < 1 ? '<span class="soldout">Sold out</span>' : '') +
          '<button class="wish on" data-wish="' + p.id + '" aria-label="Remove from wishlist"><svg class="ic" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10Z"/></svg></button>' +
          (p.stock < 1 ? '' : p.shades.length ? '<a class="quick" href="' + p.url + '">' + bag + '<span>Choose shade</span></a>' : '<button class="quick" data-add="' + p.id + '">' + bag + '<span>Add to bag</span></button>') +
          '</div><div class="card-b"><h3><a href="' + p.url + '">' + esc(p.name) + '</a></h3><p class="card-s">' + esc(p.size) + '</p><div class="price"><b>' + money(p.price) + '</b>' + (off ? '<s>' + money(p.old) + '</s>' : '') + '</div></div></article>';
      }).join('') + '</div>';
      counts();
    });
  }
  renderWish();

  /* ---------- checkout ---------- */
  var co = $('#coForm');
  if (co) {
    var data = null;
    var totals = function () {
      if (!data) return;
      var sub = 0; data.c.forEach(function (it) { sub += data.m[it.id].price * it.qty; });
      var area = (co.querySelector('[name=area]:checked') || {}).value;
      var free = +co.getAttribute('data-free'), del = free > 0 && sub >= free ? 0 : +co.getAttribute(area === 'out' ? 'data-out' : 'data-in');
      $('[data-sub]').textContent = money(sub); $('[data-del]').textContent = del ? money(del) : 'Free'; $('[data-total]').textContent = money(sub + del);
    };
    var trxBox = $('#trxBox');
    function payT() { var v = (co.querySelector('[name=payment]:checked') || {}).value; if (trxBox) trxBox.hidden = !v || v === 'cod'; }
    $$('[name=payment]', co).forEach(function (r) { r.addEventListener('change', payT); }); payT();
    joinCart().then(function (d) {
      data = d;
      if (!d.c.length) { emptyBag($('.cart-in')); return; }
      var v0 = 0; d.c.forEach(function (it) { v0 += d.m[it.id].price * it.qty; });
      px('InitiateCheckout', { content_ids: d.c.map(function (it) { return String(it.id); }), content_type: 'product', num_items: d.c.length, value: v0, currency: 'BDT' });
      $('#coItems').innerHTML = d.c.map(function (it) { var p = d.m[it.id]; return '<div class="co-it"><img src="' + esc(p.img) + '" alt="" width="56" height="56"><div><b>' + esc(p.name) + '</b><small>' + (it.shade ? esc(it.shade) + ' · ' : '') + 'Qty ' + it.qty + '</small></div><span>' + money(p.price * it.qty) + '</span></div>'; }).join('');
      totals();
    });
    $$('[name=area]', co).forEach(function (r) { r.addEventListener('change', totals); });
    try { var saved = JSON.parse(localStorage.getItem('ll_me') || '{}'); ['name', 'phone', 'address'].forEach(function (k) { if (saved[k]) co.elements[k].value = saved[k]; }); if (saved.area) { var ra = co.querySelector('[name=area][value=' + saved.area + ']'); if (ra) ra.checked = true; } } catch (e) {}
    co.addEventListener('submit', function (e) {
      e.preventDefault();
      var er = $('#coErr'), btn = $('#coBtn'), f = co.elements, bad = [];
      er.hidden = true; $$('.bad', co).forEach(function (x) { x.classList.remove('bad'); });
      var phone = f.phone.value.replace(/[০-৯]/g, function (d) { return '০১২৩৪৫৬৭৮৯'.indexOf(d); }).replace(/\D/g, '').replace(/^880/, '0');
      if (!f.name.value.trim()) bad.push(f.name);
      if (!/^01[3-9]\d{8}$/.test(phone)) bad.push(f.phone);
      if (f.address.value.trim().length < 8) bad.push(f.address);
      var pay = (co.querySelector('[name=payment]:checked') || {}).value || 'cod';
      if (pay !== 'cod' && f.trx.value.replace(/\W/g, '').length < 6) bad.push(f.trx);
      if (bad.length) { bad.forEach(function (x) { x.classList.add('bad'); }); bad[0].focus(); er.textContent = 'Please fill in your name, a valid 11 digit mobile number and your full address' + (pay !== 'cod' ? ', and the Transaction ID.' : '.'); er.hidden = false; return; }
      if (!data || !data.c.length) return;
      var body = { name: f.name.value, phone: phone, address: f.address.value, area: (co.querySelector('[name=area]:checked') || {}).value, note: f.note.value, website: f.website.value, items: data.c, payment: pay, trx: f.trx ? f.trx.value : '', event_id: 'ev' + Date.now().toString(36) + Math.random().toString(36).slice(2, 7) };
      save('ll_me', { name: body.name, phone: phone, address: body.address, area: body.area });
      btn.disabled = true; btn.textContent = 'Placing your order...';
      if (LL.demo) { // GitHub Pages preview: no server, so show a sample confirmation
        var code = 'LL' + Date.now().toString().slice(-8);
        try { var dl = JSON.parse(localStorage.getItem('ll_demo_orders') || '[]'); dl.unshift({ code: code, phone: phone, name: body.name, area: body.area, items: data.c.length, t: Date.now() }); localStorage.setItem('ll_demo_orders', JSON.stringify(dl.slice(0, 20))); } catch (x) {}
        localStorage.removeItem('ll_cart'); counts();
        $('.cart-in').innerHTML = '<div class="done-box"><span class="tick"><svg class="ic" width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="m5 12 5 5 9-10"/></svg></span><h2>Thank you, ' + esc(body.name) + '!</h2><p>Demo order <b>' + code + '</b> placed. On the real website this order goes straight to the admin panel and the shop calls you to confirm.</p><a class="btn" href="' + B + 'admin/">See the demo admin panel</a><a class="btn ghost" href="' + B + 'shop/">Continue shopping</a></div>';
        scrollTo(0, 0); return;
      }
      fetch(B + 'api/order', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) })
        .then(function (r) { return r.json(); })
        .then(function (r) {
          if (r.ok) { px('Purchase', { currency: 'BDT', value: r.value || 0, content_ids: body.items.map(function (it) { return String(it.id); }), content_type: 'product' }, { eventID: body.event_id }); localStorage.removeItem('ll_cart'); location.href = r.url; }
          else { er.textContent = r.error || 'Something went wrong.'; er.hidden = false; btn.disabled = false; btn.textContent = 'Confirm order'; }
        })
        .catch(function () { er.textContent = 'Network problem. Please try again, or order on WhatsApp.'; er.hidden = false; btn.disabled = false; btn.textContent = 'Confirm order'; });
    });
  }

  /* ---------- flash sale countdown (ends at midnight, then starts again) ---------- */
  var cd = $('[data-countdown]');
  if (cd) {
    var tick = function () {
      var n = new Date(), end = new Date(n); end.setHours(24, 0, 0, 0);
      var s = Math.max(0, Math.floor((end - n) / 1000)), p = function (x) { return (x < 10 ? '0' : '') + x; };
      $('[data-h]', cd).textContent = p(Math.floor(s / 3600)); $('[data-m]', cd).textContent = p(Math.floor(s % 3600 / 60)); $('[data-s]', cd).textContent = p(s % 60);
    };
    tick(); setInterval(tick, 1000);
  }

  /* ---------- product review form ---------- */
  var rv = $('#rvForm');
  if (rv) rv.addEventListener('submit', function (e) {
    e.preventDefault();
    var f = rv.elements, er = $('.err', rv), btn = $('button', rv);
    var body = { product: rv.getAttribute('data-pid'), name: f.name.value.trim(), phone: f.phone.value, body: f.body.value.trim(), rating: (rv.querySelector('[name=rating]:checked') || {}).value || 5, website: f.website.value };
    if (!body.name || body.body.length < 5) { er.textContent = 'Please write your name and a few words.'; er.hidden = false; return; }
    var done = function () { rv.innerHTML = '<div class="done-box sm"><h3>Thank you, ' + esc(body.name) + '!</h3><p>Your review will show here after the shop checks it.</p></div>'; };
    if (LL.demo) { done(); return; }
    btn.disabled = true;
    fetch(B + 'api/review', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) })
      .then(function (r) { return r.json(); })
      .then(function (r) { if (r.ok) done(); else { er.textContent = r.error || 'Something went wrong.'; er.hidden = false; btn.disabled = false; } })
      .catch(function () { er.textContent = 'Network problem. Please try again.'; er.hidden = false; btn.disabled = false; });
  });

  /* ---------- demo search (static preview has no server) ---------- */
  if (LL.demo && /\/search\/?$/.test(location.pathname)) {
    var qs = (new URLSearchParams(location.search).get('q') || '').trim().toLowerCase(), qc = new URLSearchParams(location.search).get('c') || '', shown = 0;
    $$('.grid .card').forEach(function (c) { var ok = (!qs || c.textContent.toLowerCase().indexOf(qs) > -1) && (!qc || c.getAttribute('data-c') === qc); c.hidden = !ok; if (ok) shown++; });
    var h = $('.list-h h1'); if (h && qs) h.textContent = 'Results for "' + qs + '"';
    var ct = $('.tools .count'); if (ct) ct.textContent = shown + ' product' + (shown === 1 ? '' : 's');
    var si = $('.search input'); if (si) si.value = qs;
  }

  /* ---------- demo: nothing found on search ---------- */
  if (LL.demo && /\/search\/?$/.test(location.pathname) && typeof shown !== 'undefined' && shown === 0 && qs) {
    var g = $('.grid'); if (g) g.insertAdjacentHTML('beforebegin', '<div class="empty-box"><h3>Nothing found for "' + esc(qs) + '"</h3><p>Try another word, like saree, heels or lipstick.</p><a class="btn" href="' + B + 'shop/">See all products</a></div>');
  }

  /* ---------- demo: order tracking (orders placed in this browser) ---------- */
  if (LL.demo && /\/track\/?$/.test(location.pathname)) {
    var tp = new URLSearchParams(location.search), tc = (tp.get('code') || '').trim().toUpperCase(), tph = (tp.get('phone') || '').replace(/\D/g, '').replace(/^88/, '');
    if (tc) {
      var mine = []; try { mine = JSON.parse(localStorage.getItem('ll_demo_orders') || '[]'); } catch (x) {}
      var o = mine.filter(function (x) { return x.code === tc && x.phone === tph; })[0];
      var tf = $('main form');
      var html = o ? '<div class="done-box sm"><h3>Order ' + esc(o.code) + '</h3><p>Hi ' + esc(o.name) + ', your order is <b>Pending</b>. The shop will call you soon to confirm. (Demo preview)</p></div>'
                   : '<p class="err">We could not find that order. Please check the order code and mobile number.</p>';
      if (tf) tf.insertAdjacentHTML('afterend', html);
    }
  }

  counts();
})();
/* close the "More" category menu when clicking elsewhere */
document.addEventListener('click', function (e) { var d = document.querySelector('.nav-more[open]'); if (d && !d.contains(e.target)) d.removeAttribute('open'); });

/* phone: department card opens its category list (Nykaa app style) */
document.addEventListener('click', function (e) {
  var a = e.target.closest('[data-sub]');
  if (a && window.matchMedia('(max-width:860px)').matches) { var p = document.getElementById('dsub-' + a.getAttribute('data-sub')); if (p) { e.preventDefault(); p.hidden = false; p.scrollTop = 0; document.documentElement.classList.add('dsub-on'); } return; }
  if (e.target.closest('[data-sub-close]')) { var o = e.target.closest('.dsub'); if (o) o.hidden = true; document.documentElement.classList.remove('dsub-on'); }
});

/* sideways rows: drag with the mouse + arrow buttons on PC (like Nykaa) */
(function () {
  if (!window.matchMedia('(hover:hover) and (pointer:fine)').matches) return;
  var sel = '.nk-strip-in,.nk-row,.nk-circ,.nk-sub,.lx-cats-in,.lx-row,.nk-spot,.nk-disc2,.lxl-chips';
  document.querySelectorAll(sel).forEach(function (r) {
    if (r.closest('.drawer,.dsub')) return;
    var w = document.createElement('div'); w.className = 'hs-wrap';
    r.parentNode.insertBefore(w, r); w.appendChild(r);
    var mk = function (d) { var b = document.createElement('button'); b.type = 'button'; b.className = 'hs-btn hs-' + d; b.setAttribute('aria-label', d === 'l' ? 'Scroll left' : 'Scroll right');
      b.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="' + (d === 'l' ? 'M15 6l-6 6 6 6' : 'M9 6l6 6-6 6') + '"/></svg>';
      b.addEventListener('click', function () { r.scrollBy({ left: (d === 'l' ? -1 : 1) * r.clientWidth * 0.8, behavior: 'smooth' }); }); w.appendChild(b); return b; };
    var L = mk('l'), R = mk('r');
    var upd = function () { var max = r.scrollWidth - r.clientWidth - 2; L.hidden = r.scrollLeft <= 2; R.hidden = r.scrollLeft >= max; };
    r.addEventListener('scroll', upd, { passive: true }); window.addEventListener('resize', upd); window.addEventListener('load', upd); upd();
    var down = false, sx = 0, sl = 0, moved = false;
    r.addEventListener('mousedown', function (e) { if (e.button !== 0) return; down = true; moved = false; sx = e.pageX; sl = r.scrollLeft; r.style.scrollSnapType = 'none'; });
    window.addEventListener('mousemove', function (e) { if (!down) return; var dx = e.pageX - sx; if (Math.abs(dx) > 5) { moved = true; r.classList.add('hs-drag'); } if (moved) { e.preventDefault(); r.scrollLeft = sl - dx; } });
    window.addEventListener('mouseup', function () { if (!down) return; down = false; r.classList.remove('hs-drag'); r.style.scrollSnapType = ''; });
    r.addEventListener('click', function (e) { if (moved) { e.preventDefault(); e.stopPropagation(); moved = false; } }, true);
    r.addEventListener('dragstart', function (e) { e.preventDefault(); });
  });
})();
