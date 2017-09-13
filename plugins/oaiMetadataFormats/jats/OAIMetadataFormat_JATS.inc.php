<?php

/**
 * @defgroup oai_format_jats
 */

/**
 * @file plugins/oaiMetadataFormats/jats/OAIMetadataFormat_JATS.inc.php
 *
 * Copyright (c) 2013-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OAIMetadataFormat_JATS
 * @ingroup oai_format
 * @see OAI
 *
 * @brief OAI metadata format class -- JATS
 */

class OAIMetadataFormat_JATS extends OAIMetadataFormat {

	/**
	 * @copydoc OAIMetadataFormat#toXml
	 */
	function toXml($record, $format = null) {
		$article = $record->getData('article');
		$journal = $record->getData('journal');
		$section = $record->getData('section');
		$issue = $record->getData('issue');
		$galleys = $record->getData('galleys');


		import('lib.pkp.classes.submission.SubmissionFile'); // SUBMISSION_FILE_... constants
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		$genres = array(); // Genre cache
		$candidateFiles = array();

		// First, look for candidates in the galleys area (published content).
		foreach ($galleys as $galley) {
			$galleyFiles = $submissionFileDao->getLatestRevisionsByAssocId(ASSOC_TYPE_GALLEY, $galley->getId(), $galley->getSubmissionId(), SUBMISSION_FILE_PROOF);
			foreach ($galleyFiles as $galleyFile) {
				if ($this->_isCandidateFile($galleyFile)) $candidateFiles[] = $galleyFile;
			}
		}

		// If no candidates were found, look in the layout area (unpublished content).
		if (empty($candidateFiles)) {
			$layoutFiles = $submissionFileDao->getLatestRevisions($article->getId(), SUBMISSION_FILE_PRODUCTION_READY);
			foreach ($layoutFiles as $layoutFile) {
				if ($this->_isCandidateFile($layoutFile)) $candidateFiles[] = $layoutFile;
			}
		}

		// If no candidate files were located, return the null XML.
		if (empty($candidateFiles)) return $this->_getNullXml();

		if (count($candidateFiles) > 1) error_log('WARNING: More than one JATS XML candidate documents were located for submission ' . $article->getId() . '.');

		// Fetch the XML document and return it.
		$candidateFile = array_shift($candidateFiles);

		$doc = new DOMDocument;
		$mock = new DOMDocument;
		$doc->loadXML(file_get_contents($candidateFile->getFilePath()));
		$articleNode = $doc->getElementsByTagName('article')->item(0);
		return $doc->saveXML($articleNode);
	}

	protected function _getNullXml() {
		return '<article xmlns:mml="http://www.w3.org/1998/Math/MathML" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" article-type="research-article" dtd-version="1.1d1" xml:lang="en"></article>';
	}

	/**
	 * Determine whether a submission file is a good candidate for JATS XML.
	 * @param $submissionFile SubmissionFile
	 * @return boolean
	 */
	protected function _isCandidateFile($submissionFile) {
		// The file type isn't XML.
		if (!in_array($submissionFile->getFileType(), array('application/xml', 'text/xml'))) return false;

		static $genres = array();
		$genreDao = DAORegistry::getDAO('GenreDAO');
		$genreId = $submissionFile->getGenreId();
		if (!isset($genres[$genreId])) $genres[$genreId] = $genreDao->getById($genreId);
		assert($genres[$genreId]);
		$genre = $genres[$genreId];

		// The genre doesn't look like a main submission document.
		if ($genre->getCategory() != GENRE_CATEGORY_DOCUMENT) return false;
		if ($genre->getDependent()) return false;
		if ($genre->getSupplementary()) return false;

		return true;
	}
}

?>
