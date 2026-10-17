// @ts-check
const {
	test: baseTest,
	expect,
} = require('../../lib/pkp/playwright/support/base-test.js');
const {createOjsApi} = require('./ojs-api.js');

/**
 * OJS-specific extended `test`. Layers OJS-only fixtures on top of the
 * shared base-test (which provides pkpApi / scenarios). Every OJS spec
 * imports `test` and `expect` from this file.
 *
 * Fixtures added here:
 *   ojsApi     — OJS-only HTTP helpers (issues, sections, subscriptions,
 *                submission factory)
 *   submission — fresh submission per test. This is the unit of isolation
 *                that lets feature specs run in parallel: each test
 *                creates its own via API and the teardown deletes it, so
 *                no two tests share state. Body is a TODO until the
 *                ojsApi methods are real.
 */
exports.test = baseTest.extend({
	ojsApi: async ({request, baseURL}, use) => {
		await use(createOjsApi({request, baseURL}));
	},

	submission: async ({ojsApi}, use) => {
		// TODO:
		//   const sub = await ojsApi.createSubmission({...});
		//   await use(sub);
		//   await ojsApi.deleteSubmission(sub.id);
		await use(null);
	},
});

exports.expect = expect;
