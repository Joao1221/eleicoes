(function () {
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    const isIos = /iphone|ipad|ipod/i.test(window.navigator.userAgent)
        || (window.navigator.platform === 'MacIntel' && window.navigator.maxTouchPoints > 1);

    if (isStandalone) {
        document.documentElement.dataset.displayMode = 'standalone';
        return;
    }

    document.documentElement.dataset.displayMode = 'browser';

    let deferredInstallPrompt = null;
    let installButton = null;

    function findInstallTarget() {
        return document.querySelector('[data-pwa-install-target]')
            || document.querySelector('.topbar-right .topbar-actions:last-of-type')
            || document.querySelector('.topbar-actions')
            || document.querySelector('.header-actions');
    }

    function createInstallButton() {
        if (installButton) {
            return installButton;
        }

        const target = findInstallTarget();
        if (!target) {
            return null;
        }

        const button = document.createElement('button');
        button.type = 'button';
        button.textContent = 'Instalar app';
        button.hidden = true;
        button.className = target.classList.contains('header-actions')
            ? 'btn btn-secondary pwa-install-btn'
            : 'btn ghost pwa-install-btn';
        button.setAttribute('aria-label', 'Instalar o app ApoiaCandidato');

        button.addEventListener('click', async () => {
            if (deferredInstallPrompt) {
                deferredInstallPrompt.prompt();
                await deferredInstallPrompt.userChoice;
                deferredInstallPrompt = null;
                button.hidden = true;
                return;
            }

            if (isIos) {
                window.alert('No iPhone, toque em Compartilhar e depois em Adicionar a Tela de Inicio.');
            }
        });

        target.prepend(button);
        installButton = button;
        return button;
    }

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredInstallPrompt = event;

        const button = createInstallButton();
        if (button) {
            button.hidden = false;
        }
    });

    window.addEventListener('appinstalled', () => {
        deferredInstallPrompt = null;
        if (installButton) {
            installButton.hidden = true;
        }
    });

    if (isIos) {
        window.addEventListener('load', () => {
            const button = createInstallButton();
            if (button) {
                button.hidden = false;
            }
        });
    }
}());
