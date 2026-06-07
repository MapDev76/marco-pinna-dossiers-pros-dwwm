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
  var locale = String(document.documentElement.getAttribute('lang') || 'en').toLowerCase();
  var isFr = locale.indexOf('fr') === 0;
  function tr(enText, frText) {
    return isFr ? frText : enText;
  }

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
    return new Intl.DateTimeFormat(isFr ? 'fr-FR' : 'en-US', { month: 'long', year: 'numeric' }).format(dateObj);
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
    return new Intl.DateTimeFormat(isFr ? 'fr-FR' : 'en-US', { weekday: 'short' }).format(dateObj);
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

  function employeeInitialsFromName(name) {
    var cleaned = String(name || '').replace(/\s+/g, ' ').trim();
    if (!cleaned) return '--';
    var parts = cleaned.split(' ').filter(Boolean);
    if (!parts.length) return '--';
    if (parts.length === 1) {
      return parts[0].slice(0, 2).toUpperCase();
    }
    return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
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
      var employeeInitials = employeeInitialsFromName(employeeName);
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
        var displayIcon = String(first.shiftIcon || '').trim() || '•';
        return {
          value: displayIcon,
          exportValue: cellValue || (first.kind === 'work' ? tr('Work', 'Travail') : tr('No work', 'Sans travail')),
          className: (first.kind === 'work' ? 'is-work' : 'is-no-work') + ' is-icon-only',
          details: '',
          style: '--print-shift-color:' + first.shiftColor + ';',
        };
      });
      return {
        userId: userId,
        employeeName: employeeName,
        employeeInitials: employeeInitials,
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
        var titleAttr = cell.exportValue ? (' title="' + escapeHtml(cell.exportValue) + '"') : '';
        return '<td class="' + cell.className + '"' + styleAttr + titleAttr + '><span>' + escapeHtml(cell.value) + '</span>' + details + '</td>';
      }).join('');
      return '<tr><th scope="row" title="' + escapeHtml(row.employeeName) + '">' + escapeHtml(row.employeeInitials || '--') + '</th>' + cells + '</tr>';
    }).join('');

    return '' +
      '<section class="dashboard-print-page">' +
        '<h3>' + escapeHtml(section.monthLabel) + '</h3>' +
        '<div class="dashboard-print-grid-wrap">' +
          '<table class="dashboard-print-grid">' +
            '<thead>' +
              '<tr><th class="employee-col">Emp</th>' + headCells + '</tr>' +
            '</thead>' +
            '<tbody>' + bodyRows + '</tbody>' +
          '</table>' +
        '</div>' +
      '</section>';
  }

  function buildAttendanceRows(monthDate, department, users) {
    var start = new Date(monthDate.getFullYear(), monthDate.getMonth(), 1, 0, 0, 0, 0);
    var end = new Date(monthDate.getFullYear(), monthDate.getMonth() + 1, 1, 0, 0, 0, 0);
    var attendanceRows = Array.isArray(plannerData.attendances) ? plannerData.attendances : [];
    var userMap = {};
    users.forEach(function (userRow) {
      userMap[Number(userRow && userRow.id || 0)] = normalizeEmployeeName(userRow);
    });

    return attendanceRows
      .filter(function (row) {
        var depId = Number(row && row.department_id || 0);
        if (depId !== Number(department && department.id || 0)) return false;
        var dateValue = new Date(String(row && row.work_date || '') + 'T12:00:00');
        if (Number.isNaN(dateValue.getTime())) return false;
        return dateValue >= start && dateValue < end;
      })
      .map(function (row) {
        var userId = Number(row && row.user_id || 0);
        var fullName = userMap[userId] || String(row && row.user_name || '').trim() || (tr('Employee', 'Employe') + ' #' + userId);
        return {
          workDate: String(row && row.work_date || ''),
          initials: employeeInitialsFromName(fullName),
          fullName: fullName,
          shiftName: String(row && row.shift_name || tr('Shift', 'Poste')),
          scheduled: (String(row && row.shift_start_time || '--:--').slice(0, 5) + ' - ' + String(row && row.shift_end_time || '--:--').slice(0, 5)),
          checkIn: String(row && row.check_in_time || '').slice(0, 5) || '--:--',
          checkOut: String(row && row.check_out_time || '').slice(0, 5) || '--:--',
          status: String(row && row.status || 'present'),
          signed: Number(row && row.digital_signature_id || 0) > 0,
          signatureData: String(row && row.signature_data || ''),
        };
      })
      .sort(function (left, right) {
        var dateCmp = left.workDate.localeCompare(right.workDate);
        if (dateCmp !== 0) return dateCmp;
        return left.fullName.localeCompare(right.fullName);
      });
  }

  function renderAttendanceTable(section, rows) {
    var bodyRows = rows.map(function (row) {
      var signatureHtml = row.signatureData
        ? ('<img class="dashboard-print-signature-image" src="' + escapeHtml(row.signatureData) + '" alt="' + escapeHtml(tr('Digital signature of ', 'Signature numerique de ') + row.fullName) + '">')
        : (row.signed ? tr('Signed', 'Signe') : '&nbsp;');
      return '' +
        '<tr>' +
          '<td>' + escapeHtml(row.workDate) + '</td>' +
          '<td title="' + escapeHtml(row.fullName) + '">' + escapeHtml(row.initials) + '</td>' +
          '<td>' + escapeHtml(row.shiftName) + '</td>' +
          '<td>' + escapeHtml(row.scheduled) + '</td>' +
          '<td>' + escapeHtml(row.checkIn) + '</td>' +
          '<td>' + escapeHtml(row.checkOut) + '</td>' +
          '<td>' + escapeHtml(row.status) + '</td>' +
          '<td>' + signatureHtml + '</td>' +
        '</tr>';
    }).join('');

    if (!bodyRows) {
      bodyRows = '<tr><td colspan="8">' + escapeHtml(tr('No attendance records found for this month.', 'Aucun enregistrement de presence trouve pour ce mois.')) + '</td></tr>';
    }

    return '' +
      '<section class="dashboard-print-page dashboard-print-attendance-page">' +
        '<h3>' + escapeHtml(section.monthLabel + ' ' + tr('attendance and signatures', 'presences et signatures')) + '</h3>' +
        '<div class="dashboard-print-grid-wrap">' +
          '<table class="dashboard-print-grid dashboard-print-attendance-grid">' +
            '<thead>' +
              '<tr>' +
                '<th>' + tr('Date', 'Date') + '</th>' +
                '<th>' + tr('Emp', 'Emp') + '</th>' +
                '<th>' + tr('Shift', 'Poste') + '</th>' +
                '<th>' + tr('Planned time', 'Horaire prevu') + '</th>' +
                '<th>' + tr('Check-in', 'Entree') + '</th>' +
                '<th>' + tr('Check-out', 'Sortie') + '</th>' +
                '<th>' + tr('Status', 'Statut') + '</th>' +
                '<th>' + tr('Signature', 'Signature') + '</th>' +
              '</tr>' +
            '</thead>' +
            '<tbody>' + bodyRows + '</tbody>' +
          '</table>' +
        '</div>' +
      '</section>';
  }

  function buildEmployeeLegend(section) {
    var rows = Array.isArray(section && section.rows) ? section.rows : [];
    if (!rows.length) {
      return '<div class="dashboard-print-legend-empty">' + escapeHtml(tr('No employees in selected department.', 'Aucun employe dans le departement selectionne.')) + '</div>';
    }
    return '<div class="dashboard-print-legend-list dashboard-print-employee-legend-list">' + rows.map(function (row) {
      return '<span class="dashboard-print-legend-item dashboard-print-employee-legend-item"><strong>' +
        escapeHtml(row.employeeInitials || '--') +
        '</strong><small>' + escapeHtml(row.employeeName || '') + '</small></span>';
    }).join('') + '</div>';
  }

  function buildFooterMeta(generatedAt, generatedBy) {
    return '' +
      '<div class="dashboard-print-sheet-footer-meta">' +
        '<strong>' + tr('Created:', 'Cree le :') + '</strong> ' + escapeHtml(dateTimeLabel(generatedAt || new Date())) +
        ' <span aria-hidden="true">•</span> <strong>' + tr('By:', 'Par :') + '</strong> ' + escapeHtml(String(generatedBy || tr('Admin', 'Admin'))) +
      '</div>';
  }

  function buildShiftLegend(activeDepartment) {
    var shifts = Array.isArray(activeDepartment && activeDepartment.shifts)
      ? activeDepartment.shifts
      : [];
    if (!shifts.length) {
      return '<div class="dashboard-print-legend-empty">' + escapeHtml(tr('No shifts configured for this department.', 'Aucun poste configure pour ce departement.')) + '</div>';
    }

    var ordered = shifts.slice().sort(function (left, right) {
      var leftTime = String(left && left.start_time || '00:00:00');
      var rightTime = String(right && right.start_time || '00:00:00');
      return leftTime.localeCompare(rightTime);
    });

    var iconsBasePrint = String((window.DashboardConfig && window.DashboardConfig.iconsBase) || '/assets/icons/');
    var isIconAssetPrint = function (icon) { return /\.(svg|png|jpe?g|gif|webp|ico)$/i.test(String(icon || '')); };

    return '<div class="dashboard-print-legend-list">' + ordered.map(function (shift) {
      var color = normalizeShiftColor(shift && shift.color || '');
      var icon = String(shift && shift.icon || '').trim();
      var name = String(shift && shift.name || tr('Shift', 'Poste')).trim();
      var start = String(shift && shift.start_time || '--:--').slice(0, 5);
      var end = String(shift && shift.end_time || '--:--').slice(0, 5);
      var iconHtml = '';
      if (icon) {
        if (isIconAssetPrint(icon)) {
          iconHtml = '<img src="' + escapeHtml(iconsBasePrint + encodeURIComponent(icon)) + '" aria-hidden="true" class="calendar-icon-img"> ';
        } else {
          iconHtml = escapeHtml(icon) + ' ';
        }
      }
      return '' +
        '<span class="dashboard-print-legend-item" style="--print-shift-color:' + escapeHtml(color) + ';">' +
          '<strong>' + iconHtml + escapeHtml(name) + '</strong>' +
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
    lines.push(tr('Planning ', 'Planning ') + section.monthLabel);
    lines.push(tr('Department', 'Departement') + ',' + toCsvValue(meta.departmentName || ''));
    lines.push(tr('Generated by', 'Genere par') + ',' + toCsvValue(meta.generatedBy || tr('Admin', 'Admin')));
    lines.push(tr('Generated at', 'Genere le') + ',' + toCsvValue(meta.generatedAt || ''));
    lines.push('');
    var headers = [tr('Employee', 'Employe')].concat(section.days.map(function (dayInfo) { return dayInfo.key; }));
    lines.push(headers.map(toCsvValue).join(','));

    section.rows.forEach(function (row) {
      var values = [row.employeeName].concat(row.cells.map(function (cell) {
        return cell.exportValue || cell.value || '';
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
        return '<td>' + escapeHtml(cell.exportValue || cell.value || '') + '</td>';
      }).join('');
      return '<tr><th>' + escapeHtml(row.employeeName) + '</th>' + cells + '</tr>';
    }).join('');

    return '' +
      '<html><head><meta charset="UTF-8"></head><body>' +
        '<table border="1" cellspacing="0" cellpadding="4">' +
          '<tr><th colspan="' + String(section.days.length + 1) + '">' + tr('Planning ', 'Planning ') + escapeHtml(section.monthLabel) + '</th></tr>' +
          '<tr><th>' + tr('Department', 'Departement') + '</th><td colspan="' + String(section.days.length) + '">' + escapeHtml(meta.departmentName || '') + '</td></tr>' +
          '<tr><th>' + tr('Generated by', 'Genere par') + '</th><td colspan="' + String(section.days.length) + '">' + escapeHtml(meta.generatedBy || tr('Admin', 'Admin')) + '</td></tr>' +
          '<tr><th>' + tr('Generated at', 'Genere le') + '</th><td colspan="' + String(section.days.length) + '">' + escapeHtml(meta.generatedAt || '') + '</td></tr>' +
          '<tr><th>' + tr('Employee', 'Employe') + '</th>' + headerCells + '</tr>' +
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
    var documentTypeSelect = modal.querySelector('[data-print-document-type]');
    var layoutSelect = modal.querySelector('[data-print-layout]');
    var previewButton = modal.querySelector('[data-print-preview]');
    var excelButton = modal.querySelector('[data-print-export-excel]');
    var pdfButton = modal.querySelector('[data-print-export-pdf]');
    var downloadButton = modal.querySelector('[data-print-download-csv]');
    var saveDocumentButton = modal.querySelector('[data-print-save-document]');
    var printButton = modal.querySelector('[data-print-trigger]');

    var currentRender = null;
    var previewEnabled = false;

    function buildSectionChunks(section) {
      return [section];
    }

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
        var mode = String((documentTypeSelect && documentTypeSelect.value) || 'planning');
        setFeedback(mode === 'attendance'
          ? tr('A4 preview enabled for signatures and attendance sheet.', 'Apercu A4 active pour les signatures et la feuille de presence.')
          : tr('A4 preview enabled. Planning is scaled to fit one A4 page.', 'Apercu A4 active. Le planning est adapte sur une seule page A4.'), false);
      }
    }

    function renderPlanning() {
      var department = getActiveDepartment();
      if (!department) {
        if (contentNode) {
          contentNode.innerHTML = '<div class="dashboard-sidebar-planner-placeholder">' + escapeHtml(tr('No department selected.', 'Aucun departement selectionne.')) + '</div>';
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
      var attendanceRows = buildAttendanceRows(baseMonth, department, users);
      var generatedAt = new Date();
      var generatedBy = getCurrentUserName();
      var documentType = String((documentTypeSelect && documentTypeSelect.value) || 'planning');

      currentRender = {
        mode: documentType,
        department: department,
        baseMonth: baseMonth,
        section: currentMonthSection,
        attendanceRows: attendanceRows,
        generatedBy: generatedBy,
        generatedAt: generatedAt,
      };

      if (metaNode) {
        metaNode.innerHTML =
          '<span><strong>' + tr('Department:', 'Departement :') + '</strong> ' + escapeHtml(String(department.name || tr('Department', 'Departement'))) + '</span>' +
          '<span><strong>' + tr('Employees:', 'Employes :') + '</strong> ' + users.length + '</span>' +
          '<span><strong>' + tr('Type:', 'Type :') + '</strong> ' + escapeHtml(documentType === 'attendance' ? tr('Signatures / attendance', 'Signatures / presences') : tr('Planning', 'Planning')) + '</span>' +
          '<span><strong>' + tr('Month:', 'Mois :') + '</strong> ' + escapeHtml(currentMonthSection.monthLabel) + '</span>' +
          '<span><strong>' + tr('Generated:', 'Genere :') + '</strong> ' + escapeHtml(dateTimeLabel(generatedAt)) + '</span>' +
          '<span><strong>' + tr('Author:', 'Auteur :') + '</strong> ' + escapeHtml(generatedBy) + '</span>';
      }

      if (contentNode) {
        var sections = buildSectionChunks(currentMonthSection);
        var layoutMode = 'a4-single';

        if (documentType === 'attendance') {
          var attendanceTableHtml = renderAttendanceTable(currentMonthSection, attendanceRows);
          var employeeLegendHtml = '<section class="dashboard-print-shifts-legend"><h4>' + tr('Employee initials legend', 'Legende des initiales employes') + '</h4>' + buildEmployeeLegend(currentMonthSection) + '</section>';
          var attendanceFooter = '<div class="dashboard-print-sheet-footer">' +
            '<div class="dashboard-print-sheet-footer-left">' + employeeLegendHtml + '</div>' +
            '<div class="dashboard-print-sheet-footer-right">' + buildFooterMeta(generatedAt, generatedBy) + '</div>' +
          '</div>';

          if (previewEnabled) {
            contentNode.innerHTML =
              '<section class="dashboard-print-a4-sheet" data-print-a4-sheet>' +
                '<div class="dashboard-print-fit-frame" data-print-fit-frame>' +
                  '<div class="dashboard-print-fit-content" data-print-fit-content>' + attendanceTableHtml + '</div>' +
                '</div>' +
                attendanceFooter +
              '</section>';
          } else {
            contentNode.innerHTML = attendanceTableHtml + attendanceFooter;
          }

          if (layoutSelect) {
            layoutSelect.disabled = true;
          }
        } else {
          var tableHtml = sections.map(function (sectionPart) {
            return renderMonthTable(sectionPart);
          }).join('');
          var shiftLegendHtml = '<section class="dashboard-print-shifts-legend"><h4>' + tr('Shift legend', 'Legende des postes') + '</h4>' + buildShiftLegend(department) + '</section>';
          var employeeLegendHtmlPlanning = '<section class="dashboard-print-shifts-legend"><h4>' + tr('Employee initials legend', 'Legende des initiales employes') + '</h4>' + buildEmployeeLegend(currentMonthSection) + '</section>';
          var footerHtml = '<div class="dashboard-print-sheet-footer">' +
            '<div class="dashboard-print-sheet-footer-left">' + shiftLegendHtml + employeeLegendHtmlPlanning + '</div>' +
            '<div class="dashboard-print-sheet-footer-right">' + buildFooterMeta(generatedAt, generatedBy) + '</div>' +
          '</div>';

          if (previewEnabled && layoutMode === 'a4-single') {
            contentNode.innerHTML =
              '<section class="dashboard-print-a4-sheet" data-print-a4-sheet>' +
                '<div class="dashboard-print-fit-frame" data-print-fit-frame>' +
                  '<div class="dashboard-print-fit-content" data-print-fit-content>' + tableHtml + '</div>' +
                '</div>' +
                footerHtml +
              '</section>';
          } else {
            contentNode.innerHTML = tableHtml + footerHtml;
          }

          if (layoutSelect) {
            layoutSelect.value = 'a4-single';
            layoutSelect.disabled = true;
          }
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
      if (String(currentRender.mode || 'planning') === 'attendance') {
        var header = [
          tr('Date', 'Date'),
          tr('Employee initials', 'Initiales employe'),
          tr('Employee name', 'Nom employe'),
          tr('Shift', 'Poste'),
          tr('Planned time', 'Horaire prevu'),
          tr('Check-in', 'Entree'),
          tr('Check-out', 'Sortie'),
          tr('Status', 'Statut'),
          tr('Signed', 'Signe'),
        ];
        var lines = [];
        lines.push(tr('Attendance and signatures ', 'Presences et signatures ') + currentRender.section.monthLabel);
        lines.push(tr('Department', 'Departement') + ',' + toCsvValue(String((currentRender.department && currentRender.department.name) || tr('Department', 'Departement'))));
        lines.push(tr('Generated by', 'Genere par') + ',' + toCsvValue(String(currentRender.generatedBy || tr('Admin', 'Admin'))));
        lines.push(tr('Generated at', 'Genere le') + ',' + toCsvValue(dateTimeLabel(currentRender.generatedAt || new Date())));
        lines.push('');
        lines.push(header.map(toCsvValue).join(','));
        (currentRender.attendanceRows || []).forEach(function (row) {
          lines.push([
            row.workDate,
            row.initials,
            row.fullName,
            row.shiftName,
            row.scheduled,
            row.checkIn,
            row.checkOut,
            row.status,
            row.signed ? tr('yes', 'oui') : tr('no', 'non'),
          ].map(toCsvValue).join(','));
        });
        return lines.join('\n');
      }
      return buildMonthCsv(currentRender.section, {
        departmentName: String((currentRender.department && currentRender.department.name) || tr('Department', 'Departement')),
        generatedBy: String(currentRender.generatedBy || tr('Admin', 'Admin')),
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

    if (layoutSelect) {
      layoutSelect.addEventListener('change', function () {
        renderPlanning();
      });
    }

    if (documentTypeSelect) {
      documentTypeSelect.addEventListener('change', function () {
        renderPlanning();
      });
    }

    if (downloadButton) {
      downloadButton.addEventListener('click', function () {
        if (!currentRender) {
          notifyError(tr('No planning data available to export.', 'Aucune donnee de planning disponible pour l export.'));
          return;
        }
        var dep = currentRender.department || {};
        var mode = String(currentRender.mode || 'planning');
        var prefix = mode === 'attendance' ? 'attendance' : 'planning';
        var fileName = prefix + '-' + String(dep.name || 'department').toLowerCase().replace(/[^a-z0-9]+/g, '-') + '-' + toDateKey(currentRender.baseMonth).slice(0, 7) + '.csv';
        downloadTextFile(fileName, buildCombinedCsv(), 'text/csv;charset=utf-8');
        notifySuccess(tr('CSV downloaded successfully.', 'CSV telecharge avec succes.'));
      });
    }

    if (excelButton) {
      excelButton.addEventListener('click', function () {
        if (!currentRender) {
          notifyError(tr('No planning data available to export.', 'Aucune donnee de planning disponible pour l export.'));
          return;
        }
        var dep = currentRender.department || {};
        var period = toDateKey(currentRender.baseMonth).slice(0, 7);
        var mode = String(currentRender.mode || 'planning');
        var prefix = mode === 'attendance' ? 'attendance' : 'planning';
        var fileName = prefix + '-' + String(dep.name || 'department').toLowerCase().replace(/[^a-z0-9]+/g, '-') + '-' + period + '.xls';
        var excel = buildMonthExcelHtml(currentRender.section, {
          departmentName: String(dep.name || tr('Department', 'Departement')),
          generatedBy: String(currentRender.generatedBy || tr('Admin', 'Admin')),
          generatedAt: dateTimeLabel(currentRender.generatedAt || new Date()),
        });
        if (mode === 'attendance') {
          var attendanceLines = buildCombinedCsv().split('\n');
          excel = '<html><head><meta charset="UTF-8"></head><body><table border="1" cellspacing="0" cellpadding="4">' + attendanceLines.map(function (line, index) {
            if (!line) return '<tr><td></td></tr>';
            var cells = line.split(',').map(function (item) {
              return '<td>' + escapeHtml(item.replace(/^"|"$/g, '').replace(/""/g, '"')) + '</td>';
            }).join('');
            return '<tr>' + cells + '</tr>';
          }).join('') + '</table></body></html>';
        }
        downloadTextFile(fileName, excel, 'application/vnd.ms-excel;charset=utf-8');
        notifySuccess(tr('Excel file downloaded successfully.', 'Fichier Excel telecharge avec succes.'));
      });
    }

    if (saveDocumentButton) {
      saveDocumentButton.addEventListener('click', async function () {
        if (!currentRender) {
          notifyError(tr('No planning data available to save.', 'Aucune donnee de planning disponible a enregistrer.'));
          return;
        }
        if (!config.apiDashboard || !window.AppAPI || typeof window.AppAPI.postJSON !== 'function') {
          notifyError(tr('Dashboard API is not available.', 'API dashboard indisponible.'));
          return;
        }

        var dep = currentRender.department || {};
        var monthStart = toDateKey(currentRender.baseMonth).slice(0, 7) + '-01';
        var mode = String(currentRender.mode || 'planning');
        var period = toDateKey(currentRender.baseMonth).slice(0, 7);
        var depSlug = String(dep.name || 'department').toLowerCase().replace(/[^a-z0-9]+/g, '-');
        var baseName = mode === 'attendance'
          ? ('attendance-' + depSlug + '-' + period + '.html')
          : ('planning-' + depSlug + '-' + period + '.csv');
          var fileContent = mode === 'attendance'
           ? ('<!doctype html><html><head><meta charset="UTF-8"><title>' + tr('Attendance signatures', 'Signatures de presence') + '</title></head><body>' +
             renderAttendanceTable(currentRender.section, currentRender.attendanceRows || []) +
             '<hr>' +
             '<h4>' + tr('Employee initials legend', 'Legende des initiales employes') + '</h4>' +
             buildEmployeeLegend(currentRender.section) +
             '<p>' + buildFooterMeta(currentRender.generatedAt || new Date(), currentRender.generatedBy || tr('Admin', 'Admin')) + '</p>' +
             '</body></html>')
          : buildCombinedCsv();
        var fileMimeType = mode === 'attendance' ? 'text/html; charset=utf-8' : 'text/csv; charset=utf-8';

        saveDocumentButton.disabled = true;
        setFeedback(tr('Saving file to Documents...', 'Enregistrement du fichier dans Documents...'), false);
        try {
          var response = await window.AppAPI.postJSON(config.apiDashboard, {
            action: 'save_dashboard_document',
            document_mode: mode,
            department_id: Number(dep.id || 0),
            month_start: monthStart,
            file_name: baseName,
            file_mime_type: fileMimeType,
            file_content_b64: utf8ToBase64(fileContent),
          });

          if (!response || response.ok === false || response.success === false) {
            throw new Error((response && (response.error || response.message)) || tr('Unable to save planning document.', 'Impossible d enregistrer le document de planning.'));
          }

          setFeedback(tr('Saved to Documents: ', 'Enregistre dans Documents : ') + String(response.file_name || baseName), false);
          notifySuccess(tr('Document saved to Documents and ready for sharing with employees.', 'Document enregistre dans Documents et pret a etre partage avec les employes.'));
        } catch (error) {
          var message = (error && error.message) ? error.message : tr('Unable to save planning document.', 'Impossible d enregistrer le document de planning.');
          setFeedback(message, true);
          notifyError(message);
        } finally {
          saveDocumentButton.disabled = false;
        }
      });
    }

    function openPrintDialog() {
      if (!currentRender) {
        notifyError(tr('No planning data available to print.', 'Aucune donnee de planning disponible a imprimer.'));
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
