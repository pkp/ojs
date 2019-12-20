/**
 * @file cypress/tests/data/60-content/CkwantesSubmission.spec.js
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 */

describe('Data suite tests', function() {
	it('Create a submission', function() {
		var title = 'The Facets Of Job Satisfaction: A Nine-Nation Comparative Study Of Construct Equivalence';
		cy.register({
			'username': 'ckwantes',
			'givenName': 'Catherine',
			'familyName': 'Kwantes',
			'affiliation': 'University of Windsor',
			'country': 'Canada'
		});

		cy.createSubmission({
			title,
			'section': 'Articles',
			'abstract': 'Archival data from an attitude survey of employees in a single multinational organization were used to examine the degree to which national culture affects the nature of job satisfaction. Responses from nine countries were compiled to create a benchmark against which nations could be individually compared. Factor analysis revealed four factors: Organizational Communication, Organizational Efficiency/Effectiveness, Organizational Support, and Personal Benefit. Comparisons of factor structures indicated that Organizational Communication exhibited the most construct equivalence, and Personal Benefit the least. The most satisfied employees were those from China, and the least satisfied from Brazil, consistent with previous findings that individuals in collectivistic nations report higher satisfaction. The research findings suggest that national cultural context exerts an effect on the nature of job satisfaction.',
			'keywords': [
				'employees',
				'survey'
			]
		});
		cy.logout();
		
		cy.findSubmissionAsEditor('dbarnes', null, title);
		cy.sendToReview();
		cy.assignReviewer('Aisla McCrae');
		cy.assignReviewer('Adela Gallego');
		cy.recordEditorialDecision('Accept Submission');
		cy.get('li.ui-state-active a:contains("Copyediting")');
		cy.assignParticipant('Copyeditor', 'Maria Fritz');
	});
})
