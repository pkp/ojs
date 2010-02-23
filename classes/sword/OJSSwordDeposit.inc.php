<?php

/**
 * @file classes/sword/OJSSwordDeposit.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OJSSwordDeposit
 * @ingroup sword
 *
 * @brief Class providing a SWORD deposit wrapper for OJS articles
 */

// $Id: CaptchaManager.inc.php,v 1.7 2010/01/22 20:42:41 asmecher Exp $


class OJSSwordDeposit {
	/** @var $package SWORD deposit METS package */
	var $package;

	/** @var $outPath Complete path and directory name to use for package creation files */
	var $outPath;

	/** @var $journal */
	var $journal;

	/** @var $section */
	var $section;

	/** @var $issue */
	var $issue;

	/**
	 * Constructor.
	 * Create a manager for handling temporary file uploads.
	 */
	function OJSSwordDeposit(&$article) {
		require_once('lib/pkp/lib/swordapp/swordappclient.php');
		require_once('lib/pkp/lib/swordapp/swordappentry.php');
		require_once('lib/pkp/lib/swordapp/packager_mets_swap.php');

		// Create a directory for deposit contents
		$this->outPath = tempnam('/tmp', 'sword');
		unlink($this->outPath);
		mkdir($this->outPath);
		mkdir($this->outPath . '/files');

		// Create a package
		$this->package = new PackagerMetsSwap(
			$this->outPath,
			'files',
			$this->outPath,
			'deposit.zip'
		);

		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$this->journal =& $journalDao->getJournal($article->getJournalId());

		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$this->section =& $sectionDao->getSection($article->getSectionId());

		$this->article =& $article;
	}

	function setMetadata() {
		$this->package->setCustodian($this->journal->getSetting('contactName'));
		$this->package->setTitle($this->article->getTitle($this->journal->getPrimaryLocale()));
		$this->package->setAbstract($this->article->getAbstract($this->journal->getPrimaryLocale()));
		$this->package->setType($this->section->getIdentifyType($this->journal->getPrimaryLocale()));

		$doi = $this->article->getDOI();
		if ($doi !== null) $this->package->setIdentifier($doi);

		foreach ($this->article->getAuthors() as $author) {
			$this->package->addCreator($author->getFullName());
		}

		$plugin =& PluginRegistry::loadPlugin('citationFormats', 'bibtex');
		$this->package->setCitation(html_entity_decode(strip_tags($plugin->fetchCitation($this->article, $this->issue, $this->journal))));
	}

	function addGalleys() {
		foreach ($this->article->getGalleys() as $galley) {
			$targetFilename = $this->outPath . '/files/' . $galley->getFilename();
			copy($galley->getFilePath(), $targetFilename);
			$this->package->addFile($galley->getFilename(), $galley->getFileType());
		}
	}

	function createPackage() {
		return $this->package->create();
	}

	function deposit($url, $username, $password) {
		$client = new SWORDAPPClient();
		$response = $client->deposit(
			$url, $username, $password,
			'',
			$this->outPath . '/deposit.zip',
			'http://purl.org/net/sword-types/METSDSpaceSIP',
			'application/zip', false, true
		);
		return $response;
	}

	function cleanup() {
		import('file.FileManager');
		$fileManager = new FileManager();

		$fileManager->rmtree($this->outPath);
	}
}

?>
