/**
 * @file cypress/tests/data/60-content/VkarbasizaedSubmission.spec.js
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 */

describe('Data suite tests', function() {
	it('Create a submission', function() {
		var title = 'Antimicrobial, heavy metal resistance and plasmid profile of coliforms isolated from nosocomial infections in a hospital in Isfahan, Iran';
		cy.register({
			'username': 'vkarbasizaed',
			'givenName': 'Vajiheh',
			'familyName': 'Karbasizaed',
			'affiliation': 'University of Tehran',
			'country': 'Iran, Islamic Republic of',
		});

		cy.createSubmission({
			'section': 'Articles',
			title,
			'abstract': 'The antimicrobial, heavy metal resistance patterns and plasmid profiles of Coliforms (Enterobacteriacea) isolated from nosocomial infections and healthy human faeces were compared. Fifteen of the 25 isolates from nosocomial infections were identified as Escherichia coli, and remaining as Kelebsiella pneumoniae. Seventy two percent of the strains isolated from nosocomial infections possess multiple resistance to antibiotics compared to 45% of strains from healthy human faeces. The difference between minimal inhibitory concentration (MIC) values of strains from clinical cases and from faeces for four heavy metals (Hg, Cu, Pb, Cd) was not significant. However most strains isolated from hospital were more tolerant to heavy metal than those from healthy persons. There was no consistent relationship between plasmid profile group and antimicrobial resistance pattern, although a conjugative plasmid (>56.4 kb) encoding resistance to heavy metals and antibiotics was recovered from eight of the strains isolated from nosocomial infections. The results indicate multidrug-resistance coliforms as a potential cause of nosocomial infection in this region.',
		});

		cy.logout();
		cy.findSubmissionAsEditor('dbarnes', null, title);
		cy.sendToReview();
		cy.assignReviewer('Julie Janssen');
		cy.assignReviewer('Paul Hudson');
		cy.recordEditorialDecision('Accept Submission');
		cy.get('li.ui-state-active a:contains("Copyediting")');
		cy.assignParticipant('Copyeditor', 'Maria Fritz');
		cy.recordEditorialDecision('Send To Production');
		cy.get('li.ui-state-active a:contains("Production")');
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
			cy.get('div[id^="fileUploadWizard"] input[type=file]').upload(
				{fileContent, 'fileName': 'article.pdf', 'mimeType': 'application/pdf', 'encoding': 'base64'}
			);
		});
		cy.get('button').contains('Continue').click();
		cy.get('button').contains('Continue').click();
		cy.get('button').contains('Complete').click();


		// Publish in current issue
		cy.publish('1', 'Vol. 1 No. 2 (2014)');
		cy.isInIssue(title, 'Vol. 1 No. 2 (2014)');
	});
});
