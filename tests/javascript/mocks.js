import { jest } from '@jest/globals';

// Required by DSFR
Object.defineProperty(window, 'matchMedia', {
    value: jest.fn().mockImplementation(query => ({
        matches: false,
        media: query,
        onchange: null,
        addEventListener: jest.fn(),
        removeEventListener: jest.fn(),
        dispatchEvent: jest.fn(),
    })),
});

// Required by DSFR
Object.defineProperty(window, 'scroll', {
    value: jest.fn().mockImplementation((_options) => { }),
});
