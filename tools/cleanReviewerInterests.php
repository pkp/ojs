<?php

/**
 * @file tools/cleanReviewerInterests.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewerInterestsDeletionTool
 * @ingroup tools
 *
 * @brief CLI tool to remove user interests that are not referenced by any user accounts. 
 */

require(dirname(__FILE__) . '/bootstrap.inc.php');

class ReviewerInterestsDeletionTool extends CommandLineTool {

	/**
	 * Constructor.
	 * @param $argv array command-line arguments
	 */
	public function __construct($argv = array()) {
		parent::__construct($argv);

		if (!sizeof($this->argv)) {
			$this->usage();
			exit(1);
		}
		
		$this->parameters = $this->argv;
	}

	/**
	 * Print command usage information.
	 */
	function usage() {
		echo "Permanently removes user interests that are not referenced by any user accounts.  USE WITH CARE.\n"
			. "Usage:\n"
			. "\t{$this->scriptName} --show : Display user interests not referenced\n"
			. "\t{$this->scriptName} --remove  : Permanently delete user interests not referenced\n";
	}

	/**
	 * Remove user interests that are not referenced by any user account
	 */
	function execute() {

		$orphans = $this->_getOrphanVocabInterests();
		if (!count($orphans)) {
			echo "No user interests to remove.\n";
			exit(0);
		}
		
		$command = $this->parameters[0];
		switch($command) {
			case '--show':
				$interests = array_map(function($entry) { return $entry->getData(CONTROLLED_VOCAB_INTEREST); }, $orphans);
				echo "Below are the user interests that are not referenced by any user account.\n";
				echo "\t" . join($interests, "\n\t") . "\n";
				break;
				
			case '--remove':
				$vocabEntryDao = DAORegistry::getDAO('ControlledVocabEntryDAO');
				foreach ($orphans as $orphanVocab) {
					$vocabEntryDao->deleteObject($orphanVocab);
				}
				echo count($orphans) . " entries deleted\n";
				break;

			default:
				echo "Invalid command.\n";
				$this->usage();
				exit(2);
		}
	}
	
	/**
	 * Returns user interests that are not referenced
	 * @return array array of ControlledVocabEntry object
	 */
	protected function _getOrphanVocabInterests() {
		
		$interestDao = DAORegistry::getDAO('InterestDAO');
		$vocabDao = DAORegistry::getDAO('ControlledVocabDAO');
		$vocabEntryDao = DAORegistry::getDAO('ControlledVocabEntryDAO');
		
		$interestVocab = $vocabDao->getBySymbolic(CONTROLLED_VOCAB_INTEREST);
		$vocabEntryIterator = $vocabEntryDao->getByControlledVocabId($interestVocab->getId());
		$vocabEntryList = $vocabEntryIterator->toArray(); 
		
		// list of vocab interests in db
		$allInterestVocabIds = array_map(
			function($entry) {
				return $entry->getId();
			}, 
			$vocabEntryList
		);
		
		// list of vocabs associated to users
		$interests = $interestDao->getAllInterests();
		$userInterestVocabIds = array_map(
			function($interest) {
				return $interest->getId();
			},
			$interests->toArray()
		);
		
		// get the difference
		$diff = array_diff($allInterestVocabIds, $userInterestVocabIds);
		
		$orphans = array_filter(
			$vocabEntryList, 
			function($entry) use($diff) {
				return in_array($entry->getId(), $diff);
			}
		);
		
		return $orphans;
	}
}

$tool = new ReviewerInterestsDeletionTool(isset($argv) ? $argv : array());
$tool->execute();


