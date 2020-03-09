/**
 * @file cypress/tests/data/60-content/AmwandengaSubmission.spec.js
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */

describe('Data suite tests', function() {

	let submission;

	before(function() {
		submission = {
			id: 0,
			section: 'Articles',
			prefix: '',
			title: 'Signalling Theory Dividends: A Review Of The Literature And Empirical Evidence',
			subtitle: '',
			abstract: 'The signaling theory suggests that dividends signal future prospects of a firm. However, recent empirical evidence from the US and the Uk does not offer a conclusive evidence on this issue. There are conflicting policy implications among financial economists so much that there is no practical dividend policy guidance to management, existing and potential investors in shareholding. Since corporate investment, financing and distribution decisions are a continuous function of management, the dividend decisions seem to rely on intuitive evaluation.'
		};
	});

	it('Create a submission', function() {
		cy.register({
			'username': 'amwandenga',
			'givenName': 'Alan',
			'familyName': 'Mwandenga',
			'affiliation': 'University of Cape Town',
			'country': 'South Africa',
		});

		cy.createSubmission(submission);

		cy.logout();
		cy.findSubmissionAsEditor('dbarnes', null, submission.title);
		cy.sendToReview();
		cy.assignReviewer('Julie Janssen');
		cy.assignReviewer('Aisla McCrae');
		cy.assignReviewer('Adela Gallego');
		cy.recordEditorialDecision('Accept Submission');
		cy.get('li.ui-state-active a:contains("Copyediting")');
		cy.assignParticipant('Copyeditor', 'Sarah Vogt');
		cy.recordEditorialDecision('Send To Production');
		cy.get('li.ui-state-active a:contains("Production")');
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
		cy.get('#titleAbstract input[name=prefix-en_US]').type(submission.prefix, {delay: 0});
		cy.get('#titleAbstract input[name=subtitle-en_US]').type(submission.subtitle, {delay: 0});
		cy.get('#titleAbstract input[name=title-en_US]').clear();
		cy.setTinyMceContent('titleAbstract-abstract-control-en_US', submission.abstract.repeat(10));
		cy.get('#titleAbstract-abstract-control-en_US').click(); // Ensure blur event is fired
		cy.get('#titleAbstract input[name=subtitle-en_US]').click();
		cy.get('#titleAbstract button').contains('Save').click();

		cy.get('#titleAbstract [id*=title-error-en_US]').find('span').contains('You must complete this field in English.');
		cy.get('#titleAbstract [id*=abstract-error-en_US]').find('span').contains('The abstract is too long.');
		cy.get('#titleAbstract input[name=title-en_US').type(submission.title, {delay: 0});
		cy.setTinyMceContent('titleAbstract-abstract-control-en_US', submission.abstract);
		cy.get('#titleAbstract-abstract-control-en_US').click(); // Ensure blur event is fired
		cy.get('input[name=subtitle-en_US]').click();
		cy.get('#titleAbstract button').contains('Save').click();

		cy.contains('The title and abstract have been updated.');

		// Metadata
		cy.get('#metadata-button').click();
		cy.get('#metadata-keywords-control-en_US').type('Professional Development{enter}', {delay: 0});
		cy.wait(100);
		cy.get('#metadata-keywords-control-en_US').type('Social Transformation{enter}', {delay: 0});
		cy.wait(100);
		cy.get('#metadata button').contains('Save').click();

		cy.contains('The metadata have been updated.');
		cy.get('#metadata-keywords-selected-en_US').contains('Professional Development');
		cy.get('#metadata-keywords-selected-en_US').contains('Social Transformation');

		// Permissions & Disclosure
		cy.get('#license-button').click();
		cy.get('#license button').contains('Save').click();
		cy.contains('The copyright and license information have been updated.');

		// Issue
		cy.get('#issue-button').click();
		cy.get('#issue [name="sectionId"]').select('Reviews');
		cy.get('#issue [name="sectionId"]').select('Articles');
		cy.get('#issue [name="pages"]').type('71-98', {delay: 0});
		cy.get('#issue [name="urlPath"]').type('mwandenga-signalling-theory space error');
		cy.get('#issue button').contains('Save').click();

		cy.get('#issue [id*="urlPath-error"]').contains('This may only contain letters, numbers, dashes and underscores.');
		cy.get('#issue [name="urlPath"]').type('mwandenga-signalling-theory');
		cy.get('#issue button').contains('Save').click();

		cy.contains('The publication\'s issue details have been updated.');

		// Contributors
		cy.get('#contributors-button').click();
		cy.get('[id*="authorgrid-addAuthor-button"]').click();
		cy.wait(250);
		cy.get('#editAuthor [name="givenName[en_US]"]').type('Lorem', {delay: 0});
		cy.get('#editAuthor [name="familyName[en_US]"]').type('Ipsum', {delay: 0});
		cy.get('#editAuthor [name="email"]').type('lorem@mailinator.com', {delay: 0});
		cy.get('#editAuthor [name="country"]').select('South Africa');
		cy.get('#editAuthor label').contains('Author').click();
		cy.get('#editAuthor [id^="submitFormButton"]').contains('Save').click();
		cy.contains('Author added.');
		cy.wait(500);
		cy.get('[id*="authorgrid-row"] span').contains('Lorem Ipsum');

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
			cy.get('div[id^="fileUploadWizard"] input[type=file]').upload(
				{fileContent, 'fileName': 'article.pdf', 'mimeType': 'application/pdf', 'encoding': 'base64'}
			);
		});
		cy.get('button').contains('Continue').click();
		cy.get('button').contains('Continue').click();
		cy.get('button').contains('Complete').click();
	});

	it('Author can not edit publication details', function() {
		cy.login('amwandenga');
		cy.visit('/index.php/publicknowledge/submissions');
		cy.contains('Signalling Theory Dividends').parent().parent().click();
		cy.get('#publication-button').click();
		cy.get('#titleAbstract button').contains('Save').should('be.disabled');

		cy.get('#contributors-button').click();
		cy.get('[id*="authorgrid-addAuthor-button"]').should('not.exist');
		cy.get('[id*="editAuthor-button"]').should('not.exist');

		cy.get('#galleys-button').click();
		cy.get('[id*="addGalley-button"]').should('not.exist');
		cy.get('[id*="editGalley-button"]').should('not.exist');
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
		cy.logout();

		cy.login('amwandenga');
		cy.visit('/index.php/publicknowledge/authorDashboard/submission/' + submission.id);
		cy.contains('Signalling Theory Dividends').parent().parent().click();
		cy.get('#publication-button').click();
		cy.get('#titleAbstract button').contains('Save').click();
		cy.contains('The title and abstract have been updated.');
	});

	it('Publish submission', function() {
		cy.login('dbarnes');
		cy.visit('/index.php/publicknowledge/workflow/access/' + submission.id);
		cy.publish('1', 'Vol. 1 No. 2 (2014)');
		cy.isInIssue(submission.title, 'Vol. 1 No. 2 (2014)');
		cy.contains(submission.title).click();
		cy.get('h1').contains(submission.title);
		cy.checkViewableGalley('PDF');
		cy.contains(submission.title).click();
		cy.contains('Alan Mwandenga');
		cy.contains('University of Cape Town');
		cy.contains('Lorem Ipsum');
		cy.contains('Professional Development');
		cy.contains('Social Transformation');
	});

	it('Article is not available when unpublished', function() {
		cy.login('dbarnes');
		cy.visit('/index.php/publicknowledge/workflow/access/' + submission.id);
		cy.get('#publication-button').click();
		cy.get('button').contains('Unpublish').click();
		cy.contains('Are you sure you don\'t want this to be published?');
		cy.get('.pkp_modal button').contains('OK').click();
		cy.visit('/index.php/publicknowledge/issue/current');
		cy.contains('Signalling Theory Dividends').should('not.exist');
		cy.request({
				url: '/index.php/publicknowledge/article/view/9',
				failOnStatusCode: false
			})
			.then((response) => {
				expect(response.status).to.equal(404);
			});

		// Re-publish it
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
		cy.get('button').contains('Yes').click();
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
		cy.get('input[name=title-en_US').type(' Version 2', {delay: 0});
		cy.get('#titleAbstract button').contains('Save').click();
		cy.contains('The title and abstract have been updated.');

		// Edit Contributor
		cy.get('#contributors-button').click();
		cy.contains('Add Contributor');
		cy.get('#contributors-grid .show_extras').eq(0).click();
		cy.get('[id*="editAuthor-button"]').eq(0).click();
		cy.wait(1500); // Wait for the form to settle
		cy.get('[name="familyName[en_US]"]').type(' Version 2', {delay: 0});
		cy.get('[id^="submitFormButton"]').contains('Save').click();
		cy.contains('Author edited.');
		cy.wait(1500); // Wait for the grid to reload
		cy.get('[id*="authorgrid-row"] span').contains('Alan Mwandenga Version 2');

		// Edit Galley
		cy.get('#galleys-button').click();
		cy.contains('Add galley');
		cy.wait(500);
		cy.get('#representations-grid .show_extras').click();
		cy.wait(500);
		cy.get('[id*="editGalley-button"]').click();
		cy.get('#editArticleGalleyMetadataTabs [name="label"]').type(' Version 2');
		cy.get('#editArticleGalleyMetadataTabs [name="urlPath"]').type('pdf');
		cy.get('#articleGalleyForm button').contains('Save').click();
		cy.wait(1500);
		cy.get('#representations-grid [id*="downloadFile-button"').contains('PDF Version 2');

		// Edit url path
		cy.get('#issue-button').click();
		cy.get('#issue [name="urlPath"]').type('mwandenga');
		cy.get('#issue button').contains('Save').click();

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
		cy.get('.versions li').eq(1).find('a').click();
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
		cy.get('.pkp_modal button').contains('OK').click();
		cy.get('.pkpWorkflow a').contains('View').click();
		cy.contains('The Signalling Theory Dividends Version 2').should('not.exist');
		cy.get('.versions').should('not.exist');
	});

	it('Recommend-only editors can not publish, unpublish or create versions', function() {
		cy.login('dbarnes');
		cy.visit('/index.php/publicknowledge/workflow/access/' + submission.id);
		cy.get('[id^="component-grid-users-stageparticipant"]').contains('Stephanie Berardo')
			.closest('td').find('.show_extras').click()
			.closest('tr').next().find('a').contains('Edit').click();
		cy.get('[name="recommendOnly"]').check();
		cy.get('[id^="submitFormButton"]').contains('OK').click();
		cy.contains('The stage assignment has been changed.');
		cy.wait(500);
		cy.get('[id^="component-grid-users-stageparticipant"]').contains('Stephanie Berardo')
			.closest('td').find('.show_extras').click()
			.closest('tr').next().find('a').contains('Login As').click();
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
		cy.get('[id^="component-grid-users-stageparticipant"]').contains('Stephanie Berardo')
			.closest('td').find('.show_extras').click()
			.closest('tr').next().find('a').contains('Edit').click();
		cy.get('[name="canChangeMetadata"]').uncheck();
		cy.get('[id^="submitFormButton"]').contains('OK').click();
		cy.contains('The stage assignment has been changed.');
		cy.wait(500);
		cy.get('[id^="component-grid-users-stageparticipant"]').contains('Stephanie Berardo')
			.closest('td').find('.show_extras').click()
			.closest('tr').next().find('a').contains('Login As').click();
		cy.get('.pkpModalConfirmButton').contains('OK').click();
		cy.get('#publication-button').click();
		cy.get('#titleAbstract button').contains('Save').should('be.disabled');
	});
});
