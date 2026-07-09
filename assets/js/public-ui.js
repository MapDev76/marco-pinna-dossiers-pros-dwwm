(function () {
  var drawer = document.querySelector('[data-site-mobile-drawer]');
  var openButtons = Array.prototype.slice.call(document.querySelectorAll('[data-site-menu-open]'));
  var closeTriggers = Array.prototype.slice.call(document.querySelectorAll('[data-site-menu-close]'));
  var body = document.body;
  var lastFocusedElement = null;
  var focusableSelector = 'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])';

  function setOpenButtonState(isExpanded) {
    openButtons.forEach(function (button) {
      button.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
    });
  }

  function focusFirstInDrawer() {
    if (!drawer) {
      return;
    }
    var panel = drawer.querySelector('.site-mobile-drawer-panel');
    if (!panel) {
      return;
    }
    var focusables = panel.querySelectorAll(focusableSelector);
    var firstFocusable = focusables[0] || panel;
    if (typeof firstFocusable.focus === 'function') {
      firstFocusable.focus();
    }
  }

  function trapDrawerFocus(event) {
    if (!drawer || drawer.hidden || event.key !== 'Tab') {
      return;
    }
    var panel = drawer.querySelector('.site-mobile-drawer-panel');
    if (!panel) {
      return;
    }
    var focusables = Array.prototype.slice.call(panel.querySelectorAll(focusableSelector));
    if (!focusables.length) {
      return;
    }
    var first = focusables[0];
    var last = focusables[focusables.length - 1];

    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
      return;
    }

    if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  }

  function openDrawer() {
    if (!drawer) {
      return;
    }
    lastFocusedElement = document.activeElement;
    drawer.hidden = false;
    body.classList.add('site-menu-open');
    setOpenButtonState(true);
    focusFirstInDrawer();
  }

  function closeDrawer() {
    if (!drawer) {
      return;
    }
    drawer.hidden = true;
    body.classList.remove('site-menu-open');
    setOpenButtonState(false);
    if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
      lastFocusedElement.focus();
      lastFocusedElement = null;
    }
  }

  openButtons.forEach(function (button) {
    button.addEventListener('click', openDrawer);
  });

  closeTriggers.forEach(function (element) {
    element.addEventListener('click', closeDrawer);
  });

  document.addEventListener('keydown', function (event) {
    trapDrawerFocus(event);
    if (event.key === 'Escape') {
      closeDrawer();
    }
  });

  window.addEventListener('resize', function () {
    if (window.innerWidth > 1100) {
      closeDrawer();
    }
  });

  var footer = document.querySelector('[data-reveal-footer]');
  if (!footer) {
    return;
  }

  var lastScrollY = window.scrollY || 0;

  function setFooterVisible(isVisible) {
    body.classList.toggle('footer-visible', isVisible);
  }

  document.addEventListener('mousemove', function (event) {
    var nearBottom = event.clientY >= window.innerHeight - 90;
    setFooterVisible(nearBottom);
  });

  window.addEventListener('scroll', function () {
    var currentY = window.scrollY || 0;
    var isTouchMode = window.matchMedia('(hover: none)').matches;

    if (isTouchMode) {
      var movedDown = currentY > lastScrollY + 5;
      if (movedDown && currentY > 30) {
        setFooterVisible(true);
      }
      if (currentY < 10) {
        setFooterVisible(false);
      }
    }

    lastScrollY = currentY;
  }, { passive: true });
})();
