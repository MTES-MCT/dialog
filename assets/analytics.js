import { beginMatomoTracking } from './lib';

const _matomoEnabledValue = process.env.MATOMO_ENABLED || '';

if (_matomoEnabledValue && _matomoEnabledValue !== 'false') {
    beginMatomoTracking();
}
