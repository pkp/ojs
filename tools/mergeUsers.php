<?php

/**
 * @file tools/mergeUsers.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class mergeUsers
 * @ingroup tools
 *
 * @brief CLI tool for merging two OJS user accounts.
 */

require(dirname(__FILE__) . '/bootstrap.inc.php');

class mergeUsers extends CommandLineTool {

	/** @var $username1 string */
	var $username1;

	/** @var $username2 string */
	var $username2;

	/**
	 * Constructor.
	 * @param $argv array command-line arguments
	 */
	function __construct($argv = array()) {
		parent::__construct($argv);

		if (!isset($this->argv[0]) || !isset($this->argv[1]) ) {
			$this->usage();
			exit(1);
		}

		$this->username1 = $this->argv[0];
		$this->username2 = $this->argv[1];
	}

	/**
	 * Print command usage information.
	 */
	function usage() {
		echo "OJS merge users tool\n"
			. "Use this tool to merge two OJS user accounts.\n\n"
			. "Usage: {$this->scriptName} [username1] [username2]\n"
			. "username1      The first user to merge.\n"
			. "username2      The second user to merge. All roles and content associated\n"
			. "               with this user account will be transferred to the user account\n"
			. "               that corresponds to username1. The user account that corresponds\n"
			. "               to username2 will be deleted.\n";
	}

	/**
	 * Execute the merge users command.
	 */
	function execute() {
		$userDao = DAORegistry::getDAO('UserDAO');

		$oldUser = $userDao->getByUsername($this->username2);
		$newUser = $userDao->getByUsername($this->username1);

		$oldUserId = isset($oldUser) ? $oldUser->getId() : null;
		$newUserId = isset($newUser) ? $newUser->getId() : null;

		if (empty($oldUserId)) {
			printf("Error: '%s' is not a valid username.\n",
				$this->username2);
			exit;
		}

		if (empty($newUserId)) {
			printf("Error: '%s' is not a valid username.\n",
				$this->username1);
			exit;
		}

		// Both user IDs are valid. Merge the accounts.
		import('classes.user.UserAction');
		UserAction::mergeUsers($oldUserId, $newUserId);

		printf("Merge completed: '%s' merged into '%s'.\n",
			$this->username2,
			$this->username1
		);
	}
}

$tool = new mergeUsers(isset($argv) ? $argv : array());
$tool->execute();
?>
