(function () {
  var drawer = document.querySelector('[data-site-mobile-drawer]');
  var openButtons = Array.prototype.slice.call(document.querySelectorAll('[data-site-menu-open]'));
  var closeTriggers = Array.prototype.slice.call(document.querySelectorAll('[data-site-menu-close]'));
  var body = document.body;

  function openDrawer() {
    if (!drawer) {
      return;
    }
    drawer.hidden = false;
    body.classList.add('site-menu-open');
  }

  function closeDrawer() {
    if (!drawer) {
      return;
    }
    drawer.hidden = true;
    body.classList.remove('site-menu-open');
  }

  openButtons.forEach(function (button) {
    button.addEventListener('click', openDrawer);
  });

  closeTriggers.forEach(function (element) {
    element.addEventListener('click', closeDrawer);
  });

  document.addEventListener('keydown', function (event) {
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
