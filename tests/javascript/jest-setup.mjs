import './mocks';
import '@testing-library/jest-dom';

// Use `await import` to ensure DSFR has access to mocks.
await import('@gouvfr/dsfr/dist/dsfr.module');

afterEach(() => document.body.innerHTML = '');
