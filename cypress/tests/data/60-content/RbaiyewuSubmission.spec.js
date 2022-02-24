/**
 * @file cypress/tests/data/60-content/RbaiyewuSubmission.spec.js
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */

describe('Data suite tests', function() {
	it('Create a submission', function() {
		cy.register({
			'username': 'rbaiyewu',
			'givenName': 'Rana',
			'familyName': 'Baiyewu',
			'affiliation': 'University of Nairobi',
			'country': 'Kenya',
		});

		var submission = {
			'section': 'Articles',
			'title': 'Yam diseases and its management in Nigeria',
			'abstract': 'This review presents different diseases associated with yam and the management strategies employed in combating its menace in Nigeria. The field and storage diseases are presented, anthracnose is regarded as the most widely spread of all the field diseases, while yam mosaic virus disease is considered to cause the most severe losses in yams. Dry rot is considered as the most devastating of all the storage diseases of yam. Dry rot of yams alone causes a marked reduction in the quantity, marketable value and edible portions of tubers and those reductions are more severe in stored yams. The management strategies adopted and advocated for combating the field diseases includes the use of crop rotation, fallowing, planting of healthy material, the destruction of infected crop cultivars and the use of resistant cultivars. With regards to the storage diseases, the use of Tecto (Thiabendazole), locally made dry gins or wood ash before storage has been found to protect yam tubers against fungal infection in storage. Finally, processing of yam tubers into chips or cubes increases its shelf live for a period of between 6 months and one year.',
			'authors': ['Rana Baiyewu'],
		};
		cy.createSubmission(submission);

		cy.logout();
		cy.findSubmissionAsEditor('dbarnes', null, 'Baiyewu');
		cy.clickDecision('Send for Review');
		cy.recordDecisionSendToReview('Send for Review', submission.authors, [submission.title]);
		cy.isActiveStageTab('Review');
		cy.assignReviewer('Paul Hudson');
		cy.assignReviewer('Aisla McCrae');
		cy.clickDecision('Accept Submission');
		cy.recordDecisionAcceptSubmission(submission.authors, [], []);
		cy.isActiveStageTab('Copyediting');
		cy.assignParticipant('Copyeditor', 'Sarah Vogt');
		cy.clickDecision('Send To Production');
		cy.recordDecisionSendToProduction(submission.authors, []);
		cy.isActiveStageTab('Production');
		cy.assignParticipant('Layout Editor', 'Stephen Hellier');
	});
});
