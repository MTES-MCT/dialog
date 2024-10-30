// @ts-check

import { getAttributeOrError, querySelectorOrError } from "./util";

customElements.define('d-responsive-iframe', class extends HTMLElement {
    connectedCallback() {
        /** @type {HTMLIFrameElement} */
        const iframe = querySelectorOrError(this, 'iframe');

        // On vérifie la 'origin' pour éviter le risque de Cross-Site Scripting (XSS)
        // Voir : https://developer.mozilla.org/en-US/docs/Web/API/Window/postMessage#security_concerns
        const targetOrigin = getAttributeOrError(this, 'targetOrigin');

        // Inspiré de : https://towardsdev.com/the-most-elegant-way-to-implement-dynamic-size-iframe-2-0-319b974f1048

        const resizeObserver = new ResizeObserver((entries) => {
            for (const entry of entries) {
                window.parent.postMessage({
                    type: 'dialog.resize',
                    width: entry.borderBoxSize[0].inlineSize,
                });
            }
        });

        resizeObserver.observe(document.body);

        const maxWidth = +(this.getAttribute('maxWidth') || iframe.clientWidth);
        const extraPadding = +(this.getAttribute('extraPadding') || '0'); // px

        window.addEventListener('message', function (event) {
            if (event.origin === targetOrigin && event.data.type === 'dialog.resize') {
                const windowWidth = event.data.width;
                const newWidth = Math.min(windowWidth - extraPadding, maxWidth);

                try {
                    iframe.style.width = `${newWidth}px`;
                } catch (e) {
                    console.error(e);
                }
            }
        });
    }
});
