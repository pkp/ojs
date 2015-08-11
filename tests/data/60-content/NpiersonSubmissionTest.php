<?php

/**
 * @file tests/data/60-content/NpiersonSubmissionTest.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2000-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NpiersonSubmissionTest
 * @ingroup tests_data
 *
 * @brief Data build suite: Create submission
 */

import('tests.ContentBaseTestCase');

class NpiersonSubmissionTest extends ContentBaseTestCase {
	/**
	 * Create a submission.
	 */
	function testSubmission() {
		$this->register(array(
			'username' => 'npierson',
			'firstName' => 'Narciso',
			'lastName' => 'Pierson',
			'affiliation' => 'Keele University',
			'country' => 'United Kingdom',
			'roles' => array('Author'),
		));

		$title = 'Cyberspace Versus Citizenship: IT and emerging non space communities';
		$this->createSubmission(array(
			'title' => $title,
			'abstract' => 'In 1964 Melvin Webber challenged the notions of community and centrality used in urban studies by demonstrating that "community without propinquity" was emerging within certain social networks. He argued that individuals were enmeshed in an overlapping range of groups, and that increasingly these social networks were not limited by physical or geographical location. His definition of community acknowledges a differentiated range of "non-place" cultures. It reflects a change to a process, rather than product oriented view of urban form, triggered in part by the influence of general systems theory. Webber influenced and was influenced by the emergence of an orientation towards non-physical aspects of community, and a participatory approach to design which emerged strongly during the seventies. Reexamination of Webber\'s work in the light of current information technology offers some insight into the nature of the globalisation of the world economy, and consequent impacts on nationality and sovereignty. The technologies that will be commonplace by the end of the century can both empower and disempower and it will be necessary to reconsider our current notions of both citizenship and of access to and control of such crucial resources. Opportunities offered by IT for marginalised or peripheral groups, whether at the level of nation, region or local community, will challenge existing definitions of centre and periphery. The moral panics surrounding such activities as "hacking" and its supporting "cyberpunk" sub culture demonstrate a growing awareness of the importance of emerging non-space communities.',
		));
		$this->logOut();

		$this->findSubmissionAsEditor('dbarnes', null, $title);
		$this->waitForElementPresent($selector = 'css=[id^=expedite-button-]');
		$this->click($selector);
		$this->waitForElementPresent($selector = 'id=issueId');
		$this->select($selector, 'Vol 1 No 1 (2014)');
		$this->waitForElementPresent($selector = '//button[text()=\'Save\']');
		$this->click($selector);
		$this->waitJQuery();
		$this->logOut();
	}
}
