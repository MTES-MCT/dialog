import { screen, waitFor } from '@testing-library/dom';
import userEvent from "@testing-library/user-event";
import { Application } from '@hotwired/stimulus';

import SubmitNoopController from './submit_noop_controller';
import FormSubmitController from '../../../assets/controllers/form_submit_controller';
import ModalController from '../../../assets/controllers/modal_controller';
import ModalTriggerController from '../../../assets/controllers/modal_trigger_controller';

describe('ModalTriggerController', () => {
    beforeEach(() => {
        document.body.innerHTML = `
        <button
            type="button"
            data-controller="modal-trigger"
            data-modal-trigger-modal-outlet="#test-modal"
            data-modal-trigger-key-value="abcd1234"
            data-action="modal-trigger#showModal:prevent"
            aria-controls="test-modal"
        >
            Open modal
        </button>

        <dialog
            data-controller="modal"
            id="test-modal"
            class="fr-modal"
            data-testid="test-modal"
        >
            <form method="dialog" data-controller="submit-noop">
                <button>Close</button>
                <button data-modal-target="value">Proceed</button>
                <button>Cancel</button>
            </form>
        </dialog>
        `;

        const application = Application.start();
        application.register('submit-noop', SubmitNoopController);
        application.register('form-submit', FormSubmitController);
        application.register('modal', ModalController);
        application.register('modal-trigger', ModalTriggerController);
    });

    it('opens the modal', async () => {
        const user = userEvent.setup();

        const modal = screen.getByTestId('test-modal');
        expect(modal).not.toBeVisible();

        const triggerBtn = screen.getByText('Open modal');
        await user.click(triggerBtn);

        await waitFor(() => expect(modal).toBeVisible());
    });
});
