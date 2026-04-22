// @ts-check
const {test, expect} = require('../support/fixtures.js');
const {SubmissionWizardPage} = require('../pages/SubmissionWizardPage.js');

/**
 * OJS submission spec. Demonstrates the end-to-end wiring:
 *   - imports `test` from the OJS fixtures file (not the shared base)
 *   - uses a storageState produced by bootstrap to skip login
 *   - receives the OJS `ojsApi` fixture for fast data setup
 *   - drives the UI through a Page Object Model
 *
 * Tag convention — filter on the CLI with `--grep @smoke` / `--grep @regression`.
 * See lib/pkp/playwright/tests/login.spec.js for the full tag list.
 */
test.use({storageState: 'playwright/.auth/author.json'});

test.describe('author submission', () => {
	// Declaration-level test.fixme — body never runs, storageState not loaded.
	// Convert to `test(...)` once bootstrap seeds .auth files.
	test.fixme(
		'creates submission via API',
		{tag: '@smoke'},
		async ({ojsApi, page}) => {
			// TODO: ojsApi.createSubmission(...), open wizard POM, assert
		},
	);
});
