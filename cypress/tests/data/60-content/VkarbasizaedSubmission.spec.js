/**
 * @file cypress/tests/data/60-content/VkarbasizaedSubmission.spec.js
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */

describe('Data suite tests', function() {
	var issueTitle = 'Vol. 2 No. 1 (2015)';
	let submission;
	let author;

	before(function() {
		author = {
			username: 'vkarbasizaed',
			givenName: 'Vajiheh',
			familyName: 'Karbasizaed',
			affiliation: 'University of Tehran',
			country: 'Iran, Islamic Republic of',
		}
		submission = {
			section: 'Articles',
			prefix: '',
			title: 'Antimicrobial, heavy metal resistance and plasmid profile of coliforms isolated from nosocomial infections in a hospital in Isfahan, Iran',
			subtitle: '',
			abstract: 'The antimicrobial, heavy metal resistance patterns and plasmid profiles of Coliforms (Enterobacteriacea) isolated from nosocomial infections and healthy human faeces were compared. Fifteen of the 25 isolates from nosocomial infections were identified as Escherichia coli, and remaining as Kelebsiella pneumoniae. Seventy two percent of the strains isolated from nosocomial infections possess multiple resistance to antibiotics compared to 45% of strains from healthy human faeces. The difference between minimal inhibitory concentration (MIC) values of strains from clinical cases and from faeces for four heavy metals (Hg, Cu, Pb, Cd) was not significant. However most strains isolated from hospital were more tolerant to heavy metal than those from healthy persons. There was no consistent relationship between plasmid profile group and antimicrobial resistance pattern, although a conjugative plasmid (>56.4 kb) encoding resistance to heavy metals and antibiotics was recovered from eight of the strains isolated from nosocomial infections. The results indicate multidrug-resistance coliforms as a potential cause of nosocomial infection in this region.',
			authors: [author.givenName + ' ' + author.familyName]
		}
	});

	it('Create a submission', function() {
		cy.register(author);

		cy.createSubmission(submission);

		cy.logout();
		cy.findSubmissionAsEditor('dbarnes', null, author.familyName);
		cy.clickDecision('Send for Review');
		cy.recordDecisionSendToReview('Send for Review', submission.authors, [submission.title]);
		cy.isActiveStageTab('Review');
		cy.assignReviewer('Julie Janssen');
		cy.assignReviewer('Paul Hudson');
		cy.clickDecision('Accept Submission');
		cy.recordDecisionAcceptSubmission(submission.authors, [], []);
		cy.isActiveStageTab('Copyediting');
		cy.assignParticipant('Copyeditor', 'Maria Fritz');
		cy.clickDecision('Send To Production');
		cy.recordDecisionSendToProduction(submission.authors, []);
		cy.isActiveStageTab('Production');
		cy.assignParticipant('Layout Editor', 'Graham Cox');
		cy.assignParticipant('Proofreader', 'Catherine Turner');

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

	it('Schedule for publication', function() {
		cy.login('dbarnes');
		// schedule for the publication in the future issue
		cy.visit('index.php/publicknowledge/submissions');
		cy.get('button[id="active-button"]').click();
		cy.get('#active .listPanel__itemTitle:contains("' + author.familyName + '")').parent().next().contains('a', 'View').click();
		cy.get('button[id="publication-button"]').click();
		cy.get('#publication button:contains("Schedule For Publication")').click();
		cy.get('select[id="assignToIssue-issueId-control"]').select(issueTitle);
		cy.get('div[id^="assign-"] button:contains("Save")').click();
		cy.get('div:contains("All publication requirements have been met. This will be published when ' + issueTitle + ' is published. Are you sure you want to schedule this for publication?")');
		cy.get('.pkpWorkflow__publishModal button:contains("Schedule For Publication")').click();

		// check status = 5 (scheduled)
		cy.wait(1000); // to be able to get the header
		// check submission status
		cy.get('.pkpWorkflow__header:contains("Scheduled")');
		// check publication status
		cy.get('.pkpPublication__header:contains("Scheduled")');
		// the button "Unschedule" exists
		// the buttons "Create New Version" (connected with submission) and "Unpublish" (connected with publication) does not exist
		cy.get('#publication button:contains("Unschedule")');
		cy.get('#publication button:contains("Create New Version")').should('not.exist');
		cy.get('#publication button:contains("Unpublish")').should('not.exist');

		// isInTOC:
		cy.visit('index.php/publicknowledge/manageIssues#future');
		cy.get('a:contains("' + issueTitle + '")').click();
		cy.get('div[id^="component-grid-toc-tocgrid-"] span:contains("' + submission.title + '")');
	});

	it('Publish the issue', function() {
		cy.login('dbarnes');
		cy.visit('index.php/publicknowledge/manageIssues#future');
		cy.get('span:contains("' + issueTitle + '")').prev('a.show_extras').click();
		cy.get('tr:contains("' + issueTitle + '")').next().contains('a', 'Publish Issue').click();
		cy.get('input[id="sendIssueNotification"]').click();
		cy.get('button[id^=submitFormButton]').click();
		// check status = 3 (published)
		cy.visit('index.php/publicknowledge/submissions');
		cy.get('button[id="archive-button"]').click();
		cy.get('#archive .listPanel__itemTitle:contains("' + author.familyName + '")').parent().next().contains('a', 'View').click();
		// check submission status
		cy.get('.pkpWorkflow__header:contains("Published")');
		// check publication status
		cy.get('.pkpPublication__header:contains("Published")');
		cy.contains('This version has been published and can not be edited.');
		// the button "Unpublish" (connected with the publication)
		// and the button "Create New Version" (connected with submission) exist
		cy.get('#publication button:contains("Unpublish")');
		cy.get('#publication button:contains("Create New Version")');
	});

	it('Unpublish the issue', function() {
		cy.login('dbarnes');
		cy.visit('index.php/publicknowledge/manageIssues#back');
		cy.get('span:contains("' + issueTitle + '")').prev('a.show_extras').click();
		cy.get('tr:contains("' + issueTitle + '")').next().contains('a', 'Unpublish Issue').click();
		cy.get('button:contains("OK")').click();
		// check status = 5 (scheduled)
		cy.visit('index.php/publicknowledge/submissions');
		cy.get('button[id="archive-button"]').click();
		cy.get('#archive .listPanel__itemTitle:contains("' + author.familyName + '")').parent().next().contains('a', 'View').click();
		// check submission status
		cy.get('.pkpWorkflow__header:contains("Scheduled")');
		// check publication status
		cy.get('.pkpPublication__header:contains("Scheduled")');
		// the button "Unschedule" exists
		// the buttons "Create New Version" (connected with submission) and "Unpublish" (connected with publication) does not exist
		cy.get('#publication button:contains("Unschedule")');
		cy.get('#publication button:contains("Create New Version")').should('not.exist');
		cy.get('#publication button:contains("Unpublish")').should('not.exist');
	});

	it('Republish the issue', function() {
		cy.login('dbarnes');
		cy.visit('index.php/publicknowledge/manageIssues#future');
		cy.get('span:contains("' + issueTitle + '")').prev('a.show_extras').click();
		cy.get('tr:contains("' + issueTitle + '")').next().contains('a', 'Publish Issue').click();
		cy.get('input[id="sendIssueNotification"]').click();
		cy.get('button[id^=submitFormButton]').click();
		// check status = 3
		cy.visit('index.php/publicknowledge/submissions');
		cy.get('button[id="archive-button"]').click();
		cy.get('#archive .listPanel__itemTitle:contains("' + author.familyName + '")').parent().next().contains('a', 'View').click();
		// check submission status
		cy.get('.pkpWorkflow__header:contains("Published")');
		// check publication status
		cy.get('.pkpPublication__header:contains("Published")');
		cy.contains('This version has been published and can not be edited.');
		// the button "Unpublish" (connected with the publication)
		// and the button "Create New Version" (connected with submission) exist
		cy.get('#publication button:contains("Unpublish")');
		cy.get('#publication button:contains("Create New Version")');
	});

	it('Remove submission from TOC', function() {
		cy.login('dbarnes');
		cy.visit('index.php/publicknowledge/manageIssues#back');
		cy.get('span:contains("' + issueTitle + '")').prev('a.show_extras').click();
		cy.get('tr:contains("' + issueTitle + '")').next().contains('a', 'Edit').click();
		cy.get('span:contains("' + submission.title + '")').prev('a.show_extras').click();
		cy.get('tr:contains("' + submission.title + '")').next().contains('a', 'Remove').click();
		cy.get('div:contains("Are you sure you wish to remove this article from the issue? The article will be available for scheduling in another issue.")');
		cy.get('button:contains("OK")').click();
		cy.get('span:contains("' + submission.title + '")').should('not.exist');
		// check status = 1
		cy.visit('index.php/publicknowledge/submissions');
		cy.get('button[id="active-button"]').click();
		cy.get('#active .listPanel__itemTitle:contains("' + author.familyName + '")').parent().next().contains('a', 'View').click();
		// check submission status
		cy.get('span').should('not.have.class', 'pkpWorkflow__identificationStatus');
		// check publication status
		cy.get('.pkpPublication__header:contains("Unscheduled")');
		// the button "Schedule For Publication" exists
		cy.get('#publication button:contains("Schedule For Publication")');
	});

	it('Return back to the original state', function() {
		cy.login('dbarnes');
		// Publish in current issue
		cy.visit('index.php/publicknowledge/submissions');
		cy.get('button[id="active-button"]').click();
		cy.get('#active .listPanel__itemTitle:contains("' + author.familyName + '")').parent().next().contains('a', 'View').click();
		cy.get('button[id="publication-button"]').click();
		cy.get('button[id="issue-button"]').click();
		cy.get('button:contains("Change Issue")').click();
		cy.get('select[id="assignToIssue-issueId-control"]').select('Vol. 1 No. 2 (2014)');
		cy.get('div[id^="assign-"] button:contains("Save")').click();
		cy.get('div[id^="publish-"] button:contains("Publish")').click();
		cy.isInIssue('Antimicrobial, heavy metal resistance', 'Vol. 1 No. 2 (2014)');
		// unpublish the future issue
		cy.visit('index.php/publicknowledge/manageIssues#back');
		cy.get('span:contains("' + issueTitle + '")').prev('a.show_extras').click();
		cy.get('tr:contains("' + issueTitle + '")').next().contains('a', 'Unpublish Issue').click();
		cy.get('button:contains("OK")').click();
		cy.visit('index.php/publicknowledge/manageIssues#future');
		cy.get('span:contains("' + issueTitle + '")');
		// define the back issue as the current issue again
		cy.visit('index.php/publicknowledge/manageIssues#back');
		cy.get('span:contains("Vol. 1 No. 2 (2014)")').prev('a.show_extras').click();
		cy.get('tr:contains("Vol. 1 No. 2 (2014)")').next().contains('a', 'Current Issue').click();
		cy.get('button:contains("OK")').click();
		cy.visit('index.php/publicknowledge/issue/current');
		cy.contains(submission.title);
	});
});
