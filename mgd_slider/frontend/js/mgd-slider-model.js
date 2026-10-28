/* Slider Datenmodell fürs Frontend (Shopify, Vorschau) - Spiegel von Config.php + Prime.php */
(function (root, factory) {
  var lib = factory();
  if (typeof module === 'object' && module.exports) { module.exports = lib; } else { root.MGDSliderModel = lib; }
})(typeof self !== 'undefined' ? self : this, function () {
  'use strict';
  // Werte wie options.json (Test)
  var D = {
    mode: 'classic', autoplay: false, interval: 5000, speed: 400, pauseOnHover: true, arrows: true, dots: true, swipe: true, loop: true,
    aspect: '16/9', heightPx: 0, fit: 'cover', align: 'left', accent: '',
    prime: { source: 'random', count: 5, buttonLabel: 'Ansehen' }
  };
  var R = { interval: [2000, 20000], speed: [0, 2000], heightPx: [0, 1200], primeCount: [1, 12] };
  var C = { mode: ['classic', 'prime'], source: ['random', 'bestseller', 'category', 'manual'] };

  function clamp(v, r, d) { var n = Number(v); if (v === '' || v == null || !isFinite(n)) { return d; } return Math.max(r[0], Math.min(r[1], Math.round(n))); }
  function pick(v, list, d) { return list.indexOf(v) >= 0 ? v : d; }

  function normalize(raw) {
    var r = raw || {}, p = r.prime || {};
    return {
      mode: pick(r.mode, C.mode, D.mode),
      autoplay: r.autoplay === true || r.autoplay === '1' || r.autoplay === 'true',
      interval: clamp(r.interval, R.interval, D.interval),
      speed: clamp(r.speed, R.speed, D.speed),
      prime: { source: pick(p.source, C.source, D.prime.source), count: clamp(p.count, R.primeCount, D.prime.count) }
    };
  }

  // shuffle, rand für Tests
  function shuffle(list, rand) {
    var a = list.slice(), rnd = rand || Math.random;
    for (var i = a.length - 1; i > 0; i--) { var j = Math.floor(rnd() * (i + 1)); var t = a[i]; a[i] = a[j]; a[j] = t; }
    return a;
  }

  // Kandidaten: id, sku, name, url, image, sales, inStock, categories
  function selectPrime(cands, cfg, rand) {
    var p = normalize(cfg).prime, seen = {}, pool = [];
    (cands || []).forEach(function (c) {
      var id = String(c.id == null ? '' : c.id);
      if (!id || seen[id] || !c.image || !c.url || c.inStock === false) { return; }
      seen[id] = 1; pool.push(c);
    });
    var wanted = (cfg && cfg.prime && cfg.prime.products) || [];
    var cats = (cfg && cfg.prime && cfg.prime.categories) || [];
    if (p.source === 'manual') { pool = pool.filter(function (c) { return wanted.indexOf(String(c.id)) >= 0 || wanted.indexOf(String(c.sku || '')) >= 0; }); }
    if (p.source === 'category') { pool = pool.filter(function (c) { return (c.categories || []).some(function (x) { return cats.indexOf(String(x)) >= 0; }); }); }
    if (p.source === 'bestseller') {
      pool = pool.map(function (c, i) { return [c, i]; }).sort(function (a, b) { return ((b[0].sales | 0) - (a[0].sales | 0)) || (a[1] - b[1]); }).map(function (x) { return x[0]; });
    } else { pool = shuffle(pool, rand); }
    return pool.slice(0, p.count);
  }

  return { defaults: D, ranges: R, normalize: normalize, shuffle: shuffle, selectPrime: selectPrime };
});
