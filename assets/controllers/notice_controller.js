import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        id: String,
    };

    connect() {
        const noticeId = this.idValue || this.element.dataset.noticeId || 'default';
        this.storageKey = `notice:dismissed:${noticeId}`;

        if (window.localStorage.getItem(this.storageKey) === '1') {
            this.removeNotice();
        }
    }

    dismiss() {
        window.localStorage.setItem(this.storageKey, '1');
        this.removeNotice();
    }

    removeNotice() {
        if (this.element && this.element.parentNode) {
            this.element.parentNode.removeChild(this.element);
        }
    }
} 
