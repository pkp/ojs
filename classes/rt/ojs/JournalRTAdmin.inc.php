<?php

/**
 * @file JournalRTAdmin.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package rt.ojs
 * @class JournalRTAdmin
 *
 * OJS-specific Reading Tools administration interface.
 *
 * $Id$
 */

import('rt.RTAdmin');
import('rt.ojs.RTDAO');

define('RT_DIRECTORY', 'rt');
define('DEFAULT_RT_LOCALE', 'en_US');

class JournalRTAdmin extends RTAdmin {

	/** @var $journalId int */
	var $journalId;

	/** @var $dao DAO */
	var $dao;


	function JournalRTAdmin($journalId) {
		$this->journalId = $journalId;
		$this->dao = &DAORegistry::getDAO('RTDAO');
	}

	function restoreVersions($deleteBeforeLoad = true) {
		import('rt.RTXMLParser');
		$parser = &new RTXMLParser();

		if ($deleteBeforeLoad) $this->dao->deleteVersionsByJournalId($this->journalId);

		$localeFilesLocation = RT_DIRECTORY . DIRECTORY_SEPARATOR . Locale::getLocale();
		if (!file_exists($localeFilesLocation)) {
			// If no reading tools exist for the given locale, use the default set
			$localeFilesLocation = RT_DIRECTORY . DIRECTORY_SEPARATOR . DEFAULT_RT_LOCALE;
			$overrideLocale = true;
		} else {
			$overrideLocale = false;
		}

		$versions = $parser->parseAll($localeFilesLocation);
		foreach ($versions as $version) {
			if ($overrideLocale) {
				$version->setLocale(Locale::getLocale());
			}
			$this->dao->insertVersion($this->journalId, $version);
		}
	}

	function importVersion($filename) {
		import ('rt.RTXMLParser');
		$parser = &new RTXMLParser();

		$version = &$parser->parse($filename);
		$this->dao->insertVersion($this->journalId, $version);
	}
}

?>
