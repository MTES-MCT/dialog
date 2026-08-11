import { startStimulusApp } from '@symfony/stimulus-bridge';
import { registerTurboEventHandlers } from './lib';

// Registers Stimulus controllers from controllers.json and in the controllers/ directory
// Les fichiers *.test.js sont exclus pour ne pas embarquer les tests (et Vitest) dans le bundle.
export const app = startStimulusApp(require.context(
    '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
    true,
    /^(?!.*\.test\.[jt]sx?$).*\.[jt]sx?$/
));

// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);

registerTurboEventHandlers();
