<?php

/**
 * @file localeCheck.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package tools
 * @class localeCheck
 *
 * CLI tool to check the various locales for consistency.
 *
 * $Id$
 */

require(dirname(__FILE__) . '/includes/cliTool.inc.php');

class localeCheck extends CommandLineTool {
	/** @var $locales List of locales to check */
	var $locales;

	function localeCheck($args) {
		parent::CommandLineTool($args);
		array_shift($args); // Knock the tool name off the list
		$this->locales = $args;
	}

	/**
	 * Print command usage information.
	 */
	function usage() {
		echo "Script to test locales for consistency\n"
			. "Usage: {$this->scriptName} [localeName (optional)] ...\n";
	}
	
	/**
	 * Test locales.
	 */
	function execute() {
		$plugins = PluginRegistry::loadAllPlugins();

		foreach (Locale::getAllLocales() as $locale => $name) {
			if (!empty($this->locales) && !in_array($locale, $this->locales)) continue;
			if ($locale != MASTER_LOCALE) {
				echo "Testing locale \"$name\" ($locale) against reference locale " . MASTER_LOCALE . ".\n";
				$errors = Locale::testLocale($locale, MASTER_LOCALE);
				$this->displayLocaleErrors($locale, $errors);

				$emailErrors = Locale::testEmails($locale, MASTER_LOCALE);
				$this->displayEmailErrors($locale, $emailErrors);
			}
		}
	}

	function displayEmailErrors($locale, $errors) {
		ksort($errors);
		echo "\nERROR REPORT FOR EMAILS IN \"$locale\":\n";
		echo "-----------------------------------\n";
		foreach ($errors as $type => $errorList) {
			if (!empty($errorList)) switch ($type) {
				case EMAIL_ERROR_MISSING_EMAIL:
					echo "The following messages are missing from the emails file and need translation.\n";
					foreach ($errorList as $error) echo " - " . $error['key'] . "\n";
					break;
				case EMAIL_ERROR_EXTRA_EMAIL:
					echo "\nThe following emails are not in the master translation and may be deleted:\n";
					foreach ($errorList as $error) echo " - " . $error['key'] . "\n";
					break;
				case EMAIL_ERROR_DIFFERING_PARAMS:
					echo "\nThe following emails are missing parameters or have extra parameters and need\n";
					echo "correcting against the master translation:\n";
					foreach ($errorList as $error) {
						echo " - " . $error['key'] . "\n";
						echo "   Mismatching parameter(s): " . implode(', ', $error['mismatch']) . "\n";
					}
					break;
				default: die("Unknown error type \"$type\"!\n");
			}
		}
	}

	function displayLocaleErrors($locale, $errors) {
		ksort($errors);
		echo "\nERROR REPORT FOR LOCALE STRINGS IN \"$locale\":\n";
		echo "---------------------------------------\n";
		foreach ($errors as $type => $errorList) {
			if (!empty($errorList)) switch ($type) {
				case LOCALE_ERROR_MISSING_KEY:
					echo "The following keys are missing from a locale file and need to be translated.\n";
					foreach ($errorList as $error) echo " - " . $error['key'] . "\n";
					break;
				case LOCALE_ERROR_EXTRA_KEY:
					echo "\nThe following keys are not in the master translation and may be deleted:\n";
					foreach ($errorList as $error) echo " - " . $error['key'] . "\n";
					break;
				case LOCALE_ERROR_SUSPICIOUS_LENGTH:
					echo "\nThe following keys have suspicious lengths compared with the master translation\n";
					echo "and may need checking:\n";
					foreach ($errorList as $error) {
						$reference = $this->truncate($error['reference'], 65);
						$value = $this->truncate($error['value'], 65);
						echo " - " . $error['key'] . "\n";
						echo "   \"" . $reference . "\" vs.\n";
						echo "   \"" . $value . "\" ($locale)\n";
					}
					break;
				case LOCALE_ERROR_MISSING_FILE:
					echo "\nThe following locale files are missing:\n";
					foreach ($errorList as $error) {
						echo " - " . $error['filename'] . "\n";
					}
					break;
				case LOCALE_ERROR_DIFFERING_PARAMS:
					echo "\nThe following keys are missing parameters or have extra parameters and need\n";
					echo "correcting against the master translation:\n";
					foreach ($errorList as $error) {
						echo " - " . $error['key'] . "\n";
						echo "   Mismatching parameter(s): " . implode(', ', $error['mismatch']) . "\n";
					}
					break;
				default: die("Unknown error type \"$type\"!\n");
			}
		}
	}

	function truncate($value, $length = 80, $ellipsis = '...') {
		if (String::strlen($value) > $length) {
			$value = String::substr($value, 0, $length - String::strlen($ellipsis));
			return $value . $ellipsis;
		}
		return $value;
	}

}

$tool = &new localeCheck(isset($argv) ? $argv : array());
$tool->execute();

?>
