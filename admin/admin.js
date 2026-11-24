(function () {
  'use strict';
  // confirm before delete
  document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) { if (!confirm(f.getAttribute('data-confirm'))) e.preventDefault(); });
  });
  // photo list: remove + drag to reorder
  var list = document.getElementById('phList');
  if (list) {
    list.addEventListener('click', function (e) { var x = e.target.closest('.ph-x'); if (x) x.parentNode.remove(); });
    var drag = null;
    list.addEventListener('dragstart', function (e) { drag = e.target.closest('.ph'); });
    list.addEventListener('dragover', function (e) {
      e.preventDefault(); var t = e.target.closest('.ph');
      if (drag && t && t !== drag) { var r = t.getBoundingClientRect(); list.insertBefore(drag, (e.clientX - r.left) > r.width / 2 ? t.nextSibling : t); }
    });
    var inp = list.querySelector('input[type=file]'), prev = document.getElementById('phNew');
    if (inp) inp.addEventListener('change', function () {
      prev.innerHTML = '';
      Array.prototype.forEach.call(inp.files, function (f) { var i = document.createElement('img'); i.src = URL.createObjectURL(f); prev.appendChild(i); });
    });
  }
  // preview for single image pickers (category, slider)
  document.querySelectorAll('.cat-img input[type=file], .slide-img input[type=file]').forEach(function (inp) {
    inp.addEventListener('change', function () {
      if (!inp.files[0]) return; var lab = inp.parentNode, img = lab.querySelector('img');
      if (!img) { img = document.createElement('img'); lab.innerHTML = ''; lab.appendChild(img); lab.appendChild(inp); }
      img.src = URL.createObjectURL(inp.files[0]);
    });
  });
})();
