/**
 * @file cypress/tests/integration/SubmissionWizard.cy.js
 *
 * Copyright (c) 2014-2023 Simon Fraser University
 * Copyright (c) 2000-2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */
import Api from '../../../lib/pkp/cypress/support/api'

describe('Submission Wizard', function() {

    var submission = {
        title: {
            en: "Ut enim ad minim veniam quis nostrud exercitation ullamco",
            fr_CA: "Phasellus hendrerit ligula non erat laoreet et feugiat risus varius"
        },
        abstract: {
            en: "Suspendisse lacinia lacinia gravida. Maecenas risus tortor, placerat et consectetur nec, finibus id justo.",
            fr_CA: "Phasellus hendrerit ligula non erat laoreet, et feugiat risus varius. Proin convallis tristique ornare."
        }
    };

    function startSubmission(title, username, section) {
        title = title || submission.title.en;
        username = username || 'ccorino';
        section = section || 'Articles';

        cy.login(username, null, 'publicknowledge');

        // Start submission in English and Articles section
		cy.get('a:contains("New Submission")').first().click();
        cy.get('label:contains("English")').click();
		cy.setTinyMceContent('startSubmission-title-control', title);
        cy.get('label:contains("' + section + '")').click();
        cy.get('label:contains("Yes, my submission meets all of these requirements.")').click();
        cy.get('button:contains("Begin Submission")').click();
    }

    it('Tests comments for the editor', function() {

        // Start submission, upload a file and go to For the Editors step
        const title = 'Duis aute irure dolor in reprehenderit in voluptate';
        const comments = 'Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.';
        startSubmission(title, null, 'Reviews');
        cy.contains('Submitting to the Reviews section in English');
        cy.get('button:contains("Continue")').click();
        cy.uploadSubmissionFiles([
            {
                'file': 'dummy.pdf',
                'fileName': title + '.pdf',
                'mimeType': 'application/pdf',
                'genre': Cypress.env('defaultGenre')
            }
        ]);
        cy.get('button:contains("Continue")').click();
        cy.get('button:contains("Continue")').click();

        // Add comments for the editor and submit
		cy.setTinyMceContent('commentsForTheEditors-commentsForTheEditors-control', comments);
        cy.get('button:contains("Continue")').click();
        cy.get('button:contains("Submit")').click();
        cy.contains('The submission, ' + title + ', will be submitted to Journal of Public Knowledge for editorial review.');
        cy.get('.modal__footer button:contains("Submit")').click();
        cy.get('h1:contains("Submission complete")');

        // View submission discussion and check participants
        cy.get('a:contains("Review this submission")').click();
        cy.get('h4:contains("Pre-Review Discussions")')
            .parents('.pkp_controllers_grid')
            .find('a:contains("Comments for the Editor")')
            .click();
        cy.get('#participantsListPlaceholder li:contains("Daniel Barnes")');
        cy.get('#participantsListPlaceholder li:contains("Minoti Inoue")');
        cy.get('#participantsListPlaceholder li:contains("Carlo Corino")');
        cy.get('#queryNotesGrid .gridCellContainer').contains(comments);
    });

    it('Sets up the submission wizard for the following tests', function() {
        const api = new Api(Cypress.env('baseUrl') + '/index.php/publicknowledge/api/v1');

		cy.login('dbarnes', null, 'publicknowledge');

        cy.getCsrfToken()
            .then(() => {
                cy.request({
                    url: api.contexts(1),
                    method: 'PUT',
                    headers: {
                        'X-Csrf-Token': this.csrfToken
                    },
                    body: {
                        agencies: 'request',
                        citations: 'request',
                        coverage: 'request',
                        dataAvailability: 'request',
                        disciplines: 'request',
                        keywords: 'require',
                        languages: 'request',
                        rights: 'request',
                        source: 'request',
                        subjects: 'require',
                        submitWithCategories: true,
                        type: 'request',
                    },
                });
            }).then(xhr => {
                expect(xhr.status).to.eq(200);
            });
    });

	it('Changes the language of a submission and tests submitting in a language other than the current UI locale', function() {
		cy.login('ccorino', null, 'publicknowledge');

        // Start submission in English and Articles section
        startSubmission();

        // Abstract required with word count limit
        cy.get('label:contains("Abstract *")');
        cy.contains('Word Count: 0/500');

        // Change submission language to French and section to Reviews
        cy.contains('Submitting to the Articles section in English');
        cy.get('button:contains("Change")').click();
        cy.get('h2:contains("Change Submission Settings")')
            .parents('.modal')
            .within(() => {
                cy.get('label:contains("French")').click();
                cy.get('label:contains("Reviews")').click();
                cy.get('button:contains("Save")').click();
            });

        // Forms load with French fields displayed instead of English
        cy.contains('Submitting to the Reviews section in French');
        cy.get('span.pkpFormLocales__locale:contains("French")');
		cy.get('#titleAbstract-keywords-control-fr_CA').type('Transformation Sociale', {delay: 0});
		cy.get('li:contains("Transformation Sociale")');
		cy.get('#titleAbstract-keywords-control-fr_CA').type('{downarrow}{enter}', {delay: 0});
		cy.setTinyMceContent('titleAbstract-abstract-control-fr_CA', submission.abstract.fr_CA);

        // No abstract requirements in Reviews section
        cy.get('label:contains("Abstract *")').should('not.exist');
        cy.get('*:contains("Word Count: 0/500")').should('not.exist');

        // Show English fields alongside French fields
        cy.get('.pkpStep:contains("Submission Details") button.pkpFormLocales__locale:contains("English")').click();
        cy.get('label:contains("Title in English")');
        cy.get('label:contains("Keywords in English")');
        cy.get('label:contains("Abstract in English")');

        // Upload a file
        cy.get('button:contains("Continue")').click();
        cy.uploadSubmissionFiles([
            {
                'file': 'dummy.pdf',
                'fileName': submission.title.en + '.pdf',
                'mimeType': 'application/pdf',
                'genre': Cypress.env('defaultGenre')
            }
        ]);

        // Skip contributors
        cy.get('button:contains("Continue")').click();
        cy.get('button:contains("Continue")').click();

        // Check metadata form shows in French only at first
        const metadata = {
            subjects: "Subjects",
            disciplines: "Disciplines",
            languages: "Languages",
            supportingAgencies: "Supporting Agencies",
            coverage: "Coverage",
            rights: "Rights",
            source: "Source",
            type: "Type",
            dataAvailability: "Data Availability Statement",
        }
        Object.keys(metadata).forEach((prop) => {
            cy.get('label[for="forTheEditors-' + prop + '-control-fr_CA"]:contains("' + metadata[prop] + '")');
            cy.get('label:contains("' + metadata[prop] + ' in English")').should('not.be.visible');
        });

        // Show English fields alongside French fields
        cy.get('.pkpStep:contains("For the Editors") button.pkpFormLocales__locale:contains("English")').click();
        Object.keys(metadata).forEach((prop) => {
            cy.get('label:contains("' + metadata[prop] + ' in English")');
        });

        // Categories should appear in UI language (English)
        cy.get('label:contains("Social Sciences > Sociology")').click();

        // Errors in review
        cy.get('button:contains("Continue")').click();
        cy.contains('There are one or more problems');
        cy.get('h3:contains("Details (French)")')
            .parents('.submissionWizard__reviewPanel')
            .find('h4:contains("Title")')
            .parent()
            .contains('This field is required.');
        cy.contains('The given name is missing in French for one or more of the contributors.');
        cy.get('h3:contains("For the Editors (French)")')
            .parents('.submissionWizard__reviewPanel')
            .find('h4:contains("Subjects")')
            .parent()
            .contains('This field is required.');
        cy.get('h3:contains("Details (English)")')
            .parents('.submissionWizard__reviewPanel')
            .find('.pkpNotification--warning')
            .should('not.exist');
        cy.get('h3:contains("For the Editors (English)")')
            .parents('.submissionWizard__reviewPanel')
            .find('.pkpNotification--warning')
            .should('not.exist');

        // Categories in current UI language (English)
        cy.get('h4:contains("Categories")')
            .parent()
            .contains('Social Sciences > Sociology');

        // Add missing data
        cy.get('.pkpSteps button:contains("Details")').click();
        cy.setTinyMceContent('titleAbstract-title-control-fr_CA', submission.title.fr_CA);
        cy.get('.pkpSteps button:contains("Contributors")').click();
        cy.get('.listPanel__itemTitle:contains("Carlo Corino")')
            .parents('.listPanel__item')
            .find('button:contains("Edit")')
            .click();
        cy.get('input[name="givenName-fr_CA"]').type('Carlo', {delay: 0});
        cy.get('input[name="familyName-fr_CA"]').type('Carlo', {delay: 0});
        cy.get('.modal').find('button:contains("Save")').click();
        cy.get('.pkpSteps button:contains("For the Editors")').click();
		cy.get('#forTheEditors-subjects-control-fr_CA').type('Sociologie française', {delay: 0});
		cy.get('li:contains("Sociologie française")');
		cy.get('#forTheEditors-subjects-control-fr_CA').type('{downarrow}{enter}', {delay: 0});
        cy.get('.pkpSteps button:contains("Review")').click();

        // Should be able to submit!
        cy.get('button:contains("Submit")').click();
        cy.contains('The submission, ' + submission.title.en + ', will be submitted to Journal of Public Knowledge for editorial review.');
        cy.get('.modal__footer button:contains("Submit")').click();
        cy.get('h1:contains("Submission complete")');
    });
})