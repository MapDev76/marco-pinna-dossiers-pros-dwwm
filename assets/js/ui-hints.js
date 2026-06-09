(() => {
  const root = document.documentElement;
  const lang = String(root.getAttribute('lang') || 'en').toLowerCase().startsWith('fr') ? 'fr' : 'en';

  const copy = {
    en: {
      actionPrefix: 'Action:',
      inputPrefix: 'Input:',
      actionFallback: 'Activate this control.',
      inputFallback: 'Enter a value in this field.',
    },
    fr: {
      actionPrefix: 'Action :',
      inputPrefix: 'Saisie :',
      actionFallback: 'Activer ce controle.',
      inputFallback: 'Saisissez une valeur dans ce champ.',
    },
  }[lang];

  const clickableSelector = [
    'button',
    'a[href]',
    'summary.site-lang-trigger',
    '[role="button"]',
    '[data-modal-target]',
    '[data-assignment-employee-open]',
    '[data-auto-assign-open]',
    '[data-auto-assign-clear]',
    '[data-document-sign-open]',
    '[data-attendance-modal-open]',
  ].join(',');

  const inputSelector = [
    'input:not([type="hidden"]):not([disabled])',
    'textarea:not([disabled])',
    'select:not([disabled])',
  ].join(',');

  function normalizeText(value) {
    return String(value || '').replace(/\s+/g, ' ').trim();
  }

  function labelTextForField(field) {
    const id = normalizeText(field.getAttribute('id'));
    if (id) {
      const externalLabel = document.querySelector(`label[for="${CSS.escape(id)}"]`);
      if (externalLabel) {
        const text = normalizeText(externalLabel.textContent);
        if (text) return text;
      }
    }

    const wrapperLabel = field.closest('label');
    if (wrapperLabel) {
      const clone = wrapperLabel.cloneNode(true);
      clone.querySelectorAll('input, textarea, select, button, small').forEach((node) => node.remove());
      const text = normalizeText(clone.textContent);
      if (text) return text;
    }

    return '';
  }

  function deriveElementName(el, kind) {
    const direct = [
      'data-function-hint',
      'aria-label',
      'title',
      'data-modal-title',
      'placeholder',
      'name',
    ];

    for (const attr of direct) {
      const value = normalizeText(el.getAttribute(attr));
      if (value) return value;
    }

    if (kind === 'input') {
      const label = labelTextForField(el);
      if (label) return label;
    }

    const text = normalizeText(el.textContent);
    if (text) return text;

    const icon = el.querySelector('img[alt]:not([alt=""])');
    if (icon) {
      const iconAlt = normalizeText(icon.getAttribute('alt'));
      if (iconAlt) return iconAlt;
    }

    return '';
  }

  function hintMessageFor(el, kind) {
    const name = deriveElementName(el, kind);
    if (kind === 'input') {
      return name ? `${copy.inputPrefix} ${name}` : copy.inputFallback;
    }
    return name ? `${copy.actionPrefix} ${name}` : copy.actionFallback;
  }

  function prepareHints(elements, kind) {
    elements.forEach((el) => {
      if (!(el instanceof HTMLElement)) return;
      if (el.dataset.uiHintReady === '1') return;
      const message = hintMessageFor(el, kind);
      el.dataset.uiHintMessage = message;
      if (!normalizeText(el.getAttribute('title'))) {
        el.setAttribute('title', message);
      }
      el.dataset.uiHintReady = '1';
    });
  }

  function ensureToast() {
    let toast = document.getElementById('ui-function-hint-toast');
    if (toast) return toast;

    toast = document.createElement('div');
    toast.id = 'ui-function-hint-toast';
    toast.setAttribute('aria-live', 'polite');
    toast.style.position = 'fixed';
    toast.style.left = '50%';
    toast.style.bottom = '16px';
    toast.style.transform = 'translateX(-50%)';
    toast.style.maxWidth = 'min(92vw, 720px)';
    toast.style.padding = '8px 12px';
    toast.style.borderRadius = '999px';
    toast.style.border = '1px solid rgba(209,159,24,0.88)';
    toast.style.background = 'rgba(17,17,17,0.92)';
    toast.style.color = '#ffffff';
    toast.style.fontSize = '12px';
    toast.style.fontWeight = '600';
    toast.style.boxShadow = '0 8px 22px rgba(0,0,0,0.25)';
    toast.style.pointerEvents = 'none';
    toast.style.opacity = '0';
    toast.style.transition = 'opacity 0.18s ease';
    toast.style.zIndex = '2147483000';
    document.body.appendChild(toast);
    return toast;
  }

  let toastTimer = null;
  function showHint(message) {
    const text = normalizeText(message);
    if (!text) return;
    const toast = ensureToast();
    toast.textContent = text;
    toast.style.opacity = '1';
    if (toastTimer) {
      window.clearTimeout(toastTimer);
    }
    toastTimer = window.setTimeout(() => {
      toast.style.opacity = '0';
    }, 1450);
  }

  function processAllHints() {
    prepareHints(Array.from(document.querySelectorAll(clickableSelector)), 'action');
    prepareHints(Array.from(document.querySelectorAll(inputSelector)), 'input');
  }

  document.addEventListener('click', (event) => {
    const target = event.target instanceof Element ? event.target.closest(clickableSelector) : null;
    if (!(target instanceof HTMLElement)) return;
    if (target.hasAttribute('disabled') || String(target.getAttribute('aria-disabled') || '').toLowerCase() === 'true') return;
    showHint(target.dataset.uiHintMessage || hintMessageFor(target, 'action'));
  });

  document.addEventListener('focusin', (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) return;
    if (!target.matches(inputSelector)) return;
    showHint(target.dataset.uiHintMessage || hintMessageFor(target, 'input'));
  });

  const observer = new MutationObserver((mutations) => {
    let needsRefresh = false;
    for (const mutation of mutations) {
      if (mutation.type === 'childList' && (mutation.addedNodes?.length || mutation.removedNodes?.length)) {
        needsRefresh = true;
        break;
      }
      if (mutation.type === 'attributes') {
        needsRefresh = true;
        break;
      }
    }
    if (needsRefresh) {
      processAllHints();
    }
  });

  processAllHints();
  observer.observe(document.body, {
    subtree: true,
    childList: true,
    attributes: true,
    attributeFilter: ['title', 'aria-label', 'placeholder', 'hidden', 'disabled'],
  });
})();
