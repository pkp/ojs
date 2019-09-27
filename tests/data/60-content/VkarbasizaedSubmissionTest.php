<?php

/**
 * @file tests/data/60-content/VkarbasizaedSubmissionTest.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class VkarbasizaedSubmissionTest
 * @ingroup tests_data
 *
 * @brief Data build suite: Create submission
 */

import('tests.ContentBaseTestCase');

class VkarbasizaedSubmissionTest extends ContentBaseTestCase {
	/**
	 * Create a submission.
	 */
	function testSubmission() {
		$this->register(array(
			'username' => 'vkarbasizaed',
			'givenName' => 'Vajiheh',
			'familyName' => 'Karbasizaed',
			'affiliation' => 'University of Tehran',
			'country' => 'Iran, Islamic Republic of',
		));

		$title = 'Antimicrobial, heavy metal resistance and plasmid profile of coliforms isolated from nosocomial infections in a hospital in Isfahan, Iran';
		$this->createSubmission(array(
			'title' => $title,
			'abstract' => 'The antimicrobial, heavy metal resistance patterns and plasmid profiles of Coliforms (Enterobacteriacea) isolated from nosocomial infections and healthy human faeces were compared. Fifteen of the 25 isolates from nosocomial infections were identified as Escherichia coli, and remaining as Kelebsiella pneumoniae. Seventy two percent of the strains isolated from nosocomial infections possess multiple resistance to antibiotics compared to 45% of strains from healthy human faeces. The difference between minimal inhibitory concentration (MIC) values of strains from clinical cases and from faeces for four heavy metals (Hg, Cu, Pb, Cd) was not significant. However most strains isolated from hospital were more tolerant to heavy metal than those from healthy persons. There was no consistent relationship between plasmid profile group and antimicrobial resistance pattern, although a conjugative plasmid (>56.4 kb) encoding resistance to heavy metals and antibiotics was recovered from eight of the strains isolated from nosocomial infections. The results indicate multidrug-resistance coliforms as a potential cause of nosocomial infection in this region.',
		));

		$this->logOut();
		$this->findSubmissionAsEditor('dbarnes', null, $title);
		$this->sendToReview();
		$this->waitForElementPresent('//a[contains(text(), \'Review\')]/*[contains(text(), \'Initiated\')]');
		$this->assignReviewer('Julie Janssen');
		$this->assignReviewer('Paul Hudson');
		$this->recordEditorialDecision('Accept Submission');
		$this->waitForElementPresent('//a[contains(text(), \'Copyediting\')]/*[contains(text(), \'Initiated\')]');
		$this->assignParticipant('Copyeditor', 'Maria Fritz');
		$this->recordEditorialDecision('Send To Production');
		$this->waitForElementPresent('//a[contains(text(), \'Production\')]/*[contains(text(), \'Initiated\')]');
		$this->assignParticipant('Layout Editor', 'Graham Cox');
		$this->assignParticipant('Proofreader', 'Catherine Turner');

		// Create a galley
		$this->click('//button[@id="publication-button"]');
		$this->click('//button[@id="galleys-button"]');
		$this->waitForElementPresent($selector='css=[id^=component-grid-articlegalleys-articlegalleygrid-addGalley-button-]');
		$this->click($selector);
		$this->waitForElementPresent('css=[id^=label-]');
		$this->type('css=[id^=label-]', 'PDF');
		$this->click('//button[text()=\'Save\']');
		$this->uploadWizardFile('PDF');

		// Publish in current issue
		$this->publish('1');
		$this->isInIssue($title, 'Vol. 1 No. 2 (2014)');

		$this->logOut();
	}
}
