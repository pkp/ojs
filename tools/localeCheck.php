<?php

/**
 * localeCheck.php
 *
 * Copyright (c) 2005-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package tools
 *
 * CLI tool to check the various locales for consistency.
 *
 * $Id$
 */

require(dirname(__FILE__) . '/includes/cliTool.inc.php');

define('MASTER_LOCALE', 'en_US');

define('LOCALE_ERROR_MISSING_KEY',		0x0000001);
define('LOCALE_ERROR_EXTRA_KEY',		0x0000002);
define('LOCALE_ERROR_SUSPICIOUS_LENGTH',	0x0000003);
define('LOCALE_ERROR_DIFFERING_PARAMS',		0x0000004);

define('EMAIL_ERROR_MISSING_EMAIL',		0x0000005);
define('EMAIL_ERROR_EXTRA_EMAIL',		0x0000006);
define('EMAIL_ERROR_DIFFERING_PARAMS',		0x0000007);

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
		// Flush the file cache just to be certain we're using
		// the most recent stuff
		import('cache.CacheManager');
		$cacheManager =& CacheManager::getManager();
		$cacheManager->flush('locale');

		// Load plugins so that their locale data is included too
		$plugins = array();
		foreach (PluginRegistry::getCategories() as $category) {
			echo "Loading plugin category \"$category\"...\n";
			$morePlugins = PluginRegistry::loadCategory($category);
			if (is_array($morePlugins)) $plugins += $morePlugins;
		}

		foreach (Locale::getAllLocales() as $locale => $name) {
			if (!empty($this->locales) && !in_array($locale, $this->locales)) continue;
			if ($locale != MASTER_LOCALE) {
				echo "Testing locale \"$name\" ($locale) against reference locale " . MASTER_LOCALE . ".\n";
				$this->testLocale($locale, MASTER_LOCALE, $plugins);
				$this->testEmails($locale, MASTER_LOCALE);
			}
		}
	}

	function testEmails($locale, $referenceLocale) {
		import('install.Installer'); // Bring in data dir

		$errors = array(
		);

		$xmlParser =& new XMLParser();
		$referenceEmails =& $xmlParser->parse(
			INSTALLER_DATA_DIR . "/data/locale/$referenceLocale/email_templates_data.xml"
		);
		$emails =& $xmlParser->parse(
			INSTALLER_DATA_DIR . "/data/locale/$locale/email_templates_data.xml"
		);
		$emailsTable =& $emails->getChildByName('table');
		$referenceEmailsTable =& $referenceEmails->getChildByName('table');
		$matchedReferenceEmails = array();

		// Pass 1: For all translated emails, check that they match
		// against reference translations.
		for ($emailIndex = 0; ($email =& $emailsTable->getChildByName('row', $emailIndex)) !== null; $emailIndex++) { 
			// Extract the fields from the email to be tested.
			$fields = $this->extractFields($email);

			// Locate the reference email and extract its fields.
			for ($referenceEmailIndex = 0; ($referenceEmail =& $referenceEmailsTable->getChildByName('row', $referenceEmailIndex)) !== null; $referenceEmailIndex++) {
				$referenceFields = $this->extractFields($referenceEmail);
				if ($referenceFields['email_key'] == $fields['email_key']) break;
			}

			// Check if a matching reference email was found.
			if (!isset($referenceEmail) || $referenceEmail === null) {
				$errors[EMAIL_ERROR_EXTRA_EMAIL][] = array(
					'key' => $fields['email_key']
				);
				continue;
			}

			// We've successfully found a matching reference email.
			// Compare it against the translation.
			$bodyParams = $this->getParameterNames($fields['body']);
			$referenceBodyParams = $this->getParameterNames($referenceFields['body']);
			if ($bodyParams !== $referenceBodyParams) {
				$errors[EMAIL_ERROR_DIFFERING_PARAMS][] = array(
					'key' => $fields['email_key'],
					'mismatch' => array_diff($bodyParams, $referenceBodyParams)
				);
			}

			$subjectParams = $this->getParameterNames($fields['subject']);
			$referenceSubjectParams = $this->getParameterNames($referenceFields['subject']);

			if ($subjectParams !== $referenceSubjectParams) {
				$errors[EMAIL_ERROR_DIFFERING_PARAMS][] = array(
					'key' => $fields['email_key'],
					'mismatch' => array_diff($subjectParams, $referenceSubjectParams)
				);
			}

			$matchedReferenceEmails[] = $fields['email_key'];

			unset($email);
			unset($referenceEmail);
		}

		// Pass 2: Make sure that there are no missing translations.
		for ($referenceEmailIndex = 0; ($referenceEmail =& $referenceEmailsTable->getChildByName('row', $referenceEmailIndex)) !== null; $referenceEmailIndex++) {
			// Extract the fields from the email to be tested.
			$referenceFields = $this->extractFields($referenceEmail);
			if (!in_array($referenceFields['email_key'], $matchedReferenceEmails)) {
				$errors[EMAIL_ERROR_MISSING_EMAIL][] = array(
					'key' => $referenceFields['email_key']
				);
			}
		}
		
		$this->displayEmailErrors($locale, $errors);
	}

	function extractFields(&$node) {
		$returner = array();
		foreach ($node->getChildren() as $field) if ($field->getName() === 'field') {
			$returner[$field->getAttribute('name')] = $field->getValue();
		}
		return $returner;
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

	function testLocale($locale, $referenceLocale, $plugins) {
		$errors = array(
			LOCALE_ERROR_MISSING_KEY => array(),
			LOCALE_ERROR_EXTRA_KEY => array(),
			LOCALE_ERROR_SUSPICIOUS_LENGTH => array(),
			LOCALE_ERROR_DIFFERING_PARAMS => array()
		);

		$localeCache =& Locale::_getCache($locale);
		$referenceLocaleCache =& Locale::_getCache($referenceLocale);

		$localeContents =& $localeCache->getContents();
		$referenceContents =& $referenceLocaleCache->getContents();

		// Add locale data for plugins
		foreach ($plugins as $plugin) {
			$pluginCache = $plugin->_getCache($locale);
			$referencePluginCache = $plugin->_getCache($referenceLocale);
			$localeContents += $pluginCache->getContents();
			$referenceContents += $referencePluginCache->getContents();
		}

		foreach ($referenceContents as $key => $referenceValue) {
			if (!isset($localeContents[$key])) {
				$errors[LOCALE_ERROR_MISSING_KEY][] = array(
					'key' => $key,
					'locale' => $locale
				);
				continue;
			}
			$value = $localeContents[$key];

			// Watch for suspicious lengths.
			if (!$this->checkLengths($referenceValue, $value)) {
				$errors[LOCALE_ERROR_SUSPICIOUS_LENGTH][] = array(
					'key' => $key,
					'locale' => $locale,
					'reference' => $referenceValue,
					'value' => $value
				);
			}

			$referenceParams = $this->getParameterNames($referenceValue);
			$params = $this->getParameterNames($value);
			if ($referenceParams !== $params) {
				$errors[LOCALE_ERROR_DIFFERING_PARAMS][] = array(
					'key' => $key,
					'locale' => $locale,
					'mismatch' => array_diff($referenceParams, $params)
				);
			}
			// After processing a key, remove it from the list;
			// this way, the remainder at the end of the loop
			// will be extra unnecessary keys.
			unset($localeContents[$key]);
		}
		
		// Leftover keys are extraneous.
		foreach ($localeContents as $key => $value) {
			$errors[LOCALE_ERROR_EXTRA_KEY][] = array(
				'key' => $key,
				'locale' => $locale
			);
		}

		$this->displayLocaleErrors($locale, $errors);
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

	function checkLengths($reference, $value) {
		$referenceLength = String::strlen($reference);
		$length = String::strlen($value);
		$lengthDifference = abs($referenceLength - $length);
		if ($referenceLength == 0) return false;
		if ($lengthDifference / $referenceLength > 1 && $lengthDifference > 10) return false;
		return true;
	}

	/**
	 * Given a locale string, get the list of parameter references of the
	 * form {$myParameterName}.
	 * @param $source string
	 * @return array
	 */
	function getParameterNames($source) {
		$matches = null;
		String::regexp_match_get('/({\$[^}]+})/' /* '/{\$[^}]+})/' */, $source, $matches);
		array_shift($matches); // Knock the top element off the array
		return $matches;
	}
}

$tool = &new localeCheck(isset($argv) ? $argv : array());
$tool->execute();

?>
