/**
 * @file playwright/support/fixtures.js
 *
 * The OJS test extension — layers OJS fixtures on top of the shared base.
 * OJS specs import from here; feature fixtures land here as suites grow.
 */
const base = require('../../lib/pkp/playwright/support/base-test.js');

const test = base.test.extend({
    // OJS alias for the shared test-API client.
    ojsApi: async ({pkpApi}, use) => {
        await use(pkpApi);
    },
});

module.exports = {test, expect: base.expect};
