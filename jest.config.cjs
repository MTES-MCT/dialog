/** @type {import('jest').Config} */
const config = {
  clearMocks: true,
  collectCoverage: true,
  coverageDirectory: "coverage",
  coverageProvider: "v8",
  testEnvironment: "jsdom",
  setupFilesAfterEnv: ['./tests/javascript/jest-setup.mjs'],
  roots: [
    'tests/javascript',
  ],
};

module.exports = config;
