<?php

/**
 * @file tools/cleanReviewerInterests.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
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
		parent::CommandLineTool($argv);

		if (!sizeof($this->argv)) {
			$this->usage();
			exit(1);
		}
		
		$command = $this->argv[0];
		if (!in_array($command, array('--show', '--remove'))) {
			print 'INVALID COMMAND !! '. PHP_EOL;
			$this->usage();
			exit(2);
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
			print 'No user interest to remove.';
			exit(0);
		}
		
		$command = $this->parameters[0];
		switch($command) {
			case '--show':
				$interests = array_map(function($entry) { return $entry->getData(CONTROLLED_VOCAB_INTEREST); }, $orphans);
				print PHP_EOL . "Below are the user interests that are not referenced by any user account." . PHP_EOL;
				print '=> ' . join($interests, "\n=> ") . PHP_EOL;
				break;
				
			case '--remove':
				$vocabEntryDao = DAORegistry::getDAO('ControlledVocabEntryDAO');
				foreach ($orphans as $orphanVocab) {
					$vocabEntryDao->deleteObject($orphanVocab);
				}
				print count($orphans) . ' rows deleted!';
				break;
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
		
		$interestVocab = $vocabDao->getBySymbolic(CONTROLLED_VOCAB_INTEREST,0,0);
		$vocabEntryIterator = $vocabEntryDao->getByControlledVocabId($interestVocab->getId());
		$vocabEntryList = $vocabEntryIterator->toArray(); 
		
		// list of vocab interests in db
		$allInterestVocabIds = array_map(
			function($entry) { return $entry->getId(); }, 
			$vocabEntryList
		);
		
		// list of vocabs associated to users
		$interests = $interestDao->getAllInterests('%');
		$userInterestVocabIds = array_map(
			function($interest) { return $interest->getId(); },
			$interests->toArray()
		);
		
		// get the difference
		$diff = array_diff($allInterestVocabIds, $userInterestVocabIds);
		
		$orphans = array_filter(
			$vocabEntryList, 
			function($entry) use($diff) { return in_array($entry->getId(), $diff); }
		);
		
		return $orphans;
	}
}

$tool = new ReviewerInterestsDeletionTool(isset($argv) ? $argv : array());
$tool->execute();
?>
