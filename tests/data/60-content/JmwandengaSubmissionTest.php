<?php

/**
 * @file tests/data/60-content/JmwandengaSubmissionTest.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JmwandengaSubmissionTest
 * @ingroup tests_data
 *
 * @brief Data build suite: Create submission
 */

import('tests.data.ContentBaseTestCase');

class JmwandengaSubmissionTest extends ContentBaseTestCase {
	/**
	 * Create a submission.
	 */
	function testSubmission() {
		$this->register(array(
			'username' => 'jmwandenga',
			'firstName' => 'John',
			'lastName' => 'Mwandenga',
			'affiliation' => 'University of Cape Town',
			'country' => 'South Africa',
			'roles' => array('Author'),
		));

		$title = 'Signalling Theory Dividends: A Review Of The Literature And Empirical Evidence';
		$this->createSubmission(array(
			'title' => $title,
			'abstract' => 'The signaling theory suggests that dividends signal future prospects of a firm. However, recent empirical evidence from the US and the Uk does not offer a conclusive evidence on this issue. There are conflicting policy implications among financial economists so much that there is no practical dividend policy guidance to management, existing and potential investors in shareholding. Since corporate investment, financing and distribution decisions are a continuous function of management, the dividend decisions seem to rely on intuitive evaluation.',
		));

		$this->logOut();
		$this->findSubmissionAsEditor('dbarnes', null, $title);

		// Remove auto-assigned Stephanie Berardo, leaving David Buskins
		$this->clickAndWait('//td[contains(text(),\'Stephanie Berardo\')]/..//a[text()=\'Delete\']');

		// Go to review
		$this->clickAndWait('link=Review');

		$this->assignReviewer('jjanssen', 'Julie Janssen');
		$this->assignReviewer('amccrae', 'Aisla McCrae');
		$this->assignReviewer('agallego', 'Adela Gallego');
		$this->recordEditorialDecision('Accept Submission');

		$this->assignCopyeditor('Vogt, Sarah');
		$this->assignLayoutEditor('Hellier, Stephen');
		$this->assignProofreader('Kumar, Sabine');

		// Upload a galley file
		$this->click('id=layoutFileTypeGalley');
		$this->attachFile('name=layoutFile', "file://" . getenv('DUMMYFILE'));
		$this->clickAndWait('//input[@name=\'layoutFile\']/..//input[@value=\'Upload\']');

		$this->logOut();
	}
}
