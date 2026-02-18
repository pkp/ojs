/**
 * @file cypress/tests/integration/ReviewerRecommendation.cy.js
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */

describe('Customize reviewer recommendations test', () => {
    let defaultRecommendations = [
        'Accept Submission',
        'Revisions Required',
        'Resubmit for Review',
        'Resubmit Elsewhere',
        'Decline Submission',
        'See Comments',
    ];

    it('Access the reviewer recommendations page', () => {
        cy.accessReviewerRecommendations('dbarnes', null, 'publicknowledge');

        cy.get('[data-cy="reviewer-recommendation-manager"]')
            .find('tbody > tr')
            .should('have.length', defaultRecommendations.length);

        defaultRecommendations.forEach((recommendation) => {
            cy.get('[data-cy="reviewer-recommendation-manager"]')
                .find('tr:contains("' + recommendation + '")')
                .should('exist');
        });
		cy.logout();
    });

  it('Check that default reviewer recommendations has a type', () => {
    cy.accessReviewerRecommendations('dbarnes', null, 'publicknowledge');

    cy.get('[data-cy="reviewer-recommendation-manager"]')
      .find('tbody > tr')
      .should('have.length', defaultRecommendations.length);

    // Default Recommendations that are already used cannot be edited/viewed, so to satisfy this test, we will only check the recommendations that are not used.
    [
      'Accept Submission',
      'Resubmit Elsewhere',
      'See Comments'
    ].forEach((recommendation) => {
      cy.get('[data-cy="reviewer-recommendation-manager"]')
        .find('tr:contains("' + recommendation + '")')
        .find('button[aria-label*="More Actions"]').click();
      cy.get('[data-cy="reviewer-recommendation-manager"]')
        .find('button:contains("Edit")').click();

      cy.get('select[name="type"]').should('not.be.empty');

			cy.get('[data-cy="active-modal"]')
				.contains('button', 'Close')
				.click({force: true});
    });

    cy.logout();
  });

    it('Add, edit, and delete reviewer recommendations', () => {
        cy.accessReviewerRecommendations('dbarnes', null, 'publicknowledge');

        // Add a new recommendation
        cy.get('[data-cy="reviewer-recommendation-manager"]')
            .find('button:contains("Add Recommendation")')
            .should('exist')
            .click();
        cy.wait(200);

        cy.get('div[role=dialog]:contains("Add Recommendation")')
            .find('button:contains("French")')
            .click();
        cy.get('div[role=dialog]:contains("Add Recommendation")')
            .find('input#reviewerRecommendation-title-control-en')
            .type('Test Recommendation');
        cy.get('div[role=dialog]:contains("Add Recommendation")')
            .find('input#reviewerRecommendation-title-control-fr_CA')
            .type('Recommandation de test');

        // Select a recommendation type
        cy.get('select[name="type"]').select(0);

        cy.get('div[role=dialog]:contains("Add Recommendation")')
            .find('button:contains("Save")')
            .click();
        cy.wait(500);

        cy.get('[data-cy="reviewer-recommendation-manager"]')
            .find('tbody > tr')
            .should('have.length', defaultRecommendations.length + 1);
        cy.get('[data-cy="reviewer-recommendation-manager"]')
            .find('tbody > tr')
            .contains('Test Recommendation');

        // Edit the recommendation
        cy.get('[data-cy="reviewer-recommendation-manager"]')
            .find('tr:contains("Test Recommendation")')
            .find('button[aria-label*="More Actions"]')
            .should('exist')
            .click();
        cy.get('[data-cy="reviewer-recommendation-manager"]')
            .find('button:contains("Edit")')
            .click();
        cy.get('div[role=dialog]:contains("Edit Recommendation")')
            .find('input#reviewerRecommendation-title-control-en')
            .clear()
            .type('Edited Recommendation');

        // Change the recommendation type
        cy.get('select[name="type"]').select(1);

        cy.get('div[role=dialog]:contains("Edit Recommendation")')
            .find('button:contains("Save")')
            .click();
        cy.wait(500);
        cy.get('[data-cy="reviewer-recommendation-manager"]')
            .find('tbody > tr')
            .contains('Edited Recommendation');

        // Delete the recommendation
        cy.get('[data-cy="reviewer-recommendation-manager"]')
            .find('tr:contains("Edited Recommendation")')
            .find('button[aria-label*="More Actions"]')
            .should('exist')
            .click();
        cy.get('[data-cy="reviewer-recommendation-manager"]')
            .find('button:contains("Delete")')
            .click();
        cy.get('div[role=dialog]:contains("Delete Recommendation")')
            .find('button:contains("No")')
            .click();
        cy.get('[data-cy="reviewer-recommendation-manager"]')
            .find('tbody > tr')
            .contains('Edited Recommendation');
        cy.get('[data-cy="reviewer-recommendation-manager"]')
            .find('tr:contains("Edited Recommendation")')
            .find('button[aria-label*="More Actions"]')
            .should('exist')
            .click();
        cy.get('[data-cy="reviewer-recommendation-manager"]')
            .find('button:contains("Delete")')
            .click();
        cy.get('div[role=dialog]:contains("Delete Recommendation")')
            .find('button:contains("Yes")')
            .click();
        cy.wait(500);
        cy.get('[data-cy="reviewer-recommendation-manager"]')
            .find('tr:contains("Edited Recommendation")')
            .should('not.exist');
        cy.get('[data-cy="reviewer-recommendation-manager"]')
            .find('tbody > tr')
            .should('have.length', defaultRecommendations.length);

		cy.logout();
    });

    it('Enable/disable reviewer recommendations', () => {
        cy.accessReviewerRecommendations('dbarnes', null, 'publicknowledge');

        cy.get('[data-cy="reviewer-recommendation-manager"]')
            .find('tr:contains("See Comments")')
            .find('input[type="checkbox"]')
            .should('be.checked');

        // Deactivate the recommendation
        cy.get('[data-cy="reviewer-recommendation-manager"]')
            .find('tr:contains("See Comments")')
            .find('input[type="checkbox"]')
            .click();
        cy.get('div[role=dialog]:contains("Deactivate Reviewer Recommendation")')
            .find('button:contains("No")')
            .click();
        cy.get('[data-cy="reviewer-recommendation-manager"]')
            .find('tr:contains("See Comments")')
            .find('input[type="checkbox"]')
            .should('be.checked');
        cy.get('[data-cy="reviewer-recommendation-manager"]')
            .find('tr:contains("See Comments")')
            .find('input[type="checkbox"]')
            .click();
        cy.get('div[role=dialog]:contains("Deactivate Reviewer Recommendation")')
            .find('button:contains("Yes")')
            .click();
        cy.get('[data-cy="reviewer-recommendation-manager"]')
            .find('tr:contains("See Comments")')
            .find('input[type="checkbox"]')
            .should('not.be.checked');

        // Activate the recommendation
        cy.get('[data-cy="reviewer-recommendation-manager"]')
            .find('tr:contains("See Comments")')
            .find('input[type="checkbox"]')
            .click();
        cy.get('div[role=dialog]:contains("Activate Reviewer Recommendation")')
            .find('button:contains("No")')
            .click();
        cy.get('[data-cy="reviewer-recommendation-manager"]')
            .find('tr:contains("See Comments")')
            .find('input[type="checkbox"]')
            .should('not.be.checked');
        cy.get('[data-cy="reviewer-recommendation-manager"]')
            .find('tr:contains("See Comments")')
            .find('input[type="checkbox"]')
            .click();
        cy.get('div[role=dialog]:contains("Activate Reviewer Recommendation")')
            .find('button:contains("Yes")')
            .click();
        cy.get('[data-cy="reviewer-recommendation-manager"]')
            .find('tr:contains("See Comments")')
            .find('input[type="checkbox"]')
            .should('be.checked');
		cy.logout();
    });

    it('Not allow to edit or delete a recommendation already used', () => {
        cy.accessReviewerRecommendations('dbarnes', null, 'publicknowledge');

        cy.get('[data-cy="reviewer-recommendation-manager"]')
            .find('tr:contains("Decline Submission")')
            .find('button[aria-label*="More Actions"]')
            .should('have.length', 0);
		cy.logout();
    });

    it('Inactive recommendation not available in review assignment for selection', () => {
        cy.accessReviewerRecommendations('dbarnes', null, 'publicknowledge');

        cy.get('[data-cy="reviewer-recommendation-manager"]')
            .find('tr:contains("Resubmit Elsewhere")')
            .find('input[type="checkbox"]')
            .click();
        cy.get('div[role=dialog]:contains("Deactivate Reviewer Recommendation")')
            .find('button:contains("Yes")')
            .click();
        cy.logout();

        cy.findSubmissionAsEditor('dbarnes', null, 'Sokoloff');
        cy.get('[data-cy="reviewer-manager"]')
            .find('button:contains("Read Review")')
            .click();
        cy.wait(1000)
        cy.get('div[role=dialog]:contains("Recommendation")')
            .last()
            .find('select#reviewerRecommendationId option:contains("Resubmit Elsewhere")')
            .should('not.exist');
        cy.logout();

        cy.accessReviewerRecommendations('dbarnes', null, 'publicknowledge');

        cy.get('[data-cy="reviewer-recommendation-manager"]')
            .find('tr:contains("Resubmit Elsewhere")')
            .find('input[type="checkbox"]')
            .click();
        cy.get('div[role=dialog]:contains("Activate Reviewer Recommendation")')
            .find('button:contains("Yes")')
            .click();
        cy.logout();

        cy.findSubmissionAsEditor('dbarnes', null, 'Sokoloff');
        cy.get('[data-cy="reviewer-manager"]')
            .find('button:contains("Read Review")')
            .click();
        cy.wait(1000)
        cy.get('div[role=dialog]:contains("Recommendation")')
            .last()
            .find('select#reviewerRecommendationId option:contains("Resubmit Elsewhere")')
            .should('exist');

        cy.logout();
    });
});
