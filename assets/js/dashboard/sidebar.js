(function(){
  function initSidebar(options) {
    var sidebar = options.sidebar;
    var sidebarHandle = options.sidebarHandle;
    var plannerDepartmentButtons = options.plannerDepartmentButtons || [];
    var setActiveDepartment = options.setActiveDepartment;

    var openSidebar = function () {
      document.body.classList.add('sidebar-expanded');
    };

    var closeSidebar = function () {
      document.body.classList.remove('sidebar-expanded');
    };

    var bindHoverSidebar = function () {
      if (!sidebar || !sidebarHandle) return;
      var closeTimer = null;
      var cancelClose = function () {
        if (closeTimer) {
          window.clearTimeout(closeTimer);
          closeTimer = null;
        }
      };
      var scheduleClose = function () {
        cancelClose();
        closeTimer = window.setTimeout(function () {
          if (!sidebar.matches(':hover') && !sidebarHandle.matches(':hover')) {
            closeSidebar();
          }
        }, 180);
      };

      sidebarHandle.addEventListener('mouseenter', function () {
        cancelClose();
        openSidebar();
      });
      sidebarHandle.addEventListener('mouseleave', scheduleClose);
      sidebarHandle.addEventListener('focusin', function () {
        cancelClose();
        openSidebar();
      });

      sidebar.addEventListener('mouseenter', cancelClose);
      sidebar.addEventListener('mouseleave', scheduleClose);
    };

    if (sidebar && sidebarHandle) {
      bindHoverSidebar();
      closeSidebar();
    }

    document.querySelectorAll('.management-toggle').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var list = btn.nextElementSibling;
        if (!list) return;
        var willOpen = list.hidden === true;
        list.hidden = !willOpen;
        btn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        btn.classList.toggle('is-active', willOpen);

        if (willOpen) {
          var deptButtons = list.querySelectorAll('.dashboard-sidebar-department-button');
          if (deptButtons && deptButtons.length) {
            deptButtons.forEach(function (b) { b.hidden = false; });
          }
        }
      });
    });

    document.addEventListener('click', function (e) {
      var btn = e.target.closest('.dashboard-sidebar-department-button');
      if (!btn) return;
      var deptList = btn.closest('.dashboard-sidebar-department-list') || btn.closest('.dashboard-management-list');
      if (!deptList) return;

      var buttons = Array.from(deptList.querySelectorAll('.dashboard-sidebar-department-button'));
      var visible = buttons.filter(function (b) { return !b.hidden; });
      if (visible.length === 1 && visible[0] === btn) {
        buttons.forEach(function (b) {
          b.hidden = false;
          b.classList.remove('is-active');
        });
        return;
      }

      buttons.forEach(function (b) {
        if (b === btn) {
          b.hidden = false;
          b.classList.add('is-active');
        } else {
          b.hidden = true;
          b.classList.remove('is-active');
        }
      });

      var deptId = btn.getAttribute('data-planner-department-id');
      if (deptId && typeof setActiveDepartment === 'function') {
        setActiveDepartment(deptId);
      }
    });

    plannerDepartmentButtons.forEach(function (button) {
      button.addEventListener('click', function () {
        if (typeof setActiveDepartment === 'function') {
          setActiveDepartment(button.getAttribute('data-planner-department-id'));
        }
      });
    });
  }

  window.DashboardSidebar = {
    init: initSidebar,
  };
})();
