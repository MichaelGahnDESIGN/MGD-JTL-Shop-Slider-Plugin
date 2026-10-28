/* MGD Super-Slider Engine - Vanilla JS, kein CDN
   Teil 1 Controller (Logik ohne DOM, node --test) / Teil 2 DOM (Pfeile, Punkte, Pause, Tastatur, Wischen) */
(function (root, factory) {
  var lib = factory();
  if (typeof module === 'object' && module.exports) { module.exports = lib; }
  else { root.MGDSlider = lib; if (root.document) { lib.autoInit(root.document); } }
})(typeof self !== 'undefined' ? self : this, function () {
  'use strict';

  var SWIPE_MIN = 40; // px, darunter ist es nur ein Tipp

  /* Controller */
  function createController(opts) {
    var o = opts || {};
    var count = Math.max(0, o.count | 0);
    var st = {
      index: 0,
      loop: o.loop !== false,
      userPaused: false,   // Pausenknopf
      hover: false,
      focus: false,
      hidden: false,       // Tab im Hintergrund / Slider nicht sichtbar
      reduced: !!o.reducedMotion,
      autoplay: !!o.autoplay
    };
    // reduced-motion: kein Autoplay, nur per Play
    if (st.reduced) { st.userPaused = true; }

    function clamp(i) {
      if (count === 0) { return 0; }
      if (st.loop) { return ((i % count) + count) % count; }
      return Math.max(0, Math.min(count - 1, i));
    }
    var c = {
      state: st,
      count: count,
      goTo: function (i) { st.index = clamp(i); return st.index; },
      next: function () { return c.goTo(st.index + 1); },
      prev: function () { return c.goTo(st.index - 1); },
      canPrev: function () { return count > 1 && (st.loop || st.index > 0); },
      canNext: function () { return count > 1 && (st.loop || st.index < count - 1); },
      setHover: function (v) { st.hover = !!v; },
      setFocus: function (v) { st.focus = !!v; },
      setHidden: function (v) { st.hidden = !!v; },
      togglePause: function () { st.userPaused = !st.userPaused; return st.userPaused; },
      // läuft Autoplay? (Hover/Fokus/Pause/Tab hidden stoppen)
      isPlaying: function () {
        return st.autoplay && count > 1 && !st.userPaused && !st.hover && !st.focus && !st.hidden && (st.loop || st.index < count - 1);
      },
      // Autoplay Schritt
      tick: function () {
        if (!c.isPlaying()) { return false; }
        c.next();
        return true;
      }
    };
    return c;
  }

  /* Wischen - waagerecht nur wenn deutlich mehr als senkrecht */
  function swipeDirection(dx, dy, min) {
    var m = min == null ? SWIPE_MIN : min;
    if (Math.abs(dx) < m || Math.abs(dx) < Math.abs(dy) * 1.5) { return 0; }
    return dx < 0 ? 1 : -1; // nach links wischen = nächste Slide
  }

  /* DOM */
  function el(tag, cls, attrs) {
    var n = document.createElement(tag);
    if (cls) { n.className = cls; }
    Object.keys(attrs || {}).forEach(function (k) { n.setAttribute(k, attrs[k]); });
    return n;
  }
  var ICON = {
    prev: '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    next: '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    pause: '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path d="M8 5v14M16 5v14" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"/></svg>',
    play: '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path d="M8 5l11 7-11 7z" fill="currentColor"/></svg>'
  };

  function readCfg(node) {
    var cfg = {};
    try { cfg = JSON.parse(node.getAttribute('data-mgd-slider') || '{}') || {}; } catch (e) { cfg = {}; }
    return cfg;
  }

  function init(node) {
    if (!node || node.getAttribute('data-mgd-ready') === '1') { return null; }
    var cfg = readCfg(node);
    var track = node.querySelector('.mgd-slider__track');
    var slides = track ? Array.prototype.slice.call(track.children) : [];
    if (!slides.length) { return null; }
    var win = node.ownerDocument.defaultView || window;
    var reduced = !!(win.matchMedia && win.matchMedia('(prefers-reduced-motion: reduce)').matches);
    var ctl = createController({
      count: slides.length, loop: cfg.loop !== false, autoplay: !!cfg.autoplay, reducedMotion: reduced
    });
    node.setAttribute('data-mgd-ready', '1');
    node.setAttribute('tabindex', '0'); // damit Pfeiltasten greifen
    var speed = reduced ? 0 : (cfg.speed == null ? 400 : cfg.speed);
    track.style.transitionDuration = speed + 'ms';

    var prevBtn, nextBtn, pauseBtn, dots = [];
    if (slides.length > 1) {
      if (cfg.arrows !== false) {
        prevBtn = el('button', 'mgd-slider__nav mgd-slider__prev', { type: 'button', 'aria-label': 'Vorherige Slide' });
        nextBtn = el('button', 'mgd-slider__nav mgd-slider__next', { type: 'button', 'aria-label': 'Nächste Slide' });
        prevBtn.innerHTML = ICON.prev; nextBtn.innerHTML = ICON.next;
        node.appendChild(prevBtn); node.appendChild(nextBtn);
      }
      var bar = el('div', 'mgd-slider__bar');
      if (cfg.dots !== false) {
        var list = el('div', 'mgd-slider__dots');
        slides.forEach(function (_, i) {
          var d = el('button', 'mgd-slider__dot', { type: 'button', 'aria-label': 'Slide ' + (i + 1) + ' anzeigen' });
          d.addEventListener('click', function () { show(i, true); });
          list.appendChild(d); dots.push(d);
        });
        bar.appendChild(list);
      }
      if (cfg.autoplay) {
        // Pausenknopf Pflicht bei Autoplay (WCAG 2.2.2)
        pauseBtn = el('button', 'mgd-slider__pause', { type: 'button' });
        pauseBtn.addEventListener('click', function () { ctl.togglePause(); sync(); });
        bar.appendChild(pauseBtn);
      }
      if (bar.children.length) { node.appendChild(bar); }
    }

    var timer = null;
    function sync() {
      var i = ctl.state.index;
      track.style.transform = 'translate3d(' + (-100 * i) + '%,0,0)';
      slides.forEach(function (s, n) {
        var on = n === i;
        s.classList.toggle('is-active', on);
        if (on) { s.removeAttribute('inert'); s.removeAttribute('aria-hidden'); }
        else { s.setAttribute('inert', ''); s.setAttribute('aria-hidden', 'true'); }
      });
      dots.forEach(function (d, n) { if (n === i) { d.setAttribute('aria-current', 'true'); } else { d.removeAttribute('aria-current'); } });
      if (prevBtn) { prevBtn.disabled = !ctl.canPrev(); nextBtn.disabled = !ctl.canNext(); }
      var playing = ctl.isPlaying();
      // Screenreader nur ansagen wenn kein Autoplay
      track.setAttribute('aria-live', ctl.state.autoplay && !ctl.state.userPaused ? 'off' : 'polite');
      if (pauseBtn) {
        var paused = ctl.state.userPaused;
        pauseBtn.innerHTML = paused ? ICON.play : ICON.pause;
        pauseBtn.setAttribute('aria-label', paused ? 'Automatischen Wechsel starten' : 'Automatischen Wechsel anhalten');
        pauseBtn.setAttribute('aria-pressed', paused ? 'true' : 'false');
      }
      schedule(playing);
    }
    function schedule(playing) {
      if (timer) { win.clearTimeout(timer); timer = null; }
      if (!playing) { return; }
      timer = win.setTimeout(function () { timer = null; if (ctl.tick()) { sync(); } else { schedule(ctl.isPlaying()); } }, cfg.interval || 5000);
    }
    function show(i, byUser) {
      ctl.goTo(i);
      if (byUser) { schedule(ctl.isPlaying()); }
      sync();
    }

    if (prevBtn) { prevBtn.addEventListener('click', function () { ctl.prev(); sync(); }); }
    if (nextBtn) { nextBtn.addEventListener('click', function () { ctl.next(); sync(); }); }
    node.addEventListener('keydown', function (e) {
      if (e.target && e.target.closest && e.target.closest('input,textarea,select')) { return; }
      if (e.key === 'ArrowLeft') { ctl.prev(); sync(); e.preventDefault(); }
      else if (e.key === 'ArrowRight') { ctl.next(); sync(); e.preventDefault(); }
      else if (e.key === 'Home') { ctl.goTo(0); sync(); e.preventDefault(); }
      else if (e.key === 'End') { ctl.goTo(slides.length - 1); sync(); e.preventDefault(); }
    });
    if (cfg.pauseOnHover !== false) {
      node.addEventListener('mouseenter', function () { ctl.setHover(true); sync(); });
      node.addEventListener('mouseleave', function () { ctl.setHover(false); sync(); });
    }
    node.addEventListener('focusin', function () { ctl.setFocus(true); sync(); });
    node.addEventListener('focusout', function (e) { if (!node.contains(e.relatedTarget)) { ctl.setFocus(false); sync(); } });

    // Wischen (Touch / Stift)
    if (cfg.swipe !== false && slides.length > 1) {
      var sx = 0, sy = 0, down = false;
      node.addEventListener('pointerdown', function (e) { if (e.pointerType === 'mouse') { return; } down = true; sx = e.clientX; sy = e.clientY; });
      node.addEventListener('pointerup', function (e) {
        if (!down) { return; } down = false;
        var dir = swipeDirection(e.clientX - sx, e.clientY - sy);
        if (dir) { dir > 0 ? ctl.next() : ctl.prev(); show(ctl.state.index, true); }
      });
      node.addEventListener('pointercancel', function () { down = false; });
    }

    // Tab hidden / Slider nicht sichtbar > Autoplay stop
    var doc = node.ownerDocument;
    doc.addEventListener('visibilitychange', function () { ctl.setHidden(doc.hidden); sync(); });
    if (win.IntersectionObserver) {
      new win.IntersectionObserver(function (en) { ctl.setHidden(!en[0].isIntersecting); sync(); }, { threshold: 0.2 }).observe(node);
    }

    sync();
    return { controller: ctl, show: show, destroy: function () { if (timer) { win.clearTimeout(timer); } } };
  }

  function autoInit(doc) {
    var go = function () {
      Array.prototype.forEach.call(doc.querySelectorAll('[data-mgd-slider]'), init);
    };
    if (doc.readyState === 'loading') { doc.addEventListener('DOMContentLoaded', go); } else { go(); }
  }

  return { createController: createController, swipeDirection: swipeDirection, init: init, autoInit: autoInit };
});
