<?php

/**
 * @defgroup article
 */

/**
 * @file classes/article/Article.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Article
 * @ingroup article
 * @see ArticleDAO
 *
 * @brief Article class.
 */


// Submission status constants
define('STATUS_ARCHIVED', 0);
define('STATUS_QUEUED', 1);
// define('STATUS_SCHEDULED', 2); // #2187: Scheduling queue removed.
define('STATUS_PUBLISHED', 3);
define('STATUS_DECLINED', 4);

// AuthorSubmission::getSubmissionStatus will return one of these in place of QUEUED:
define ('STATUS_QUEUED_UNASSIGNED', 5);
define ('STATUS_QUEUED_REVIEW', 6);
define ('STATUS_QUEUED_EDITING', 7);
define ('STATUS_INCOMPLETE', 8);

// Author display in ToC
define ('AUTHOR_TOC_DEFAULT', 0);
define ('AUTHOR_TOC_HIDE', 1);
define ('AUTHOR_TOC_SHOW', 2);

// Article RT comments
define ('COMMENTS_SECTION_DEFAULT', 0);
define ('COMMENTS_DISABLE', 1);
define ('COMMENTS_ENABLE', 2);

// License settings (internal use only)
define ('PERMISSIONS_FIELD_LICENSE_URL', 1);
define ('PERMISSIONS_FIELD_COPYRIGHT_HOLDER', 2);
define ('PERMISSIONS_FIELD_COPYRIGHT_YEAR', 3);

import('lib.pkp.classes.submission.Submission');

class Article extends Submission {
	/**
	 * Constructor.
	 */
	function Article() {
		// Switch on meta-data adapter support.
		$this->setHasLoadableAdapters(true);

		parent::Submission();
	}

	/**
	 * @see Submission::getAssocType()
	 */
	function getAssocType() {
		return ASSOC_TYPE_ARTICLE;
	}

	/**
	 * Get "localized" article title (if applicable). DEPRECATED
	 * in favour of getLocalizedTitle.
	 * @return string
	 */
	function getArticleTitle() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedTitle();
	}

	/**
	 * Get "localized" article abstract (if applicable). DEPRECATED
	 * in favour of getLocalizedAbstract.
	 * @return string
	 */
	function getArticleAbstract() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedAbstract();
	}

	//
	// Get/set methods
	//

	/**
	 * Get ID of article.
	 * @return int
	 */
	function getArticleId() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getId();
	}

	/**
	 * Set ID of article.
	 * @param $articleId int
	 */
	function setArticleId($articleId) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->setId($articleId);
	}

	/**
	 * Return the "best" article ID -- If a public article ID is set,
	 * use it; otherwise use the internal article Id. (Checks the journal
	 * settings to ensure that the public ID feature is enabled.)
	 * @param $journal Object the journal this article is in
	 * @return string
	 */
	function getBestArticleId($journal = null) {
		// Retrieve the journal, if necessary.
		if (!isset($journal)) {
			$journalDao =& DAORegistry::getDAO('JournalDAO');
			$journal = $journalDao->getById($this->getJournalId());
		}

		if ($journal->getSetting('enablePublicArticleId')) {
			$publicArticleId = $this->getPubId('publisher-id');
			if (!empty($publicArticleId)) return $publicArticleId;
		}
		return $this->getId();
	}

	/**
	 * Get the localized copyright holder for this article, attaching it
	 * from journal settings as necessary.
	 * @param $preview boolean If true, preview the license; do not attach
	 */
	function getLocalizedCopyrightHolder($preview = false) {
		$copyrightHolders = $this->getCopyrightHolder(null, $preview);
		foreach (AppLocale::getLocalePrecedence() as $locale) {
			if (isset($copyrightHolders[$locale])) return $copyrightHolders[$locale];
		}
		// Fallback: return anything available
		return array_shift($copyrightHolders);
	}

	/**
	 * Get the license URL for this article, attaching it from journal
	 * settings as necessary.
	 * @param $preview boolean If true, preview the license; do not attach
	 */
	function getLicenseUrl($preview = false) {
		return $this->_getLicenseFieldValue(null, PERMISSIONS_FIELD_LICENSE_URL, $preview);
	}

	/**
	 * Get the copyright holder for this article, attaching it from journal
	 * settings as necessary.
	 * @param $locale string Locale
	 * @param $preview boolean If true, preview the license; do not attach
	 */
	function getCopyrightHolder($locale, $preview = false) {
		return $this->_getLicenseFieldValue($locale, PERMISSIONS_FIELD_COPYRIGHT_HOLDER, $preview);
	}

	/**
	 * Get the copyright year for this article, attaching it as necessary
	 * @param $preview boolean If true, preview the license; do not attach
	 */
	function getCopyrightYear($preview = false) {
		return $this->_getLicenseFieldValue(null, PERMISSIONS_FIELD_COPYRIGHT_YEAR, $preview);
	}

	/**
	 * Get a license field for this article, attaching it from journal
	 * settings as necessary.
	 * @param $locale string Locale
	 * @param $field int PERMISSIONS_FIELD_... Which to return
	 * @param $preview boolean If true, preview the license; do not attach
	 */
	function _getLicenseFieldValue($locale, $field, $preview = false) {
		// If possible, use the stored permissions info
		switch ($field) {
			case PERMISSIONS_FIELD_LICENSE_URL:
				$fieldValue = $this->getStoredLicenseURL();
				break;
			case PERMISSIONS_FIELD_COPYRIGHT_HOLDER:
				$fieldValue = $this->getStoredCopyrightHolder($locale);
				break;
			case PERMISSIONS_FIELD_COPYRIGHT_YEAR:
				$fieldValue = $this->getStoredCopyrightYear($locale);
				break;
			default: assert(false);
		}
		if (!empty($fieldValue)) {
			if ($locale === null || !is_array($fieldValue)) return $fieldValue;
			if (isset($fieldValue[$locale])) return $fieldValue[$locale];
			return null;
		}

		// Otherwise, get the permissions info from journal settings.
		$journalDao = DAORegistry::getDAO('JournalDAO');
		$journal =& $journalDao->getById($this->getJournalId());
		$licenseUrl = $journal->getSetting('licenseURL');
		switch($journal->getSetting('copyrightHolderType')) {
			case 'author':
				$copyrightHolder = array($journal->getPrimaryLocale() => $this->getAuthorString());
				break;
			case 'other':
				$copyrightHolder = $journal->getSetting('copyrightHolderOther');
				break;
			case 'journal':
			default:
				$copyrightHolder = $journal->getTitle(null);
				break;
		}
		$copyrightYear = date('Y');

		if (!$preview) {
			// If not previewing the license, attach to the article
			$this->setStoredLicenseURL($licenseUrl);
			$this->setStoredCopyrightHolder($copyrightHolder, null);
			$this->setStoredCopyrightYear($copyrightYear);
			$articleDao = DAORegistry::getDAO('ArticleDAO');
			$articleDao->updateLocaleFields($this);
		}

		switch ($field) {
			case PERMISSIONS_FIELD_LICENSE_URL:
				$fieldValue = $licenseUrl;
				break;
			case PERMISSIONS_FIELD_COPYRIGHT_HOLDER:
				$fieldValue = $copyrightHolder;
				break;
			case PERMISSIONS_FIELD_COPYRIGHT_YEAR:
				$fieldValue = $copyrightYear;
				break;
			default: assert(false);
		}

		// Return the fetched license field
		if ($locale === null || !is_array($fieldValue)) return $fieldValue;
		if (isset($fieldValue[$locale])) return $fieldValue[$locale];
		return null;
	}

	/**
	 * Get a public ID for this article.
	 * @param $pubIdType string One of the NLM pub-id-type values or
	 * 'other::something' if not part of the official NLM list
	 * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
	 * @var $preview boolean If true, generate a non-persisted preview only.
	 */
	function getPubId($pubIdType, $preview = false) {
		// FIXME: Move publisher-id to PID plug-in.
		if ($pubIdType === 'publisher-id') {
			$pubId = $this->getStoredPubId($pubIdType);
			return ($pubId ? $pubId : null);
		}

		$pubIdPlugins =& PluginRegistry::loadCategory('pubIds', true, $this->getJournalId());

		if (is_array($pubIdPlugins)) {
			foreach ($pubIdPlugins as $pubIdPlugin) {
				if ($pubIdPlugin->getPubIdType() == $pubIdType) {
					// If we already have an assigned ID, use it.
					$storedId = $this->getStoredPubId($pubIdType);
					if (!empty($storedId)) return $storedId;

					return $pubIdPlugin->getPubId($this, $preview);
				}
			}
		}
		return null;
	}

	/**
	 * Get ID of journal.
	 * @return int
	 */
	function getJournalId() {
		return $this->getData('journalId');
	}

	/**
	 * Set ID of journal.
	 * @param $journalId int
	 */
	function setJournalId($journalId) {
		return $this->setData('journalId', $journalId);
	}

	/**
	 * Get ID of article's section.
	 * @return int
	 */
	function getSectionId() {
		return $this->getData('sectionId');
	}

	/**
	 * Set ID of article's section.
	 * @param $sectionId int
	 */
	function setSectionId($sectionId) {
		return $this->setData('sectionId', $sectionId);
	}

	/**
	 * Get stored public ID of the submission.
	 * @param $pubIdType string One of the NLM pub-id-type values or
	 * 'other::something' if not part of the official NLM list
	 * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
	 * @return int
	 */
	function getStoredPubId($pubIdType) {
		return $this->getData('pub-id::'.$pubIdType);
	}

	/**
	 * Set the stored public ID of the submission.
	 * @param $pubIdType string One of the NLM pub-id-type values or
	 * 'other::something' if not part of the official NLM list
	 * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
	 * @param $pubId string
	 */
	function setStoredPubId($pubIdType, $pubId) {
		return $this->setData('pub-id::'.$pubIdType, $pubId);
	}

	/**
	 * Get stored copyright holder for the submission.
	 * @param $locale string locale
	 * @return string
	 */
	function getStoredCopyrightHolder($locale) {
		return $this->getData('copyrightHolder', $locale);
	}

	/**
	 * Set the stored copyright holder for the submission.
	 * @param $copyrightHolder string Copyright holder
	 * @param $locale string locale
	 */
	function setStoredCopyrightHolder($copyrightHolder, $locale) {
		return $this->setData('copyrightHolder', $copyrightHolder, $locale);
	}

	/**
	 * Get stored copyright year for the submission.
	 * @return string
	 */
	function getStoredCopyrightYear() {
		return $this->getData('copyrightYear');
	}

	/**
	 * Set the stored copyright year for the submission.
	 * @param $copyrightYear string Copyright holder
	 */
	function setStoredCopyrightYear($copyrightYear) {
		return $this->setData('copyrightYear', $copyrightYear);
	}

	/**
	 * Get stored license URL for the submission content.
	 * @return string
	 */
	function getStoredLicenseURL() {
		return $this->getData('licenseURL');
	}

	/**
	 * Set the stored license URL for the submission content.
	 * @param $license string License of submission content
	 */
	function setStoredLicenseURL($licenseUrl) {
		return $this->setData('licenseURL', $licenseUrl);
	}

	/**
	 * Get title of article's section.
	 * @return string
	 */
	function getSectionTitle() {
		return $this->getData('sectionTitle');
	}

	/**
	 * Set title of article's section.
	 * @param $sectionTitle string
	 */
	function setSectionTitle($sectionTitle) {
		return $this->setData('sectionTitle', $sectionTitle);
	}

	/**
	 * Get section abbreviation.
	 * @return string
	 */
	function getSectionAbbrev() {
		return $this->getData('sectionAbbrev');
	}

	/**
	 * Set section abbreviation.
	 * @param $sectionAbbrev string
	 */
	function setSectionAbbrev($sectionAbbrev) {
		return $this->setData('sectionAbbrev', $sectionAbbrev);
	}

	/**
	 * Return the localized discipline. DEPRECATED in favour of
	 * getLocalizedDiscipline.
	 * @return string
	 */
	function getArticleDiscipline() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedDiscipline();
	}

	/**
	 * Return the localized subject classification. DEPRECATED
	 * in favour of getLocalizedSubjectClass.
	 * @return string
	 */
	function getArticleSubjectClass() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedSubjectClass();
	}

	/**
	 * Return the localized subject. DEPRECATED in favour of
	 * getLocalizedSubject.
	 * @return string
	 */
	function getArticleSubject() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedSubject();
	}

	/**
	 * Return the localized geographical coverage. DEPRECATED
	 * in favour of getLocalizedCoverageGeo.
	 * @return string
	 */
	function getArticleCoverageGeo() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedCoverageGeo();
	}

	/**
	 * Return the localized chronological coverage. DEPRECATED
	 * in favour of getLocalizedCoverageChron.
	 * @return string
	 */
	function getArticleCoverageChron() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedCoverageChron();
	}

	/**
	 * Return the localized sample coverage. DEPRECATED in favour
	 * of getLocalizedCoverageSample.
	 * @return string
	 */
	function getArticleCoverageSample() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedCoverageSample();
	}

	/**
	 * Return the localized type (method/approach). DEPRECATED
	 * in favour of getLocalizedType.
	 * @return string
	 */
	function getArticleType() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedType();
	}

	/**
	 * Return the localized sponsor. DEPRECATED in favour
	 * of getLocalizedSponsor.
	 * @return string
	 */
	function getArticleSponsor() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedSponsor();
	}

	/**
	 * Get the localized article cover filename. DEPRECATED
	 * in favour of getLocalizedFileName.
	 * @return string
	 */
	function getArticleFileName() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedFileName('fileName');
	}

	/**
	 * Get the localized article cover width. DEPRECATED
	 * in favour of getLocalizedWidth.
	 * @return string
	 */
	function getArticleWidth() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedWidth();
	}

	/**
	 * Get the localized article cover height. DEPRECATED
	 * in favour of getLocalizedHeight.
	 * @return string
	 */
	function getArticleHeight() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedHeight();
	}

	/**
	 * Get the localized article cover filename on the uploader's computer.
	 * DEPRECATED in favour of getLocalizedOriginalFileName.
	 * @return string
	 */
	function getArticleOriginalFileName() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedData('originalFileName');
	}

	/**
	 * Get the localized article cover alternate text. DEPRECATED
	 * in favour of getLocalizedCoverPageAltText.
	 * @return string
	 */
	function getArticleCoverPageAltText() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedCoverPageAltText();
	}

	/**
	 * Get the flag indicating whether or not to show
	 * an article cover page. DEPRECATED in favour of
	 * getLocalizedShowCoverPage.
	 * @return string
	 */
	function getArticleShowCoverPage() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedShowCoverPage();
	}

	/**
	 * Get comments to editor.
	 * @return string
	 */
	function getCommentsToEditor() {
		return $this->getData('commentsToEditor');
	}

	/**
	 * Set comments to editor.
	 * @param $commentsToEditor string
	 */
	function setCommentsToEditor($commentsToEditor) {
		return $this->setData('commentsToEditor', $commentsToEditor);
	}

	/**
	 * Get current review round.
	 * @return int
	 */
	function getCurrentRound() {
		return $this->getData('currentRound');
	}

	/**
	 * Set current review round.
	 * @param $currentRound int
	 */
	function setCurrentRound($currentRound) {
		return $this->setData('currentRound', $currentRound);
	}

	/**
	 * Get editor file id.
	 * @return int
	 */
	function getEditorFileId() {
		return $this->getData('editorFileId');
	}

	/**
	 * Set editor file id.
	 * @param $editorFileId int
	 */
	function setEditorFileId($editorFileId) {
		return $this->setData('editorFileId', $editorFileId);
	}

	/**
	 * get expedited
	 * @return boolean
	 */
	function getFastTracked() {
		return $this->getData('fastTracked');
	}

	/**
	 * set fastTracked
	 * @param $fastTracked boolean
	 */
	function setFastTracked($fastTracked) {
		return $this->setData('fastTracked',$fastTracked);
	}

	/**
	 * Return option selection indicating if author should be hidden in issue ToC.
	 * @return int AUTHOR_TOC_...
	 */
	function getHideAuthor() {
		return $this->getData('hideAuthor');
	}

	/**
	 * Set option selection indicating if author should be hidden in issue ToC.
	 * @param $hideAuthor int AUTHOR_TOC_...
	 */
	function setHideAuthor($hideAuthor) {
		return $this->setData('hideAuthor', $hideAuthor);
	}

	/**
	 * Return locale string corresponding to RT comments status.
	 * @return string
	 */
	function getCommentsStatusString() {
		switch ($this->getCommentsStatus()) {
			case COMMENTS_DISABLE:
				return 'article.comments.disable';
			case COMMENTS_ENABLE:
				return 'article.comments.enable';
			default:
				return 'article.comments.sectionDefault';
		}
	}

	/**
	 * Return boolean indicating if article RT comments should be enabled.
	 * Checks both the section and article comments status. Article status
	 * overrides section status.
	 * @return int
	 */
	function getEnableComments() {
		switch ($this->getCommentsStatus()) {
			case COMMENTS_DISABLE:
				return false;
			case COMMENTS_ENABLE:
				return true;
			case COMMENTS_SECTION_DEFAULT:
				$sectionDao =& DAORegistry::getDAO('SectionDAO');
				$section =& $sectionDao->getSection($this->getSectionId(), $this->getJournalId(), true);
				if ($section->getDisableComments()) {
					return false;
				} else {
					return true;
				}
		}
	}

	/**
	 * Get an associative array matching RT comments status codes with locale strings.
	 * @return array comments status => localeString
	 */
	function &getCommentsStatusOptions() {
		static $commentsStatusOptions = array(
			COMMENTS_SECTION_DEFAULT => 'article.comments.sectionDefault',
			COMMENTS_DISABLE => 'article.comments.disable',
			COMMENTS_ENABLE => 'article.comments.enable'
		);
		return $commentsStatusOptions;
	}

	/**
	 * Get an array of user IDs associated with this article
	 * @param $authors boolean
	 * @param $reviewers boolean
	 * @param $editors boolean
	 * @param $proofreader boolean
	 * @param $copyeditor boolean
	 * @param $layoutEditor boolean
	 * @return array User IDs
	 */
	function getAssociatedUserIds($authors = true, $reviewers = true, $editors = true, $proofreader = true, $copyeditor = true, $layoutEditor = true) {
		$articleId = $this->getId();
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');

		$userIds = array();

		if($authors) {
			$userId = $this->getUserId();
			if ($userId) $userIds[] = array('id' => $userId, 'role' => 'author');
		}

		if($editors) {
			$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
			$editAssignments =& $editAssignmentDao->getEditorAssignmentsByArticleId($articleId);
			while ($editAssignment =& $editAssignments->next()) {
				$userId = $editAssignment->getEditorId();
				if ($userId) $userIds[] = array('id' => $userId, 'role' => 'editor');
				unset($editAssignment);
			}
		}

		if($copyeditor) {
			$copyedSignoff = $signoffDao->build('SIGNOFF_COPYEDITING_INITIAL', ASSOC_TYPE_ARTICLE, $articleId);
			$userId = $copyedSignoff->getUserId();
			if ($userId) $userIds[] = array('id' => $userId, 'role' => 'copyeditor');
		}

		if($layoutEditor) {
			$layoutSignoff = $signoffDao->build('SIGNOFF_LAYOUT', ASSOC_TYPE_ARTICLE, $articleId);
			$userId = $layoutSignoff->getUserId();
			if ($userId) $userIds[] = array('id' => $userId, 'role' => 'layoutEditor');
		}

		if($proofreader) {
			$proofSignoff = $signoffDao->build('SIGNOFF_PROOFREADING_PROOFREADER', ASSOC_TYPE_ARTICLE, $articleId);
			$userId = $proofSignoff->getUserId();
			if ($userId) $userIds[] = array('id' => $userId, 'role' => 'proofreader');
		}

		if($reviewers) {
			$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
			$reviewAssignments =& $reviewAssignmentDao->getBySubmissionId($articleId);
			foreach ($reviewAssignments as $reviewAssignment) {
				$userId = $reviewAssignment->getReviewerId();
				if ($userId) $userIds[] = array('id' => $userId, 'role' => 'reviewer');
				unset($reviewAssignment);
			}
		}

		return $userIds;
	}

	/**
	 * Get the signoff for this article
	 * @param $signoffType string
	 * @return Signoff
	 */
	function getSignoff($signoffType) {
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');
		return $signoffDao->build($signoffType, ASSOC_TYPE_ARTICLE, $this->getId());
	}

	/**
	 * Get the file for this article at a given signoff stage
	 * @param $signoffType string
	 * @param $idOnly boolean Return only file ID
	 * @return ArticleFile
	 */
	function &getFileBySignoffType($signoffType, $idOnly = false) {
		$articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');

		$signoff = $signoffDao->build($signoffType, ASSOC_TYPE_ARTICLE, $this->getId());
		if (!$signoff) {
			$returner = false;
			return $returner;
		}

		if ($idOnly) {
			$returner = $signoff->getFileId();
			return $returner;
		}

		$articleFile =& $articleFileDao->getArticleFile($signoff->getFileId(), $signoff->getFileRevision());
		return $articleFile;
	}

	/**
	 * Get the user associated with a given signoff and this article
	 * @param $signoffType string
	 * @return User
	 */
	function &getUserBySignoffType($signoffType) {
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');

		$signoff = $signoffDao->build($signoffType, ASSOC_TYPE_ARTICLE, $this->getId());
		if (!$signoff) {
			$returner = false;
			return $returner;
		}

		$user =& $userDao->getById($signoff->getUserId());
		return $user;
	}

	/**
	 * Get the user id associated with a given signoff and this article
	 * @param $signoffType string
	 * @return int
	 */
	function getUserIdBySignoffType($signoffType) {
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');

		$signoff = $signoffDao->build($signoffType, ASSOC_TYPE_ARTICLE, $this->getId());
		if (!$signoff) return false;

		return $signoff->getUserId();
	}

	/**
	 * Get starting page of an article.
	 * @return int
	 */
	function getStartingPage() {
		preg_match('/^[^\d]*(\d+)\D*(.*)$/', $this->getPages(), $pages);
		return $pages[1];
	}

	/**
	 * Get ending page of an article.
	 * @return int
	 */
	function getEndingPage() {
		preg_match('/^[^\d]*(\d+)\D*(.*)$/', $this->getPages(), $pages);
		return $pages[2];
	}
}

?>
