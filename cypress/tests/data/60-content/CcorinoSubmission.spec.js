/**
 * @file cypress/tests/data/60-content/CcorinoSubmission.spec.js
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */

describe('Data suite tests', function() {
	it('Create a submission', function() {
		var familyName = 'Corino';
		cy.register({
			'username': 'ccorino',
			'givenName': 'Carlo',
			'familyName': familyName,
			'affiliation': 'University of Bologna',
			'country': 'Italy'
		});

		cy.createSubmission({
			'title': 'The influence of lactation on the quantity and quality of cashmere production',
			'section': 'Articles',
			'abstract': 'The effects of pressed beet pulp silage (PBPS) replacing barley for 10% and 20% (DM basis) were studied on heavy pigs fed dairy whey-diluted diets. 60 Hypor pigs (average initial weight of 28 kg), 30 barrows and 30 gilts, were homogeneously allocated to three exper- imental groups: T1 (control) in which pigs were fed a traditional sweet whey- diluted diet (the ratio between whey and dry matter was 4.5/1); T2 in which PBPS replaced barley for 10% (DM basis) during a first period (from the beginning to the 133rd day of trial) and thereafter for 20% (DM basis); T3 in which PBPS replaced barley for 20% (DM basis) throughout the experimental period. In diets T2 and T3 feed was dairy whey-diluted as in group T1. No significant (P>0.05) differences were observed concerning growth parameters (ADG and FCR). Pigs on diets contain- ing PBPS showed significantly higher (P<0.05) percentages of lean cuts and lower percentages of fat cuts. On the whole, ham weight losses during seasoning were moderate but significantly (P<0.05) more marked for PBPS-fed pigs as a prob- able consequence of their lower adiposity degree. Fatty acid composition of ham fat was unaffected by diets. With regard to m. Semimembranosus colour, pigs receiving PBPS showed lower (P<0.05) "L", "a" and "Chroma" values. From an economical point of view it can be concluded that the use of PBPS (partially replacing barley) and dairy whey in heavy pig production could be of particular interest in areas where both these by products are readily available.',
			'keywords': [
				'pigs',
				'food security'
			]
		});
		cy.logout();

		cy.findSubmissionAsEditor('dbarnes', null, familyName);
		cy.sendToReview();
		cy.assignParticipant('Section editor', 'Minoti Inoue', true);

		// Select pre-existing keywords
		cy.get('#publication-button').click();
		cy.get('#metadata-button').click();
		cy.get('#metadata-keywords-control-en_US').type('pr', {delay: 0, force: true});
		cy.wait(500);
		cy.get('li').contains('Professional Development').click({force: true});
		cy.wait(500);
		cy.get('#metadata-keywords-control-en_US').type('socia', {delay: 0, force: true});
		cy.wait(500);
		cy.contains('Social Transformation');
		cy.wait(500);
		cy.get('#metadata-keywords-control-en_US').type('l{downarrow}{downarrow}{enter}', {delay: 0});
		cy.get('#metadata button').contains('Save').click();
		cy.get('#metadata [role="status"]').contains('Saved');
		cy.get('#metadata-keywords-selected-en_US').contains('Professional Development');
		cy.get('#metadata-keywords-selected-en_US').contains('Social Transformation');

		cy.logout();

		cy.login('minoue');
		cy.visit('/index.php/publicknowledge/submissions');
		cy.get('a').contains('View Corino').click({force: true});
		cy.recordEditorialRecommendation('Accept Submission');

		cy.logout();
		cy.findSubmissionAsEditor('dbarnes', null, familyName);
		cy.get('div.pkp_workflow_recommendations:contains("Recommendations: Accept Submission")');
	});
})
