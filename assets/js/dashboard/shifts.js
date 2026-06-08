(() => {
  const feedback = window.DashboardFeedback;

  function notifyError(message) {
    if (feedback) {
      feedback.error('Oops!', message);
      return;
    }
    console.error(message);
  }

  function notifySuccess(message) {
    if (feedback) {
      feedback.success('Done', message);
      return;
    }
  }

  function getCurrentMonthRange() {
    const now = new Date();
    const y = now.getFullYear();
    const m = now.getMonth();
    const first = new Date(y, m, 1);
    const last = new Date(y, m + 1, 0);
    const fmt = (d) => d.toISOString().slice(0, 10);
    return { start: fmt(first), end: fmt(last) };
  }

  function getDefaultIcon() {
    const row = getCreateRow();
    const input = row ? row.querySelector('input[data-field="icon"]') : null;
    return (input?.defaultValue || input?.value || '🕒').trim();
  }

  function getDefaultColor() {
    const row = getCreateRow();
    const input = row ? row.querySelector('input[data-field="color"]') : null;
    return (input?.defaultValue || input?.value || '#2f6fed').trim();
  }

  function getColorPreviewInput(scope) {
    return scope ? scope.querySelector('input[data-color-preview]') : null;
  }

  function getIconPreviewInput(scope) {
    return scope ? scope.querySelector('input[data-icon-preview]') : null;
  }

  function buildIconUrl(input, iconValue) {
    if (!input || !iconValue || !/\.(svg|png|jpe?g|gif|webp|ico)$/i.test(iconValue)) return '';
    const base = input.dataset.iconBase || '/assets/icons/';
    return base + encodeURIComponent(iconValue);
  }

  function syncIconPreview(scope) {
    const iconInput = getIconPreviewInput(scope);
    if (!iconInput) return;
    const iconValue = (iconInput.value || '').trim();
    const iconUrl = buildIconUrl(iconInput, iconValue);
    if (iconUrl) {
      iconInput.style.setProperty('background-image', `url("${iconUrl}")`);
      iconInput.title = iconValue;
    } else {
      iconInput.style.removeProperty('background-image');
      iconInput.title = iconValue;
    }
  }

  function isShiftsPanelActive() {
    const panel = document.querySelector('.settings-panel[data-settings-panel="shifts"]');
    return !!panel && !panel.hidden;
  }

  function getCreateRow() {
    return document.querySelector('[data-shift-create-row]');
  }

  function getShiftRow(el) {
    return el.closest && el.closest('[data-shift-id]');
  }

  function getShiftDrawer(row) {
    return row ? row.querySelector('.settings-edit-drawer') : null;
  }

  function closeAllDrawers() {
    document.querySelectorAll('[data-shift-id] .settings-edit-drawer').forEach((drawer) => {
      drawer.hidden = true;
    });
  }

  function openDrawer(row) {
    if (!row) return;
    closeAllDrawers();
    const drawer = getShiftDrawer(row);
    if (drawer) drawer.hidden = false;
  }

  function closeDrawer(row) {
    const drawer = getShiftDrawer(row);
    if (drawer) drawer.hidden = true;
  }

  function closeAllPickers(exceptPopover = null) {
    document.querySelectorAll('[data-picker-popover]').forEach((popover) => {
      const isShiftPicker = !!popover.__ownerScope || !!popover.closest('[data-shift-create-row], [data-shift-id]');
      if (!isShiftPicker) return;
      const shouldKeepOpen = exceptPopover && popover === exceptPopover;
      popover.hidden = !shouldKeepOpen;
      const stack = getPopoverStack(popover);
      if (stack) stack.classList.toggle('is-open', shouldKeepOpen);
      const toggle = getPopoverToggle(popover, stack);
      if (toggle) toggle.setAttribute('aria-expanded', shouldKeepOpen ? 'true' : 'false');
      if (!shouldKeepOpen) {
        restorePopover(popover);
      }
    });
  }

  function getPopoverStack(popover) {
    if (!popover) return null;
    const local = popover.closest('.settings-picker-stack');
    if (local) return local;
    const scope = popover.__ownerScope;
    const field = popover.getAttribute('data-picker-popover');
    if (!scope || !field) return null;
    const toggle = scope.querySelector(`[data-picker-toggle="${field}"]`);
    return toggle ? toggle.closest('.settings-picker-stack') : null;
  }

  function getPopoverToggle(popover, stack = null) {
    const field = popover?.getAttribute?.('data-picker-popover');
    if (!field) return null;
    if (stack) {
      const inStack = stack.querySelector(`[data-picker-toggle="${field}"]`);
      if (inStack) return inStack;
    }
    const scope = popover?.__ownerScope;
    return scope ? scope.querySelector(`[data-picker-toggle="${field}"]`) : null;
  }

  function positionPopover(popover, toggle) {
    if (!popover || !toggle) return;
    const rect = toggle.getBoundingClientRect();
    const viewportPadding = 8;
    const gap = 6;
    const maxWidth = Math.min(360, window.innerWidth - 16);
    const desiredWidth = Math.max(rect.width, 280);
    const width = Math.min(desiredWidth, maxWidth);

    popover.style.position = 'fixed';
    popover.style.zIndex = '40000';
    popover.style.width = `${width}px`;
    popover.style.maxHeight = `${Math.max(240, window.innerHeight - (viewportPadding * 2))}px`;
    popover.style.overflow = 'auto';

    let left = rect.left;
    if (left + width > window.innerWidth - viewportPadding) {
      left = window.innerWidth - width - viewportPadding;
    }
    if (left < viewportPadding) left = viewportPadding;

    const panelHeight = Math.min(
      popover.getBoundingClientRect().height || popover.scrollHeight || 420,
      parseInt(popover.style.maxHeight, 10) || 420
    );

    const spaceBelow = Math.max(0, window.innerHeight - rect.bottom - viewportPadding);
    const spaceAbove = Math.max(0, rect.top - viewportPadding);
    let top = rect.bottom + gap;

    if (panelHeight > spaceBelow && spaceAbove > spaceBelow) {
      top = rect.top - panelHeight - gap;
    }

    if (top + panelHeight > window.innerHeight - viewportPadding) {
      top = window.innerHeight - viewportPadding - panelHeight;
    }
    if (top < viewportPadding) {
      top = viewportPadding;
    }

    popover.style.left = `${Math.round(left)}px`;
    popover.style.top = `${Math.round(top)}px`;
  }

  function portalPopover(popover, scope, toggle) {
    if (!popover || !scope || !toggle) return;
    if (!popover.__originParent) {
      popover.__originParent = popover.parentNode;
      popover.__originNextSibling = popover.nextSibling;
    }
    popover.__ownerScope = scope;
    if (popover.parentNode !== document.body) {
      document.body.appendChild(popover);
    }
  }

  function restorePopover(popover) {
    if (!popover) return;
    if (popover.parentNode === document.body && popover.__originParent) {
      const parent = popover.__originParent;
      const next = popover.__originNextSibling;
      if (next && next.parentNode === parent) {
        parent.insertBefore(popover, next);
      } else {
        parent.appendChild(popover);
      }
    }
    popover.style.removeProperty('position');
    popover.style.removeProperty('z-index');
    popover.style.removeProperty('width');
    popover.style.removeProperty('max-height');
    popover.style.removeProperty('left');
    popover.style.removeProperty('top');
  }

  function syncChoiceState(scope) {
    if (!scope) return;
    scope.querySelectorAll('[data-choice-field]').forEach((group) => {
      const field = group.getAttribute('data-choice-field');
      const input = scope.querySelector(`input[data-field="${field}"]`);
      const currentValue = (input?.value || '').trim();
      const previewInput = field === 'color' ? getColorPreviewInput(scope) : null;
      if (previewInput) {
        previewInput.value = '';
        previewInput.style.setProperty('--selected-color', currentValue || '#2f6fed');
        previewInput.title = currentValue || '#2f6fed';
      }
      group.querySelectorAll('[data-choice-value]').forEach((btn) => {
        const isSelected = btn.getAttribute('data-choice-value') === currentValue;
        btn.classList.toggle('is-selected', isSelected);
        btn.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
      });
    });
    syncIconPreview(scope);
  }

  function setChoiceValue(scope, field, value) {
    if (!scope || !field) return;
    const input = scope.querySelector(`input[data-field="${field}"]`);
    if (!input) return;
    input.value = value;
    if (field === 'color') {
      const previewInput = getColorPreviewInput(scope);
      if (previewInput) {
        previewInput.value = '';
        previewInput.style.setProperty('--selected-color', value || '#2f6fed');
        previewInput.title = value || '#2f6fed';
      }
    }
    syncChoiceState(scope);
    closeAllPickers();
  }

  function togglePicker(scope, field) {
    if (!scope || !field) return;
    const popover = scope.querySelector(`[data-picker-popover="${field}"]`);
    const toggle = scope.querySelector(`[data-picker-toggle="${field}"]`);
    if (!popover || !toggle) return;
    const willOpen = popover.hidden;
    closeAllPickers(willOpen ? popover : null);
    if (willOpen) {
      portalPopover(popover, scope, toggle);
    }
    popover.hidden = !willOpen;
    const stack = toggle.closest('.settings-picker-stack');
    if (stack) stack.classList.toggle('is-open', willOpen);
    toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    if (!willOpen) {
      restorePopover(popover);
    } else {
      positionPopover(popover, toggle);
    }
  }

  function resetCreateShiftForm() {
    const row = getCreateRow();
    if (!row) return;
    const activeDepartmentId = Number(window.DashboardPlannerData?.active_department_id || 0);
    const month = getCurrentMonthRange();
    const deptSelect = row.querySelector('select[data-field="department_ids"]');
    if (deptSelect && activeDepartmentId) {
      Array.from(deptSelect.options || []).forEach((option) => {
        option.selected = Number(option.value) === activeDepartmentId;
      });
    }
    const defaults = {
      'input[data-field="name"]': '',
      'input[data-field="icon"]': row.querySelector('input[data-field="icon"]')?.defaultValue || getDefaultIcon(),
      'input[data-field="color"]': row.querySelector('input[data-field="color"]')?.defaultValue || getDefaultColor(),
      'input[data-field="description"]': '',
      'input[data-field="range_start"]': month.start,
      'input[data-field="range_end"]': month.end,
      'input[data-field="start_time"]': '09:00',
      'input[data-field="end_time"]': '17:00',
    };
    Object.entries(defaults).forEach(([selector, value]) => {
      const input = row.querySelector(selector);
      if (input) input.value = value;
    });
    const kindSelect = row.querySelector('select[data-field="kind"]');
    if (kindSelect) kindSelect.value = 'work';
    syncChoiceState(row);
  }

  function getSelectedDepartmentIds(row) {
    const multi = row.querySelector('select[data-field="department_ids"]');
    if (multi) {
      const ids = Array.from(multi.selectedOptions || [])
        .map((option) => parseInt(option.value || '0', 10) || 0)
        .filter((id) => id > 0);
      return Array.from(new Set(ids));
    }

    const single = parseInt(row.querySelector('select[data-field="department_id"]')?.value || '0', 10) || 0;
    return single > 0 ? [single] : [];
  }

  function getSharedShiftIds(row) {
    if (!row) return [];
    const fromDataset = String(row.dataset.shiftSharedIds || '')
      .split(',')
      .map((value) => parseInt(value.trim() || '0', 10) || 0)
      .filter((id) => id > 0);
    const primaryId = parseInt(row.dataset.shiftId || '0', 10) || 0;
    if (primaryId > 0) {
      fromDataset.push(primaryId);
    }
    return Array.from(new Set(fromDataset));
  }

  async function createShift() {
    const row = getCreateRow();
    if (!row) return;
    const departmentIds = getSelectedDepartmentIds(row);
    const icon = row.querySelector('input[data-field="icon"]')?.value.trim() || getDefaultIcon();
    const color = row.querySelector('input[data-field="color"]')?.value.trim() || getDefaultColor();
    const description = row.querySelector('input[data-field="description"]')?.value.trim() || '';
    const kind = row.querySelector('select[data-field="kind"]')?.value || 'work';
    const name = row.querySelector('input[data-field="name"]')?.value.trim() || '';
    const range_start = row.querySelector('input[data-field="range_start"]')?.value || '';
    const range_end = row.querySelector('input[data-field="range_end"]')?.value || '';
    const start_time = row.querySelector('input[data-field="start_time"]')?.value || '';
    const end_time = row.querySelector('input[data-field="end_time"]')?.value || '';

    if (!departmentIds.length) return notifyError('Choose at least one department for the new shift.');
    if (!name) return notifyError('Enter a shift title.');
    if (kind === 'work') {
      if (!range_start || !range_end) return notifyError('Set both start and end date.');
      if (range_end < range_start) return notifyError('End date must be after start date.');
    }

    const normalizedStart = kind === 'work' ? start_time : (start_time || '00:00');
    const normalizedEnd = kind === 'work' ? end_time : (end_time || '23:59');

    try {
      const res = await AppAPI.shifts.create(window.DashboardConfig.apiShifts, {
        department_ids: departmentIds,
        department_id: departmentIds[0],
        name,
        start_time: normalizedStart,
        end_time: normalizedEnd,
        icon,
        color,
        description,
        kind,
        range_start,
        range_end,
      });
      if (res?.ok) {
        if (feedback?.reloadSettingsTabWithSuccess) {
          feedback.reloadSettingsTabWithSuccess('shifts', 'Done', 'Shift created successfully.');
        } else {
          notifySuccess('Shift created successfully.');
          location.reload();
        }
      } else {
        notifyError('Failed to create shift: ' + (res?.error || 'unknown'));
      }
    } catch (e) {
      console.error(e);
      notifyError('Error creating shift.');
    }
  }

  async function saveShift(row) {
    if (!row) return;
    const shiftIds = getSharedShiftIds(row);
    if (!shiftIds.length) return;
    const normalizeTime = (raw) => {
      const value = String(raw || '').trim();
      if (!value) return '';
      return value.length >= 5 ? value.slice(0, 5) : value;
    };
    const startInput = row.querySelector('input[data-field="start_time"]');
    const endInput = row.querySelector('input[data-field="end_time"]');
    const providedName = row.querySelector('input[data-field="name"]')?.value?.trim() || '';
    const payloadBase = {
      name: providedName || (row.dataset.shiftName || '').trim(),
      icon: row.querySelector('input[data-field="icon"]')?.value || getDefaultIcon(),
      color: row.querySelector('input[data-field="color"]')?.value || getDefaultColor(),
      description: row.querySelector('input[data-field="description"]')?.value || '',
      kind: row.dataset.shiftKind || 'work',
      start_time: normalizeTime(startInput?.value || startInput?.defaultValue || ''),
      end_time: normalizeTime(endInput?.value || endInput?.defaultValue || ''),
    };
    if (!payloadBase.name) {
      delete payloadBase.name;
    }
    if (shiftIds.length === 1) {
      payloadBase.department_id = parseInt(row.dataset.shiftDepartmentId || '0', 10) || null;
    }
    try {
      const results = await Promise.all(
        shiftIds.map((shiftId) => AppAPI.shifts.update(window.DashboardConfig.apiShifts, {
          ...payloadBase,
          id: shiftId,
        }).then((result) => ({ ok: !!result?.ok, result })).catch((error) => ({ ok: false, error })))
      );
      const successCount = results.filter((item) => item.ok).length;
      const firstFailed = results.find((item) => !item.ok);
      if (successCount === shiftIds.length) {
        if (feedback?.reloadSettingsTabWithSuccess) {
          feedback.reloadSettingsTabWithSuccess('shifts', 'Done', 'Shift updated successfully.');
        } else {
          notifySuccess('Shift updated successfully.');
          location.reload();
        }
      } else if (successCount > 0) {
        if (feedback?.reloadSettingsTabWithSuccess) {
          feedback.reloadSettingsTabWithSuccess('shifts', 'Done', `Shift updated on ${successCount}/${shiftIds.length} shared rows.`);
        } else {
          notifySuccess(`Shift updated on ${successCount}/${shiftIds.length} shared rows.`);
          location.reload();
        }
      } else {
        const failureMessage = firstFailed?.result?.error || firstFailed?.error?.message || 'unknown';
        notifyError('Failed to save shift: ' + failureMessage);
      }
    } catch (e) {
      console.error(e);
      notifyError('Error saving shift.');
    }
  }

  async function deleteShift(row) {
    if (!row) return;
    const shiftIds = getSharedShiftIds(row);
    if (!shiftIds.length) return;
    const locale = (document.documentElement.getAttribute('lang') || 'en').toLowerCase();
    const isFr = locale.startsWith('fr');
    const tr = (en, fr) => (isFr ? fr : en);
    const canDelete = feedback?.confirm ? await feedback.confirm(tr('Delete this shift?', 'Supprimer ce poste ?'), tr('Confirm deletion', 'Confirmer la suppression')) : false;
    if (!feedback?.confirm) {
      notifyError('Confirmation dialog is not available.');
      return;
    }
    if (!canDelete) return;
    try {
      const results = await Promise.all(
        shiftIds.map((shiftId) => AppAPI.shifts.delete(window.DashboardConfig.apiShifts, shiftId))
      );
      const failed = results.find((result) => !result?.ok);
      if (!failed) {
        if (feedback?.reloadSettingsTabWithSuccess) {
          feedback.reloadSettingsTabWithSuccess('shifts', 'Done', 'Shift deleted successfully.');
        } else {
          notifySuccess('Shift deleted successfully.');
          row.remove();
        }
      } else {
        notifyError('Failed to delete shift: ' + (failed?.error || 'unknown'));
      }
    } catch (e) {
      console.error(e);
      notifyError('Error deleting shift.');
    }
  }

  // Make multi-department selection easier: single click toggles each option.
  document.addEventListener('mousedown', (ev) => {
    if (!isShiftsPanelActive()) return;
    const option = ev.target.closest && ev.target.closest('option');
    if (!option) return;
    const select = option.parentElement;
    if (!select || select.tagName !== 'SELECT') return;
    if (select.getAttribute('data-field') !== 'department_ids' || !select.multiple) return;
    ev.preventDefault();
    option.selected = !option.selected;
    select.dispatchEvent(new Event('change', { bubbles: true }));
  });

  document.addEventListener('click', (ev) => {
    if (!isShiftsPanelActive()) return;

    const pickerToggle = ev.target.closest && ev.target.closest('[data-picker-toggle]');
    if (pickerToggle) {
      ev.preventDefault();
      const scope = pickerToggle.closest('[data-shift-id], [data-shift-create-row]');
      const field = pickerToggle.getAttribute('data-picker-toggle');
      togglePicker(scope, field);
      return;
    }

    const choiceBtn = ev.target.closest && ev.target.closest('[data-choice-field] [data-choice-value]');
    if (choiceBtn) {
      ev.preventDefault();
      const group = choiceBtn.closest('[data-choice-field]');
      const popup = choiceBtn.closest('[data-picker-popover]');
      const scope = choiceBtn.closest('[data-shift-id], [data-shift-create-row]') || popup?.__ownerScope || null;
      const field = group?.getAttribute('data-choice-field');
      const value = choiceBtn.getAttribute('data-choice-value') || '';
      if (scope && field && value) {
        setChoiceValue(scope, field, value);
      }
      return;
    }

    const createBtn = ev.target.closest && ev.target.closest('.settings-shift-create');
    if (createBtn) {
      ev.preventDefault();
      createShift();
      return;
    }

    const resetBtn = ev.target.closest && ev.target.closest('.settings-shift-reset');
    if (resetBtn) {
      ev.preventDefault();
      resetCreateShiftForm();
      return;
    }

    const editBtn = ev.target.closest && ev.target.closest('.settings-shift-edit');
    if (editBtn) {
      ev.preventDefault();
      openDrawer(getShiftRow(editBtn));
      return;
    }

    const cancelBtn = ev.target.closest && ev.target.closest('.settings-shift-cancel');
    if (cancelBtn) {
      ev.preventDefault();
      closeDrawer(getShiftRow(cancelBtn));
      return;
    }

    const saveBtn = ev.target.closest && ev.target.closest('.settings-shift-save');
    if (saveBtn) {
      ev.preventDefault();
      saveShift(getShiftRow(saveBtn));
      return;
    }

    const deleteBtn = ev.target.closest && ev.target.closest('.settings-shift-delete');
    if (deleteBtn) {
      ev.preventDefault();
      deleteShift(getShiftRow(deleteBtn));
      return;
    }

    if (!ev.target.closest('.settings-picker-stack') && !ev.target.closest('.settings-picker-popover')) {
      closeAllPickers();
    }
  });

  window.addEventListener('resize', () => {
    document.querySelectorAll('[data-picker-popover]').forEach((popover) => {
      if (popover.hidden || !popover.__ownerScope) return;
      const field = popover.getAttribute('data-picker-popover');
      if (!field) return;
      const toggle = popover.__ownerScope.querySelector(`[data-picker-toggle="${field}"]`);
      if (toggle) positionPopover(popover, toggle);
    });
  });

  document.querySelectorAll('[data-shift-create-row], [data-shift-id]').forEach((scope) => {
    syncChoiceState(scope);
  });

  resetCreateShiftForm();
})();
