<?php

/**
 * @file tools/genEmailUpdates.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class genEmailUpdates
 * @ingroup tools
 *
 * @brief CLI tool to generate update scripts to upgrade any email texts that
 * were modified since the last release.
 */

require(dirname(__FILE__) . '/bootstrap.inc.php');

class genEmailUpdates extends CommandLineTool {
	/** @var $oldTag string Name of the old git tag to use */
	var $oldTag;

	/** @var $oldTag string Name of the new git tag to use */
	var $newTag;

	/**
	 * Constructor.
	 * @param $argv array command-line arguments
	 * 	If specified, the first argument should be the file to parse
	 */
	function __construct($argv = array()) {
		parent::__construct($argv);

		if (count($argv) != 3) {
			$this->usage();
			exit(1);
		}

		$this->oldTag = $argv[1];
		$this->newTag = $argv[2];
	}

	/**
	 * Print command usage information.
	 */
	function usage() {
		echo "Script to generate update SQL for potentially modified email texts\n"
			. "Usage: {$this->scriptName} old-tag new-tag\n";
	}

	/**
	 * Fetch a particular version of a file from github.
	 * @param $repository string ojs, pkp-lib, etc.
	 * @param $filename string
	 * @param $tag string
	 * @return mixed string file contents on success, false on failure.
	 */
	function fetchFileVersion($repository, $filename, $tag) {
		return(@file_get_contents('https://raw.github.com/pkp/' . urlencode($repository) . '/' . urlencode($tag) . '/' . $filename));
	}

	/**
	 * Parse into structures the XML contents of an email data file.
	 * @param $contents string
	 * @return array
	 */
	function parseEmails($contents) {
		$parser = new XMLParser();
		$result = $parser->parseTextStruct($contents, array('email_text', 'subject', 'body'));
		return $result;
	}

	/**
	 * Execute the command
	 */
	function execute() {
		$stderr = fopen('php://stdout', 'w');
		$locales = AppLocale::getAllLocales();
		$dbConn = DBConnection::getConn();
		foreach ($locales as $locale => $localeName) {
			fprintf($stderr, "Checking $localeName...\n");

			$oldTemplatesText = $this->fetchFileVersion('ojs', "locale/$locale/emailTemplates.xml", $this->oldTag);
			$newTemplatesText = $this->fetchFileVersion('ojs', "locale/$locale/emailTemplates.xml", $this->newTag);
			if ($oldTemplatesText === false || $newTemplatesText === false) {
				fprintf($stderr, "Skipping $localeName; could not fetch.\n");
				continue;
			}

			$oldEmails = $this->parseEmails($oldTemplatesText);
			$newEmails = $this->parseEmails($newTemplatesText);

			foreach ($oldEmails['email_text'] as $oi => $junk) {
				$key = $junk['attributes']['key'];
				$ni = null;

				foreach ($newEmails['email_text'] as $ni => $junk) {
					if ($key == $junk['attributes']['key']) break;
				}

				if ($oldEmails['subject'][$oi]['value'] != $newEmails['subject'][$ni]['value']) {
					echo "UPDATE email_templates_default_data SET subject='" . $dbConn->escape($newEmails['subject'][$ni]['value']) . "' WHERE key='" . $dbConn->escape($key) . "' AND locale='" . $dbConn->escape($locale) . "' AND subject='" . $dbConn->escape($oldEmails['subject'][$oi]['value']) . "';\n";
				}
				if ($oldEmails['body'][$oi]['value'] != $newEmails['body'][$ni]['value']) {
					echo "UPDATE email_templates_default_data SET body='" . $dbConn->escape($newEmails['body'][$ni]['value']) . "' WHERE key='" . $dbConn->escape($key) . "' AND locale='" . $dbConn->escape($locale) . "' AND body='" . $dbConn->escape($oldEmails['body'][$oi]['value']) . "';\n";
				}
			}
		}
		fclose($stderr);
	}
}

$tool = new genEmailUpdates(isset($argv) ? $argv : array());
$tool->execute();


