import { startStimulusApp } from '@symfony/stimulus-bridge';
import { registerTurboEventHandlers } from './lib';

// Registers Stimulus controllers from controllers.json and in the controllers/ directory
export const app = startStimulusApp(require.context(
    '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
    true,
    /\.[jt]sx?$/
));

// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);

// Désactive Turbo Drive par défaut, pour des raisons d'accessibilité
// On peut toujours réactiver Turbo Drive au cas par cas avec data-turbo="true"
// https://turbo.hotwired.dev/reference/drive#turbo.session.drive
Turbo.session.drive = false;

registerTurboEventHandlers();
