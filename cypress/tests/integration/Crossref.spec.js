/**
 * @file cypress/tests/integration/Crossref.spec.js
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */

describe('DOI tests', function() {
	const issueDescription = "Vol. 1 No. 2 (2014)";
	const issueId = 1;
	const submissionId = 17;
	const publicationId = 18;
	const galleyId = 3;

	it('Check DOI Configuration', function() {
		cy.login('dbarnes', null, 'publicknowledge');

		cy.get('a:contains("Distribution")').click();

		cy.get('button#dois-button').click();

		// DOI is or can be enabled
		cy.get('input[name="enableDois"]').check();
		cy.get('input[name="enableDois"]').should('be.checked');

		// Check all content
		cy.get('input[name="enabledDoiTypes"][value="publication"]').check();
		cy.get('input[name="enabledDoiTypes"][value="issue"]').check();
		cy.get('input[name="enabledDoiTypes"][value="representation"]').check();

		// Declare DOI Prefix
		cy.get('input[name=doiPrefix]').focus().clear().type('10.1234');

		// Select automatic DOI creation time
		cy.get('select[name="doiCreationTime"]').select('copyEditCreationTime');

		// Select DOI suffix pattern type
		cy.get('input[name="customDoiSuffixType"][value="issueBased"]')

		// Save
		cy.get('#doisSetup button').contains('Save').click();
		cy.get('#doisSetup [role="status"]').contains('Saved');
	});

	it('Check Issue DOI Assignment', function() {
		cy.login('dbarnes', null, 'publicknowledge');

		cy.get('a:contains("##plugins.pubIds.doi.manager.displayName##")').click(); // TODO: Change to DOIs
		cy.get('button#issue-doi-management-button').click();

		// Select the first issue
		cy.get(`input[name="issue[]"][value=${issueId}]`).check()

		// Select assign DOIs from bulk actions
		cy.get('#issue-doi-management button:contains("Bulk Actions")').click({multiple: true});
		cy.get('button#openBulkAssign').click();

		// Confirm assignment
		cy.get('div[data-modal="bulkActions"] button:contains("Assign DOIs")').click();
		cy.get('.app__notifications').contains('Items successfully assigned new DOIs', {timeout:20000});

		cy.get(`#list-item-issue-${issueId} button.expander`).click();
		cy.get(`input#${issueId}-issue`).should('have.value', '10.9876/jpkjpk.v1i2');
	});

	it('Check Issue DOI visible', function() {
		cy.login('dbarnes', null, 'publicknowledge');

		cy.get('a:contains("##plugins.pubIds.doi.manager.displayName##")').click(); // TODO: Change to DOIs
		cy.get('button#issue-doi-management-button').click();

		// View issue with assigned DOI
			cy.visit('');
			cy.get('a:contains("Archives")').click();
			cy.get('a:contains("' + issueDescription + '")').click();
			cy.get('div.pub_id').should('have.class', 'doi');
			cy.get('div.doi span.id a').contains('https://doi.org/10.1234/')
	});

	it('Check Publication/Galley DOI Assignments', function() {
		cy.login('dbarnes', null, 'publicknowledge');

		cy.get('a:contains("##plugins.pubIds.doi.manager.displayName##")').click(); // TODO: Change to DOIs
		cy.get('button#article-doi-management-button').click();

		// Select the first article
		cy.get(`input[name="submission[]"][value=${submissionId}]`).check()

		// Select assign DOIs from bulk actions
		cy.get('#article-doi-management button:contains("Bulk Actions")').click({multiple: true});
		cy.get('button#openBulkAssign').click();

		// Confirm assignment
		cy.get('div[data-modal="bulkActions"] button:contains("Assign DOIs")').click();
		cy.get('.app__notifications').contains('Items successfully assigned new DOIs', {timeout:20000});

		cy.get(`#list-item-submission-${submissionId} button.expander`).click();
		cy.get(`input#${submissionId}-article-${publicationId}`).should('have.value', '10.1234/jpkjpk.v1i2.17');
		cy.get(`input#${submissionId}-galley-${galleyId}`).should('have.value', '10.1234/jpkjpk.v1i2.17.g3');
	});

	it('Check Publication/Galley DOI visible', function() {
		cy.login('dbarnes', null, 'publicknowledge');

		// Select a submission
		cy.visit('/index.php/publicknowledge/article/view/' + submissionId);

		cy.get('section.item.doi')
			.find('span.value').contains('https://doi.org/10.1234/');
	});

	it('Check Issue Marked Registered', function() {
		cy.login('dbarnes', null, 'publicknowledge');

		cy.get('a:contains("##plugins.pubIds.doi.manager.displayName##")').click(); // TODO: Change to DOIs
		cy.get('button#issue-doi-management-button').click();

		// Select the first issue
		cy.get(`input[name="issue[]"][value=${issueId}]`).check()

		// Select mark registered from bulk actions
		cy.get('#issue-doi-management button:contains("Bulk Actions")').click({multiple: true});
		cy.get('button#openBulkMarkRegistered').click();

		// Confirm assignment
		cy.get('div[data-modal="bulkActions"] button:contains("Mark DOIs registered")').click();
		cy.get('.app__notifications').contains('Items successfully marked registered', {timeout:20000});

		cy.get(`#list-item-issue-${issueId} .pkpBadge`).contains('Registered');
	});

	it('Check Publication/Galley Marked Registered', function() {
		cy.login('dbarnes', null, 'publicknowledge');

		cy.get('a:contains("##plugins.pubIds.doi.manager.displayName##")').click(); // TODO: Change to DOIs
		cy.get('button#article-doi-management-button').click();

		// Select the first article
		cy.get(`input[name="submission[]"][value=${submissionId}]`).check()

		// Select mark registered from bulk actions
		cy.get('#article-doi-management button:contains("Bulk Actions")').click({multiple: true});
		cy.get('button#openBulkMarkRegistered').click();

		// Confirm assignment
		cy.get('div[data-modal="bulkActions"] button:contains("Mark DOIs registered")').click();
		cy.get('.app__notifications').contains('Items successfully marked registered', {timeout:20000});

		cy.get(`#list-item-submission-${submissionId} .pkpBadge`).contains('Registered');
	});
});

describe('Crossref tests', function () {
	const submissionId = 17;

	it('Check Crossref Configuration', function() {
		cy.login('dbarnes', null, 'publicknowledge');

		cy.get('a:contains("Website")').click();

		cy.waitJQuery();
		cy.get('button#plugins-button').click();

		// Crossref plugin is or can be enabled
		cy.get('input[id^=select-cell-crossrefplugin]').check();
		cy.get('input[id^=select-cell-crossrefplugin]').should('be.checked');

		// Crossref is enabled as DOI registration agency.
		cy.get('a:contains("Distribution")').click();
		cy.get('button#dois-button').click();
		cy.get('button#doisRegistration-button').click()

		cy.get('select#doiRegistrationSettings-registrationAgency-control').select('crossrefplugin');

		// Save
		cy.get('#doisRegistration button').contains('Save').click();
		cy.get('#doisRegistration [role="status"]').contains('Saved');
		cy.get('select#doiRegistrationSettings-registrationAgency-control').should('have.value', 'crossrefplugin');

		// Configure Crossref settings
		cy.get('a:contains("##plugins.pubIds.doi.manager.displayName##")').click(); // TODO: Change to DOIs
		cy.get('button#crossref-settings-button').click();

		cy.get('input[name=depositorName]').focus().clear().type('admin');
		cy.get('input[name=depositorEmail]').focus().clear().type('pkpadmin@mailinator.com');
		cy.get('form#crossrefSettingsForm button:contains("Save")').click();
		cy.get('div:contains("Your changes have been saved.")');
	});

	it('Check Crossref Export', function() {
		cy.login('dbarnes', null, 'publicknowledge');

		// Submit export submission DOI XML request
		cy.window()
		.then((win) => {
			const csrfToken = win.pkp.currentUser.csrfToken;
			cy.request({
					url: '/index.php/publicknowledge/api/v1/dois/submissions/export',
					method: 'POST',
					headers: {
						'X-Csrf-Token': csrfToken
					},
					body: {
						ids: [submissionId]
					}
				})
		})
		.then((response) => {
			expect(response.status).to.equal(200);
			expect(response.body).to.haveOwnProperty('tempFileId');
			expect(response.body.tempFileId).to.be.a('number');
		});
	});
});
