/**
 * @file cypress/tests/data/60-content/ZzeddSubmission.cy.js
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */

describe('Data suite tests', function() {

	let submission, suggestions;

	before(function() {
		const title = 'Transformative Impact of AI Tools on Modern Education: Opportunities, Challenges, and Future Directions';
		submission = {
			id: 0,
			section: 'Articles',
			prefix: '',
			title: title,
			subtitle: '',
			abstract: 'The integration of artificial intelligence (AI) tools into educational systems is reshaping pedagogical practices, offering unprecedented opportunities for personalized learning, administrative efficiency, and scalable access to quality education. This study examines the multifaceted impact of AI technologies—such as adaptive learning platforms, automated grading systems, and natural language processing (NLP)-driven tutoring tools—on students, educators, and institutions. By analyzing current applications and case studies, we highlight AI’s capacity to tailor instruction to individual learner needs, reduce educators’ administrative burdens, and provide real-time feedback. Emerging evidence suggests that AI-enhanced tools like virtual tutors and chatbots can bridge gaps in resource-limited settings, fostering inclusivity and engagement.',
			shortAuthorString: 'Zayan, et al.',
			authorNames: ['Zayan Zedd', 'Nargis Parvin'],
			assignedAuthorNames: ['Zayan Zedd'],
			authors: [
				{
					givenName: 'Nargis',
					familyName: 'Parvin',
					email: 'nparvin@mailinator.com',
					country: 'Bangladesh',
					affiliation: 'Public Knowledge Project'
				}
			],
			files: [
				{
					'file': 'dummy.pdf',
					'fileName': title + '.pdf',
					'mimeType': 'application/pdf',
					'genre': Cypress.env('defaultGenre')
				},
			]
		},
        suggestions = [
            // Suggestion as non existance user
            {
                givenName: 'Jhon',
                familyName: 'Doe',
                fullname: 'Jhon Doe',
                username: 'jdoe',
                email: 'jdoe@mailinator.com',
                affiliation: 'Delft University of Technology',
                reason: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
            },
            
            // Suggestion as non existance user
            {
                givenName: 'Lisset',
                familyName: 'Von',
                fullname: 'Lisset Von',
                username: 'lvon',
                email: 'lvon@mailinator.com',
                affiliation: 'Leiden University',
                reason: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
            },

            // Suggestion as non existance user
            {
                givenName: 'Rajek',
                familyName: 'Sharif',
                fullname: 'Rajek Sharif',
                username: 'rsharif',
                email: 'rsharif@mailinator.com',
                affiliation: 'Wageningen University & Research',
                reason: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
            },

            // Suggestion as existing user with reviewer role
            {
                givenName: 'Julie',
                familyName: 'Janssen',
                fullname: 'Julie Janssen',
                username: 'jjanssen',
                email: 'jjanssen@mailinator.com',
                affiliation: 'Utrecht University',
                reason: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
            },

            // Suggestion as existing user with reviewer role
            {
                givenName: 'Paul',
                familyName: 'Hudson',
                fullname: 'Paul Hudson',
                username: 'phudson',
                email: 'phudson@mailinator.com',
                affiliation: 'McGill University',
                reason: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
            },

            // Suggestion as existing user without reviewer role
            {
                givenName: 'Sabine',
                familyName: 'Kumar',
                fullname: 'Sabine Kumar',
                username: 'skumar',
                email: 'skumar@mailinator.com',
                affiliation: 'National University of Singapore',
                reason: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
            },

            // Suggestion as existing user without reviewer role
            {
                givenName: 'Stephen',
                familyName: 'Hellier',
                fullname: 'Stephen Hellier',
                username: 'shellier',
                email: 'shellier@mailinator.com',
                affiliation: 'University of Cape Town',
                reason: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
            },

            // Suggestion as existing user without reviewer role
            {
                givenName: 'Catherine',
                familyName: 'Turner',
                fullname: 'Catherine Turner',
                username: 'cturner',
                email: 'cturner@mailinator.com',
                affiliation: 'Imperial College London',
                reason: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
            },

            // Suggestion as existing user with reviewer role
            {
                givenName: 'Aisla',
                familyName: 'McCrae',
                fullname: 'Aisla McCrae',
                username: 'amccrae',
                email: 'amccrae@mailinator.com',
                affiliation: 'University of Manitoba',
                reason: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
            },
        ];

        cy.enableReviewerSuggestion();
        cy.logout();
	});

	it('Registers as author and create submission with several suggested reviewer', function() {
		cy.register({
			'username': 'zzedd',
			'givenName': 'Zayan',
			'familyName': 'Zedd',
			'affiliation': 'Rajshahi University of Engineering and Technology',
			'country': 'Bangladesh',
		});

        cy.contains('Make a New Submission').click();

		// Begin submission
		cy.setTinyMceContent('startSubmission-title-control', submission.title);
		cy.get('label:contains("Articles")').click();
		cy.get('label:contains("English")').click();
		cy.get('input[name="submissionRequirements"]').check();
		cy.get('input[name="privacyConsent"]').check();
		cy.contains('Begin Submission').click();


		// The submission wizard has loaded
		cy.contains('Make a Submission: Details');
		cy.get('.submissionWizard__submissionDetails').contains('Zedd');
		cy.get('.submissionWizard__submissionDetails').contains(submission.title);
		cy.contains('Submitting to the Articles section in English');
		cy.get('.pkpSteps__step__label--current').contains('Details');
		cy.get('.pkpSteps__step__label').contains('Upload Files');
		cy.get('.pkpSteps__step__label').contains('Contributors');
		cy.get('.pkpSteps__step__label').contains('For the Editors');
        cy.get('.pkpSteps__step__label').contains('Reviewer Suggestions');
		cy.get('.pkpSteps__step__label').contains('Review');

		// Save the submission id for later tests
		cy.location('search')
			.then(search => {
				submission.id = parseInt(search.split('=')[1]);
			});

		// Enter details
		cy.get('h2').contains('Submission Details');
		cy.setTinyMceContent('titleAbstract-abstract-control-en', submission.abstract);
		cy.get('#titleAbstract-title-control-en').click({force: true}); // Ensure blur event is fired
		cy.get('.submissionWizard__footer button').contains('Continue').click();

		// Upload files and set file genres
		cy.contains('Make a Submission: Upload Files');
		cy.get('h2').contains('Upload Files');
		cy.get('h2').contains('Files');
		cy.uploadSubmissionFiles(submission.files);
		cy.get('.submissionWizard__footer button').contains('Continue').click();

		// Add Contributors
		cy.contains('Make a Submission: Contributors');
		cy.get('.pkpSteps__step__label--current').contains('Contributors');
		cy.get('h2').contains('Contributors');
		cy.get('.listPanel__item:contains("Zayan Zedd")');
        cy.get('button').contains('Add Contributor').click();
        cy.get('.pkpFormField:contains("Given Name")').find('input[name*="-en"]').type(submission.authors[0].givenName);
		cy.get('.pkpFormField:contains("Family Name")').find('input[name*="-en"]').type(submission.authors[0].familyName);
		cy.get('.pkpFormField:contains("Country")').find('select').select(submission.authors[0].country)
        cy.get('.pkpFormField:contains("Email")').find('input').type(submission.authors[0].email);
		cy.get('div[role=dialog]:contains("Add Contributor")').find('button').contains('Save').click();
		cy.wait(3000);
        cy.get('.submissionWizard__footer button').contains('Continue').click();

		// For the Editors
		cy.contains('Make a Submission: For the Editors');
		cy.get('.pkpSteps__step__label--current').contains('For the Editors');
		cy.get('h2').contains('For the Editors');
		cy.get('.submissionWizard__footer button').contains('Continue').click();

        // Reviewer Suggestions
        cy.contains('Make a Submission: Reviewer Suggestions');
        cy.get('.pkpSteps__step__label--current').contains('Reviewer Suggestions');
        cy.get('h2').contains('Reviewer Suggestions');
        
        cy.get('button').contains('Add Reviewer Suggestion').should('be.visible').click();
        cy.get('div[role=dialog]:contains("Add Reviewer Suggestion")').find('button').contains('Save').click();

        cy.get('#reviewerSuggestions-givenName-error-en').contains('This field is required.');
        cy.get('#reviewerSuggestions-familyName-error-en').contains('This field is required.');
        cy.get('#reviewerSuggestions-email-error').contains('This field is required.');
        cy.get('#reviewerSuggestions-affiliation-error-en').contains('This field is required.');
        cy.get('#reviewerSuggestions-affiliation-error-en').contains('This field is required.');
        cy.get('#reviewerSuggestions-suggestionReason-error-en').contains('This field is required.');
        cy.get('.pkpFormField:contains("Given Name")').find('input[name*="-en"]').type('Test');
        cy.get('.pkpFormField:contains("Family Name")').find('input[name*="-en"]').type('Suggestion');
        cy.get('.pkpFormField:contains("Email")').find('input[name="email"]').type('testsuggestion@mail.test');
        cy.get('.pkpFormField:contains("Affiliation")').find('input[name*="-en"]').type('Test Affiliation');
        cy.setTinyMceContent('reviewerSuggestions-suggestionReason-control-en', 'Test suggestion reason');
        cy.get('div[role=dialog]:contains("Add Reviewer Suggestion")')
            .find('button:contains("Save")')
            .click();
        cy.wait(2000);
        cy.get('div.reviewerSuggestionsListPanel').contains('Test Suggestion');
        cy.get('div.reviewerSuggestionsListPanel').contains('testsuggestion@mail.test');
        
        cy.get('div.reviewerSuggestionsListPanel').find('button').contains('Edit').click();
        cy.get('.pkpFormField:contains("Given Name")').find('input[name*="-en"]').click().focused().clear().type('Testing');
        cy.get('.pkpFormField:contains("Family Name")').find('input[name*="-en"]').click().focused().clear().type('Suggestion 01');
        cy.get('div[role=dialog]:contains("Edit")').find('button').contains('Save').click();
        cy.wait(2000);
        cy.get('div.reviewerSuggestionsListPanel').contains('Testing Suggestion 01');
        cy.get('div.reviewerSuggestionsListPanel').find('li.listPanel__item').should('have.length', 1);
        
        cy.get('div.reviewerSuggestionsListPanel').find('button:contains("Delete")').click();
        cy.wait(200);
        cy.get('div[role=dialog]:contains("Delete Reviewer Suggestion")').find('button:contains("Cancel")').click();
        cy.get('div.reviewerSuggestionsListPanel').find('li.listPanel__item').should('have.length', 1);
        cy.get('div.reviewerSuggestionsListPanel').find('button:contains("Delete")').click();
        cy.wait(200);
        cy.get('div[role=dialog]:contains("Delete Reviewer Suggestion")').find('button:contains("Delete Reviewer Suggestion")').click();
        cy.wait(2000);
        cy.get('div.reviewerSuggestionsListPanel').find('li.listPanel__item').should('have.length', 0);

        suggestions.forEach((suggestion) => {
            cy.get('button:contains("Add Reviewer Suggestion")').should('be.visible').click();
            cy.get('.pkpFormField:contains("Given Name")')
                .find('input[name*="-en"]')
                .type(suggestion.givenName);
            cy.get('.pkpFormField:contains("Family Name")')
                .find('input[name*="-en"]')
                .type(suggestion.familyName);
            cy.get('.pkpFormField:contains("Email")')
                .find('input[name="email"]')
                .type(suggestion.email);
            cy.get('.pkpFormField:contains("Affiliation")')
                .find('input[name*="-en"]')
                .type(suggestion.affiliation);
            cy.setTinyMceContent('reviewerSuggestions-suggestionReason-control-en', suggestion.reason);
            cy.get('div[role=dialog]:contains("Add Reviewer Suggestion")')
                .find('button:contains("Save")')
                .click();
            cy.wait(2000);
        });

        cy.get('div.reviewerSuggestionsListPanel')
            .find('li.listPanel__item')
            .should('have.length', suggestions.length);
        cy.get('.submissionWizard__footer button').contains('Continue').click();

		// Review
		cy.contains('Make a Submission: Review');
		cy.get('.pkpSteps__step__label--current').contains('Review');
		cy.get('h2').contains('Review and Submit');
		submission.files.forEach(function(file) {
			cy
				.get('h3')
				.contains('Files')
				.parents('.submissionWizard__reviewPanel')
				.contains(file.fileName)
				.parents('.submissionWizard__reviewPanel__item__value')
				.find('.pkpBadge')
				.contains(file.genre);
		});
		submission.authorNames.forEach(function(author) {
			cy.get('h3')
				.contains('Contributors')
				.parents('.submissionWizard__reviewPanel')
				.contains(author)
				.parents('.submissionWizard__reviewPanel__item__value')
				.find('.pkpBadge')
				.contains('Author');
		});

		cy.get('h3').contains('Details (English)')
			.parents('.submissionWizard__reviewPanel')
			.find('h4')
            .contains('Title')
            .siblings('.submissionWizard__reviewPanel__item__value')
            .contains(submission.title)
			.parents('.submissionWizard__reviewPanel')
			.find('h4')
            .contains('Keywords')
            .siblings('.submissionWizard__reviewPanel__item__value')
            .contains('None provided')
			.parents('.submissionWizard__reviewPanel')
			.find('h4')
            .contains('Abstract')
            .siblings('.submissionWizard__reviewPanel__item__value')
            .contains(submission.abstract);

		cy.get('h3')
            .contains('For the Editors (English)')
			.parents('.submissionWizard__reviewPanel')
			.find('h4').contains('Comments for the Editor')
            .siblings('.submissionWizard__reviewPanel__item__value')
            .contains('None');
            
		cy.get('h3').contains('For the Editors (French (Canada))')
        
        suggestions.forEach(function(suggestion) {
			cy.get('h3')
                .contains('Reviewer Suggestions')
                .parents('.submissionWizard__reviewPanel')
                .contains(suggestion.fullname);
		});

		// Save for later
		cy.get('button').contains('Save for Later').click();
		cy.contains('Saved for Later');
		cy.contains('Your submission details have been saved');
		cy.contains('We have emailed a copy of this link to you at zzedd@mailinator.com.');
		cy.get('a').contains(submission.title).click();

		// Submit
		cy.contains('Make a Submission: Review');
		cy.get('button:contains("Submit")').click();
		const message = 'The submission, ' + submission.title + ', will be submitted to ' + Cypress.env('contextTitles').en + ' for editorial review';
		cy.get('div[role=dialog]:contains("' + message + '")').find('button').contains('Submit').click();
		cy.contains('Submission complete');
		cy.get('a').contains('Create a new submission');
		cy.get('a').contains('Return to your dashboard');
		cy.get('a').contains('Review this submission').click();
		cy.get('[data-cy="active-modal"] p:contains("' + submission.title + '")');
	});

    it('Submission has reviewer suggestion section visible', function() {
		cy.findSubmissionAsEditor('dbarnes', null, 'Zedd');
        cy.get('h3').contains('Reviewers Suggested by Author');
        
        cy.assertReviewerSuggestionsCount(suggestions.length);
        
        // In submission stage the, there will be no option to interact with the suggestions
        cy.get('[data-cy="reviewer-suggestion-manager"]')
            .find('button[aria-label*=" More Actions"]')
            .should('have.length', 0);

        cy.logout();
        cy.disableReviewerSuggestion();
        cy.logout();

        cy.findSubmissionAsEditor('dbarnes', null, 'Zedd');
        cy.get('h3:contains("Reviewers Suggested by Author")').should('have.length', 0);
        cy.logout();

        cy.enableReviewerSuggestion();
    });

    it('Send submission to review stage with visible reviewer suggestion', function () {
        cy.findSubmissionAsEditor('dbarnes', null, 'Zedd');
		cy.clickDecision('Send for Review');
		cy.recordDecisionSendToReview('Send for Review', submission.assignedAuthorNames, [submission.title]);
        cy.isActiveStageTab('Review');
        cy.get('h3').contains('Reviewers Suggested by Author');
        
        cy.assertReviewerSuggestionsCount(suggestions.length);

        cy.get('[data-cy="reviewer-suggestion-manager"]')
            .find('button[aria-label*=" More Actions"]')
            .should('have.length', suggestions.length);
    });

    it('Add non exist suggested reviewer from reviewer suggestion manager panel', function () {
        cy.findSubmissionAsEditor('dbarnes', null, 'Zedd');
        cy.wait(1000);

        cy.get('[data-cy="reviewer-suggestion-manager"]')
            .find('button[aria-label="'+suggestions[0].fullname+' More Actions"]')
            .click({ force: true });
        cy.get('[data-cy="reviewer-suggestion-manager"]')
            .find('button:contains("Add Reviewer")')
            .click();
        cy.wait(2000);

        cy.get('div[role=dialog]:contains("Add Reviewer")')
            .find('input[name="username"]')
            .type(suggestions[0].username);
        
        cy.get('div[role=dialog]:contains("Add Reviewer")')
            .find('button:contains("Add Reviewer")')
            .last()
            .click();
        cy.wait(2000);

        cy.get('[data-cy="reviewer-manager"]').contains(suggestions[0].fullname);
        cy.get('[data-cy="reviewer-suggestion-manager"]')
            .find('div:contains("'+suggestions[0].fullname+'")')
            .should('have.length', 0);
    });

    it('Add non exist suggested reviewer from Add Reviewer list', function () {
        cy.findSubmissionAsEditor('dbarnes', null, 'Zedd');

		cy.get('[data-cy="reviewer-manager"]')
            .find('button:contains("Add Reviewer")')
            .click();
        cy.wait(2000);

        cy.get('div[role=dialog]:contains("Add Reviewer")')
            .find('div.listPanel__itemTitle:contains("'+suggestions[1].fullname+'")')
            .parents('li.listPanel__item')
            .find('button:contains("Select Reviewer")')
            .click();
        cy.wait(2000);

        cy.get('div[role=dialog]:contains("Add Reviewer")')
            .last()
            .find('input[name="username"]')
            .type(suggestions[1].username);
        cy.get('div[role=dialog]:contains("Add Reviewer")')
            .last()
            .find('button:contains("Add Reviewer")')
            .last()
            .click();
        cy.wait(2000);

        cy.get('div[role=dialog]:contains("Add Reviewer")')
            .last()
            .find('button:contains("Close")')
            .click();
        cy.wait(1000);

        cy.get('[data-cy="reviewer-manager"]').contains(suggestions[1].fullname);
        cy.get('[data-cy="reviewer-suggestion-manager"]')
            .find('div:contains("'+suggestions[1].fullname+'")')
            .should('have.length', 0);
    });

    it('Add reviewer from suggestion with existed reviewer role from reviewer suggestion manager panel', function () {
        cy.findSubmissionAsEditor('dbarnes', null, 'Zedd');

        cy.get('[data-cy="reviewer-suggestion-manager"]')
            .find('button[aria-label="'+suggestions[3].fullname+' More Actions"]')
            .click({ force: true });
        cy.get('[data-cy="reviewer-suggestion-manager"]')
            .find('button:contains("Add Reviewer")')
            .click();
        cy.wait(2000);

        cy.get('div[role=dialog]:contains("Add Reviewer")')
            .find('button:contains("Add Reviewer")')
            .last()
            .click();
        cy.waitJQuery();
        cy.wait(2000);

        cy.get('[data-cy="reviewer-manager"]').contains(suggestions[3].fullname);
        cy.get('[data-cy="reviewer-suggestion-manager"]')
            .find('div:contains("'+suggestions[3].fullname+'")')
            .should('have.length', 0);
    });

    it('Add reviewer from suggestion with existed reviewer role from Add Reviewer list', function () {
        cy.findSubmissionAsEditor('dbarnes', null, 'Zedd');

		cy.get('[data-cy="reviewer-manager"]')
            .find('button:contains("Add Reviewer")')
            .click();
        cy.wait(2000);

        cy.get('div[role=dialog]:contains("Add Reviewer")')
            .find('div.reviewer-sugestions-list')
            .find('div.listPanel__itemTitle:contains("'+suggestions[4].fullname+'")')
            .parents('li.listPanel__item')
            .find('button:contains("Select Reviewer")')
            .click();
        cy.wait(2000);

        cy.get('div[role=dialog]:contains("Add Reviewer")')
            .last()
            .find('button:contains("Add Reviewer")')
            .click();
        cy.wait(2000);

        cy.get('[data-cy="reviewer-manager"]').contains(suggestions[4].fullname);
        cy.get('[data-cy="reviewer-suggestion-manager"]')
            .find('div:contains("'+suggestions[4].fullname+'")')
            .should('have.length', 0);
    });

    it('Add reviewer from suggestion with existed user but no reviewer role from reviewer suggestion manager panel', function () {
        cy.findSubmissionAsEditor('dbarnes', null, 'Zedd');

		cy.get('[data-cy="reviewer-suggestion-manager"]')
            .find('button[aria-label*="'+suggestions[5].fullname+' More Actions"]')
            .click({ force: true });
        cy.get('[data-cy="reviewer-suggestion-manager"]')
            .find('button:contains("Add Reviewer")')
            .click();
        cy.wait(2000);

        cy.get('div[role=dialog]:contains("Add Reviewer")')
            .find('button:contains("Add Reviewer")')
            .last()
            .click();
        cy.wait(2000);

        cy.get('[data-cy="reviewer-manager"]').contains(suggestions[5].fullname);
        cy.get('[data-cy="reviewer-suggestion-manager"]')
            .find('div:contains("'+suggestions[5].fullname+'")')
            .should('have.length', 0);
    });

    it('Add reviewer from suggestion with existed user but no reviewer role from Add Reviewer list', function () {
        cy.findSubmissionAsEditor('dbarnes', null, 'Zedd');
		
        cy.get('[data-cy="reviewer-manager"]')
            .find('button:contains("Add Reviewer")')
            .click();
        cy.wait(2000);

        cy.get('div[role=dialog]:contains("Add Reviewer")')
            .find('div.listPanel__itemTitle:contains("'+suggestions[6].fullname+'")')
            .parents('li.listPanel__item')
            .find('button:contains("Select Reviewer")')
            .click();
        cy.wait(2000);

        cy.get('div[role=dialog]:contains("Add Reviewer")')
            .last()
            .find('button:contains("Add Reviewer")')
            .last()
            .click();
        cy.wait(2000);

        cy.get('div[role=dialog]:contains("Add Reviewer")')
            .last()
            .find('button:contains("Close")')
            .click();
        cy.wait(1000);

        cy.get('[data-cy="reviewer-manager"]').contains(suggestions[6].fullname);
        cy.get('[data-cy="reviewer-suggestion-manager"]')
            .find('div:contains("'+suggestions[6].fullname+'")')
            .should('have.length', 0);
    });

    it('Create new user with reviewer role which match reviewer suggestion from Add Reviewer list', function () {
        cy.findSubmissionAsEditor('dbarnes', null, 'Zedd');
		
        cy.get('[data-cy="reviewer-manager"]')
            .find('button:contains("Add Reviewer")')
            .click();
        cy.wait(2000);

        cy.get('div[role=dialog]:contains("Add Reviewer")')
            .find('a:contains("Create New Reviewer")')
            .click();
        
        cy.get('div[role=dialog]:contains("Add Reviewer")')
            .find('input[name="givenName[en]"]')
            .type(suggestions[2].givenName);
        
        cy.get('div[role=dialog]:contains("Add Reviewer")')
            .find('input[name="familyName[en]"]')
            .type(suggestions[2].familyName);
        
        cy.get('div[role=dialog]:contains("Add Reviewer")')
            .find('input[name="username"]')
            .type(suggestions[2].username);
        
        cy.get('div[role=dialog]:contains("Add Reviewer")')
            .find('input[name="email"]')
            .type(suggestions[2].email);
        
        cy.get('div[role=dialog]:contains("Add Reviewer")')
            .find('input[name="affiliation[en]"]')
            .type(suggestions[2].affiliation);
        
        cy.get('div[role=dialog]:contains("Add Reviewer")')
            .find('button:contains("Add Reviewer")')
            .last()
            .click();
        cy.wait(2000);

        cy.get('[data-cy="reviewer-manager"]').contains(suggestions[2].fullname);
        cy.get('[data-cy="reviewer-suggestion-manager"]')
            .find('div:contains("'+suggestions[2].fullname+'")')
            .should('have.length', 0);

        cy.logout();
    });

    it('Enroll an existing user who match with a suggested revirwer', function () {
        cy.findSubmissionAsEditor('dbarnes', null, 'Zedd');
		
        cy.get('[data-cy="reviewer-manager"]')
            .find('button:contains("Add Reviewer")')
            .click();
        cy.wait(2000);

        cy.get('div[role=dialog]:contains("Add Reviewer")')
            .find('a:contains("Enroll Existing User")')
            .click();
        
        cy.get('div[role=dialog]:contains("Add Reviewer")')
            .find('input[name="userId_input"]')
            .type(suggestions[7].fullname);
        
        cy.get('div[role=dialog]:contains("Add Reviewer")')
            .find('li.ui-menu-item:contains("'+suggestions[7].fullname+'")')
            .last()
            .click();

        cy.get('div[role=dialog]:contains("Add Reviewer")')
            .find('input[name="userId_input"]')
            .should('have.value', suggestions[7].fullname + ' ('+suggestions[7].email+')');

        cy.get('div[role=dialog]:contains("Add Reviewer")')
            .find('button:contains("Add Reviewer")')
            .last()
            .click();
        cy.wait(2000);

        cy.get('[data-cy="reviewer-manager"]').contains(suggestions[7].fullname);
        cy.get('[data-cy="reviewer-suggestion-manager"]')
            .find('div:contains("'+suggestions[7].fullname+'")')
            .should('have.length', 0);    

        cy.logout();
    });

    it('Add Reviewer from pre existing list that match a suggested reviewer', function () {
        cy.findSubmissionAsEditor('dbarnes', null, 'Zedd');
		
        cy.get('[data-cy="reviewer-manager"]')
            .find('button:contains("Add Reviewer")')
            .click();
        cy.wait(2000);

        cy.get('div[role=dialog]:contains("Add Reviewer")')
            .find('div.listPanel--selectReviewer')
            .last()
            .find('div.listPanel__itemTitle:contains("'+suggestions[8].fullname+'")')
            .parents('li.listPanel__item')
            .find('button:contains("Select Reviewer")')
            .click();
        cy.wait(2000);

        cy.get('div[role=dialog]:contains("Add Reviewer")')
            .last()
            .find('button:contains("Add Reviewer")')
            .click();
        cy.wait(2000);

        cy.get('[data-cy="reviewer-manager"]').contains(suggestions[8].fullname);
        cy.get('[data-cy="reviewer-suggestion-manager"]').should('not.exist');
        cy.logout();
    });

    after(function() {
        cy.disableReviewerSuggestion();
    });
});
