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
        cy.get('label:contains("Yes, I agree to have my data collected")').click();
        cy.get('button:contains("Begin Submission")').click();
    }

    it('The comments for the editor are converted to a discussion with all editors and authors assigned', function() {

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
        cy.contains(comments);
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

        cy.logout();
    });

    it('As an author, I am unable to submit to a section when it is marked inactive or when it is configured so that only editors can submit to it', function() {

        // Make all sections editor-restricted
        cy.login('dbarnes', null, 'publicknowledge');
        cy.get('.app__navGroup:contains("Settings") a:contains("Journal")').click();
        cy.get('.pkpTabs__buttons button:contains("Sections")').click();
        cy.get('#sectionsGridContainer a.show_extras')
            .each(($showExtras) => {
                cy.wrap($showExtras).click();
                cy.wrap($showExtras)
                    .parents('tr')
                    .next('tr')
                    .find('a:contains("Edit")')
                    .click();
                cy.wait(1500); // Let modal render. Fixes console error, maybe with TinyMCE init
                cy.get('label:contains("Items can only be submitted by Editors and Section Editors.")').click();
                cy.get('.pkp_modal button:contains("Save")').click();
                cy.get('.pkp_modal').should('not.exist');
            });

        // Can't submit as author
        cy.logout();
        cy.login('ccorino', null, 'publicknowledge');
        cy.get('a:contains("New Submission")').first().click();
        cy.get('h1:contains("Not Allowed")');

        // Make Articles inactive and leave Reviews editor-restricted
        cy.logout();
        cy.login('dbarnes', null, 'publicknowledge');
        cy.get('.app__navGroup:contains("Settings") a:contains("Journal")').click();
        cy.get('.pkpTabs__buttons button:contains("Sections")').click();
        cy.get('#sectionsGridContainer tr:contains("Articles") input').check();
        cy.get('.pkp_modal_confirmation button:contains("OK")').click();
        cy.get('.pkpNotification:contains("Your changes have been saved")');

        // Still can't submit as author
        cy.logout();
        cy.login('ccorino', null, 'publicknowledge');
        cy.get('a:contains("New Submission")').first().click();
        cy.get('h1:contains("Not Allowed")');

        // Make Reviews not editor-restricted
        cy.logout();
        cy.login('dbarnes', null, 'publicknowledge');
        cy.get('.app__navGroup:contains("Settings") a:contains("Journal")').click();
        cy.get('.pkpTabs__buttons button:contains("Sections")').click();
        cy.get('#sectionsGridContainer tr:contains("Reviews")')
            .then(($tr) => {
                cy.wrap($tr).find('a.show_extras').click();
                cy.wrap($tr)
                    .next('tr')
                    .then(($actionsTr) => {
                        cy.wrap($actionsTr).find('a:contains("Edit")').click();
                    });
            });
        cy.wait(1500); // Let modal render. Fixes console error, maybe with TinyMCE init
        cy.get('label:contains("Items can only be submitted by Editors and Section Editors.")').click();
        cy.get('.pkp_modal button:contains("Save")').click();
        cy.get('.pkp_modal').should('not.exist');

        // Can submit to only one section (no option to choose section)
        cy.logout();
        cy.login('ccorino', null, 'publicknowledge');
        cy.get('a:contains("New Submission")').first().click();
        cy.get('h1:contains("Make a Submission")');
        cy.get('legend:contains("Section")').should('not.exist');

        // Reactivate Articles section to restore test data conditions
        cy.logout();
        cy.login('dbarnes', null, 'publicknowledge');
        cy.get('.app__navGroup:contains("Settings") a:contains("Journal")').click();
        cy.get('.pkpTabs__buttons button:contains("Sections")').click();
        cy.get('#sectionsGridContainer tr:contains("Articles")')
            .then(($tr) => {
                cy.wrap($tr).find('a.show_extras').click();
                cy.wrap($tr)
                    .next('tr')
                    .then(($actionsTr) => {
                        cy.wrap($actionsTr).find('a:contains("Edit")').click();
                    });
            });
        cy.wait(1500); // Let modal render. Fixes console error, maybe with TinyMCE init
        cy.get('label:contains("Items can only be submitted by Editors and Section Editors.")').click();
        cy.get('label:contains("Deactivate this section")').click();
        cy.get('.pkp_modal button:contains("Save")').click();
        cy.get('.pkp_modal').should('not.exist');

        cy.logout();
    });

    it('When a copyright notice is configured in the context, it appears in the review step of the submission wizard and I am unable to submit without checking its checkbox.', function() {
        const api = new Api(Cypress.env('baseUrl') + '/index.php/publicknowledge/api/v1');
        const title = 'Et malesuada fames ac turpis';
        const copyrightNotice = {
            en: 'Turpis massa tincidunt dui ut ornare.',
            fr_CA: 'Vitae semper quis lectus nulla at.',
        };

        cy.login('dbarnes', null, 'publicknowledge');

        cy.getCsrfToken()
            .then(() => {
                cy.request({
                    url: api.contexts(1),
                    method: 'PUT',
                    headers: {
                        'X-Csrf-Token': this.csrfToken
                    },
                    body: {copyrightNotice},
                });
            }).then(xhr => {
                expect(xhr.status).to.eq(200);
            });

        cy.logout();

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
        cy.get('button:contains("Continue")').click();
        cy.get('button:contains("Submit")').should('be.disabled');
        cy.get('legend:contains("Copyright")')
            .parent()
            .then(($fieldset) => {
                cy.wrap($fieldset)
                    .find('blockquote')
                    .contains(copyrightNotice.en);
                cy.wrap($fieldset)
                    .find('input')
                    .check();
                cy.get('button:contains("Submit")').should('be.enabled');
            });

        cy.changeLanguage('Français');
        cy.get('button:contains("Continuer")').click();
        cy.get('button:contains("Continuer")').click();
        cy.get('button:contains("Continuer")').click();
        cy.get('button:contains("Continuer")').click();
        cy.get('button:contains("Soumettre")').should('be.disabled');
        cy.get('legend:contains("Droit d\'auteur")')
            .parent()
            .then(($fieldset) => {
                cy.wrap($fieldset)
                    .find('blockquote')
                    .contains(copyrightNotice.fr_CA);
                cy.wrap($fieldset)
                    .find('input')
                    .check();
                cy.get('button:contains("Soumettre")').should('be.enabled');
            });

        // Remove copyright notice to reset test conditions
        cy.logout();
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
                        copyrightNotice: {
                            en: '',
                            fr_CA: ''
                        }
                    },
                });
            }).then(xhr => {
                expect(xhr.status).to.eq(200);
            });
    });

    it('When I try to submit without required data, it throws a validation error. I can submit after clearing all visible validation errors.', function() {
        const submission = {
            abstract: 'In hac habitasse platea dictumst quisque.',
            title: 'Massa tincidunt dui ut ornare lectus sit amet est',
            keywords: 'Social Transformation',
            citations: 'Massa tincidunt dui ut ornare lectus sit amet est',
            metadata: {
                autosuggest: {
                    disciplines: 'Faucibus',
                    languages: 'Ornare',
                    subjects: 'Suspendisse',
                    supportingAgencies: 'Porttitor',
                },
                string: {
                    coverage: 'Lacus',
                    rights: 'Aliquet',
                    source: 'Condimentum',
                    type: 'Tincidunt',
                },
                tinyMce: {
                    dataAvailability: 'Viverra',
                },
            }
        };

        const detailFields = [
            'Title',
            'Keywords',
            'Abstract',
            'References',
        ];

        const forTheEditorFields = [
            'Supporting Agencies',
            'Coverage',
            'Data Availability Statement',
            'Disciplines',
            'Languages',
            'Rights',
            'Source',
            'Subjects',
            'Type',
        ];

        // Require all submission wizard fields
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
                        agencies: 'require',
                        citations: 'require',
                        coverage: 'require',
                        dataAvailability: 'require',
                        disciplines: 'require',
                        keywords: 'require',
                        languages: 'require',
                        rights: 'require',
                        source: 'require',
                        subjects: 'require',
                        type: 'require',
                    },
                });
            }).then(xhr => {
                expect(xhr.status).to.eq(200);
            });

        // Start submission in English and Articles section
        cy.logout();
        startSubmission();
        cy.contains('Submitting to the Articles section in English');

        // Remove title and go to review
        cy.setTinyMceContent('titleAbstract-title-control-en', '');
        cy.get('button:contains("Continue")').click();
        cy.get('button:contains("Continue")').click();
        cy.get('button:contains("Continue")').click();
        cy.get('button:contains("Continue")').click();

        // Can't submit, check errors
        cy.contains('There are one or more problems');
        cy.get('button:contains("Submit")').should('be.disabled');
        cy.contains('You must upload at least one Article Text file.');
        cy.get('h3:contains("Details (English)")')
            .then($h3 => {
                detailFields.forEach(field => {
                    cy.wrap($h3)
                        .parents('.submissionWizard__reviewPanel')
                        .find('h4:contains("' + field + '")')
                        .parent()
                        .contains('This field is required.');
                });
            });
        cy.get('h3:contains("For the Editors (English)")')
            .then($h3 => {
                forTheEditorFields.forEach(field => {
                    cy.wrap($h3)
                        .parents('.submissionWizard__reviewPanel')
                        .find('h4:contains("' + field + '")')
                        .parent()
                        .contains('This field is required.');
                });
            });

        // Add missing data
        cy.get('.pkpSteps button:contains("Details")').click();
        cy.setTinyMceContent('titleAbstract-title-control-en', submission.title);
        cy.setTinyMceContent('titleAbstract-abstract-control-en', submission.abstract);
        cy.get('#titleAbstract-keywords-control-en').type(submission.keywords, {delay: 0});
        cy.get('li:contains("' + submission.keywords + '")');
        cy.get('#titleAbstract-keywords-control-en').type('{downarrow}{enter}', {delay: 0});
        cy.get('#citations-citationsRaw-control').type(submission.citations);

        cy.get('.pkpSteps button:contains("Upload Files")').click();
        cy.uploadSubmissionFiles([
            {
                'file': 'dummy.pdf',
                'fileName': submission.title + '.pdf',
                'mimeType': 'application/pdf',
                'genre': Cypress.env('defaultGenre')
            }
        ]);

        cy.get('.pkpSteps button:contains("For the Editors")').click();
        Object.keys(submission.metadata.autosuggest).forEach(field => {
            cy.get('#forTheEditors-' + field + '-control-en').type(submission.metadata.autosuggest[field], {delay: 0});
            cy.get('li:contains("' + submission.metadata.autosuggest[field] + '")');
            cy.get('#forTheEditors-' + field + '-control-en').type('{downarrow}{enter}', {delay: 0});
        });
        Object.keys(submission.metadata.string).forEach(field => {
            cy.get('#forTheEditors-' + field + '-control-en').type(submission.metadata.string[field]);
        });
        Object.keys(submission.metadata.tinyMce).forEach(field => {
            cy.setTinyMceContent('forTheEditors-' + field + '-control-en', submission.metadata.tinyMce[field]);
        });

        // All errors should be gone and submit should be allowed.
        cy.get('.pkpSteps button:contains("Review")').click();
        cy.get('*:contains("There are one or more problems")').should('not.exist');
        cy.get('button:contains("Submit")').should('be.enabled');
        cy.get('*:contains("You must upload at least one Article Text file.")').should('not.exist');
        cy.get('h3:contains("Details (English)")')
            .then($h3 => {
                detailFields.forEach(field => {
                    cy.wrap($h3)
                        .parents('.submissionWizard__reviewPanel')
                        .find('h4:contains("' + field + '")')
                        .parent()
                        .find('*:contains("This field is required.")')
                        .should('not.exist');
                });
            });
        cy.get('h3:contains("For the Editors (English)")')
            .then($h3 => {
                forTheEditorFields.forEach(field => {
                    cy.wrap($h3)
                        .parents('.submissionWizard__reviewPanel')
                        .find('h4:contains("' + field + '")')
                        .parent()
                        .find('*:contains("This field is required.")')
                        .should('not.exist');
                });
            });

        // Submit
        cy.get('button:contains("Submit")').click();
        cy.contains('The submission, ' + submission.title + ', will be submitted to Journal of Public Knowledge for editorial review.');
        cy.get('.modal__footer button:contains("Submit")').click();
        cy.get('h1:contains("Submission complete")');

        cy.logout();
    });

    it('I can change the submission language to a different language from the language I am using the site, and the submission forms and validation checks are applied to the language of the submission', function() {

        // Enable all submission wizard fields
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

        // Start submission in English and Articles section
        cy.logout();
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

        // Should be able to submit!
        cy.get('.pkpSteps button:contains("Review")').click();
        cy.get('button:contains("Submit")').click();
        cy.contains('The submission, ' + submission.title.en + ', will be submitted to Journal of Public Knowledge for editorial review.');
        // delay is needed so previous changes gets pushed, before the submit should be triggered
        cy.wait(500);
        cy.get('.modal__footer button:contains("Submit")').click();
        cy.get('h1:contains("Submission complete")');

        cy.logout();
    });

    it('Resets the submission wizard fields to more common configuration', function() {

        // Reset all submission wizard fields
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
                        agencies: '0',
                        citations: '0',
                        coverage: '0',
                        dataAvailability: '0',
                        disciplines: '0',
                        keywords: 'request',
                        languages: '0',
                        rights: '0',
                        source: '0',
                        subjects: '0',
                        submitWithCategories: false,
                        type: '0',
                    },
                });
            }).then(xhr => {
                expect(xhr.status).to.eq(200);
            });
    });
})