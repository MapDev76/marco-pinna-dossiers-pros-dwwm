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

    const closeBtn = document.createElement('button');
    closeBtn.className = 'flash-close';
    closeBtn.setAttribute('aria-label', 'Chiudi messaggio');
    closeBtn.type = 'button';
    closeBtn.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg>';

    const icon = document.createElement('span');
    icon.className = 'flash-icon';
    icon.setAttribute('aria-hidden', 'true');
    icon.textContent = isError ? '⚠️' : '✅';

    const body = document.createElement('div');
    body.className = 'flash-body';

    const titleEl = document.createElement('div');
    titleEl.className = 'flash-title';
    titleEl.textContent = title || (isError ? 'Oops!' : 'Done');

    const messageEl = document.createElement('p');
    messageEl.textContent = message || '';

    body.appendChild(titleEl);
    body.appendChild(messageEl);
    flash.appendChild(closeBtn);
    flash.appendChild(icon);
    flash.appendChild(body);

    document.body.appendChild(backdrop);
    document.body.appendChild(flash);

    closeBtn.addEventListener('click', () => closeFlash(flash, backdrop));

    requestAnimationFrame(() => {
      backdrop.classList.add('show');
      flash.classList.add('show');
    });

    setTimeout(() => closeFlash(flash, backdrop), 2800);
  }

  function getActiveSettingsTab() {
    const activeTab = document.querySelector('.settings-tab.is-active');
    return activeTab ? activeTab.getAttribute('data-settings-tab') : 'users';
  }

  function reloadSettingsTab(tab) {
    const nextTab = tab || getActiveSettingsTab();
    const url = new URL(window.location.href);
    url.searchParams.set('route', 'dashboard');
    url.searchParams.set('modal', 'settings');
    url.searchParams.set('settings_tab', nextTab || 'users');
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
  };
})();
