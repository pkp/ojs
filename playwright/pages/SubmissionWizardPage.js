// @ts-check
const {BasePage} = require('../../lib/pkp/playwright/pages/BasePage.js');

/**
 * POM for the OJS submission wizard. OJS-specific — OMP/OPS have their
 * own equivalents in their own repos. Locator stubs only; real action
 * methods (fillMetadata, addAuthor, uploadFile, submit) fill in during
 * per-spec migration from Cypress.
 */
exports.SubmissionWizardPage = class SubmissionWizardPage extends BasePage {
	constructor(page) {
		super(page);
		this.titleInput = page.getByLabel('Title');
		this.submitButton = page.getByRole('button', {name: 'Submit'});
	}

	async goto(contextPath = 'publicknowledge') {
		await this.page.goto(`/${contextPath}/submissions/new`);
	}

	// TODO: fillMetadata, addAuthor, uploadFile, submit
};
