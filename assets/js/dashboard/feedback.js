(() => {
  const RELOAD_FLASH_KEY = 'dashboard:reload-flash';

  function removeExistingFlash() {
    document.querySelectorAll('.flash.js-dashboard-flash, .flash-backdrop.js-dashboard-flash').forEach((el) => {
      el.remove();
    });
  }

  function closeFlash(flashEl, backdropEl) {
    if (!flashEl) return;
    flashEl.classList.remove('show');
    if (backdropEl) backdropEl.classList.remove('show');
    setTimeout(() => {
      if (flashEl.parentNode) flashEl.parentNode.removeChild(flashEl);
      if (backdropEl && backdropEl.parentNode) backdropEl.parentNode.removeChild(backdropEl);
    }, 260);
  }

  function formatFlashTitle(title, isError) {
    if (title && title.trim()) {
      if (title === 'Done') return _fbTr('Done', 'Terminé');
      if (title === 'Oops!') return _fbTr('Oops!', 'Oups !');
      return title;
    }

    return isError ? _fbTr('Oops!', 'Oups !') : _fbTr('Done', 'Terminé');
  }

  function formatFlashMessage(message) {
    const raw = String(message || '').trim();
    if (!raw || !_fbIsFr) return raw;

    const dictionary = {
      'User created successfully.': 'Utilisateur créé avec succès.',
      'User updated successfully.': 'Utilisateur mis à jour avec succès.',
      'User deleted successfully.': 'Utilisateur supprimé avec succès.',
      'Department created successfully.': 'Département créé avec succès.',
      'Department updated successfully.': 'Département mis à jour avec succès.',
      'Department deleted successfully.': 'Département supprimé avec succès.',
      'Company created successfully.': 'Entreprise créée avec succès.',
      'Company updated successfully.': 'Entreprise mise à jour avec succès.',
      'Company deleted successfully.': 'Entreprise supprimée avec succès.',
      'Shift created successfully.': 'Poste créé avec succès.',
      'Shift updated successfully.': 'Poste mis à jour avec succès.',
      'Shift deleted successfully.': 'Poste supprimé avec succès.',
      'Assignment updated successfully.': 'Affectation mise à jour avec succès.',
      'Shift assigned successfully.': 'Poste assigné avec succès.',
      'Shift unassigned successfully.': 'Poste désassigné avec succès.',
      'Absence assigned successfully.': 'Absence assignée avec succès.',
      'Work shift assigned successfully.': 'Poste de travail assigné avec succès.',
      'Absence forced successfully.': 'Absence forcée avec succès.',
      'Work shift forced successfully.': 'Poste de travail forcé avec succès.',
      'Attendance updated successfully.': 'Présence mise à jour avec succès.',
      'Attendance cancelled successfully.': 'Présence annulée avec succès.',
      'Company Wi-Fi IP updated.': 'IP Wi-Fi de l\'entreprise mise à jour.',
    };

    return dictionary[raw] || raw;
  }

  function show(type, title, message) {
    removeExistingFlash();

    const isError = type === 'error';
    const flashClass = isError ? 'flash-error' : 'flash-success';

    const backdrop = document.createElement('div');
    backdrop.className = 'flash-backdrop js-dashboard-flash';

    const flash = document.createElement('div');
    flash.className = `flash ${flashClass} js-dashboard-flash`;
    flash.setAttribute('role', 'alert');
    flash.setAttribute('aria-live', 'assertive');

    const icon = document.createElement('span');
    icon.className = 'flash-icon';
    icon.setAttribute('aria-hidden', 'true');
    icon.textContent = isError ? '⚠️' : '✅';

    const body = document.createElement('div');
    body.className = 'flash-body';

    const titleEl = document.createElement('div');
    titleEl.className = 'flash-title';
    titleEl.textContent = formatFlashTitle(title, isError);

    const messageEl = document.createElement('p');
    messageEl.textContent = formatFlashMessage(message || '');

    body.appendChild(titleEl);
    body.appendChild(messageEl);
    flash.appendChild(icon);
    flash.appendChild(body);

    document.body.appendChild(backdrop);
    document.body.appendChild(flash);

    requestAnimationFrame(() => {
      backdrop.classList.add('show');
      flash.classList.add('show');
    });

    setTimeout(() => closeFlash(flash, backdrop), 2800);
  }

  const _fbLocale = (document.documentElement.getAttribute('lang') || 'en').toLowerCase();
  const _fbIsFr = _fbLocale.startsWith('fr');
  const _fbTr = (en, fr) => (_fbIsFr ? fr : en);
  const _fbIconsBase = String((window.DashboardConfig && window.DashboardConfig.iconsBase) || '/assets/icons/');

  function confirmAction(message, title) {
    if (!title) title = _fbTr('Confirm action', 'Confirmer l\'action');
    removeExistingFlash();

    return new Promise((resolve) => {
      const backdrop = document.createElement('div');
      backdrop.className = 'flash-backdrop js-dashboard-flash show';

      const flash = document.createElement('div');
      flash.className = 'flash flash-confirm js-dashboard-flash show';
      flash.setAttribute('role', 'alertdialog');
      flash.setAttribute('aria-live', 'assertive');

      const icon = document.createElement('span');
      icon.className = 'flash-icon';
      icon.setAttribute('aria-hidden', 'true');
      const iconImg = document.createElement('img');
      iconImg.src = _fbIconsBase + 'triangle-alert.svg';
      iconImg.alt = '';
      iconImg.style.cssText = 'width:32px;height:32px;object-fit:contain;';
      icon.appendChild(iconImg);

      const body = document.createElement('div');
      body.className = 'flash-body';

      const titleEl = document.createElement('div');
      titleEl.className = 'flash-title';
      titleEl.textContent = title;

      const messageEl = document.createElement('p');
      messageEl.textContent = message || _fbTr('Are you sure?', 'Êtes-vous sûr ?');

      const actions = document.createElement('div');
      actions.className = 'flash-actions';

      const cancelBtn = document.createElement('button');
      cancelBtn.type = 'button';
      cancelBtn.className = 'flash-action-btn flash-action-btn-cancel';
      cancelBtn.textContent = _fbTr('Cancel', 'Annuler');

      const confirmBtn = document.createElement('button');
      confirmBtn.type = 'button';
      confirmBtn.className = 'flash-action-btn flash-action-btn-confirm';
      confirmBtn.textContent = _fbTr('Confirm', 'Confirmer');

      const finish = (ok) => {
        closeFlash(flash, backdrop);
        resolve(!!ok);
      };

      cancelBtn.addEventListener('click', () => finish(false));
      confirmBtn.addEventListener('click', () => finish(true));
      backdrop.addEventListener('click', () => finish(false));

      actions.appendChild(cancelBtn);
      actions.appendChild(confirmBtn);
      body.appendChild(titleEl);
      body.appendChild(messageEl);
      body.appendChild(actions);

      flash.appendChild(icon);
      flash.appendChild(body);

      document.body.appendChild(backdrop);
      document.body.appendChild(flash);
      confirmBtn.focus();
    });
  }

  function getActiveSettingsTab() {
    const activeTab = document.querySelector('.settings-tab.is-active');
    return activeTab ? activeTab.getAttribute('data-settings-tab') : 'users';
  }

  function getSelectedSettingsCompanyId() {
    const select = document.querySelector('[data-settings-company-select]');
    const value = (select && typeof select.value === 'string') ? select.value.trim() : '';
    return value !== '' ? value : null;
  }

  function reloadSettingsTab(tab) {
    const nextTab = tab || getActiveSettingsTab();
    const url = new URL(window.location.href);
    url.searchParams.set('route', 'dashboard');
    url.searchParams.set('modal', 'settings');
    url.searchParams.set('settings_tab', nextTab || 'users');
    const selectedCompanyId = getSelectedSettingsCompanyId();
    if (selectedCompanyId) {
      url.searchParams.set('settings_company_id', selectedCompanyId);
    }
    window.location.assign(url.toString());
  }

  function queueReloadFlash(type, title, message) {
    try {
      window.sessionStorage.setItem(RELOAD_FLASH_KEY, JSON.stringify({
        type: type === 'error' ? 'error' : 'success',
        title: title || '',
        message: message || '',
      }));
    } catch (e) {
      // Ignore storage errors and fall back to immediate flash only.
    }
  }

  function flushQueuedFlash() {
    try {
      const raw = window.sessionStorage.getItem(RELOAD_FLASH_KEY);
      if (!raw) return;
      window.sessionStorage.removeItem(RELOAD_FLASH_KEY);
      const payload = JSON.parse(raw);
      if (!payload || !payload.message) return;
      show(payload.type === 'error' ? 'error' : 'success', payload.title || '', payload.message || '');
    } catch (e) {
      // Ignore malformed storage payloads.
    }
  }

  function reloadSettingsTabWithFlash(tab, type, title, message) {
    queueReloadFlash(type, title, message);
    reloadSettingsTab(tab);
  }

  flushQueuedFlash();

  window.DashboardFeedback = {
    success: (title, message) => show('success', title, message),
    error: (title, message) => show('error', title, message),
    info: (title, message) => show('success', title, message),
    activeTab: getActiveSettingsTab,
    reloadSettingsTab,
    reloadSettingsTabWithSuccess: (tab, title, message) => reloadSettingsTabWithFlash(tab, 'success', title, message),
    reloadSettingsTabWithError: (tab, title, message) => reloadSettingsTabWithFlash(tab, 'error', title, message),
    confirm: (message, title) => confirmAction(message, title),
  };
})();
