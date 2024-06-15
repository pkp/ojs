/**
 * @file cypress/tests/integration/Doi.cy.js
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */

describe('DOI tests', function() {
	const issueDescription = "Vol. 1 No. 2 (2014)";
	const issueId = 1;
	const unpublishedIssueId = 2;
	const submissionId = 17;
	const unpublishedSubmissionId = 19;
	const publicationId = 18;
	const galleyId = 3;

	const loginAndGoToDoiPage = (itemType = 'submission') => {
		cy.login('dbarnes', null, 'publicknowledge');
		goToDoiPage(itemType);
	};

	const goToDoiPage = (itemType = 'submission') => {
		cy.get('a:contains("DOIs")').click();
		cy.get(`button#${itemType}-doi-management-button`).click();
	};

	const clearFilter = (itemType = 'submission') => {
		cy.get(`#${itemType}-doi-management button:contains("Clear filter")`).each(
			($el, index, $list) => {
				cy.wrap($el).click();
			}
		);
	};

	it('Check DOI Configuration', function() {
		cy.login('dbarnes', null, 'publicknowledge');
		cy.checkDoiConfig(['publication', 'issue', 'representation']);
	});

	it('Check DOI Assignment and Visibility', function() {
		cy.log('Check Issue assignment');
		loginAndGoToDoiPage('issue');
		cy.assignDois(issueId, 'issue');

		cy.get(`#list-item-issue-${issueId} button.expander`).click();
		cy.checkDoiAssignment(`${issueId}-issue`);

		cy.log('Check Submission Assignment');
		goToDoiPage();
		cy.assignDois(submissionId);

		cy.get(`#list-item-submission-${submissionId} button.expander`).click();
		cy.checkDoiAssignment(`${submissionId}-article-${publicationId}`);
		cy.checkDoiAssignment(`${submissionId}-representation-${galleyId}`);

		cy.log('Check Issue Visibility');
		// View issue with assigned DOI
		cy.visit('');
		cy.get('a:contains("Archives")').click();
		cy.get('a:contains("' + issueDescription + '")').click();
		cy.get('div.pub_id').should('have.class', 'doi');
		cy.get('div.doi span.id a').contains('https://doi.org/10.1234/');

		cy.log('Check Submission Visibility');
		// Select a submission
		cy.visit(`/index.php/publicknowledge/article/view/${submissionId}`);

		cy.get('section.item.doi')
			.find('span.value').contains('https://doi.org/10.1234/');
	});

	it.skip('Check filters and mark registered', function() {
		cy.log('Check Issue Filter Behaviour (pre-deposit)');
		loginAndGoToDoiPage('issue');

		cy.checkDoiFilterResults('Needs DOI', 'Vol. 2 No. 1 (2015)', 1, 'issue');;
		cy.checkDoiFilterResults('DOI Assigned', 'Vol. 1 No. 2 (2014)', 1, 'issue');
		clearFilter('issue');
		cy.checkDoiFilterResults('Unregistered', 'Vol. 1 No. 2 (2014)', 1, 'issue');
		clearFilter('issue');

		cy.log('Check Issue Marked Registered');
		cy.checkDoiMarkedStatus('Registered', issueId, true, 'Registered', 'issue');

		cy.log('Check Issue Filter Behaviour (post-deposit)');
		cy.checkDoiFilterResults('Submitted', 'No items found.', 0, 'issue');
		cy.checkDoiFilterResults('Registered', 'Vol. 1 No. 2 (2014)', 1, 'issue');

		cy.log('Check Submission filter behaviour (pre-deposit)');
		goToDoiPage();

		cy.checkDoiFilterResults('Needs DOI', 'Woods — Finocchiaro: Arguments About Arguments', 8);
		cy.checkDoiFilterResults('DOI Assigned', 'Karbasizaed — Antimicrobial, heavy metal resistance and plasmid profile of coliforms isolated from nosocomial infections in a hospital in Isfahan, Iran', 1);
		clearFilter();
		cy.checkDoiFilterResults('Unregistered', 'Karbasizaed — Antimicrobial, heavy metal resistance and plasmid profile of coliforms isolated from nosocomial infections in a hospital in Isfahan, Iran', 1);
		clearFilter();

		cy.log('Check Submission Marked Registered');
		cy.checkDoiMarkedStatus('Registered', submissionId, true, 'Registered');

		cy.log('Check Submission Filter Behaviour (post-deposit)');
		cy.checkDoiFilterResults('Submitted', 'No items found.', 0);
		cy.checkDoiFilterResults('Registered', 'Karbasizaed — Antimicrobial, heavy metal resistance and plasmid profile of coliforms isolated from nosocomial infections in a hospital in Isfahan, Iran', 1);
	});

	it.skip('Check Marked Status Behaviour', function() {
		loginAndGoToDoiPage('issue');

		cy.log('Check unpublished Issue Marked Registered displays error');
		cy.checkDoiMarkedStatus('Registered', unpublishedIssueId, false, 'Unpublished', 'issue');
		cy.log('Check Issue Marked Needs Sync');
		cy.checkDoiMarkedStatus('Needs Sync', issueId, true, 'Needs Sync', 'issue');
		cy.log('Check Issue Marked Unregistered');
		cy.checkDoiMarkedStatus('Unregistered', issueId, true, 'Unregistered', 'issue');
		cy.log('Check invalid Issue Marked Needs Sync displays error');
		cy.checkDoiMarkedStatus('Needs Sync', issueId, false, 'Unregistered', 'issue');

		goToDoiPage();

		cy.log('Check unpublished Submission Marked Registered displays error');
		cy.checkDoiMarkedStatus('Registered', unpublishedSubmissionId, false, 'Unpublished');
		cy.log('Check Submission Marked Needs Sync');
		cy.checkDoiMarkedStatus('Needs Sync', submissionId, true, 'Needs Sync');
		cy.log('Check Submission Marked Unregistered');
		cy.checkDoiMarkedStatus('Unregistered', submissionId, true, 'Unregistered');
		cy.log('Check invalid Submission Marked Needs Sync displays error');
		cy.checkDoiMarkedStatus('Needs Sync', submissionId, false, 'Unregistered');
	});
});
