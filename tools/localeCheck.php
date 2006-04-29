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

class localeCheck extends CommandLineTool {
	
	/**
	 * Print command usage information.
	 */
	function usage() {
		echo "Script to test locales for consistency\n"
			. "Usage: {$this->scriptName}\n";
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
		foreach (PluginRegistry::getCategories() as $category) {
			PluginRegistry::loadCategory($category);
		}
		
		$locales =& Locale::getAllLocales();
		foreach ($locales as $locale => $name) {
			if ($locale != MASTER_LOCALE) {
				echo "Testing locale \"$name\" ($locale) against reference locale " . MASTER_LOCALE . ".\n";
				$this->testLocale($locale, MASTER_LOCALE);
			}
		}
	}

	function testLocale($locale, $referenceLocale) {
		$localeCache =& Locale::_getCache($locale);
		$referenceLocaleCache =& Locale::_getCache($referenceLocale);

		$localeContents =& $localeCache->getContents();
		$referenceContents =& $referenceLocaleCache->getContents();

		foreach ($referenceContents as $key => $referenceValue) {
			if (!isset($localeContents[$key])) {
				echo "\tThe key \"$key\" is missing from $locale.\n";
				continue;
			}
			$value = $localeContents[$key];

			// Watch for suspicious lengths.
			$referenceLength = String::strlen($referenceValue);
			$length = String::strlen($value);
			$lengthDifference = abs($referenceLength - $length);
			if ($referenceLength == 0) {
				echo "WARNING: Zero-length reference value for key \"$key\"!\n";
			} elseif ($lengthDifference / $referenceLength > 1 && $lengthDifference > 10) {
				echo "\tThe key \"$key\" in $locale has a suspicious length:\n";
				echo "\t\t\"$value\" vs.\n";
				echo "\t\t\"$referenceValue\"\n";
			}

			$referenceParams = $this->getParameterNames($referenceValue);
			$params = $this->getParameterNames($value);
			if ($referenceParams !== $params) {
				$mismatch = array_diff($referenceParams, $params);
				echo "\tUsage of the following parameters in \"$key\" in $locale differs from the reference locale: " . implode(', ', $mismatch) . "\n";
			}
			// After processing a key, remove it from the list;
			// this way, the remainder at the end of the loop
			// will be extra unnecessary keys.
			unset($localeContents[$key]);
		}
		
		// Leftover keys are extraneous.
		foreach ($localeContents as $key => $value) {
			echo "\tExtra key \"$key\" in $locale.\n";
		}
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
