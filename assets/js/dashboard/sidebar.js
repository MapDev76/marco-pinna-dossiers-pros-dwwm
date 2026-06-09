(function(){
  var iconsBase = String((window.DashboardConfig && window.DashboardConfig.iconsBase) || '/assets/icons/');
  var isIconAsset = function (icon) {
    return /\.(svg|png|jpe?g|gif|webp|ico)$/i.test(String(icon || ''));
  };
  var escapeHtml = function (value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  };
  var renderIconHtml = function (icon) {
    if (!icon) return '';
    if (isIconAsset(icon)) {
      return '<img src="' + escapeHtml(iconsBase + encodeURIComponent(icon)) + '" aria-hidden="true" class="calendar-icon-img">';
    }
    return escapeHtml(icon);
  };

  function initSidebar(options) {
    var sidebar = options.sidebar;
    var sidebarHandle = options.sidebarHandle;
    var plannerDepartmentButtons = options.plannerDepartmentButtons || [];
    var setActiveDepartment = options.setActiveDepartment;
    var onTransferEmployeeDepartment = options.onTransferEmployeeDepartment;
    var departmentToggle = document.querySelector('[data-sidebar-department-toggle]');
    var departmentList = document.querySelector('.dashboard-sidebar-department-list');
    var currentDepartment = document.querySelector('[data-sidebar-current-department]');

    var getDepartmentButtons = function () {
      return departmentList ? Array.from(departmentList.querySelectorAll('.dashboard-sidebar-department-button')) : [];
    };

    var getActiveDepartmentButton = function () {
      return getDepartmentButtons().find(function (button) {
        return button.classList.contains('is-active');
      }) || null;
    };

    var parseDragUserId = function (event) {
      if (!event || !event.dataTransfer) return 0;
      var raw = event.dataTransfer.getData('application/json') || event.dataTransfer.getData('text/plain') || '';
      if (!raw) return 0;
      try {
        var payload = JSON.parse(raw);
        var userId = parseInt(String(payload && payload.userId || '0'), 10) || 0;
        return userId > 0 ? userId : 0;
      } catch (_error) {
        return 0;
      }
    };

    var clearDepartmentDropTargets = function () {
      getDepartmentButtons().forEach(function (button) {
        button.classList.remove('is-drop-target');
      });
    };

    var syncDepartmentToggleState = function (expanded) {
      if (!departmentToggle) return;
      departmentToggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    };

    var syncCurrentDepartment = function (button) {
      if (!currentDepartment) return;
      if (!button) {
        currentDepartment.hidden = true;
        currentDepartment.innerHTML = '';
        return;
      }

      var icon = button.getAttribute('data-planner-department-icon') || '🏷️';
      var name = button.getAttribute('data-planner-department-name') || 'Department';
      var color = button.getAttribute('data-planner-department-color') || '#b98b12';
      currentDepartment.hidden = false;
      currentDepartment.innerHTML = '<button type="button" class="dashboard-sidebar-current-department-button" style="color:' + escapeHtml(color) + ';">' + renderIconHtml(icon) + ' ' + escapeHtml(name) + '</button>';
      var currentButton = currentDepartment.querySelector('button');
      if (currentButton) {
        currentButton.addEventListener('click', function () {
          expandDepartments();
        });
      }
    };

    var expandDepartments = function () {
      if (!departmentList) return;
      departmentList.hidden = false;
      getDepartmentButtons().forEach(function (button) {
        button.hidden = false;
      });
      syncDepartmentToggleState(true);
      if (currentDepartment) currentDepartment.hidden = true;
    };

    var collapseDepartments = function (selectedButton) {
      if (!departmentList) return;
      var activeButton = selectedButton || getActiveDepartmentButton();
      departmentList.hidden = true;
      getDepartmentButtons().forEach(function (button) {
        button.hidden = !!activeButton && button !== activeButton;
      });
      syncDepartmentToggleState(false);
      syncCurrentDepartment(activeButton);
    };

    var openSidebar = function () {
      document.body.classList.add('sidebar-expanded');
    };

    var closeSidebar = function () {
      document.body.classList.remove('sidebar-expanded');
    };

    var bindToggleSidebar = function () {
      if (!sidebar || !sidebarHandle) return;

      var toggleSidebar = function () {
        if (document.body.classList.contains('sidebar-expanded')) {
          closeSidebar();
        } else {
          openSidebar();
        }
      };

      sidebarHandle.addEventListener('click', function (event) {
        event.preventDefault();
        toggleSidebar();
      });

      sidebarHandle.addEventListener('keydown', function (event) {
        if (event.key !== 'Enter' && event.key !== ' ') return;
        event.preventDefault();
        toggleSidebar();
      });
    };

    if (sidebar && sidebarHandle) {
      bindToggleSidebar();
      closeSidebar();
    }

    if (departmentList) {
      collapseDepartments();
    }

    document.querySelectorAll('.management-toggle').forEach(function (btn) {
      if (btn.hasAttribute('data-sidebar-department-toggle')) {
        return;
      }

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

    if (departmentToggle) {
      departmentToggle.addEventListener('click', function () {
        expandDepartments();
      });
    }

    document.addEventListener('click', function (e) {
      var btn = e.target.closest('.dashboard-sidebar-department-button');
      if (!btn) return;
      var deptList = btn.closest('.dashboard-sidebar-department-list') || btn.closest('.dashboard-management-list');
      if (!deptList) return;

      var buttons = Array.from(deptList.querySelectorAll('.dashboard-sidebar-department-button'));
      buttons.forEach(function (button) {
        button.classList.toggle('is-active', button === btn);
      });

      collapseDepartments(btn);

      var deptId = btn.getAttribute('data-planner-department-id');
      if (deptId && typeof setActiveDepartment === 'function') {
        setActiveDepartment(deptId);
      }
    });

    getDepartmentButtons().forEach(function (button) {
      button.addEventListener('dragover', function (event) {
        if (typeof onTransferEmployeeDepartment !== 'function') return;
        var userId = parseDragUserId(event);
        if (userId <= 0) return;
        event.preventDefault();
        if (event.dataTransfer) {
          event.dataTransfer.dropEffect = 'move';
        }
        clearDepartmentDropTargets();
        button.classList.add('is-drop-target');
      });

      button.addEventListener('dragleave', function () {
        button.classList.remove('is-drop-target');
      });

      button.addEventListener('drop', function (event) {
        if (typeof onTransferEmployeeDepartment !== 'function') return;
        var userId = parseDragUserId(event);
        var targetDepartmentId = parseInt(String(button.getAttribute('data-planner-department-id') || '0'), 10) || 0;
        if (userId <= 0 || targetDepartmentId <= 0) {
          clearDepartmentDropTargets();
          return;
        }

        event.preventDefault();
        clearDepartmentDropTargets();
        onTransferEmployeeDepartment({
          userId: userId,
          targetDepartmentId: targetDepartmentId,
          targetDepartmentName: String(button.getAttribute('data-planner-department-name') || '').trim(),
        });
      });
    });

    document.addEventListener('dragend', clearDepartmentDropTargets);

    plannerDepartmentButtons.forEach(function (button) {
      button.addEventListener('click', function () {
        if (typeof setActiveDepartment === 'function') {
          setActiveDepartment(button.getAttribute('data-planner-department-id'));
        }
      });
    });

    collapseDepartments();
  }

  window.DashboardSidebar = {
    init: initSidebar,
  };
})();
