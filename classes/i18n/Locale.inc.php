<?php

/**
 * Locale.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package i18n
 *
 * Internationalization class. 
 * Provides methods for loading locale data and translating strings identified by unique keys
 *
 * $Id$
 */

import('i18n.LocaleFile');

define('LOCALE_REGISTRY_FILE', Config::getVar('general', 'registry_dir') . '/locales.xml');
define('LOCALE_DEFAULT', Config::getVar('i18n', 'locale'));
define('LOCALE_ENCODING', Config::getVar('i18n', 'client_charset'));

define('MASTER_LOCALE', 'en_US');

// Error types for locale checking.
// Note: Cannot use numeric symbols for the constants below because
// array_merge_recursive doesn't treat numeric keys nicely.
define('LOCALE_ERROR_MISSING_KEY',		'LOCALE_ERROR_MISSING_KEY');
define('LOCALE_ERROR_EXTRA_KEY',		'LOCALE_ERROR_EXTRA_KEY');
define('LOCALE_ERROR_SUSPICIOUS_LENGTH',	'LOCALE_ERROR_SUSPICIOUS_LENGTH');
define('LOCALE_ERROR_DIFFERING_PARAMS',		'LOCALE_ERROR_DIFFERING_PARAMS');
define('LOCALE_ERROR_MISSING_FILE',		'LOCALE_ERROR_MISSING_FILE');

define('EMAIL_ERROR_MISSING_EMAIL',		'EMAIL_ERROR_MISSING_EMAIL');
define('EMAIL_ERROR_EXTRA_EMAIL',		'EMAIL_ERROR_EXTRA_EMAIL');
define('EMAIL_ERROR_DIFFERING_PARAMS',		'EMAIL_ERROR_DIFFERING_PARAMS');

class Locale {
	/**
	 * Constructor.
	 */
	function Locale() {
	}

	/**
	 * Get a list of locale files currently registered, either in all
	 * locales (in an array for each locale), or for a specific locale.
	 * @param $locale string Locale identifier (optional)
	 */
	function &getLocaleFiles($locale = null) {
		static $localeFiles;
		if (!isset($localeFiles)) {
			$localeFiles = array();
		}

		if ($locale !== null) {
			if (!isset($localeFiles[$locale])) $localeFiles[$locale] = array();
			return $localeFiles[$locale];
		}
		return $localeFiles;
	}

	/**
	 * Translate a string using the selected locale.
	 * Substitution works by replacing tokens like "{$foo}" with the value
	 * of the parameter named "foo" (if supplied).
	 * @param $key string
	 * @params $params array named substitution parameters
	 * @params $locale string the locale to use
	 * @return string
	 */
	function translate($key, $params = array(), $locale = null) {
		if (!isset($locale)) $locale = Locale::getLocale();
		if (($key = trim($key)) == '') return '';

		$localeFiles =& Locale::getLocaleFiles($locale);
		$value = '';
		for ($i = 0; $i < count($localeFiles); $i++) { // By reference
			$value = $localeFiles[$i]->translate($key, $params);
			if ($value !== null) return $value;
		}

		// Add a missing key to the debug notes.
		$notes =& Registry::get('system.debug.notes');
		$notes[] = array('debug.notes.missingLocaleKey', array('key' => $key));
	
		// Add some octothorpes to missing keys to make them more obvious
		return '##' . $key . '##';
	}

	/**
	 * Get the filename for the locale file given a locale name.
	 */
	function getMainLocaleFilename($locale) {
		return "locale/$locale/locale.xml";
	}

	/**
	 * Initialize the locale system.
	 */
	function initialize() {
		// Use defaults if locale info unspecified.
		$locale = Locale::getLocale();
		$localeFile = Locale::getMainLocaleFilename($locale);

		$sysLocale = $locale . '.' . LOCALE_ENCODING;
		if (!@setlocale(LC_ALL, $sysLocale, $locale)) {
			// For PHP < 4.3.0
			if(setlocale(LC_ALL, $sysLocale) != $sysLocale) {
				setlocale(LC_ALL, $locale);
			}
		}

		Locale::registerLocaleFile($locale, $localeFile);
	}

	/**
	 * Register a locale file against the current list.
	 * @param $locale string Locale key
	 * @param $filename string Filename to new locale XML file
	 * @param $addToTop boolean Whether to add to the top of the list (true)
	 * 	or the bottom (false). Allows overriding.
	 */
	function &registerLocaleFile ($locale, $filename, $addToTop = false) {
		$localeFiles =& Locale::getLocaleFiles($locale);
		$localeFile =& new LocaleFile($locale, $filename);
		if (!$localeFile->isValid()) {
			$localeFile = null;
			return $localeFile;
		}
		if ($addToTop) {
			// Work-around: unshift by reference.
			array_unshift($localeFiles, '');
			$localeFiles[0] =& $localeFile;
		} else {
			$localeFiles[] =& $localeFile;
		}
		return $localeFile;
	}

	/**
	 * Return the key name of the user's currently selected locale (default
	 * is "en_US" for U.S. English).
	 * @return string 
	 */
	function getLocale() {
		static $currentLocale;
		if (!isset($currentLocale)) {
			if (defined('SESSION_DISABLE_INIT')) {
				$locale = Request::getCookieVar('currentLocale');
			
			} else {
				$sessionManager = &SessionManager::getManager();
				$session = &$sessionManager->getUserSession();
				$locale = $session->getSessionVar('currentLocale');
			
				$journal = &Request::getJournal();
				$site = &Request::getSite();
			
				if (!isset($locale)) {
					$locale = Request::getCookieVar('currentLocale');
				}
				
				if (isset($locale)) {
					// Check if user-specified locale is supported
					if ($journal != null) {
						$locales = &$journal->getSupportedLocaleNames();
					} else {
						$locales = &$site->getSupportedLocaleNames();
					}
					
					if (!in_array($locale, array_keys($locales))) {
						unset($locale);
					}
				}
				
				if (!isset($locale)) {
					// Use journal/site default
					if ($journal != null) {
						$locale = $journal->getLocale();
					}
					
					if (!isset($locale)) {
						$locale = $site->getLocale();
					}
				}
			}
			
			if (!Locale::isLocaleValid($locale)) {
				$locale = LOCALE_DEFAULT;
			}
			
			$currentLocale = $locale;
		}
		return $currentLocale;
	}

	/**
	 * Check if the supplied locale is currently installable.
	 * @param $locale string
	 * @return boolean
	 */
	function isLocaleValid($locale) {
		if (empty($locale)) return false;
		if (!preg_match('/^[a-z][a-z]_[A-Z][A-Z]$/', $locale)) return false;
		if (file_exists(Locale::getMainLocaleFilename($locale))) return true;
		return false;
	}
	
	/**
	 * Retrieve the primary locale of the current context.
	 * @return string
	 */
	function getPrimaryLocale() {
		$journal = &Request::getJournal();
		
		if (isset($journal)) {
			$locale = $journal->getLocale();
		}
		
		if (!isset($locale)) {
			$site = &Request::getSite();
			$locale = $site->getLocale();
		}
		
		if (!isset($locale) || !Locale::isLocaleValid($locale)) {
			$locale = LOCALE_DEFAULT;
		}
		
		return $locale;
	}

	/**
	 * Get the cache object for the current list of all locales.
	 */
	function &_getAllLocalesCache() {
		static $cache;
		if (!isset($cache)) {
			import('cache.CacheManager');
			$cacheManager =& CacheManager::getManager();
			$cache =& $cacheManager->getFileCache(
				'locale', 'list',
				array('Locale', '_allLocalesCacheMiss')
			);

			// Check to see if the data is outdated
			$cacheTime = $cache->getCacheTime();
			if ($cacheTime !== null && $cacheTime < filemtime(LOCALE_REGISTRY_FILE)) {
				$cache->flush();
			}
		}
		return $cache;
	}

	function _allLocalesCacheMiss(&$cache, $id) {
		static $allLocales;
		if (!isset($allLocales)) {
			// Add a locale load to the debug notes.
			$notes =& Registry::get('system.debug.notes');
			$notes[] = array('debug.notes.localeListLoad', array('localeList' => LOCALE_REGISTRY_FILE));

			// Reload locale registry file
			$xmlDao = &new XMLDAO();
			$data = $xmlDao->parseStruct(LOCALE_REGISTRY_FILE, array('locale'));
	
			// Build array with ($localKey => $localeName)
			if (isset($data['locale'])) {
				foreach ($data['locale'] as $localeData) {
					$allLocales[$localeData['attributes']['key']] = $localeData['attributes']['name'];
				}
			}
			asort($allLocales);
			$cache->setEntireCache($allLocales);
		}
		return null;
	}

	/**
	 * Return a list of all available locales.
	 * @return array
	 */
	function &getAllLocales() {
		$cache =& Locale::_getAllLocalesCache();

		// if client encoding is set to iso-8859-1, transcode locales from utf8
		if (LOCALE_ENCODING == "iso-8859-1") {
			foreach ($cache->getContents() as $locale => $language) {
				$cache_contents[$locale] = utf8_decode($language);
			}
			return $cache_contents;

		} else {

			return $cache->getContents();
		}

	}
	
	/**
	 * Check if the current locale is one of the journal's alternate locales
	 * @return int the alternate # (or 0, if no match).
	 */
	function isAlternateJournalLocale($journalId) {
		static $alternateLocaleNum;
		
		if (!isset($alternateLocaleNum)) {
			$localeNum = 0;
			$locale = Locale::getLocale();
			
			$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
			$alternateLocale1 = $journalSettingsDao->getSetting($journalId, 'alternateLocale1');
			if (isset($alternateLocale1)) {
				if ($alternateLocale1 == $locale) {
					$localeNum = 1;
				} else {
					$alternateLocale2 = $journalSettingsDao->getSetting($journalId, 'alternateLocale2');
					if ($alternateLocale2 == $locale) {
						$localeNum = 2;
					}
				}
			}
			
			$alternateLocaleNum = $localeNum;
		}
		
		return $alternateLocaleNum;
	}

	/**
	 * Get the path and filename for the email templates data for the
	 * given locale
	 * @param $locale string
	 * @return string
	 */
	function getEmailTemplateFilename($locale) {
		return 'dbscripts/xml/data/locale/' . $locale . '/email_templates_data.xml';
	}

	function getFilesToInstall($locale) {
		return array(
			Locale::getEmailTemplateFilename($locale)
		);
	}

	/**
	 * Uninstall support for an existing locale.
	 * @param $locale string
	 */
	function installLocale($locale) {
		// Install default locale-specific data
		import('db.DBDataXMLParser');
		
		$filesToInstall = Locale::getFilesToInstall($locale);
		
		$dataXMLParser = &new DBDataXMLParser();
		foreach ($filesToInstall as $fileName) {
			if (file_exists($fileName)) {
				$sql = $dataXMLParser->parseData($fileName);
				$dataXMLParser->executeData();
			}
		}
		$dataXMLParser->destroy();
	}
	
	/**
	 * Install support for a new locale.
	 * @param $locale string
	 */
	function uninstallLocale($locale) {
		// Delete locale-specific data
		$emailTemplateDao = &DAORegistry::getDAO('EmailTemplateDAO');
		$emailTemplateDao->deleteEmailTemplatesByLocale($locale);
		$emailTemplateDao->deleteDefaultEmailTemplatesByLocale($locale);
	}
	
	/**
	 * Reload locale-specific data.
	 * @param $locale string
	 */
	function reloadLocale($locale) {
		Locale::uninstallLocale($locale);
		Locale::installLocale($locale);
	}

	/**
	 * Test all locale files for the supplied locale against the supplied
	 * reference locale, returning an array of errors.
	 * @param $locale string Name of locale to test
	 * @param $referenceLocale string Name of locale to test against
	 * @return array
	 */
	function testLocale($locale, $referenceLocale) {
		$localeFile =& new LocaleFile($locale, Locale::getMainLocaleFilename($locale));
		$referenceLocaleFile =& new LocaleFile($referenceLocale, Locale::getMainLocaleFilename($referenceLocale));

		$errors = $localeFile->testLocale($referenceLocaleFile);
		unset($localeFile);
		unset($referenceLocaleFile);

		$plugins =& PluginRegistry::loadAllPlugins();
		foreach (array_keys($plugins) as $key) {
			$plugin =& $plugins[$key];
			$localeFile =& new LocaleFile($locale, $plugin->getLocaleFilename($locale));
			$referenceLocaleFile =& new LocaleFile($referenceLocale, $plugin->getLocaleFilename($referenceLocale));
			$errors = array_merge_recursive($errors, $localeFile->testLocale($referenceLocaleFile));
			unset($localeFile);
			unset($referenceLocaleFile);
			unset($plugin);
		}
		return $errors;
	}

	/**
	 * Test the emails in the supplied locale against those in the supplied
	 * reference locale.
	 * @param $locale string
	 * @param $referenceLocale string
	 * @return array List of errors
	 */
	function testEmails($locale, $referenceLocale) {
		$errors = array(
		);

		$xmlParser =& new XMLParser();
		$referenceEmails =& $xmlParser->parse(Locale::getEmailTemplateFilename($referenceLocale));
		$emails =& $xmlParser->parse(Locale::getEmailTemplateFilename($locale));
		$emailsTable =& $emails->getChildByName('table');
		$referenceEmailsTable =& $referenceEmails->getChildByName('table');
		$matchedReferenceEmails = array();

		// Pass 1: For all translated emails, check that they match
		// against reference translations.
		for ($emailIndex = 0; ($email =& $emailsTable->getChildByName('row', $emailIndex)) !== null; $emailIndex++) { 
			// Extract the fields from the email to be tested.
			$fields = Locale::extractFields($email);

			// Locate the reference email and extract its fields.
			for ($referenceEmailIndex = 0; ($referenceEmail =& $referenceEmailsTable->getChildByName('row', $referenceEmailIndex)) !== null; $referenceEmailIndex++) {
				$referenceFields = Locale::extractFields($referenceEmail);
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
			$bodyParams = Locale::getParameterNames($fields['body']);
			$referenceBodyParams = Locale::getParameterNames($referenceFields['body']);
			if ($bodyParams !== $referenceBodyParams) {
				$errors[EMAIL_ERROR_DIFFERING_PARAMS][] = array(
					'key' => $fields['email_key'],
					'mismatch' => array_diff($bodyParams, $referenceBodyParams)
				);
			}

			$subjectParams = Locale::getParameterNames($fields['subject']);
			$referenceSubjectParams = Locale::getParameterNames($referenceFields['subject']);

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
			$referenceFields = Locale::extractFields($referenceEmail);
			if (!in_array($referenceFields['email_key'], $matchedReferenceEmails)) {
				$errors[EMAIL_ERROR_MISSING_EMAIL][] = array(
					'key' => $referenceFields['email_key']
				);
			}
		}

		return $errors;
	}

	/**
	 * Given a parent XML node, extract child nodes of the following form:
	 * <field name="something">some_value</field>
	 * ... into an associate array $array['something'] = 'some_value';
	 * @param $node object
	 * @return array
	 */
	function extractFields(&$node) {
		$returner = array();
		foreach ($node->getChildren() as $field) if ($field->getName() === 'field') {
			$returner[$field->getAttribute('name')] = $field->getValue();
		}
		return $returner;
	}

	/**
	 * Determine whether or not the lengths of the two supplied values are
	 * "similar".
	 * @param $reference string
	 * @param $value string
	 * @return boolean True if the lengths match very roughly.
	 */
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

?>
