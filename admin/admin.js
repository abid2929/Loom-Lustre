(function () {
  'use strict';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

  // phone menu
  var side = $('#side'), shade = $('#shade'), mb = $('#menuBtn');
  function menu(open) { document.body.classList.toggle('menu-open', open); }
  if (mb) mb.addEventListener('click', function () { menu(!document.body.classList.contains('menu-open')); });
  if (shade) shade.addEventListener('click', function () { menu(false); });

  // close the bell when clicking elsewhere
  document.addEventListener('click', function (e) { $$('details.bell[open]').forEach(function (d) { if (!d.contains(e.target)) d.open = false; }); });

  // confirm before delete
  document.addEventListener('submit', function (e) {
    var f = e.target, m = f.getAttribute('data-confirm');
    if (m && !LLA.demo && !confirm(m)) e.preventDefault();
  });

  // quick table filter (works in the static demo too)
  $$('input[data-filter]').forEach(function (inp) {
    var t = $(inp.getAttribute('data-filter')); if (!t) return;
    function run() { var q = inp.value.trim().toLowerCase(); $$('tbody tr[data-s]', t).forEach(function (r) { r.style.display = !q || r.getAttribute('data-s').indexOf(q) > -1 ? '' : 'none'; }); }
    inp.addEventListener('input', run); if (inp.value) run();
  });

  // flash message fades
  var fl = $('.flash.ok'); if (fl) setTimeout(function () { fl.classList.add('gone'); }, 5000);

  // orders: select all + count
  var all = $('#ckAll');
  function cnt() { var n = $$('.ck1:checked').length, s = $('#selN'); if (s) s.textContent = n ? n + ' selected' : ''; }
  if (all) all.addEventListener('change', function () { $$('.ck1').forEach(function (c) { if (c.closest('tr').style.display !== 'none') c.checked = all.checked; }); cnt(); });
  $$('.ck1').forEach(function (c) { c.addEventListener('change', cnt); });

  // stock steppers
  $$('.stepper').forEach(function (s) {
    var inp = $('input', s);
    $$('button', s).forEach(function (b) { b.addEventListener('click', function () { inp.value = Math.max(0, (parseInt(inp.value, 10) || 0) + parseInt(b.getAttribute('data-d'), 10)); inp.dispatchEvent(new Event('input')); }); });
    inp.addEventListener('input', function () { var v = parseInt(inp.value, 10) || 0; inp.classList.toggle('out', v <= 0); inp.classList.toggle('low', v > 0 && v <= 5); });
  });

  /* ---------- product form ---------- */
  var pf = $('#pform');
  if (pf) {
    // category / collection switch
    function place() { var col = $('input[name=place][value=col]', pf).checked; $$('.col-only', pf).forEach(function (x) { x.style.display = col ? '' : 'none'; }); if (!col) { var s = $('select[name=collection_id]', pf); if (s) s.value = '0'; } }
    $$('input[name=place]', pf).forEach(function (r) { r.addEventListener('change', place); }); place();

    // discount preview
    var reg = $('#pReg'), sale = $('#pSale'), disc = $('#pDisc');
    function dp() { var r = +reg.value, s = +sale.value; disc.textContent = s > 0 && s < r ? Math.round(100 - s * 100 / r) + '% off badge will show' : (s >= r && s > 0 ? 'Sale price must be lower than regular price' : ''); disc.className = s >= r && s > 0 ? 'bad' : 'good'; }
    if (reg && sale) { reg.addEventListener('input', dp); sale.addEventListener('input', dp); dp(); }

    // chips (shades, sizes)
    $$('.chips-f', pf).forEach(function (box) {
      var hid = $('input[type=hidden]', box), wrap = $('.chips', box), inp = $('.chip-add input', box);
      function list() { return hid.value.split(/\s*,\s*/).filter(Boolean); }
      function draw() {
        wrap.innerHTML = '';
        list().forEach(function (v, i) { var c = document.createElement('span'); c.className = 'chip'; c.textContent = v; var x = document.createElement('button'); x.type = 'button'; x.textContent = '×'; x.setAttribute('aria-label', 'Remove ' + v); x.onclick = function () { var l = list(); l.splice(i, 1); hid.value = l.join(', '); draw(); }; c.appendChild(x); wrap.appendChild(c); });
        if (!list().length) wrap.innerHTML = '<em>None yet</em>';
        if (box.getAttribute('data-name') === 'shades') { var dl = $('#shadeList'); if (dl) dl.innerHTML = list().map(function (v) { return '<option value="' + v.replace(/"/g, '&quot;') + '">'; }).join(''); }
      }
      function add(v) { v = (v || '').replace(/,/g, ' ').trim(); if (!v) return; var l = list(); if (l.map(function (x) { return x.toLowerCase(); }).indexOf(v.toLowerCase()) < 0) l.push(v); hid.value = l.join(', '); draw(); }
      inp.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ',') { e.preventDefault(); add(inp.value); inp.value = ''; } });
      $('.chip-add button', box).addEventListener('click', function () { add(inp.value); inp.value = ''; inp.focus(); });
      $$('.quick button', box).forEach(function (b) { b.addEventListener('click', function () { add(b.getAttribute('data-v')); }); });
      draw();
    });

    // gallery: remove, drag to reorder, add rows
    var gal = $('#gal');
    pf.addEventListener('click', function (e) { var x = e.target.closest('.rm'); if (x) x.closest('.gi, .nrow').remove(); });
    if (gal) {
      var drag = null;
      gal.addEventListener('dragstart', function (e) { drag = e.target.closest('.gi'); if (drag) drag.classList.add('drag'); });
      gal.addEventListener('dragend', function () { if (drag) drag.classList.remove('drag'); drag = null; });
      gal.addEventListener('dragover', function (e) { e.preventDefault(); var t = e.target.closest('.gi'); if (drag && t && t !== drag) { var r = t.getBoundingClientRect(); gal.insertBefore(drag, (e.clientX - r.left) > r.width / 2 ? t.nextSibling : t); } });
    }
    var tpl = $('#imgRow'), box = $('#newImgs');
    function addRow() {
      var n = tpl.content.firstElementChild.cloneNode(true); box.appendChild(n);
      var f = $('input[type=file]', n);
      f.addEventListener('change', function () { var lab = f.parentNode; if (!f.files[0]) return; var im = $('img', lab) || document.createElement('img'); im.src = URL.createObjectURL(f.files[0]); lab.insertBefore(im, lab.firstChild); $('span', lab).textContent = f.files[0].name.slice(0, 18); lab.classList.add('has'); });
    }
    if (tpl) { $('#addImg').addEventListener('click', addRow); if (!$$('.gi', gal).length) addRow(); addRow(); }

    // AI copywriter
    var ab = $('#aiBtn');
    if (ab) ab.addEventListener('click', function () {
      var name = $('#pName').value.trim(), note = $('#aiNote');
      if (!name) { note.textContent = 'Write the product name first.'; note.className = 'muted sm bad'; $('#pName').focus(); return; }
      var cat = $('#pCat'); var fd = new FormData();
      fd.append('act', 'ai_copy'); fd.append('csrf', LLA.csrf); fd.append('name', name); fd.append('cat', cat.options[cat.selectedIndex].text);
      fd.append('shades', $('input[name=shades]', pf).value); fd.append('size', $('#pSize').value);
      function fill(j) {
        if (!j.ok) { note.textContent = j.error || 'Could not write now. Try again.'; note.className = 'muted sm bad'; return; }
        $('#fShort').value = j.short || ''; $('#fDesc').value = j.description || ''; $('#fHow').value = j.how_to || ''; if (j.tags) $('#fTags').value = j.tags;
        note.textContent = 'Done with ' + (j.src || 'AI') + '. Check the text and change anything you like.'; note.className = 'muted sm good';
      }
      ab.disabled = true; ab.classList.add('busy'); note.textContent = 'Writing...';
      if (LLA.demo) { setTimeout(function () { ab.disabled = false; ab.classList.remove('busy'); fill({ ok: true, src: 'the demo writer', short: 'Rich colour and soft comfort, all day long.', description: name + ' is picked for its quality and value. It is easy to use and looks beautiful.\n\nEvery order is checked by hand. Cash on delivery all over Bangladesh.', how_to: 'Apply a little and build up as you like.', tags: name.toLowerCase() + ', cosmetics bangladesh, original' }); }, 700); return; }
      fetch(LLA.base, { method: 'POST', body: fd, headers: { 'X-CSRF': LLA.csrf }, credentials: 'same-origin' })
        .then(function (r) { return r.json(); }).then(fill)
        .catch(function () { fill({ ok: false, error: 'Could not reach the server. Please try again.' }); })
        .then(function () { ab.disabled = false; ab.classList.remove('busy'); });
    });
  }

  // preview for any single image file input
  $$('input[type=file][name=image], .banner input[type=file]').forEach(function (inp) {
    inp.addEventListener('change', function () {
      if (!inp.files[0]) return; var u = URL.createObjectURL(inp.files[0]);
      var bp = inp.closest('.banner') && $('.bprev', inp.closest('.banner'));
      if (bp) { bp.style.backgroundImage = 'url(' + u + ')'; return; }
      var im = $('img.prev', inp.closest('form')); if (!im) { im = document.createElement('img'); im.className = 'prev'; inp.closest('label').before(im); } im.src = u;
    });
  });
})();
