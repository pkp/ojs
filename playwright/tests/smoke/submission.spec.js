// @ts-check
const {test, expect} = require('../../support/fixtures.js');
const {SubmissionWizardPage} = require('../../pages/SubmissionWizardPage.js');

/**
 * OJS-specific smoke spec. Demonstrates the end-to-end wiring:
 *   - imports `test` from the OJS fixtures file (not the shared base)
 *   - uses a storageState produced by bootstrap to skip login
 *   - receives the OJS `ojsApi` fixture for fast data setup
 *   - drives the UI through a Page Object Model
 */
test.use({storageState: 'playwright/.auth/author.json'});

test.describe('author submission smoke', () => {
	// Declaration-level test.fixme — body never runs, storageState not loaded.
	// Convert to `test(...)` once bootstrap seeds .auth files.
	test.fixme('creates submission via API', async ({ojsApi, page}) => {
		// TODO: ojsApi.createSubmission(...), open wizard POM, assert
	});
});
