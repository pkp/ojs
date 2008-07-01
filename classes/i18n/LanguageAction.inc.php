<?php
/**
 * @defgroup admin
 */

/**
 * @file classes/i18n/LanguageAction.inc.php
 * @defgroup admin
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class LanguageAction
 * @ingroup admin
 *
 * @brief LanguageAction class.
 */

// $Id$


define('LANGUAGE_PACK_DESCRIPTOR_URL', 'http://pkp.sfu.ca/ojs/xml/%s/locales.xml');
define('LANGUAGE_PACK_TAR_URL', 'http://pkp.sfu.ca/ojs/xml/%s/%s.tar.gz');

if (!function_exists('stream_get_contents')) {
	function stream_get_contents($fp) {
		fflush($fp);
	}
}

class LanguageAction {
	/**
	 * Constructor.
	 */
	function LanguageAction() {
	}

	/**
	 * Actions.
	 */

	/**
	 * Check to see whether the tar function exists and appears to be
	 * available for execution from PHP, and various other conditions that
	 * are required for locale downloading to be available.
	 * @return boolean
	 */
	function isDownloadAvailable() {
		// Check to see that proc_open is available
		if (!function_exists('proc_open')) return false;

		// Check to see that we're using at least PHP 4.3 for
		// stream_set_blocking.
		if (!version_compare(phpversion(), '4.3.0')) return false;

		// Check to see that tar can be executed
		$fds = array(
			0 => array('pipe', 'r'),
			1 => array('pipe', 'w'),
			2 => array('pipe', 'w')
		);
		$pipes = $process = null;
		@$process = proc_open('tar --version', $fds, $pipes);
		if (!is_resource($process)) return false;
		fclose($pipes[0]); // No input necessary
		stream_get_contents($pipes[1]); // Flush pipes. fflush seems to
		stream_get_contents($pipes[2]); // behave oddly, so it's avoided
		fclose($pipes[1]);
		fclose($pipes[2]);
		if (proc_close($process) !== 0) return false;

		// Check that we can write to a few locations
		if (!is_file(LOCALE_REGISTRY_FILE) || !is_writable(LOCALE_REGISTRY_FILE)) return false;

		return true;
	}

	/**
	 * Get the list of locales that can be downloaded for the current
	 * version.
	 */
	function getDownloadableLocales() {
		$versionDao =& DAORegistry::getDAO('VersionDAO');
		$version =& $versionDao->getCurrentVersion();
		$versionString = $version->getVersionString();

		$descriptorFilename = sprintf(LANGUAGE_PACK_DESCRIPTOR_URL, $versionString);
		return Locale::loadLocaleList($descriptorFilename);
	}

	/**
	 * Download and install a locale.
	 * @param $locale string
	 * @param $errors array
	 * @return boolean
	 */
	function downloadLocale($locale, &$errors) {
		$downloadableLocales =& $this->getDownloadableLocales();
		if (!is_array($downloadableLocales) || !isset($downloadableLocales[$locale])) {
			$errors[] = Locale::translate('admin.languages.download.cannotOpen');
			return false;
		}

		$versionDao =& DAORegistry::getDAO('VersionDAO');
		$version =& $versionDao->getCurrentVersion();
		$versionString = $version->getVersionString();

		// Set up to download and extract the language pack
		$fds = array(
			0 => array('pipe', 'r'),
			1 => array('pipe', 'w'),
			2 => array('pipe', 'w')
		);
		$pipes = null;
		$process = proc_open('tar -x -z --wildcards \\*' . $locale . '\\*.xml \\*' . $locale . '\\*.png', $fds, $pipes);
		if (!is_resource($process)) return false;

		// Download and feed the pack to the tar process
		$languagePackUrl = sprintf(LANGUAGE_PACK_TAR_URL, $versionString, $locale);
		$wrapper =& FileWrapper::wrapper($languagePackUrl);
		if (!$wrapper->open()) {
			$errors[] = Locale::translate('admin.languages.download.cannotOpen');
		}

		stream_set_blocking($pipes[0], 0);
		stream_set_blocking($pipes[1], 0);
		stream_set_blocking($pipes[2], 0);

		$pipeStdout = $pipeStderr = '';
		while ($data = $wrapper->read()) {
			fwrite($pipes[0], $data);
			$pipeStdout .= fgets($pipes[1]);
			$pipeStderr .= fgets($pipes[2]);
		}
		fclose($pipes[0]);
		fclose($pipes[1]);
		fclose($pipes[2]);
		unset($wrapper);

		if (!empty($pipeStderr)) {
			$errors[] = $pipeStderr;
			return false;
		}

		// Finish up the tar process
		if (proc_close($process) !== 0) return false;

		// The tar file has now been extracted -- check whether the
		// locales list needs to have the new locale added.
		$locales = Locale::getAllLocales();
		if (!isset($locales[$locale])) {
			// The locale does not exist in the local locale list
			$wrapper =& FileWrapper::wrapper(LOCALE_REGISTRY_FILE);
			$contents = $wrapper->contents();
			$pos = strpos($contents, '</locales>');
			if ($pos === false) {
				// Unable to locate insert point for new locale
				$errors[] = Locale::translate('admin.languages.download.cannotModifyRegistry');
				return false;
			}
			$contents = substr_replace($contents, "\t<locale key=\"$locale\" name=\"" . $downloadableLocales[$locale]['name'] . "\" />\n", $pos, 0);
			$fp = fopen(LOCALE_REGISTRY_FILE, 'w');
			if (!$fp) {
				// Unable to locate insert point for new locale
				$errors[] = Locale::translate('admin.languages.download.cannotModifyRegistry');
				return false;
			}
			fwrite($fp, $contents);
			fclose($fwrite);
		}

		return true;
	}
}

?>
