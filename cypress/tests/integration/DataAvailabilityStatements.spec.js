/**
 * @file cypress/tests/integration/DataAvailabilityStatements.spec.js
*/

describe('DataAvailabilityStatements', function () {
	var statement = 'This is an example of a data availability statement';
	var reviewer = { name: 'Paul Hudson', login: 'phudson' };
	var submission = {
		title: 'Towards Designing an Intercultural Curriculum',
		author_family_name: 'Daniel'
	};

	it('Sends submission for review with "disclosed author" method and check Statement as reviewer', function () {
		cy.findSubmissionAsEditor('dbarnes', null, submission.author_family_name);
		cy.clickDecision('Send for Review');
		cy.contains('Skip this email').click();
		cy.get('.pkpButton--isPrimary').contains('Record Decision').click();
		cy.findSubmissionAsEditor('dbarnes', null, submission.author_family_name);
		cy.contains('Add Reviewer').click();
		cy.contains(reviewer.name).parentsUntil('.listPanel__item').find('.pkpButton').click();
		cy.get('#skipEmail').check();
		cy.get('#reviewMethod-1').check();
		cy.get('#advancedSearchReviewerForm').contains('Add Reviewer').click();
		cy.logout();
		cy.login(reviewer.login);
		cy.visit("/index.php/publicknowledge/submissions");
		cy.contains('View ' + submission.title).click();
		cy.contains('View All Submission Details').click();
		cy.contains('Data Availability Statement');
		cy.contains(statement);
	});
});
