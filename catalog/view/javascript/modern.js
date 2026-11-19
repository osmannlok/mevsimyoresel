(function () {
  function onReady(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  onReady(function () {
    var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    document.documentElement.classList.add('has-modern-theme');

    // ---------- Lenis smooth scroll (v2) ----------
    var lenis = null;
    if (!prefersReduced && window.Lenis) {
      lenis = new Lenis({
        autoRaf: true,
        smoothWheel: true,
        lerp: 0.1
      });

      if (window.ScrollTrigger) {
        lenis.on('scroll', ScrollTrigger.update);
      }
    }

    // ---------- GSAP animations ----------
    if (!prefersReduced && window.gsap) {
      var gsap = window.gsap;
      if (window.ScrollTrigger) {
        gsap.registerPlugin(ScrollTrigger);
        if (window.Observer) gsap.registerPlugin(window.Observer);
      }

      // Hero / top content reveal
      var heroTargets = document.querySelectorAll('.home-hero-copy');
      if (heroTargets.length) {
        gsap.from(heroTargets, {
          opacity: 0,
          y: 24,
          duration: 0.8,
          stagger: 0.08,
          ease: 'power2.out',
          clearProps: 'transform'
        });
      }

      // Scroll reveals
      if (window.ScrollTrigger) {
        var revealSets = [
          { sel: '.product-thumb', y: 30, stagger: 0.06 },
          { sel: '.home-trust-wrap .trust-item, .trust-section .trust-card, .product-trust .trust-item', y: 18, stagger: 0.04 },
          { sel: '.home-story-copy, .home-steps-head, .home-step', y: 22, stagger: 0.05 },
          { sel: '#content h1, #content h2, #content h3', y: 20, stagger: 0.05 },
          { sel: 'footer .row > div', y: 20, stagger: 0.08 },
          { sel: '.breadcrumb, #product-info .row > div, .card, .list-group, .table', y: 18, stagger: 0.04 }
        ];

        revealSets.forEach(function (cfg) {
          var els = gsap.utils.toArray(cfg.sel).filter(function (el) {
            return !el.closest('#menu') && !el.closest('#top');
          });
          if (!els.length) return;
          gsap.from(els, {
            y: cfg.y,
            duration: 0.7,
            stagger: cfg.stagger,
            ease: 'power2.out',
            clearProps: 'transform',
            scrollTrigger: {
              trigger: els[0],
              start: 'top 95%',
              once: true
            }
          });
        });

        // Refresh only on resize/orientation change
        var refresh = function () { ScrollTrigger.refresh(); };
        window.addEventListener('resize', refresh, { passive: true });
        window.addEventListener('orientationchange', refresh, { passive: true });
      }

      // Subtle hover lift for cards/products
      var hoverEls = document.querySelectorAll('.product-thumb, .card, .category-card');
      hoverEls.forEach(function (el) {
        el.addEventListener('mouseenter', function () {
          gsap.to(el, { y: -6, duration: 0.25, ease: 'power2.out', overwrite: 'auto' });
        });
        el.addEventListener('mouseleave', function () {
          gsap.to(el, { y: 0, duration: 0.3, ease: 'power2.out', overwrite: 'auto' });
        });
      });
    }

    // Header scroll state
    var topBar = document.querySelector('header');
    function onScroll() {
      var y = window.scrollY || document.documentElement.scrollTop;
      if (y > 10) document.body.classList.add('is-scrolled');
      else document.body.classList.remove('is-scrolled');
    }
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });

    // Drawer + search toggle (GT Omega style, mobile)
    var body = document.body;
    var menuToggle = document.getElementById('menuToggle');
    var menuClose = document.getElementById('menuClose');
    var drawerOverlay = document.getElementById('drawerOverlay');
    var searchToggle = document.getElementById('searchToggle');
    var searchBar = document.getElementById('headerSearchBar');
    var desktopSearch = window.matchMedia('(min-width: 1200px)');

    function closeDrawer() {
      body.classList.remove('drawer-open');
      if (menuToggle) menuToggle.setAttribute('aria-expanded', 'false');
    }
    function openDrawer() {
      body.classList.add('drawer-open');
      if (menuToggle) menuToggle.setAttribute('aria-expanded', 'true');
    }
    function closeSearch() {
      body.classList.remove('search-open');
      if (searchToggle) searchToggle.setAttribute('aria-expanded', 'false');
    }

    if (menuToggle) menuToggle.addEventListener('click', function () {
      var open = body.classList.toggle('drawer-open');
      menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      closeSearch();
    });
    if (menuClose) menuClose.addEventListener('click', closeDrawer);
    if (drawerOverlay) drawerOverlay.addEventListener('click', closeDrawer);

    if (searchToggle && searchBar) {
      searchToggle.addEventListener('click', function () {
        var open = body.classList.toggle('search-open');
        searchToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) {
          closeDrawer();
          var input = searchBar.querySelector('input[type="text"]');
          if (input) input.focus();
        }
      });
    }

    // Menü: üst kategori linkine tıklayınca paneli aç/kapat (gezinmeye gerek yok, hover da çalışır)
    function closeAllPanels(except) {
      document.querySelectorAll('.main-menu-item--has-children.expanded').forEach(function (item) {
        if (item !== except) {
          item.classList.remove('expanded');
          var parent = item.querySelector(':scope > .main-menu-link');
          if (parent) parent.setAttribute('aria-expanded', 'false');
        }
      });
    }

    document.querySelectorAll('.main-menu-item--has-children > .main-menu-link').forEach(function (link) {
      link.addEventListener('click', function (e) {
        // Ctrl/Cmd/Shift+tık gibi gerçek gezinti isteklerine karışma
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

        var item = link.closest('.main-menu-item--has-children');
        if (!item) return;

        e.preventDefault();
        var willOpen = !item.classList.contains('expanded');
        closeAllPanels(item);
        item.classList.toggle('expanded', willOpen);
        link.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
      });
    });

    // Panel dışına tıklayınca kapat
    document.addEventListener('click', function (e) {
      if (!e.target.closest('.main-menu-item--has-children')) {
        closeAllPanels(null);
      }
    });

    // Escape ile kapat
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeAllPanels(null);
    });

    // Close drawer / search on Escape and when resizing to desktop
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') { closeDrawer(); closeSearch(); }
    });
    function onResize() {
      if (desktopSearch.matches) { closeSearch(); if (body.classList.contains('drawer-open')) closeDrawer(); }
    }
    window.addEventListener('resize', onResize, { passive: true });
  });
})();