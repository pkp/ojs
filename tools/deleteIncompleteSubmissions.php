<?php

/**
 * @file tools/deleteIncompleteSubmissions.php
 *
 *
 * @class deleteIncompleteSubmissions
 * @ingroup tools
 *
 * @brief CLI tool to delete incomplete submissions
 */

require(dirname(__FILE__) . '/bootstrap.inc.php');

class IncompleteSubmissionDeletionTool extends CommandLineTool
{
	/**
	 * Constructor.
	 *
	 * @param array $argv command-line arguments
	 */
	public function __construct($argv = [])
	{
		parent::__construct($argv);

		// If no parameters, show usage
		if (!sizeof($this->argv)) {
			$this->usage();
			exit(1);
		}

		$this->days = $argv[1];
		$this->dryrun = false;

		if (count($argv) > 2) {
			if ($argv[2] === '--dryrun') {
				$this->dryrun = $argv[2];
			} else {
				echo "Unknown argument\n";
				exit;
			}
		}
	}

	/**
	 * Print command usage information.
	 */
	public function usage()
	{
		echo "Permanently removes incomplete submissions.\n"
			. "Usage: {$this->scriptName} days [--dryrun]\n"
			. "\t\tdays: The number of days since the submission was last active, for example 365 is all submissions older than 1 year\n"
			. "\t\t--dryrun: Only list the incomplete submission id's to be removed\n";
	}

	/**
	 * Delete submission data and associated files
	 */
	public function execute()
	{
		if (!is_numeric($this->days)) {
			echo "Number of days has to be numeric\n";
			exit(1);
		}

		// Fetch all incomplete submission that are older than $this->days days
		$contextDao = Application::getContextDAO();
		$contexts = $contextDao->getAll();
		while ($context = $contexts->next()) {

			$submissions = iterator_to_array(\Services::get('submission')->getMany([
				'contextId' => $context->getId(),
				'isIncomplete' => 1,
				'daysInactive' => $this->days
			]));

			$this->deleteArticles($submissions);
		}
	}

	public function deleteArticles(array $submissions)
	{
		foreach ($submissions as $submission) {
			$submissionId = $submission->getId();

			if ($this->dryrun) {
				echo 'Found incomplete submission: ' . $submissionId . "\n";
			} else {
				echo 'Deleting submission: ' . $submissionId . "\n";
				$submissionDao = DAORegistry::getDAO('SubmissionDAO');
				$submissionDao->deleteById($submissionId);
			}
		}
	}
}

$tool = new IncompleteSubmissionDeletionTool($argv ?? []);
$tool->execute();
