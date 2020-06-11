/**
 * @file cypress/tests/data/60-content/DsokoloffSubmission.spec.js
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */

describe('Data suite tests', function() {
	it('Create a submission', function() {
		cy.register({
			'username': 'dsokoloff',
			'givenName': 'Domatilia',
			'familyName': 'Sokoloff',
			'affiliation': 'University College Cork',
			'country': 'Ireland',
		});

		cy.createSubmission({
			'section': 'Articles',
			'title': 'Developing efficacy beliefs in the classroom',
			'abstract': 'A major goal of education is to equip children with the knowledge, skills and self-belief to be confident and informed citizens - citizens who continue to see themselves as learners beyond graduation. This paper looks at the key role of nurturing efficacy beliefs in order to learn and participate in school and society. Research findings conducted within a social studies context are presented, showing how strategy instruction can enhance self-efficacy for learning. As part of this research, Creative Problem Solving (CPS) was taught to children as a means to motivate and support learning. It is shown that the use of CPS can have positive effects on self-efficacy for learning, and be a valuable framework to involve children in decision-making that leads to social action. Implications for enhancing self-efficacy and motivation to learn in the classroom are discussed.',
			'keywords': [
				'education',
				'citizenship',
			],
		});

		cy.logout();
		cy.findSubmissionAsEditor('dbarnes', null, 'Sokoloff');
		cy.sendToReview();
		cy.assignReviewer('Paul Hudson');
		cy.assignReviewer('Aisla McCrae');
		cy.assignReviewer('Adela Gallego');
		cy.logout();
		cy.performReview('phudson', null, 'Developing efficacy beliefs in the classroom', 'Decline Submission');
	});
});
