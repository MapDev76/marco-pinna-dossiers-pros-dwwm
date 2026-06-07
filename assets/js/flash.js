document.addEventListener('DOMContentLoaded', function () {
    // Handles flash banners with a fade-in effect and automatic dismissal.
    function initFlash(flashEl) {
        if (!flashEl) return;
        const backdropId = flashEl.dataset.backdrop || (flashEl.id ? 'flash-backdrop-' + flashEl.id.replace(/^flash-/, '') : null);
        const backdrop = backdropId ? document.getElementById(backdropId) : null;
        let autoCloseTimer = null;
        const closeBtn = flashEl.querySelector('.flash-close');

        function dismissFlash() {
            if (autoCloseTimer) {
                clearTimeout(autoCloseTimer);
                autoCloseTimer = null;
            }
            flashEl.classList.remove('show');
            if (backdrop) backdrop.classList.remove('show');
            setTimeout(() => {
                if (backdrop && backdrop.parentNode) backdrop.parentNode.removeChild(backdrop);
                if (flashEl && flashEl.parentNode) flashEl.parentNode.removeChild(flashEl);
            }, 260);
        }
        
        requestAnimationFrame(() => setTimeout(() => {
            if (backdrop) backdrop.classList.add('show');
            flashEl.classList.add('show');
        }, 10));

        autoCloseTimer = setTimeout(dismissFlash, 2800);

        if (closeBtn) {
            closeBtn.addEventListener('click', dismissFlash);
        }
    }

    document.querySelectorAll('.flash').forEach(initFlash);
});
