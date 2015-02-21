<?php

/**
 * @file classes/rt/ojs/JournalRTAdmin.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JournalRTAdmin
 * @ingroup rt_ojs
 *
 * @brief OJS-specific Reading Tools administration interface.
 */

import('lib.pkp.classes.rt.RTAdmin');
import('classes.rt.ojs.RTDAO');

define('RT_DIRECTORY', 'rt');
define('DEFAULT_RT_LOCALE', 'en_US');

class JournalRTAdmin extends RTAdmin {

	/** @var $journalId int */
	var $journalId;

	/** @var $dao DAO */
	var $dao;


	function JournalRTAdmin($journalId) {
		$this->journalId = $journalId;
		$this->dao =& DAORegistry::getDAO('RTDAO');
	}

	function restoreVersions($deleteBeforeLoad = true) {
		import('lib.pkp.classes.rt.RTXMLParser');
		$parser = new RTXMLParser();

		if ($deleteBeforeLoad) $this->dao->deleteVersionsByJournalId($this->journalId);

		$localeFilesLocation = RT_DIRECTORY . DIRECTORY_SEPARATOR . AppLocale::getLocale();
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
				$version->setLocale(AppLocale::getLocale());
			}
			$this->dao->insertVersion($this->journalId, $version);
		}
	}

	function importVersion($filename) {
		import ('lib.pkp.classes.rt.RTXMLParser');
		$parser = new RTXMLParser();

		$version =& $parser->parse($filename);
		$this->dao->insertVersion($this->journalId, $version);
	}
}

?>
