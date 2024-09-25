/**
 * @file cypress/tests/integration/ChangeSubmissionLanguage.cy.js
 *
 * Copyright (c) 2024 Simon Fraser University
 * Copyright (c) 2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 */

describe('Change Submission Language', function() {
	let user;
	let author;
	let password;
	let originalLanguage;
	let originalLocaleKey;
	let newLanguage;
	let newLocaleKey;
	let title;
	let abstract;

	before(function() {
		user = 'dbarnes';
		password = user + user;
		author = {
			familyName: 'Karbasizaed',
		}
		originalLanguage = 'English';
		originalLocaleKey = 'en';
		newLanguage = 'French (Canada)';
		newLocaleKey = 'fr_CA';
		title = {
			[newLocaleKey]: "Résistance aux antimicrobiens et aux métaux lourds et profil plasmidique des coliformes isolés d'infections nosocomiales dans un hôpital d'Ispahan, Iran",
		};
		abstract = {
			[newLocaleKey]: "Les profils de résistance aux antimicrobiens et aux métaux lourds ainsi que les profils plasmidiques des coliformes (Enterobacteriacea) isolés d'infections nosocomiales et de matières fécales humaines saines ont été comparés. Quinze des 25 isolats provenant d'infections nosocomiales ont été identifiés comme étant des Escherichia coli, et les autres comme étant des Kelebsiella pneumoniae. Soixante-douze pour cent des souches isolées d'infections nosocomiales possèdent une résistance multiple aux antibiotiques, contre 45 % des souches provenant de matières fécales humaines saines. La différence entre les valeurs de concentration minimale inhibitrice (CMI) des souches provenant de cas cliniques et de matières fécales pour quatre métaux lourds (Hg, Cu, Pb, Cd) n'était pas significative. Cependant, la plupart des souches isolées à l'hôpital étaient plus tolérantes aux métaux lourds que celles provenant de personnes en bonne santé. Il n'y avait pas de relation cohérente entre le groupe de profil plasmidique et le profil de résistance aux antimicrobiens, bien qu'un plasmide conjugatif (>56,4 kb) codant pour la résistance aux métaux lourds et aux antibiotiques ait été récupéré chez huit des souches isolées d'infections nosocomiales. Les résultats indiquent que les coliformes multirésistants sont une cause potentielle d'infection nosocomiale dans cette région.",
		};
	});

	it('Try to change submission language after publication', function() {
		cy.login(user, password, 'publicknowledge');
		cy.get('button[id="archive-button"]').click();
		cy.contains('View ' + author.familyName).click({force: true});
		cy.get(`.pkpPublication__changeSubmissionLanguage > button`).should('not.exist');
	});

	it('Change submission language', function() {
		cy.login(user, password, 'publicknowledge');
		cy.get('button[id="archive-button"]').click();
		cy.contains('View ' + author.familyName).click({force: true});
		// Unpublish
		cy.get('button[id="publication-button"]').click();
		cy.get('#publication button:contains("Unpublish")').click();
		cy.get('#headlessui-dialog-panel-5 button:contains("Unpublish")').click();
		// Change language
		cy.get(`.pkpPublication__changeSubmissionLanguage > button`).should('be.enabled').click();
		cy.get('#changeSubmissionLanguage').find(`input[value="${newLocaleKey}"]`).click();
		cy.setTinyMceContent('changeSubmissionLanguageMetadata-title-control', title[newLocaleKey]);
		cy.setTinyMceContent('changeSubmissionLanguageMetadata-abstract-control', abstract[newLocaleKey]);
		cy.get('#changeSubmissionLanguage button[label="Confirm"]').click();
		cy.get(`.pkpPublication__changeSubmissionLanguage > span`).contains(`Current Submission Language: ${newLanguage}`);
	});

	it('Change submission language back to the original', function() {
		cy.findSubmissionAsEditor(user, password, author.familyName);
		// Change language
		cy.get('button[id="publication-button"]').click();
		cy.get(`.pkpPublication__changeSubmissionLanguage > button`).click();
		cy.get('#changeSubmissionLanguage').find(`input[value="${originalLocaleKey}"]`).click();
		cy.get('#changeSubmissionLanguage button[label="Confirm"]').click();
		// Publish
		cy.get(`.pkpPublication__changeSubmissionLanguage > span`).contains(`Current Submission Language: ${originalLanguage}`);
		cy.get('button[id="publication-button"]').click();
		cy.get('div#publication button:contains("Schedule For Publication")').click();
		cy.get('div.pkpWorkflow__publishModal button:contains("Publish")').click();
	});
});
