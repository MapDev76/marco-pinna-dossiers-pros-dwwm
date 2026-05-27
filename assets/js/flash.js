document.addEventListener('DOMContentLoaded', function () {
    function initFlash(flashEl) {
        if (!flashEl) return;
        const backdropId = flashEl.dataset.backdrop || (flashEl.id ? 'flash-backdrop-' + flashEl.id.replace(/^flash-/, '') : null);
        const backdrop = backdropId ? document.getElementById(backdropId) : null;
        // Afficher après un bref délai pour l'animation
        requestAnimationFrame(() => setTimeout(() => {
            if (backdrop) backdrop.classList.add('show');
            flashEl.classList.add('show');
        }, 10));

        const closeBtn = flashEl.querySelector('.flash-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                flashEl.classList.remove('show');
                if (backdrop) backdrop.classList.remove('show');
                setTimeout(() => {
                    if (backdrop && backdrop.parentNode) backdrop.parentNode.removeChild(backdrop);
                    if (flashEl && flashEl.parentNode) flashEl.parentNode.removeChild(flashEl);
                }, 260);
            });
        }
    }

    document.querySelectorAll('.flash').forEach(initFlash);
});
