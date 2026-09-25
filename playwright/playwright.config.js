/**
 * @file playwright/playwright.config.js
 *
 * OJS fleet: port 8000 (+parallelIndex per worker). All harness mechanics live
 * in the shared factory.
 */
const path = require('path');
const {definePkpConfig} = require('../lib/pkp/playwright/config-factory.js');

module.exports = definePkpConfig({
    appName: 'ojs',
    appRoot: path.resolve(__dirname, '..'),
    basePort: 8000,
});
