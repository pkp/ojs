/**
 * @file cypress/tests/integration/Z_ArticleViewDCMetadata.cy.js
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */

describe('Article View Metadata - DC Plugin', function() {
	let submission;
	let uniqueId;
	let today;
	let dcElements;

	before(function() {
		today = new Date();
		const uniqueSeed = Date.now().toString();
		uniqueId = Cypress._.uniqueId(uniqueSeed);

		submission = {
			sectionId: 1,
			section: 'Articles',
			prefix: 'Test prefix',
			title: 'Test title',
			subtitle: 'Test subtitle',
			abstract: 'Test abstract',
			authors: [
				'Name 1 Author 1',
				'Name 2 Author 2'
			],
			submitterRole: 'Journal manager',
			additionalAuthors: [
				{
					givenName: {en: 'Name 1'},
					familyName: {en: 'Author 1'},
					country: 'US',
					affiliation: {en: 'Stanford University'},
					email: 'nameauthor1Test@mailinator.com',
					userGroupId: Cypress.env('authorUserGroupId')
				},
				{
					givenName: {en: 'Name 2'},
					familyName: {en: 'Author 2'},
					country: 'US',
					affiliation: {en: 'Stanford University'},
					email: 'nameauthor2Test@mailinator.com',
					userGroupId: Cypress.env('authorUserGroupId')
				}
			],
			files: [
				{
					'file': 'dummy.pdf',
					'fileName': 'Test prefixTest titleTest subtitle.pdf',
					'mimeType': 'application/pdf',
					'genre': Cypress.env('defaultGenre')
				}
			],
			localeTitles: {
				fr_CA: {
					title: "Test title FR",
					subtitle: "Test subtitle FR",
					abstract: "Test abstract FR",
					prefix: "Test prefix FR",
				}
			},
			localeMetadata: [
				{
					locale: 'fr_CA',
					manyValues: [
						{
							metadata: 'keywords',
							values: [
								'Test keyword 1 FR',
								'Test keyword 2 FR'
							],
						},
					],
					oneValue: [
						{
							metadata: 'coverage',
							value: 'Test coverage FR'
						},
						{
							metadata: 'type',
							value: 'Test type FR'
						},
					],
				},
				{
					locale: 'en',
					manyValues: [
						{
							metadata: 'keywords',
							values: [
								'Test keyword 1',
								'Test keyword 2'
							],
						},
					],
					oneValue: [
						{
							metadata: 'coverage',
							value: 'Test coverage'
						},
						{
							metadata: 'type',
							value: 'Test type'
						},
					],
				}
			],
			source: {
				name: 'Journal of Public Knowledge',
				issn: '0378-5955',
				issue: '2',
				volume: '1',
				uri: '/index.php/publicknowledge',
				issueTitle: 'Vol. 1 No. 2 (2014)',
				doiPrefix: '10.1234',
			},
			identifiers: {
				pageNumber: '71-98',
			},
			urlPath: 'testing-dc-metadata-submission-' + uniqueId,
			licenceUrl: 'https://creativecommons.org/licenses/by/4.0/',
			publishIssueSections: [
				'Articles'
			],
			galleys: [
				{
					label: 'PDF',
					genre: 'Article Text',
					mimeType: 'application/pdf',
				}
			],
		};

		dcElements = {
			localized: [
				{
					element: 'DC.Coverage',
					values: [
						{
							locale: 'en',
							contents: [
								submission.localeMetadata
									.find(element => element.locale == 'en')
									.oneValue
									.find(element => element.metadata == 'coverage')
									.value
							]
						},
						{
							locale: 'fr',
							contents: [
								submission.localeMetadata
									.find(element => element.locale == 'fr_CA')
									.oneValue
									.find(element => element.metadata == 'coverage')
									.value
							]
						},
					]
				},
				{
					element: 'DC.Description',
					values: [
						{
							locale: 'en',
							contents: [
								submission.abstract
							]
						},
						{
							locale: 'fr',
							contents: [
								submission.localeTitles.fr_CA.abstract
							]
						},
					]
				},
				{
					element: 'DC.Title.Alternative',
					values: [
						{
							locale: 'fr',
							contents: [
								submission.localeTitles.fr_CA.prefix + ' ' + submission.localeTitles.fr_CA.title + ': ' + submission.localeTitles.fr_CA.subtitle
							]
						},

					]
				},
				{
					element: 'DC.Type',
					values: [
						{
							locale: 'en',
							contents: [
								submission.localeMetadata
									.find(element => element.locale == 'en')
									.oneValue
									.find(element => element.metadata == 'type')
									.value
							]

						},
						{
							locale: 'fr',
							contents: [
								submission.localeMetadata
									.find(element => element.locale == 'fr_CA')
									.oneValue
									.find(element => element.metadata == 'type')
									.value
							],
						},
					]
				},
				{
					element: 'DC.Subject',
					values: [
						{
							locale: 'en',
							contents: submission.localeMetadata
								.find(element => element.locale == 'en')
								.manyValues
								.find(element => element.metadata == 'keywords')
								.values
						},
						{
							locale: 'fr',
							contents: submission.localeMetadata
								.find(element => element.locale == 'fr_CA')
								.manyValues
								.find(element => element.metadata == 'keywords')
								.values
						},
					]
				},
			],
			nonLocalized: [
				{
					element: 'DC.Creator.PersonalName',
					values: submission.authors
				},
				{
					element: 'DC.Identifier',
					values: [
						submission.urlPath
					]
				},
				{
					element: 'DC.Identifier.pageNumber',
					values: [
						submission.identifiers.pageNumber
					]
				},
				{
					element: 'DC.Identifier.URI',
					values: [
						submission.source.uri + '/article/view/' + submission.urlPath
					]
				},
				{
					element: 'DC.Rights',
					values: [
						'Copyright (c) ' + today.toJSON().slice(0,4) + ' ' + submission.source.name,
						submission.licenceUrl
					]
				},
				{
					element: 'DC.Source',
					values: [
						submission.source.name
					]
				},
				{
					element: 'DC.Source.ISSN',
					values: [
						submission.source.issn
					]
				},
				{
					element: 'DC.Source.Issue',
					values: [
						submission.source.issue
					]
				},
				{
					element: 'DC.Source.Volume',
					values: [
						submission.source.volume
					]
				},
				{
					element: 'DC.Source.URI',
					values: [
						submission.source.uri
					]
				},
				{
					element: 'DC.Title',
					values: [
						submission.prefix + ' ' + submission.title + ': ' + submission.subtitle
					]
				},
				{
					element: 'DC.Type',
					values: [
						'Text.Serial.Journal'
					]
				},
				{
					element: 'DC.Type.articleType',
					values: [
						submission.section
					]
				},
			],
			withScheme: [
				{
					element: 'DC.Format',
					scheme: 'IMT',
					content: 'application/pdf'
				},
				{
					element: 'DC.Language',
					scheme: 'ISO639-1',
					content: 'en'
				},
				{
					element: 'DC.Date.created',
					scheme: 'ISO8601',
					content: today.toJSON().slice(0, 10)
				},
				{
					element: 'DC.Date.dateSubmitted',
					scheme: 'ISO8601',
					content: today.toJSON().slice(0, 10)
				},
				{
					element: 'DC.Date.issued',
					scheme: 'ISO8601',
					content: today.toJSON().slice(0, 10)
				},
				{
					element: 'DC.Date.modified',
					scheme: 'ISO8601',
					content: today.toJSON().slice(0, 10)
				},
			],
		};

		// Login as admin
		cy.login('admin', 'admin');
		cy.get('a').contains('admin').click();
		cy.get('a').contains('Dashboard').click();

		// Enable metadata settings
		cy.get('.app__nav a').contains('Workflow').click();
		cy.get('button').contains('Metadata').click();
		cy.get('span').contains('Enable coverage metadata').prev('input[type="checkbox"]').check();
		cy.get('span').contains('Enable type metadata').prev('input[type="checkbox"]').check();
		cy.get('span').contains('Enable keyword metadata').prev('input[type="checkbox"]').check();
		cy.get('#metadata button').contains('Save').click();
		cy.get('#metadata [role="status"]').contains('Saved');
		cy.wait(500);

		// Enable dois
		cy.checkDoiConfig(['publication', 'issue', 'representation']);

		// After configuration, go to submissions
		cy.get('.app__nav a').contains('Submissions').click();

		// Create a new submission
		cy.getCsrfToken();
		cy.window()
			.then(() => {
				return cy.createSubmissionWithApi(submission, this.csrfToken);
			})
			.then(xhr => {
				return cy.submitSubmissionWithApi(submission.id, this.csrfToken);
			})
			.then(xhr => {
				cy.visit('/index.php/publicknowledge/workflow/index/' + submission.id + '/1');
			});


		// Go to publication tabs
		cy.get('#publication-button').click();

		// Open multilanguage inputs and add data to fr_CA inputs
		cy.get('div#titleAbstract button').contains('French').click();

		cy.get('#titleAbstract input[name=prefix-en]').type(submission.prefix, {delay: 0});
		cy.setTinyMceContent('titleAbstract-subtitle-control-en', submission.subtitle);

		cy.setTinyMceContent('titleAbstract-title-control-fr_CA', submission.localeTitles.fr_CA.title);
		cy.get('#titleAbstract input[name=prefix-fr_CA]').type(submission.localeTitles.fr_CA.prefix, {delay: 0});
		cy.setTinyMceContent('titleAbstract-subtitle-control-fr_CA', submission.localeTitles.fr_CA.subtitle);
		cy.setTinyMceContent('titleAbstract-abstract-control-fr_CA', submission.localeTitles.fr_CA.abstract);
		cy.get('#titleAbstract-title-control-fr_CA').click({force:true}); // Ensure blur event is fired
		cy.get('#titleAbstract-subtitle-control-fr_CA').click({force:true});
		cy.get('#titleAbstract button').contains('Save').click();
		cy.get('#titleAbstract [role="status"]').contains('Saved');

		// Go to metadata
		cy.get('#metadata-button').click();
		cy.get('div#metadata button').contains('French').click();

		// Add the metadata to the submission
		submission.localeMetadata.forEach((locale) => {
			var localeName = locale.locale;

			locale.manyValues.forEach((manyValueMetadata) => {
				manyValueMetadata.values.forEach((value) => {
					cy.get('#metadata-' + manyValueMetadata.metadata + '-control-' + localeName).type(value, {delay: 0});
					cy.wait(2000);
					cy.get('#metadata-' + manyValueMetadata.metadata + '-control-' + localeName).type('{enter}', {delay: 0});
					cy.wait(500);
					cy.get('#metadata-' + manyValueMetadata.metadata + '-selected-' + localeName).contains(value);
					cy.wait(1000);
				});
			});

			locale.oneValue.forEach((oneValueMetadata) => {
				cy.get('#metadata-' + oneValueMetadata.metadata + '-control-' + localeName).type(oneValueMetadata.value, {delay: 0});
			});
		});

		cy.get('#metadata button').contains('Save').click();
		cy.get('#metadata [role="status"]').contains('Saved');

		// Permissions & Disclosure
		cy.get('#license-button').click();
		cy.get('#license [name="licenseUrl"]').type(submission.licenceUrl, {delay: 0});
		cy.get('#license button').contains('Save').click();
		cy.get('#license [role="status"]').contains('Saved');

		// Create a galley
		submission.galleys.forEach((galley) => {
			cy.get('button#galleys-button').click();
			cy.wait(1500); // Wait for the form to settle
			cy.get('div#representations-grid a').contains('Add galley').click();
			cy.wait(1500); // Wait for the form to settle
			cy.get('input[id^=label-]').type(galley.label, {delay: 0});
			cy.get('form#articleGalleyForm button:contains("Save")').click();
			cy.get('select[id=genreId]').select(galley.genre);
			cy.wait(250);
			cy.fixture('dummy.pdf', 'base64').then(fileContent => {
				cy.get('div[id^="fileUploadWizard"] input[type=file]').attachFile(
					{fileContent, 'filePath': 'article.pdf', 'mimeType': galley.mimeType, 'encoding': 'base64'}
				);
			});
			cy.get('button').contains('Continue').click();
			cy.get('button').contains('Continue').click();
			cy.get('button').contains('Complete').click();
		});


		// Issue
		cy.get('#issue-button').click();
		submission.publishIssueSections.forEach((sectionTitle) => {
			cy.get('#issue [name="sectionId"]').select(sectionTitle);
		});
		cy.get('#issue [name="pages"]').type(submission.identifiers.pageNumber, {delay: 0});
		cy.get('#issue [name="urlPath"]').type(submission.urlPath);
		cy.get('#issue button').contains('Save').click();
		cy.get('#issue [role="status"]').contains('Saved');

		// Go to workflow to send the submission to Copyediting stage
		cy.get('#workflow-button').click();
		cy.clickDecision('Accept and Skip Review');
		cy.recordDecision('and has been sent to the copyediting stage');
		cy.isActiveStageTab('Copyediting');

		// Publish the submission
		cy.publish(submission.source.volume, submission.source.issueTitle);
	});

	it('Tests if Header DC Metadata are present and consistent', function() {
		cy.visit('/index.php/publicknowledge/article/view/' + submission.urlPath);

		cy.get('meta[name^="DC."]').each((item, index, list) => {
			cy.wrap(item)
				.should("have.attr", "content")
				.and("not.be.empty");
		});

		dcElements.localized.forEach((item) => {
			item.values.forEach((value) => {
				value.contents.forEach((content) => {
					cy.get('meta[name="' + item.element + '"][content="' + content + '"][xml\\:lang="' + value.locale + '"]')
						.should('exist');
				});
			});
		});

		dcElements.nonLocalized.forEach((item) => {
			item.values.forEach((value) => {
				cy.get('meta[name="' + item.element + '"][content*="' + value + '"]')
					.should('exist');
			});
		});

		dcElements.withScheme.forEach((item) => {
			cy.get('meta[name="' + item.element + '"][content="' + item.content + '"][scheme="' + item.scheme + '"]')
					.should('exist');
		});
	});
});
