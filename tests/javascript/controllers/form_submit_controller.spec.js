import { jest } from '@jest/globals';
import { screen } from '@testing-library/dom';
import { Application } from '@hotwired/stimulus';

import SubmitNoopController from './submit_noop_controller';
import FormSubmitController from '../../../assets/controllers/form_submit_controller';

describe('FormSubmitController', () => {
    beforeEach(() => {
        document.body.innerHTML = `
        <form
            data-controller="form-submit submit-noop"
            data-form-submit-event-name-value="custom:submit"
            data-testid="form"
        ></form>
        `;

        const application = Application.start();
        application.register('submit-noop', SubmitNoopController);
        application.register('form-submit', FormSubmitController);
    });

    test('submits the form', async () => {
        const form = screen.getByTestId("form");
        form.requestSubmit = jest.fn();
        form.dispatchEvent(new CustomEvent("custom:submit"));
        expect(form.requestSubmit).toHaveBeenCalled();
    });
});
