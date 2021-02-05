/**
 * @file cypress/tests/data/50-CreateIssues.spec.js
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */

describe('Data suite tests', function() {
	it('Creates issues', function() {
		cy.login('admin', 'admin');
		cy.get('a').contains('admin').click();
		cy.get('a').contains('Dashboard').click();
		cy.get('.app__nav a').contains('Issues').click();
		cy.get('a[id^=component-grid-issues-futureissuegrid-addIssue-button-]').click();
		cy.wait(1000); // Avoid occasional failure due to form init taking time
		cy.get('input[name="volume"]').type('1', {delay: 0});
		cy.get('input[name="number"]').type('2', {delay: 0});
		cy.get('input[name="year"]').type('2014', {delay: 0});
		cy.get('input[id=showTitle]').click();
		cy.get('button[id^=submitFormButton]').click();

		cy.get('a.show_extras').click();
		cy.contains('Publish Issue').click();
		cy.get('input[id="sendIssueNotification"]').click();
		cy.get('button[id^=submitFormButton]').click();

		// create a future issue
		cy.wait(1000);
		cy.get('a[id^=component-grid-issues-futureissuegrid-addIssue-button-]').click();
		cy.wait(1000); // Avoid occasional failure due to form init taking time
		cy.get('input[name="volume"]').type('2', {delay: 0});
		cy.get('input[name="number"]').type('1', {delay: 0});
		cy.get('input[name="year"]').type('2015', {delay: 0});
		cy.get('input[id=showTitle]').click();
		cy.get('button[id^=submitFormButton]').click();
	});
})
