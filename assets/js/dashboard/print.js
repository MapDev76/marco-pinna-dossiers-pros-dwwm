/*
 * Dashboard planning print/export module.
 *
 * Builds an Excel-like planning grid for the selected department and allows:
 * - Print (selected month from navigator)
 * - CSV download
 * - Save CSV into Documents for messaging attachments
 */
(function () {
  var config = window.DashboardConfig || {};
  var plannerData = window.DashboardPlannerData || {};
  var feedback = window.DashboardFeedback || null;

  function notifySuccess(message) {
    if (feedback && typeof feedback.success === 'function') {
      feedback.success('Done', message);
      return;
    }
    console.log(message);
  }

  function notifyError(message) {
    if (feedback && typeof feedback.error === 'function') {
      feedback.error('Oops!', message);
      return;
    }
    console.error(message);
  }

  function escapeHtml(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/\"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function pad(value) {
    return String(value).padStart(2, '0');
  }

  function toDateKey(dateObj) {
    return dateObj.getFullYear() + '-' + pad(dateObj.getMonth() + 1) + '-' + pad(dateObj.getDate());
  }

  function monthLabel(dateObj) {
    return new Intl.DateTimeFormat('en-US', { month: 'long', year: 'numeric' }).format(dateObj);
  }

  function dateTimeLabel(dateObj) {
    return new Intl.DateTimeFormat('en-GB', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
    }).format(dateObj);
  }

  function weekdayShort(dateObj) {
    return new Intl.DateTimeFormat('en-US', { weekday: 'short' }).format(dateObj);
  }

  function getRuntime() {
    return window.DashboardPlannerRuntime || null;
  }

  function getState() {
    var runtime = getRuntime();
    if (runtime && typeof runtime.getState === 'function') {
      return runtime.getState() || {};
    }
    return {};
  }

  function getDepartments() {
    var runtime = getRuntime();
    if (runtime && typeof runtime.getDepartments === 'function') {
      return runtime.getDepartments() || [];
    }
    return Array.isArray(plannerData.departments) ? plannerData.departments : [];
  }

  function getEvents() {
    var runtime = getRuntime();
    if (runtime && typeof runtime.getEvents === 'function') {
      return runtime.getEvents() || [];
    }
    return Array.isArray(plannerData.assignments) ? plannerData.assignments : [];
  }

  function getActiveDepartmentId() {
    var state = getState();
    var stateId = Number(state.activeDepartmentId || 0);
    if (stateId > 0) return stateId;

    var activeButton = document.querySelector('.dashboard-sidebar-department-button.is-active[data-planner-department-id]');
    if (activeButton) {
      return Number(activeButton.getAttribute('data-planner-department-id') || 0);
    }

    return Number(plannerData.active_department_id || 0);
  }

  function getActiveDepartment() {
    var departmentId = getActiveDepartmentId();
    var departments = getDepartments();
    for (var index = 0; index < departments.length; index += 1) {
      if (Number(departments[index] && departments[index].id || 0) === departmentId) {
        return departments[index];
      }
    }
    return departments[0] || null;
  }

  function getBaseMonthDate() {
    var state = getState();
    var focusDate = state && state.focusDate ? new Date(state.focusDate) : null;
    if (focusDate && !Number.isNaN(focusDate.getTime())) {
      return new Date(focusDate.getFullYear(), focusDate.getMonth(), 1, 12, 0, 0, 0);
    }
    var now = new Date();
    return new Date(now.getFullYear(), now.getMonth(), 1, 12, 0, 0, 0);
  }

  function getCurrentUserName() {
    var title = document.querySelector('.site-header-title');
    if (title && title.textContent) {
      var parsedTitle = String(title.textContent).trim();
      if (parsedTitle) return parsedTitle;
    }
    var userMeta = document.querySelector('.site-header-meta .site-header-title');
    if (userMeta && userMeta.textContent) {
      var parsedMeta = String(userMeta.textContent).trim();
      if (parsedMeta) return parsedMeta;
    }
    return 'Admin';
  }

  function buildMonthDays(monthDate) {
    var first = new Date(monthDate.getFullYear(), monthDate.getMonth(), 1, 12, 0, 0, 0);
    var last = new Date(monthDate.getFullYear(), monthDate.getMonth() + 1, 0, 12, 0, 0, 0);
    var days = [];
    for (var day = 1; day <= last.getDate(); day += 1) {
      var current = new Date(first.getFullYear(), first.getMonth(), day, 12, 0, 0, 0);
      days.push({
        date: current,
        key: toDateKey(current),
        day: day,
        weekday: weekdayShort(current),
      });
    }
    return days;
  }

  function normalizeEmployeeName(userRow) {
    var first = String(userRow && userRow.first_name || '').trim();
    var last = String(userRow && userRow.last_name || '').trim();
    var full = (first + ' ' + last).trim();
    if (full) return full;
    return String(userRow && userRow.email || 'Employee #' + Number(userRow && userRow.id || 0));
  }

  function sortUsers(users) {
    return users.slice().sort(function (left, right) {
      return normalizeEmployeeName(left).localeCompare(normalizeEmployeeName(right));
    });
  }

  function normalizeShiftColor(colorValue) {
    var value = String(colorValue || '').trim();
    if (!/^#[0-9a-fA-F]{3,8}$/.test(value)) {
      return '#9ca3af';
    }
    return value;
  }

  function buildAssignmentIndex(assignments, departmentId) {
    var index = {};
    (assignments || []).forEach(function (item) {
      var itemDepartmentId = Number(item && item.department_id || 0);
      var userId = Number(item && item.user_id || 0);
      var dateKey = String(item && item.work_date || '');
      if (itemDepartmentId !== Number(departmentId || 0)) return;
      if (!userId || !/^\d{4}-\d{2}-\d{2}$/.test(dateKey)) return;

      var key = userId + '|' + dateKey;
      if (!index[key]) {
        index[key] = [];
      }
      index[key].push({
        kind: String(item && item.shift_kind || 'work').toLowerCase(),
        shiftName: String(item && item.shift_name || ''),
        shiftIcon: String(item && item.shift_icon || ''),
        shiftColor: normalizeShiftColor(item && item.shift_color || ''),
      });
    });
    return index;
  }

  function monthSectionData(monthDate, users, assignmentIndex) {
    var days = buildMonthDays(monthDate);
    var rows = users.map(function (userRow) {
      var userId = Number(userRow && userRow.id || 0);
      var employeeName = normalizeEmployeeName(userRow);
      var cells = days.map(function (dayInfo) {
        var key = userId + '|' + dayInfo.key;
        var dayAssignments = assignmentIndex[key] || [];
        if (!dayAssignments.length) {
          return {
            value: '',
            className: 'is-empty',
            details: '',
            style: '',
          };
        }

        var first = dayAssignments[0];
        var cellValue = ((first.shiftIcon ? first.shiftIcon + ' ' : '') + first.shiftName).trim();
        return {
          value: cellValue || (first.kind === 'work' ? 'Work' : 'No work'),
          className: first.kind === 'work' ? 'is-work' : 'is-no-work',
          details: '',
          style: '--print-shift-color:' + first.shiftColor + ';',
        };
      });
      return {
        userId: userId,
        employeeName: employeeName,
        cells: cells,
      };
    });

    return {
      monthDate: monthDate,
      monthLabel: monthLabel(monthDate),
      days: days,
      rows: rows,
    };
  }

  function renderMonthTable(section) {
    var headCells = section.days.map(function (dayInfo) {
      return '<th><div class="dashboard-print-day-number">' + dayInfo.day + '</div><div class="dashboard-print-day-weekday">' + escapeHtml(dayInfo.weekday) + '</div></th>';
    }).join('');

    var bodyRows = section.rows.map(function (row) {
      var cells = row.cells.map(function (cell) {
        var details = cell.details ? ('<small>' + escapeHtml(cell.details) + '</small>') : '';
        var styleAttr = cell.style ? (' style="' + escapeHtml(cell.style) + '"') : '';
        return '<td class="' + cell.className + '"' + styleAttr + '><span>' + escapeHtml(cell.value) + '</span>' + details + '</td>';
      }).join('');
      return '<tr><th scope="row">' + escapeHtml(row.employeeName) + '</th>' + cells + '</tr>';
    }).join('');

    return '' +
      '<section class="dashboard-print-page">' +
        '<h3>' + escapeHtml(section.monthLabel) + '</h3>' +
        '<div class="dashboard-print-grid-wrap">' +
          '<table class="dashboard-print-grid">' +
            '<thead>' +
              '<tr><th class="employee-col">Employee</th>' + headCells + '</tr>' +
            '</thead>' +
            '<tbody>' + bodyRows + '</tbody>' +
          '</table>' +
        '</div>' +
      '</section>';
  }

  function buildShiftLegend(activeDepartment) {
    var shifts = Array.isArray(activeDepartment && activeDepartment.shifts)
      ? activeDepartment.shifts
      : [];
    if (!shifts.length) {
      return '<div class="dashboard-print-legend-empty">No shifts configured for this department.</div>';
    }

    var ordered = shifts.slice().sort(function (left, right) {
      var leftTime = String(left && left.start_time || '00:00:00');
      var rightTime = String(right && right.start_time || '00:00:00');
      return leftTime.localeCompare(rightTime);
    });

    return '<div class="dashboard-print-legend-list">' + ordered.map(function (shift) {
      var color = normalizeShiftColor(shift && shift.color || '');
      var icon = String(shift && shift.icon || '').trim();
      var name = String(shift && shift.name || 'Shift').trim();
      var start = String(shift && shift.start_time || '--:--').slice(0, 5);
      var end = String(shift && shift.end_time || '--:--').slice(0, 5);
      var label = ((icon ? icon + ' ' : '') + name).trim();
      return '' +
        '<span class="dashboard-print-legend-item" style="--print-shift-color:' + escapeHtml(color) + ';">' +
          '<strong>' + escapeHtml(label) + '</strong>' +
          '<small>' + escapeHtml(start + ' - ' + end) + '</small>' +
        '</span>';
    }).join('') + '</div>';
  }

  function toCsvValue(value) {
    var text = String(value == null ? '' : value);
    if (/[,\"\n]/.test(text)) {
      return '"' + text.replace(/\"/g, '""') + '"';
    }
    return text;
  }

  function buildMonthCsv(section, meta) {
    var lines = [];
    lines.push('Planning ' + section.monthLabel);
    lines.push('Department,' + toCsvValue(meta.departmentName || ''));
    lines.push('Generated by,' + toCsvValue(meta.generatedBy || 'Admin'));
    lines.push('Generated at,' + toCsvValue(meta.generatedAt || ''));
    lines.push('');
    var headers = ['Employee'].concat(section.days.map(function (dayInfo) { return dayInfo.key; }));
    lines.push(headers.map(toCsvValue).join(','));

    section.rows.forEach(function (row) {
      var values = [row.employeeName].concat(row.cells.map(function (cell) {
        return cell.value || '';
      }));
      lines.push(values.map(toCsvValue).join(','));
    });

    return lines.join('\n');
  }

  function buildMonthExcelHtml(section, meta) {
    var headerCells = section.days.map(function (dayInfo) {
      return '<th>' + escapeHtml(dayInfo.key) + '</th>';
    }).join('');

    var bodyRows = section.rows.map(function (row) {
      var cells = row.cells.map(function (cell) {
        return '<td>' + escapeHtml(cell.value || '') + '</td>';
      }).join('');
      return '<tr><th>' + escapeHtml(row.employeeName) + '</th>' + cells + '</tr>';
    }).join('');

    return '' +
      '<html><head><meta charset="UTF-8"></head><body>' +
        '<table border="1" cellspacing="0" cellpadding="4">' +
          '<tr><th colspan="' + String(section.days.length + 1) + '">Planning ' + escapeHtml(section.monthLabel) + '</th></tr>' +
          '<tr><th>Department</th><td colspan="' + String(section.days.length) + '">' + escapeHtml(meta.departmentName || '') + '</td></tr>' +
          '<tr><th>Generated by</th><td colspan="' + String(section.days.length) + '">' + escapeHtml(meta.generatedBy || 'Admin') + '</td></tr>' +
          '<tr><th>Generated at</th><td colspan="' + String(section.days.length) + '">' + escapeHtml(meta.generatedAt || '') + '</td></tr>' +
          '<tr><th>Employee</th>' + headerCells + '</tr>' +
          bodyRows +
        '</table>' +
      '</body></html>';
  }

  function utf8ToBase64(value) {
    var bytes = new TextEncoder().encode(String(value || ''));
    var binary = '';
    bytes.forEach(function (b) {
      binary += String.fromCharCode(b);
    });
    return window.btoa(binary);
  }

  function downloadTextFile(fileName, content, mimeType) {
    var blob = new Blob([content], { type: mimeType || 'text/plain;charset=utf-8' });
    var url = URL.createObjectURL(blob);
    var anchor = document.createElement('a');
    anchor.href = url;
    anchor.download = fileName;
    document.body.appendChild(anchor);
    anchor.click();
    anchor.remove();
    window.setTimeout(function () {
      URL.revokeObjectURL(url);
    }, 100);
  }

  function initPrintModal() {
    var modal = document.getElementById('modal-print');
    if (!modal) return;

    var shellNode = modal.querySelector('[data-print-shell]');
    var contentNode = modal.querySelector('[data-print-content]');
    var metaNode = modal.querySelector('[data-print-meta]');
    var feedbackNode = modal.querySelector('[data-print-feedback]');
    var refreshButton = modal.querySelector('[data-print-refresh]');
    var previewButton = modal.querySelector('[data-print-preview]');
    var excelButton = modal.querySelector('[data-print-export-excel]');
    var pdfButton = modal.querySelector('[data-print-export-pdf]');
    var downloadButton = modal.querySelector('[data-print-download-csv]');
    var saveDocumentButton = modal.querySelector('[data-print-save-document]');
    var printButton = modal.querySelector('[data-print-trigger]');

    var currentRender = null;
    var previewEnabled = false;

    function setFeedback(message, isError) {
      if (!feedbackNode) return;
      feedbackNode.textContent = message || '';
      feedbackNode.classList.toggle('is-error', !!isError);
      feedbackNode.classList.toggle('is-success', !!message && !isError);
    }

    function fitPreviewToA4() {
      if (!previewEnabled || !contentNode) return;
      var frame = contentNode.querySelector('[data-print-fit-frame]');
      var fitContent = contentNode.querySelector('[data-print-fit-content]');
      if (!frame || !fitContent) return;

      fitContent.style.transform = 'scale(1)';
      fitContent.style.left = '0px';
      fitContent.style.top = '0px';

      var frameWidth = frame.clientWidth;
      var frameHeight = frame.clientHeight;
      var contentWidth = fitContent.scrollWidth || fitContent.offsetWidth || 1;
      var contentHeight = fitContent.scrollHeight || fitContent.offsetHeight || 1;
      if (!frameWidth || !frameHeight || !contentWidth || !contentHeight) return;

      var scaleWidth = frameWidth / contentWidth;
      var scaleHeight = frameHeight / contentHeight;
      var scale = Math.min(scaleWidth, scaleHeight, 1);
      if (scale < 0.62) {
        scale = Math.min(scaleWidth, 0.62);
      }
      var offsetX = Math.max(0, (frameWidth - (contentWidth * scale)) / 2);
      var offsetY = Math.max(0, (frameHeight - (contentHeight * scale)) / 2);

      fitContent.style.left = offsetX + 'px';
      fitContent.style.top = offsetY + 'px';
      fitContent.style.transform = 'scale(' + scale.toFixed(4) + ')';
    }

    function setPreviewState(nextState) {
      previewEnabled = !!nextState;
      if (shellNode) {
        shellNode.classList.toggle('is-preview-active', previewEnabled);
      }
      if (previewButton) {
        previewButton.classList.toggle('is-active', previewEnabled);
        previewButton.textContent = previewEnabled ? 'Hide Preview' : 'Preview A4';
      }
      if (previewEnabled) {
        setFeedback('A4 preview enabled. Planning is scaled to fit one A4 page.', false);
      }
    }

    function renderPlanning() {
      var department = getActiveDepartment();
      if (!department) {
        if (contentNode) {
          contentNode.innerHTML = '<div class="dashboard-sidebar-planner-placeholder">No department selected.</div>';
        }
        if (metaNode) {
          metaNode.textContent = '';
        }
        currentRender = null;
        return;
      }

      var users = sortUsers(Array.isArray(department.users) ? department.users : []);
      var assignments = getEvents();
      var assignmentIndex = buildAssignmentIndex(assignments, Number(department.id || 0));
      var baseMonth = getBaseMonthDate();
      var currentMonthSection = monthSectionData(baseMonth, users, assignmentIndex);
      var generatedAt = new Date();
      var generatedBy = getCurrentUserName();

      currentRender = {
        department: department,
        baseMonth: baseMonth,
        section: currentMonthSection,
        generatedBy: generatedBy,
        generatedAt: generatedAt,
      };

      if (metaNode) {
        metaNode.innerHTML =
          '<span><strong>Department:</strong> ' + escapeHtml(String(department.name || 'Department')) + '</span>' +
          '<span><strong>Employees:</strong> ' + users.length + '</span>' +
          '<span><strong>Month:</strong> ' + escapeHtml(currentMonthSection.monthLabel) + '</span>' +
          '<span><strong>Generated:</strong> ' + escapeHtml(dateTimeLabel(generatedAt)) + '</span>' +
          '<span><strong>Author:</strong> ' + escapeHtml(generatedBy) + '</span>';
      }

      if (contentNode) {
        var tableHtml = renderMonthTable(currentMonthSection);
        var legendHtml = '<section class="dashboard-print-shifts-legend"><h4>Shift legend</h4>' + buildShiftLegend(department) + '</section>';

        if (previewEnabled) {
          contentNode.innerHTML =
            '<section class="dashboard-print-a4-sheet" data-print-a4-sheet>' +
              '<div class="dashboard-print-fit-frame" data-print-fit-frame>' +
                '<div class="dashboard-print-fit-content" data-print-fit-content>' + tableHtml + '</div>' +
              '</div>' +
              legendHtml +
            '</section>';
        } else {
          contentNode.innerHTML = tableHtml + legendHtml;
        }
      }
      if (previewEnabled) {
        window.requestAnimationFrame(fitPreviewToA4);
      } else {
        setFeedback('', false);
      }
    }

    function buildCombinedCsv() {
      if (!currentRender) return '';
      return buildMonthCsv(currentRender.section, {
        departmentName: String((currentRender.department && currentRender.department.name) || 'Department'),
        generatedBy: String(currentRender.generatedBy || 'Admin'),
        generatedAt: dateTimeLabel(currentRender.generatedAt || new Date()),
      });
    }

    if (refreshButton) {
      refreshButton.addEventListener('click', function () {
        renderPlanning();
      });
    }

    if (previewButton) {
      previewButton.addEventListener('click', function () {
        setPreviewState(!previewEnabled);
        renderPlanning();
      });
    }

    if (downloadButton) {
      downloadButton.addEventListener('click', function () {
        if (!currentRender) {
          notifyError('No planning data available to export.');
          return;
        }
        var dep = currentRender.department || {};
        var fileName = 'planning-' + String(dep.name || 'department').toLowerCase().replace(/[^a-z0-9]+/g, '-') + '-' + toDateKey(currentRender.baseMonth).slice(0, 7) + '.csv';
        downloadTextFile(fileName, buildCombinedCsv(), 'text/csv;charset=utf-8');
        notifySuccess('CSV downloaded successfully.');
      });
    }

    if (excelButton) {
      excelButton.addEventListener('click', function () {
        if (!currentRender) {
          notifyError('No planning data available to export.');
          return;
        }
        var dep = currentRender.department || {};
        var period = toDateKey(currentRender.baseMonth).slice(0, 7);
        var fileName = 'planning-' + String(dep.name || 'department').toLowerCase().replace(/[^a-z0-9]+/g, '-') + '-' + period + '.xls';
        var excel = buildMonthExcelHtml(currentRender.section, {
          departmentName: String(dep.name || 'Department'),
          generatedBy: String(currentRender.generatedBy || 'Admin'),
          generatedAt: dateTimeLabel(currentRender.generatedAt || new Date()),
        });
        downloadTextFile(fileName, excel, 'application/vnd.ms-excel;charset=utf-8');
        notifySuccess('Excel file downloaded successfully.');
      });
    }

    if (saveDocumentButton) {
      saveDocumentButton.addEventListener('click', async function () {
        if (!currentRender) {
          notifyError('No planning data available to save.');
          return;
        }
        if (!config.apiDashboard || !window.AppAPI || typeof window.AppAPI.postJSON !== 'function') {
          notifyError('Dashboard API is not available.');
          return;
        }

        var dep = currentRender.department || {};
        var monthStart = toDateKey(currentRender.baseMonth).slice(0, 7) + '-01';
        var baseName = 'planning-' + String(dep.name || 'department').toLowerCase().replace(/[^a-z0-9]+/g, '-') + '-' + toDateKey(currentRender.baseMonth).slice(0, 7) + '.csv';
        var csvContent = buildCombinedCsv();

        saveDocumentButton.disabled = true;
        setFeedback('Saving file to Documents...', false);
        try {
          var response = await window.AppAPI.postJSON(config.apiDashboard, {
            action: 'save_planning_document',
            department_id: Number(dep.id || 0),
            month_start: monthStart,
            file_name: baseName,
            csv_content_b64: utf8ToBase64(csvContent),
          });

          if (!response || response.ok === false || response.success === false) {
            throw new Error((response && (response.error || response.message)) || 'Unable to save planning document.');
          }

          setFeedback('Saved to Documents: ' + String(response.file_name || baseName), false);
          notifySuccess('Planning CSV saved to Documents and ready for message attachments.');
        } catch (error) {
          var message = (error && error.message) ? error.message : 'Unable to save planning document.';
          setFeedback(message, true);
          notifyError(message);
        } finally {
          saveDocumentButton.disabled = false;
        }
      });
    }

    function openPrintDialog() {
      if (!currentRender) {
        notifyError('No planning data available to print.');
        return;
      }

      var runPrint = function () {
        document.body.classList.add('print-planning-mode');
        window.print();
      };

      if (!previewEnabled) {
        setPreviewState(true);
        renderPlanning();
        window.setTimeout(runPrint, 80);
        return;
      }

      window.requestAnimationFrame(function () {
        fitPreviewToA4();
        runPrint();
      });
    }

    if (pdfButton) {
      pdfButton.addEventListener('click', function () {
        openPrintDialog();
        setFeedback('In the print dialog choose "Save as PDF".', false);
      });
    }

    if (printButton) {
      printButton.addEventListener('click', function () {
        openPrintDialog();
      });
    }

    window.addEventListener('afterprint', function () {
      document.body.classList.remove('print-planning-mode');
    });

    modal.addEventListener('modal:open', function () {
      renderPlanning();
    });

    window.addEventListener('resize', function () {
      if (!modal.hidden && previewEnabled) {
        window.requestAnimationFrame(fitPreviewToA4);
      }
    });

    document.addEventListener('dashboard:planner-updated', function () {
      if (!modal.hidden) {
        renderPlanning();
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPrintModal);
  } else {
    initPrintModal();
  }
})();
