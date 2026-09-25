// @ts-check
const {BasePage} = require('../../lib/pkp/playwright/pages/BasePage.js');

/**
 * POM for the OJS issue management page. OJS-specific.
 * Shell for now; real action methods fill in per-migration.
 */
exports.IssuePage = class IssuePage extends BasePage {
	constructor(page) {
		super(page);
		this.createIssueButton = page.getByRole('button', {name: 'Create Issue'});
	}

	async goto(contextPath = 'publicknowledge') {
		await this.page.goto(`/${contextPath}/management/issues`);
	}

	// TODO: createIssue, assignSubmission, publishIssue
};
