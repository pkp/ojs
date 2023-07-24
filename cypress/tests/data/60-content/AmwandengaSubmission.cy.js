/**
 * @file cypress/tests/data/60-content/AmwandengaSubmission.cy.js
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */

describe('Data suite: Amwandenga', function() {

	let submission;

	before(function() {
		const title = 'Signalling Theory Dividends: A Review Of The Literature And Empirical Evidence';
		submission = {
			id: 0,
			section: 'Articles',
			prefix: '',
			title: title,
			subtitle: '',
			abstract: 'The signaling theory suggests that dividends signal future prospects of a firm. However, recent empirical evidence from the US and the Uk does not offer a conclusive evidence on this issue. There are conflicting policy implications among financial economists so much that there is no practical dividend policy guidance to management, existing and potential investors in shareholding. Since corporate investment, financing and distribution decisions are a continuous function of management, the dividend decisions seem to rely on intuitive evaluation.',
			shortAuthorString: 'Mwandenga, et al.',
			authorNames: ['Alan Mwandenga', 'Amina Mansour'],
			assignedAuthorNames: ['Alan Mwandenga'],
			authors: [
				{
					givenName: 'Amina',
					familyName: 'Mansour',
					email: 'amansour@mailinator.com',
					country: 'Barbados',
					affiliation: 'Public Knowledge Project'
				}
			],
			files: [
				{
					'file': 'dummy.pdf',
					'fileName': title + '.pdf',
					'mimeType': 'application/pdf',
					'genre': Cypress.env('defaultGenre')
				},
				{
					'file': 'dummy.odt',
					'fileName': 'structured-interview-guide.odt',
					'mimeType': 'application/vnd.oasis.opendocument.text',
					'genre': 'Other'
				},
				{
					'file': 'dummy.ods',
					'fileName': 'response-evaluation-all-team-members-draft-after-edits-final-version-final.ods',
					'mimeType': 'application/vnd.oasis.opendocument.spreadsheet',
					'genre': 'Data Set'
				},
				{
					'file': 'dummy.xlsx',
					'fileName': 'signalling-theory-dataset.pdf',
					'mimeType': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
					'genre': 'Data Set'
				},
				{
					'file': 'dummy.docx',
					'fileName': 'author-disclosure-form.docx',
					'mimeType': 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
					'genre': 'Other'
				}
			]
		};
	});

	it('Registers as author and creates a submission', function() {

		cy.register({
			'username': 'amwandenga',
			'givenName': 'Alan',
			'familyName': 'Mwandenga',
			'affiliation': 'University of Cape Town',
			'country': 'South Africa',
		});

		cy.contains('Make a New Submission').click();

		// All required fields in the start submission form
		cy.contains('Begin Submission').click();
		cy.get('#startSubmission-title-error').contains('This field is required.');
		cy.get('#startSubmission-sectionId-error').contains('This field is required.');
		cy.get('#startSubmission-locale-error').contains('This field is required.');
		cy.get('#startSubmission-submissionRequirements-error').contains('This field is required.');
		cy.get('#startSubmission-privacyConsent-error').contains('This field is required.');
		cy.setTinyMceContent('startSubmission-title-control', submission.title);
		cy.get('label:contains("Articles")').click();
		cy.get('label:contains("English")').click();
		cy.get('input[name="submissionRequirements"]').check();
		cy.get('input[name="privacyConsent"]').check();
		cy.contains('Begin Submission').click();


		// The submission wizard has loaded
		cy.contains('Make a Submission: Details');
		cy.get('.submissionWizard__submissionDetails').contains('Mwandenga');
		cy.get('.submissionWizard__submissionDetails').contains(submission.title);
		cy.contains('Submitting to the Articles section in English');
		cy.get('.pkpSteps__step__label--current').contains('Details');
		cy.get('.pkpSteps__step__label').contains('Upload Files');
		cy.get('.pkpSteps__step__label').contains('Contributors');
		cy.get('.pkpSteps__step__label').contains('For the Editors');
		cy.get('.pkpSteps__step__label').contains('Review');

		// Save the submission id for later tests
		cy.location('search')
			.then(search => {
				submission.id = parseInt(search.split('=')[1]);
			});

		// Enter details
		cy.get('h2').contains('Submission Details');
		cy.setTinyMceContent('titleAbstract-abstract-control-en', submission.abstract);
		cy.get('#titleAbstract-title-control-en').click({force: true}); // Ensure blur event is fired

		cy.get('.submissionWizard__footer button').contains('Continue').click();

		// Upload files and set file genres
		cy.contains('Make a Submission: Upload Files');
		cy.get('h2').contains('Upload Files');
		cy.get('h2').contains('Files');
		cy.uploadSubmissionFiles(submission.files);

		// Delete a file
		cy.uploadSubmissionFiles([{
			'file': 'dummy.pdf',
			'fileName': 'delete-this-file.pdf',
			'mimeType': 'application/pdf',
			'genre': Cypress.env('defaultGenre')
		}]);
		cy.get('.listPanel__item:contains("delete-this-file.pdf")').find('button').contains('Remove').click();
		cy.get('.modal__panel:contains("Are you sure you want to remove this file?")').find('button').contains('Yes').click();
		cy.get('.listPanel__item:contains("delete-this-file.pdf")').should('not.exist');

		cy.get('.submissionWizard__footer button').contains('Continue').click();

		// Add Contributors
		cy.contains('Make a Submission: Contributors');
		cy.get('.pkpSteps__step__label--current').contains('Contributors');
		cy.get('h2').contains('Contributors');
		cy.get('.listPanel__item:contains("Alan Mwandenga")');
		cy.get('button').contains('Add Contributor').click();
		cy.get('.modal__panel:contains("Add Contributor")').find('button').contains('Save').click();
		cy.get('#contributor-givenName-error-en').contains('This field is required.');
		cy.get('#contributor-email-error').contains('This field is required.');
		cy.get('#contributor-country-error').contains('This field is required.');
		cy.get('.pkpFormField:contains("Given Name")').find('input[name*="-en"]').type(submission.authors[0].givenName);
		cy.get('.pkpFormField:contains("Family Name")').find('input[name*="-en"]').type(submission.authors[0].familyName);
		cy.get('.pkpFormField:contains("Country")').find('select').select(submission.authors[0].country)
		cy.get('.pkpFormField:contains("Email")').find('input').type('notanemail');
		cy.get('.modal__panel:contains("Add Contributor")').find('button').contains('Save').click();
		cy.get('#contributor-email-error').contains('This is not a valid email address.');
		cy.get('.pkpFormField:contains("Email")').find('input').type(submission.authors[0].email);
		cy.get('.modal__panel:contains("Add Contributor")').find('button').contains('Save').click();
		cy.get('button').contains('Order').click();
		cy.get('button:contains("Decrease position of Alan Mwandenga")').click();
		cy.get('button').contains('Save Order').click();
		cy.get('button:contains("Preview")').click(); // Will only appear after order is saved
		cy.get('.modal__panel:contains("List of Contributors")').find('tr:contains("Abbreviated")').contains('Mansour et al.');
		cy.get('.modal__panel:contains("List of Contributors")').find('tr:contains("Publication Lists")').contains('Amina Mansour, Alan Mwandenga (Author)');
		cy.get('.modal__panel:contains("List of Contributors")').find('tr:contains("Full")').contains('Amina Mansour, Alan Mwandenga (Author)');
		cy.get('.modal__panel:contains("List of Contributors")').find('.modal__closeButton').click();
		cy.get('.listPanel:contains("Contributors")').find('button').contains('Order').click();
		cy.get('button:contains("Increase position of Alan Mwandenga")').click();
		cy.get('.listPanel:contains("Contributors")').find('button').contains('Save Order').click();
		cy.get('.listPanel:contains("Contributors") button:contains("Preview")').click(); // Will only appear after order is saved
		cy.get('.modal__panel:contains("List of Contributors")').find('tr:contains("Abbreviated")').contains('Mwandenga et al.');
		cy.get('.modal__panel:contains("List of Contributors")').find('tr:contains("Publication Lists")').contains('Alan Mwandenga, Amina Mansour (Author)');
		cy.get('.modal__panel:contains("List of Contributors")').find('tr:contains("Full")').contains('Alan Mwandenga, Amina Mansour (Author)');
		cy.get('.modal__panel:contains("List of Contributors")').find('.modal__closeButton').click();

		// Delete a contributor
		cy.get('.listPanel:contains("Contributors")').find('button').contains('Add Contributor').click();
		cy.get('.pkpFormField:contains("Given Name")').find('input[name*="-en"]').type('Fake Author Name');
		cy.get('.pkpFormField:contains("Email")').find('input').type('delete@mailinator.com');
		cy.get('.pkpFormField:contains("Country")').find('select').select('Barbados');
		cy.get('.modal__panel:contains("Add Contributor")').find('button').contains('Save').click();
		cy.get('.listPanel__item:contains("Fake Author Name")').find('button').contains('Delete').click();
		cy.get('.modal__panel:contains("Are you sure you want to remove Fake Author Name as a contributor?")').find('button').contains('Delete Contributor').click();
		cy.get('.listPanel__item:contains("Fake Author Name")').should('not.exist');

		cy.get('.submissionWizard__footer button').contains('Continue').click();

		// For the Editors
		cy.contains('Make a Submission: For the Editors');
		cy.get('.pkpSteps__step__label--current').contains('For the Editors');
		cy.get('h2').contains('For the Editors');

		cy.get('.submissionWizard__footer button').contains('Continue').click();

		// Review
		cy.contains('Make a Submission: Review');
		cy.get('.pkpSteps__step__label--current').contains('Review');
		cy.get('h2').contains('Review and Submit');
		submission.files.forEach(function(file) {
			cy
				.get('h3')
				.contains('Files')
				.parents('.submissionWizard__reviewPanel')
				.contains(file.fileName)
				.parents('.submissionWizard__reviewPanel__item__value')
				.find('.pkpBadge')
				.contains(file.genre);
		});
		submission.authorNames.forEach(function(author) {
			cy
				.get('h3')
				.contains('Contributors')
				.parents('.submissionWizard__reviewPanel')
				.contains(author)
				.parents('.submissionWizard__reviewPanel__item__value')
				.find('.pkpBadge')
				.contains('Author');
		});
		cy.get('h3').contains('Details (English)')
			.parents('.submissionWizard__reviewPanel')
			.find('h4').contains('Title').siblings('.submissionWizard__reviewPanel__item__value').contains(submission.title)
			.parents('.submissionWizard__reviewPanel')
			.find('h4').contains('Keywords').siblings('.submissionWizard__reviewPanel__item__value').contains('None provided')
			.parents('.submissionWizard__reviewPanel')
			.find('h4').contains('Abstract').siblings('.submissionWizard__reviewPanel__item__value').contains(submission.abstract);
		cy.get('h3').contains('Details (French)')
			.parents('.submissionWizard__reviewPanel')
			.find('h4').contains('Title').siblings('.submissionWizard__reviewPanel__item__value').contains('None provided')
			.parents('.submissionWizard__reviewPanel')
			.find('h4').contains('Keywords').siblings('.submissionWizard__reviewPanel__item__value').contains('None provided')
			.parents('.submissionWizard__reviewPanel')
			.find('h4').contains('Abstract').siblings('.submissionWizard__reviewPanel__item__value').contains('None provided');
		cy.get('h3').contains('For the Editors (English)')
			.parents('.submissionWizard__reviewPanel')
			.find('h4').contains('Comments for the Editor').siblings('.submissionWizard__reviewPanel__item__value').contains('None');
		cy.get('h3').contains('For the Editors (French)') // FIXME: Should be French

		// Save for later
		cy.get('button').contains('Save for Later').click();
		cy.contains('Saved for Later');
		cy.contains('Your submission details have been saved');
		cy.contains('We have emailed a copy of this link to you at amwandenga@mailinator.com.');
		cy.get('a').contains(submission.title).click();

		// Submit
		cy.contains('Make a Submission: Review');
		cy.get('button:contains("Submit")').click();
		const message = 'The submission, ' + submission.title + ', will be submitted to ' + Cypress.env('contextTitles').en + ' for editorial review';
		cy.get('.modal__panel:contains("' + message + '")').find('button').contains('Submit').click();
		cy.contains('Submission complete');
		cy.get('a').contains('Create a new submission');
		cy.get('a').contains('Return to your dashboard');
		cy.get('a').contains('Review this submission').click();
		cy.get('h1:contains("' + submission.title + '")');
	});

	it('Sends a submission to review, assigns reviewers, accepts a submission, and sends to production', function() {
		cy.findSubmissionAsEditor('dbarnes', null, 'Mwandenga');
		cy.clickDecision('Send for Review');
		cy.recordDecisionSendToReview('Send for Review', submission.assignedAuthorNames, [submission.title]);
		cy.isActiveStageTab('Review');
		cy.assignReviewer('Julie Janssen');
		cy.assignReviewer('Aisla McCrae');
		cy.assignReviewer('Adela Gallego');
		cy.clickDecision('Accept Submission');
		cy.recordDecisionAcceptSubmission(submission.assignedAuthorNames, [], []);
		cy.isActiveStageTab('Copyediting');
		cy.assignParticipant('Copyeditor', 'Sarah Vogt');
		cy.clickDecision('Send To Production');
		cy.recordDecisionSendToProduction(submission.assignedAuthorNames, []);
		cy.isActiveStageTab('Production');
		cy.assignParticipant('Layout Editor', 'Stephen Hellier');
		cy.assignParticipant('Proofreader', 'Sabine Kumar');
	});

	it('Editor can edit publication details', function() {
		cy.login('dbarnes');
		cy.visit('/index.php/publicknowledge/workflow/access/' + submission.id);
		cy.get('#publication-button').click();

		// Title and abstract
		submission.prefix = 'The';
		submission.title = 'Signalling Theory Dividends';
		submission.subtitle = 'A Review Of The Literature And Empirical Evidence';
		cy.get('#titleAbstract input[name=prefix-en]').type(submission.prefix, {delay: 0});
		cy.setTinyMceContent('titleAbstract-subtitle-control-en', submission.subtitle);
		cy.setTinyMceContent('titleAbstract-title-control-en', '');
		cy.setTinyMceContent('titleAbstract-abstract-control-en', submission.abstract.repeat(10));
		cy.get('#titleAbstract-title-control-en').click({force:true}); // Ensure blur event is fired
		cy.get('#titleAbstract-subtitle-control-en').click({force: true});
		cy.get('#titleAbstract button').contains('Save').click();

		cy.get('#titleAbstract [id*=title-error-en]').find('span').contains('This field is required.');
		cy.setTinyMceContent('titleAbstract-title-control-en', submission.title);
		cy.get('#titleAbstract button').contains('Save').click();

		cy.get('#titleAbstract [id*=abstract-error-en]').find('span').contains('The abstract is too long.');
		cy.setTinyMceContent('titleAbstract-abstract-control-en', submission.abstract);
		cy.get('#titleAbstract-title-control-en').click({force:true}); // Ensure blur event is fired
		cy.get('#titleAbstract-subtitle-control-en').click({force:true});
		cy.get('#titleAbstract button').contains('Save').click();
		cy.get('#titleAbstract [role="status"]').contains('Saved');

		// Metadata
		cy.get('#metadata-button').click();
		cy.get('#metadata-keywords-control-en').type('Professional Development', {delay: 0});
		cy.get('.autosuggest__results-item').contains('Professional Development');
		cy.get('#metadata-keywords-control-en').type('{enter}', {delay: 0});
		cy.get('#metadata-keywords-selected-en').contains('Professional Development');
		cy.get('#metadata-keywords-control-en').type('Social Transformation', {delay: 0});
		cy.get('.autosuggest__results-item').contains('Social Transformation');
		cy.get('#metadata-keywords-control-en').type('{enter}', {delay: 0});
		cy.get('#metadata-keywords-selected-en').contains('Social Transformation');
		cy.get('#metadata button').contains('Save').click();
		cy.get('#metadata [role="status"]').contains('Saved');

		// Permissions & Disclosure
		cy.get('#license-button').click();
		cy.get('#license button').contains('Save').click();
		cy.get('#license [role="status"]').contains('Saved');

		// Issue
		cy.get('#issue-button').click();
		cy.get('#issue [name="sectionId"]').select('Reviews');
		cy.get('#issue [name="sectionId"]').select('Articles');
		cy.get('#issue [name="pages"]').type('71-98', {delay: 0});
		cy.get('#issue [name="urlPath"]').type('mwandenga-signalling-theory space error');
		cy.get('#issue button').contains('Save').click();

		cy.get('#issue [id*="urlPath-error"]').contains('This may only contain letters, numbers, dashes, underscores and periods.');
		cy.get('#issue [name="urlPath"]').clear().type('mwandenga-signalling-theory');
		cy.get('#issue button').contains('Save').click();
		cy.get('#issue [role="status"]').contains('Saved');

		// Contributors
		cy.wait(1500);
		cy.get('#contributors-button').click();

		cy.get('#contributors button').contains('Add Contributor').click();

		cy.get('#contributors [name="givenName-en"]').type('Nicolas', {delay: 0});
		cy.get('#contributors [name="familyName-en"]').type('Riouf', {delay: 0});
		cy.get('#contributors [name="email"]').type('nriouf@mailinator.com', {delay: 0});
		cy.get('#contributors [name="country"]').select('South Africa');
		cy.get('#contributors button').contains('Save').click();
		cy.wait(500);
		cy.get('#contributors div').contains('Nicolas Riouf');

		// Create a galley
		cy.get('button#publication-button').click();
		cy.get('button#galleys-button').click();
		cy.get('a[id^="component-grid-articlegalleys-articlegalleygrid-addGalley-button-"]').click();
		cy.wait(1000); // Wait for the form to settle
		cy.get('input[id^=label-]').type('PDF', {delay: 0});
		cy.get('form#articleGalleyForm button:contains("Save")').click();
		cy.get('select[id=genreId]').select('Article Text');
		cy.wait(250);
		cy.fixture('dummy.pdf', 'base64').then(fileContent => {
			cy.get('div[id^="fileUploadWizard"] input[type=file]').attachFile(
				{fileContent, 'filePath': 'article.pdf', 'mimeType': 'application/pdf', 'encoding': 'base64'}
			);
		});
		cy.get('button').contains('Continue').click();
		cy.get('button').contains('Continue').click();
		cy.get('button').contains('Complete').click();
	});

	it('Author can not edit publication details', function() {
		cy.login('amwandenga');
		cy.visit('/index.php/publicknowledge/submissions');
		cy.contains('View Mwandenga').click({force: true});
		cy.get('#publication-button').click();
		cy.get('#titleAbstract button').contains('Save').should('be.disabled');

		cy.get('#contributors-button').click();

		cy.get('#contributors button').contains('Add Contributor').should('not.exist');
		cy.get('#contributors button').contains('Edit').should('not.exist');

		cy.get('#galleys-button').click();
		cy.get('[id*="addGalley-button"]').should('not.exist');
		cy.get('[id*="editGalley-button"]').contains('View').should('exist');
	});

	it('Allow author to edit publication details', function() {
		cy.login('dbarnes');
		cy.visit('/index.php/publicknowledge/workflow/access/' + submission.id);
		cy.get('#stageParticipantGridContainer .label').contains('Alan Mwandenga')
			.parent().parent().find('.show_extras').click()
			.parent().parent().siblings().find('a').contains('Edit').click();
		cy.get('[name="canChangeMetadata"]').check();
		cy.get('[id^="submitFormButton"]').contains('OK').click();
		cy.contains('The stage assignment has been changed.');
		cy.wait(1000);
		cy.logout();
		cy.wait(1000);

		cy.login('amwandenga');
		cy.visit('/index.php/publicknowledge/authorDashboard/submission/' + submission.id);
		cy.get('#publication-button').click();
		cy.get('#titleAbstract button').contains('Save').click();
		cy.get('#titleAbstract [role="status"]').contains('Saved');
	});

	it('Publish submission', function() {
		cy.login('dbarnes');
		cy.visit('/index.php/publicknowledge/workflow/access/' + submission.id);
		cy.publish('1', 'Vol. 1 No. 2 (2014)');
		cy.isInIssue(submission.title, 'Vol. 1 No. 2 (2014)');
		cy.contains(submission.title).click();
		cy.get('h1:contains("' + submission.title + '")');
		cy.checkViewableGalley('PDF');
		cy.contains(submission.title).click();
		cy.contains('Alan Mwandenga');
		cy.contains('University of Cape Town');
		cy.contains('Amina Mansour');
		cy.contains('Nicolas Riouf');
		cy.contains('Professional Development');
		cy.contains('Social Transformation');
	});

	it('Article is not available when unpublished', function() {
		cy.login('dbarnes');
		cy.visit('/index.php/publicknowledge/workflow/access/' + submission.id);
		cy.get('#publication-button').click();
		cy.get('button').contains('Unpublish').click();
		cy.contains('Are you sure you don\'t want this to be published?');
		cy.get('.modal__panel button').contains('Unpublish').click();
		cy.wait(1000);
		cy.visit('/index.php/publicknowledge/issue/current');
		cy.contains('Signalling Theory Dividends').should('not.exist');
		cy.logout();
		cy.request({
				url: '/index.php/publicknowledge/article/view/' + submission.id,
				failOnStatusCode: false
			})
			.then((response) => {
				expect(response.status).to.equal(404);
			});

		// Re-publish it
		cy.login('dbarnes');
		cy.visit('/index.php/publicknowledge/workflow/access/' + submission.id);
		cy.get('#publication-button').click();
		cy.get('.pkpPublication button').contains('Schedule For Publication').click();
		cy.contains('All publication requirements have been met.');
		cy.get('.pkpWorkflow__publishModal button').contains('Publish').click();
	});

	it('Editor must create version to make changes', function() {
		cy.login('dbarnes');
		cy.visit('/index.php/publicknowledge/workflow/access/' + submission.id);
		cy.get('#publication-button').click();
		cy.get('#titleAbstract button').contains('Save').should('be.disabled');
		cy.get('#publication button').contains('Create New Version').click();
		cy.contains('Are you sure you want to create a new version?');
		cy.get('.modal__panel:contains("Create New Version")').get('button').contains('Yes').click();
		cy.wait(3000);

		// Toggle between versions
		cy.get('#publication button').contains('All Versions').click();
		cy.get('.pkpPublication__versions .pkpDropdown__action').eq(0).click();
		cy.wait(3000);
		cy.contains('This version has been published and can not be edited.');
		cy.get('#titleAbstract button').contains('Save').should('be.disabled');
		cy.get('#publication button').contains('All Versions').click();
		cy.get('.pkpPublication__versions .pkpDropdown__action').eq(1).click();
		cy.wait(3000);
		cy.get('#publication button').contains('Publish');
		cy.contains('This version has been published and can not be edited.').should('not.exist');

		// Edit unpublished version's title
		cy.setTinyMceContent('titleAbstract-title-control-en', 'The Signalling Theory Dividends Version 2');
		cy.get('#titleAbstract button').contains('Save').click();
		cy.get('#titleAbstract [role="status"]').contains('Saved');

		// Edit Contributor
		cy.wait(1500);
		cy.get('#contributors-button').click();

		cy.get('#contributors div').contains('Alan Mwandenga').parent().parent().find('button').contains('Edit').click();
		cy.get('#contributors [name="familyName-en"]').type(' Version 2', {delay: 0});
		cy.get('#contributors button').contains('Save').click();
		// cy.get('#contributors button').contains('Save').should("not.be.visible");
		cy.wait(1500); // Wait for the grid to reload
		cy.get('#contributors div').contains('Alan Mwandenga Version 2');

		// Edit Galley
		cy.get('#galleys-button').click();
		cy.contains('Add galley');
		cy.get('#representations-grid .show_extras').click();
		cy.get('[id*="editGalley-button"]').click();
		cy.waitJQuery(); // Wait for the form initialization
		cy.wait(1000); // Additional wait needed to reduce failures
		cy.get('#articleGalleyForm').within(() => {
			cy.get('[name="label"]').type(' Version 2');
			cy.get('[name="urlPath"]').type('pdf');
			cy.get('button').contains('Save').click();
		});
		cy.wait(3000);
		cy.get('#representations-grid [id*="downloadFile-button"]:contains("PDF Version 2")');

		// Edit url path
		cy.get('#issue-button').click();
		cy.get('#issue [name="urlPath"]').clear().type('mwandenga');
		cy.get('#issue button').contains('Save').click();
		cy.get('#issue [role="status"]').contains('Saved');

		// Publish version
		cy.get('#publication button').contains('Publish').click();
		cy.contains('All publication requirements have been met.');
		cy.get('.pkpWorkflow__publishModal button').contains('Publish').click();
	});

	it('Article landing page displays versions at correct url path', function() {
		cy.visit('/index.php/publicknowledge/article/view/mwandenga');
		cy.get('h1').contains('The Signalling Theory Dividends Version 2');
		cy.contains('Alan Mwandenga Version 2');
		cy.checkViewableGalley('PDF Version 2');
		cy.contains('The Signalling Theory Dividends Version 2').click();
		cy.get('.versions a').contains('(1)').click();
		cy.contains('This is an outdated version');
		cy.checkViewableGalley('PDF');
		cy.contains('This is an outdated version');
		cy.get('.galley_view_notice a').click();
		cy.get('h1').contains('The Signalling Theory Dividends Version 2');
		cy.contains('This is an outdated version').should('not.exist');
	});

	it('Article landing page displays correct version after version is unpublished', function() {
		cy.login('dbarnes');
		cy.visit('/index.php/publicknowledge/workflow/access/' + submission.id);
		cy.get('#publication-button').click();
		cy.get('button').contains('Unpublish').click();
		cy.contains('Are you sure you don\'t want this to be published?');
		cy.get('.modal__panel button').contains('Unpublish').click();
		cy.wait(1000);
		cy.get('.pkpWorkflow__header a').contains('View').click();
		cy.contains('The Signalling Theory Dividends Version 2').should('not.exist');
		cy.get('.versions').should('not.exist');
	});

	it('Recommend-only editors can not publish, unpublish or create versions', function() {
		cy.login('dbarnes');
		cy.visit('/index.php/publicknowledge/workflow/access/' + submission.id);
		cy.wait(1000);
		cy.clickStageParticipantButton('Stephanie Berardo', 'Edit');
		cy.get('[name="recommendOnly"]').check();
		cy.get('[id^="submitFormButton"]').contains('OK').click();
		cy.contains('The stage assignment has been changed.');
		cy.waitJQuery();
		cy.clickStageParticipantButton('Stephanie Berardo', 'Login As');
		cy.get('.pkpModalConfirmButton').contains('OK').click();
		cy.get('#publication-button').click();
		cy.get('.pkpPublication .pkpHeader__actions button:contains("Publish")').should('not.exist');
		cy.get('.pkpPublication .pkpHeader__actions button:contains("Create Version")').should('not.exist');
		cy.contains('All Versions').click();
		cy.get('.pkpPublication__versions .pkpDropdown__action').eq(0).click();
		cy.contains('This version has been published and can not be edited.');
		cy.get('.pkpPublication .pkpHeader__actions button:contains("Unpublish")').should('not.exist');
	});

	it('Section editors can have their permission to edit publication data revoked', function() {
		cy.login('dbarnes');
		cy.visit('/index.php/publicknowledge/workflow/access/' + submission.id);
		cy.clickStageParticipantButton('Stephanie Berardo', 'Edit');
		cy.get('[name="canChangeMetadata"]').uncheck();
		cy.get('[id^="submitFormButton"]').contains('OK').click();
		cy.contains('The stage assignment has been changed.');
		cy.waitJQuery();
		cy.clickStageParticipantButton('Stephanie Berardo', 'Login As');
		cy.get('.pkpModalConfirmButton').contains('OK').click();
		cy.get('#publication-button').click();
		cy.get('#titleAbstract button').contains('Save').should('be.disabled');
	});
});
