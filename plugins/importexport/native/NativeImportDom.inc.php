<?php

/**
 * NativeImportDom.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * Native import/export plugin DOM functions for import
 *
 * $Id$
 */

import('xml.XMLWriter');

class NativeImportDom {
	function importArticles(&$journal, &$nodes, &$issue, &$section, &$articles, &$errors, &$user, $isCommandLine) {
		$articles = array();
		$dependentItems = array();
		$hasErrors = false;
		foreach ($nodes as $node) {
			$result = NativeImportDom::handleArticleNode($journal, $node, $issue, $section, $article, $publishedArticle, $articleErrors, $user, $isCommandLine, $dependentItems);
			if ($result) {
				$articles[] = $article;
			} else {
				$errors = array_merge($errors, $articleErrors);
				$hasErrors = true;
			}
		}
		if ($hasErrors) {
			NativeImportDom::cleanupFailure ($dependentItems);
			return false;
		}
		return true;
	}

	function importArticle(&$journal, &$node, &$issue, &$section, &$article, &$errors, &$user, $isCommandLine) {
		$dependentItems = array();
		$result = NativeImportDom::handleArticleNode($journal, $node, $issue, $section, $article, $publishedArticle, $errors, $user, $isCommandLine, $dependentItems);
		if (!$result) {
			NativeImportDom::cleanupFailure ($dependentItems);
		}
		return $result;
	}

	function importIssues(&$journal, &$issueNodes, &$issues, &$errors, &$user, $isCommandLine) {
		$dependentItems = array();
		$errors = array();
		$issues = array();
		$hasErrors = false;
		foreach ($issueNodes as $issueNode) {
			$result = &NativeImportDom::importIssue($journal, $issueNode, $issue, $issueErrors, $user, $isCommandLine, $dependentItems, false);
			if ($result) {
				// Success. Add this issue to the list of
				// successfully imported issues.
				$issues[] = $issue;
			} else {
				// Failure. Record the errors and keep trying
				// with the next issue, even though we will just
				// delete any successful issues, so that the
				// user can be presented with as many relevant
				// error messages as possible.
				$errors = array_merge($errors, $issueErrors);
				$hasErrors = true;
			}
		}
		if ($hasErrors) {
			// There were errors. Delete all the issues we've
			// successfully created.
			NativeImportDom::cleanupFailure ($dependentItems);
			$issueDao = &DAORegistry::getDAO('IssueDAO');
			foreach ($issues as $issue) {
				$issueDao->deleteIssue($issue);
			}
			return false;
		}
		return true;
	}

	function importIssue(&$journal, &$issueNode, &$issue, &$errors, &$user, $isCommandLine, &$dependentItems, $cleanupErrors = true) {
		$errors = array();
		$issue = null;
		$hasErrors = false;

		$issueDao = &DAORegistry::getDAO('IssueDAO');
		$issue = new Issue();
		$issue->setJournalId($journal->getJournalId());

		/* --- Set title, description, volume, number, and year --- */

		if (($node = $issueNode->getChildByName('title'))) {
			$issue->setTitle($node->getValue());
		} else {
			$errors[] = array('plugins.importexport.native.import.error.titleMissing', array());
			// Set a placeholder title so that further errors are
			// somewhat meaningful; this placeholder will not be
			// inserted into the database.
			$issue->setTitle(Locale::translate('plugins.importexport.native.import.error.defaultTitle'));
			$hasErrors = true;
		}
		if (($node = $issueNode->getChildByName('description'))) $issue->setDescription($node->getValue());

		if (($node = $issueNode->getChildByName('volume'))) $issue->setVolume($node->getValue());
		if (($node = $issueNode->getChildByName('number'))) $issue->setNumber($node->getValue());
		if (($node = $issueNode->getChildByName('year'))) $issue->setYear($node->getValue());

		/* --- Set date published --- */

		if (($node = $issueNode->getChildByName('date_published'))) {
			$publishedDate = strtotime($node->getValue());
			if ($publishedDate === -1) {
				$errors[] = array('plugins.importexport.native.import.error.invalidDate', array('value' => $node->getValue()));
				if ($cleanupErrors) {
					NativeImportDom::cleanupFailure ($dependentItems);
				}
				return false;
			} else {
				$issue->setDatePublished($publishedDate);
			}
		}

		/* --- Set attributes: Identification type, published, current, public ID --- */

		switch(($value = $issueNode->getAttribute('identification'))) {
			case 'num_vol_year':
				$issue->setLabelFormat(ISSUE_LABEL_NUM_VOL_YEAR);
				break;
			case 'vol_year':
				$issue->setLabelFormat(ISSUE_LABEL_VOL_YEAR);
				break;
			case 'year':
				$issue->setLabelFormat(ISSUE_LABEL_YEAR);
				break;
			case 'title':
			case '':
			case null:
				$issue->setLabelFormat(ISSUE_LABEL_TITLE);
				break;
			default:
				$errors[] = array('plugins.importexport.native.import.error.unknownIdentificationType', array('identificationType' => $value, 'issueTitle' => $issue->getTitle()));
				$hasErrors = true;
				break;
		}

		switch(($value = $issueNode->getAttribute('published'))) {
			case 'true':
				$issue->setPublished(1);
				break;
			case 'false':
			case '':
			case null:
				$issue->setPublished(0);
				break;
			default:
				$errors[] = array('plugins.importexport.native.import.error.invalidBooleanValue', array('value' => $value));
				$hasErrors = true;
				break;
		}

		switch(($value = $issueNode->getAttribute('current'))) {
			case 'true':
				$issue->setCurrent(1);
				break;
			case 'false':
			case '':
			case null:
				$issue->setCurrent(0);
				break;
			default:
				$errors[] = array('plugins.importexport.native.import.error.invalidBooleanValue', array('value' => $value));
				$hasErrors = true;
				break;
		}

		if (($value = $issueNode->getAttribute('public_id')) != '') {
			$anotherIssue = $issueDao->getIssueByPublicIssueId($value, $journal->getJournalId());
			if ($anotherIssue) {
				$errors[] = array('plugins.importexport.native.import.error.duplicatePublicId', array('issueTitle' => $issue->getIssueIdentification(), 'otherIssueTitle' => $anotherIssue->getIssueIdentification()));
				$hasErrors = true;
			} else {
				$issue->setPublicIssueId($value);
			}
		}

		/* --- Access Status --- */

		$node = $issueNode->getChildByName('open_access');
		$issue->setAccessStatus($node?1:0);

		if (($node = $issueNode->getChildByName('access_date'))) {
			$accessDate = strtotime($node->getValue());
			if ($accessDate === -1) {
				$errors[] = array('plugins.importexport.native.import.error.invalidDate', array('value' => $node->getValue()));
				$hasErrors = true;
			} else {
				$issue->setOpenAccessDate($accessDate);
			}
		}

		/* --- Temporarily set values that may be changed later --- */

		$issue->setShowCoverPage(0);

		/* --- All processing that does not require an inserted issue ID
		   --- has been performed by this point. If there were no errors
		   --- then insert the issue and carry on. If there were errors,
		   --- then abort without performing the insertion. */

		if ($hasErrors) {
			$issue = null;
			if ($cleanupErrors) {
				NativeImportDom::cleanupFailure ($dependentItems);
			}
			return false;
		} else {
			if ($issue->getCurrent()) {
				$issueDao->updateCurrentIssue($journal->getJournalId());
			}
			$issue->setIssueId($issueDao->insertIssue($issue));
			$dependentItems[] = array('issue', $issue);
		}

		/* --- Handle cover --- */

		if (($node = $issueNode->getChildByName('cover'))) {
			if (!NativeImportDom::handleCoverNode($journal, $node, $issue, $coverErrors, $isCommandLine)) {
				$errors = array_merge($errors, $coverErrors);
				$hasErrors = true;
			}
		}

		/* --- Handle sections --- */
		for ($index = 0; ($node = $issueNode->getChildByName('section', $index)); $index++) {
			if (!NativeImportDom::handleSectionNode($journal, $node, $issue, $sectionErrors, $user, $isCommandLine, $dependentItems)) {
				$errors = array_merge($errors, $sectionErrors);
				$hasErrors = true;
			}
		}

		/* --- See if any errors occurred since last time we checked.
		   --- If so, delete the created issue and return failure.
		   --- Otherwise, the whole process was successful. */

		if ($hasErrors) {
			$issueDao->deleteIssue($issue);
			$issue = null;
			if ($cleanupErrors) {
				NativeImportDom::cleanupFailure ($dependentItems);
			}
			return false;
		}

		$issueDao->updateIssue($issue);
		return true;
	}

	function handleCoverNode(&$journal, &$coverNode, &$issue, &$errors, $isCommandLine) {
		$errors = array();
		$hasErrors = false;

		$issue->setShowCoverPage(1);

		if (($node = $coverNode->getChildByName('caption'))) $issue->setCoverPageDescription($node->getValue());

		if (($node = $coverNode->getChildByName('image'))) {
			import('file.PublicFileManager');
			$publicFileManager = &new PublicFileManager();
			$newName = 'cover_' . $issue->getIssueId() . '.';

			if (($href = $node->getChildByName('href'))) {
				$url = $href->getAttribute('src');
				if ($isCommandLine || NativeImportDom::isAllowedMethod($url)) {
					if ($isCommandLine && NativeImportDom::isRelativePath($url)) {
						// The command-line tool does a chdir; we need to prepend the original pathname to relative paths so we're not looking in the wrong place.
						$url = PWD . '/' . $url;
					}

					$originalName = basename($url);
					$newName .= $publicFileManager->getExtension($originalName);
					if (!$publicFileManager->copyJournalFile($journal->getJournalId(), $url, $newName)) {
						$errors[] = array('plugins.importexport.native.import.error.couldNotCopy', array('url' => $url));
						$hasErrors = true;
					}
					$issue->setFileName($newName);
					$issue->setOriginalFileName($originalName);
				}
			}
			if (($embed = $node->getChildByName('embed'))) {
				if (($type = $embed->getAttribute('encoding')) !== 'base64') {
					$errors[] = array('plugins.importexport.native.import.error.unknownEncoding', array('type' => $type));
					$hasErrors = true;
				} else {
					$originalName = $embed->getAttribute('filename');
					$newName .= $publicFileManager->getExtension($originalName);
					$issue->setFileName($newName);
					$issue->setOriginalFileName($originalName);
					if ($publicFileManager->writeJournalFile($journal->getJournalId(), $newName, base64_decode($embed->getValue()))===false) {
						$errors[] = array('plugins.importexport.native.import.error.couldNotWriteFile', array('originalName' => $originalName));
						$hasErrors = true;
					}
				}
			}
		}

		if ($hasErrors) {
			return false;
		}
		return true;
	}

	function isRelativePath($url) {
		// FIXME This is not very comprehensive, but will work for now.
		if (NativeImportDom::isAllowedMethod($url)) return false;
		if ($url[0] == '/') return false;
		return true;
	}

	function isAllowedMethod($url) {
		$allowedPrefixes = array(
			'http://',
			'ftp://',
			'https://',
			'ftps://'
		);
		foreach ($allowedPrefixes as $prefix) {
			if (substr($url, 0, strlen($prefix)) === $prefix) return true;
		}
		return false;
	}

	function handleSectionNode(&$journal, &$sectionNode, &$issue, &$errors, &$user, $isCommandLine, &$dependentItems) {
		$sectionDao = &DAORegistry::getDAO('SectionDAO');

		$errors = array();

		// The following page or two is responsible for locating an
		// existing section based on title and/or abbrev, or, if none
		// can be found, creating a new one.

		if (!($titleNode = $sectionNode->getChildByName('title'))) {
			$errors[] = array('plugins.importexport.native.import.error.sectionTitleMissing', array('issueTitle' => $issue->getIssueIdentification()));
			return false;
		}
		$title = $titleNode->getValue();

		if (($abbrevNode = $sectionNode->getChildByName('abbrev'))) $abbrev = $abbrevNode->getValue();
		else $abbrev = null;

		// $title and, optionally, $abbrev contain information that can
		// be used to locate an existing section. Otherwise, we'll
		// create a new one. If $title and $abbrev each match an
		// existing section, but not the same section, throw an error.
		$section = $abbrevSection = null;
		if (!empty($title) && !empty($abbrev)) {
			$section = $sectionDao->getSectionByTitleAndAbbrev($title, $abbrev, $journal->getJournalId());
			if (!$section) $abbrevSection = $sectionDao->getSectionByAbbrev($abbrev, $journal->getJournalId());
		}
		if (!$section) {
			$section = $sectionDao->getSectionByTitle($title, $journal->getJournalId());
			if ($section && $abbrevSection && $section->getSectionId() != $abbrevSection->getSectionId()) {
				// Mismatching sections found. Throw an error.
				$errors[] = array('plugins.importexport.native.import.error.sectionMismatch', array('sectionTitle' => $title, 'sectionAbbrev' => $abbrev, 'issueTitle' => $issue->getIssueIdentification()));
				return false;
			}
			if (!$section) {
				// The section was not matched. Create one.
				// Note that because sections are global-ish,
				// we're not maintaining a list of created
				// sections to delete in case the import fails.
				$section = &new Section();

				// FIXME: This should handle localized sections
				// with more dignity.
				$section->setTitle($title);
				$section->setAbbrev($abbrev);
				$section->setJournalId($journal->getJournalId());
				// Kludge: We'll assume that there are less than
				// 10,000 sections; thus when the sections are
				// renumbered, this one should be last on the
				// list.
				$section->setSequence(10000);

				$section->setMetaIndexed(1);
				$section->setEditorRestricted(1);
				$section->setSectionId($sectionDao->insertSection($section));
				$sectionDao->resequenceSections($journal->getJournalId());
			}
		}

		// $section *must* now contain a valid section, whether it was
		// found amongst existing sections or created anew.
		$hasErrors = false;
		for ($index = 0; ($node = $sectionNode->getChildByName('article', $index)); $index++) {
			if (!NativeImportDom::handleArticleNode($journal, $node, $issue, $section, $article, $publishedArticle, $articleErrors, $user, $isCommandLine, $dependentItems)) {
				$errors = array_merge($errors, $articleErrors);
				$hasErrors = true;
			}
		}
		if ($hasErrors) return false;

		return true;
	}

	function handleArticleNode(&$journal, &$articleNode, &$issue, &$section, &$article, &$publishedArticle, &$errors, &$user, $isCommandLine, &$dependentItems) {
		$errors = array();

		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
		$articleDao = &DAORegistry::getDAO('ArticleDAO');

		$article = new Article();
		$article->setJournalId($journal->getJournalId());
		$article->setUserId($user->getUserId());
		$article->setSectionId($section->getSectionId());
		$article->setStatus(STATUS_PUBLISHED);
		$article->setSubmissionProgress(0);
		$article->setDateSubmitted(Core::getCurrentDate());
		$article->stampStatusModified();

		for ($index=0; ($node = $articleNode->getChildByName('title', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '' || $locale == Locale::getLocale()) {
				$article->setTitle($node->getValue());
			} elseif ($locale == $journal->getSetting('alternateLocale1')) {
				$article->setTitleAlt1($node->getValue());
			} elseif ($locale == $journal->getSetting('alternateLocale2')) {
				$article->setTitleAlt2($node->getValue());
			} else {
				$errors[] = array('plugins.importexport.native.import.error.articleTitleLocaleUnsupported', array('issueTitle' => $issue->getIssueIdentification(), 'sectionTitle' => $section->getSectionTitle(), 'articleTitle' => $node->getValue(), 'locale' => $locale));
				return false;
			}
		}
		if ($article->getTitle() == '') {
			$errors[] = array('plugins.importexport.native.import.error.articleTitleMissing', array('issueTitle' => $issue->getIssueIdentification(), 'sectionTitle' => $section->getSectionTitle()));
			return false;
		}

		for ($index=0; ($node = $articleNode->getChildByName('abstract', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '' || $locale == Locale::getLocale()) {
				$article->setAbstract($node->getValue());
			} elseif ($locale == $journal->getSetting('alternateLocale1')) {
				$article->setAbstractAlt1($node->getValue());
			} elseif ($locale == $journal->getSetting('alternateLocale2')) {
				$article->setAbstractAlt2($node->getValue());
			} else {
				$errors[] = array('plugins.importexport.native.import.error.articleAbstractLocaleUnsupported', array('issueTitle' => $issue->getIssueIdentification(), 'sectionTitle' => $section->getSectionTitle(), 'articleTitle' => $article->getTitle(), 'locale' => $locale));
				return false;
			}
		}

		if (($indexingNode = $articleNode->getChildByName('indexing'))) {
			if (($node = $indexingNode->getChildByName('discipline'))) $article->setDiscipline($node->getValue());
			if (($node = $indexingNode->getChildByName('subject'))) $article->setSubject($node->getValue());
			if (($node = $indexingNode->getChildByName('subject_class'))) $article->setSubjectClass($node->getValue());
			if (($coverageNode = $indexingNode->getChildByName('coverage'))) {
				if (($node = $coverageNode->getChildByName('geographical'))) $article->setCoverageGeo($node->getValue());
				if (($node = $coverageNode->getChildByName('chronological'))) $article->setCoverageChron($node->getValue());
				if (($node = $coverageNode->getChildByName('sample'))) $article->setCoverageSample($node->getValue());
			}
		}

		if (($node = $articleNode->getChildByName('pages'))) $article->setPages($node->getValue());

		$authors = array();
		for ($index=0; ($authorNode = $articleNode->getChildByName('author', $index)); $index++) {
			$author = &new Author();
			if (($node = $authorNode->getChildByName('firstname'))) $author->setFirstName($node->getValue());
			if (($node = $authorNode->getChildByName('middlename'))) $author->setMiddleName($node->getValue());
			if (($node = $authorNode->getChildByName('lastname'))) $author->setLastName($node->getValue());
			if (($node = $authorNode->getChildByName('affiliation'))) $author->setAffiliation($node->getValue());
			if (($node = $authorNode->getChildByName('email'))) $author->setEmail($node->getValue());
			if (($node = $authorNode->getChildByName('biography'))) $author->setBiography($node->getValue());

			$author->setPrimaryContact($authorNode->getAttribute('primary_contact')==='true'?1:0);
			$author->setSequence($index+1);

			$authors[] = $author;
		}
		$article->setAuthors($authors);

		$articleDao->insertArticle($article);
		$dependentItems[] = array('article', $article);

		// Create submission mangement records
		$copyeditorSubmissionDao = &DAORegistry::getDAO('CopyeditorSubmissionDAO');
		$copyeditorSubmission = &new CopyeditorSubmission();
		$copyeditorSubmission->setArticleId($article->getArticleId());
		$copyeditorSubmission->setCopyeditorId(0);
		$copyeditorSubmissionDao->insertCopyeditorSubmission($copyeditorSubmission);

		$layoutDao = &DAORegistry::getDAO('LayoutAssignmentDAO');
		$layoutAssignment = &new LayoutAssignment();
		$layoutAssignment->setArticleId($article->getArticleId());
		$layoutAssignment->setEditorId(0);
		$layoutDao->insertLayoutAssignment($layoutAssignment);

		$proofAssignmentDao = &DAORegistry::getDAO('ProofAssignmentDAO');
		$proofAssignment = &new ProofAssignment();
		$proofAssignment->setArticleId($article->getArticleId());
		$proofAssignment->setProofreaderId(0);
		$proofAssignmentDao->insertProofAssignment($proofAssignment);

		// Log the import in the article event log.
		import('article.log.ArticleLog');
		import('article.log.ArticleEventLogEntry');
		ArticleLog::logEvent(
			$article->getArticleId(),
			ARTICLE_LOG_ARTICLE_IMPORT,
			ARTICLE_LOG_TYPE_DEFAULT,
			0,
			'log.imported',
			array('userName' => $user->getFullName(), 'articleId' => $article->getArticleId())
		);

		// Insert published article entry.
		$publishedArticle = &new PublishedArticle();
		$publishedArticle->setArticleId($article->getArticleId());
		$publishedArticle->setIssueId($issue->getIssueId());
		
		if (($node = $articleNode->getChildByName('date_published'))) {
			$publishedDate = strtotime($node->getValue());
			if ($publishedDate === -1) {
				$errors[] = array('plugins.importexport.native.import.error.invalidDate', array('value' => $node->getValue()));
				return false;
			} else {
				$publishedArticle->setDatePublished($publishedDate);
			}
		}
		$node = $articleNode->getChildByName('open_access');
		$publishedArticle->setAccessStatus($node?1:0);

		// Kludge: This article should be last on the list. We resequence
		// the articles at the end of this code to make the seq meaningful.
		$publishedArticle->setSeq(100000);

		$publishedArticle->setViews(0);
		$publishedArticle->setPublicArticleId($articleNode->getAttribute('public_id'));

		$publishedArticle->setPubId($publishedArticleDao->insertPublishedArticle($publishedArticle));

		$publishedArticleDao->resequencePublishedArticles($section->getSectionId(), $issue->getIssueId());

		/* --- Galleys (html or otherwise handled simultaneously) --- */
		import('file.ArticleFileManager');
		$articleFileManager = &new ArticleFileManager($article->getArticleId());
		$galleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');

		$galleyCount = 0;
		for ($index=0; $index < count($articleNode->children); $index++) {
			$galleyNode = &$articleNode->children[$index];
			if ($galleyNode->getName() == 'htmlgalley') $isHtml = true;
			elseif ($galleyNode->getName() == 'galley') $isHtml = false;
			else continue;

			if ($isHtml) $galley = &new ArticleHtmlGalley();
			else $galley = &new ArticleGalley();

			$galley->setArticleId($article->getArticleId());
			$galley->setSequence(++$galleyCount);

			if (!($node = $galleyNode->getChildByName('label'))) {
				$errors[] = array('plugins.importexport.native.import.error.galleyLabelMissing', array('articleTitle' => $article->getTitle(), 'issueTitle' => $issue->getIssueIdentification(), 'sectionTitle' => $section->getSectionTitle()));
				return false;
			}
			$galley->setLabel($node->getValue());

			/* --- Galley File --- */

			if (!($node = $galleyNode->getChildByName('file'))) {
				$errors[] = array('plugins.importexport.native.import.error.galleyFileMissing', array('articleTitle' => $article->getTitle(), 'sectionTitle' => $section->getSectionTitle(), 'issueTitle' => $issue->getIssueIdentification()));
				return false;
			}

			if (($href = $node->getChildByName('href'))) {
				$url = $href->getAttribute('src');
				if ($isCommandLine || NativeImportDom::isAllowedMethod($url)) {
					if ($isCommandLine && NativeImportDom::isRelativePath($url)) {
						// The command-line tool does a chdir; we need to prepend the original pathname to relative paths so we're not looking in the wrong place.
						$url = PWD . '/' . $url;
					}

					if (($fileId = $articleFileManager->copyPublicFile($url, $href->getAttribute('mime_type')))===false) {
						$errors[] = array('plugins.importexport.native.import.error.couldNotCopy', array('url' => $url));
						return false;
					}
				}
			}
			if (($embed = $node->getChildByName('embed'))) {
				if (($type = $embed->getAttribute('encoding')) !== 'base64') {
					$errors[] = array('plugins.importexport.native.import.error.unknownEncoding', array('type' => $type));
					return false;
				}
				$originalName = $embed->getAttribute('filename');
				if (($fileId = $articleFileManager->writePublicFile($originalName, base64_decode($embed->getValue()), $embed->getAttribute('mime_type')))===false) {
					$errors[] = array('plugins.importexport.native.import.error.couldNotWriteFile', array('originalName' => $originalName));
					return false;
				}
			}
			if (!isset($fileId)) {
				$errors[] = array('plugins.importexport.native.import.error.galleyFileMissing', array('articleTitle' => $article->getTitle(), 'sectionTitle' => $section->getSectionTitle(), 'issueTitle' => $issue->getIssueIdentification()));
				return false;
			}
			$galley->setFileId($fileId);
			$galleyDao->insertGalley($galley);

			if ($isHtml) {
				$result = NativeImportDom::handleHtmlGalleyNodes($galleyNode, $articleFileManager, $galley, $errors, $isCommandLine);
				if (!$result) return false;
			}
		}

		/* --- Supplemental Files --- */
		$suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
		for ($index=0; $suppNode = $articleNode->getChildByName('supplemental_file', $index); $index++) {
			$suppFile = &new SuppFile();
			$suppFile->setArticleId($article->getArticleId());

			if (($node = $suppNode->getChildByName('title'))) $suppFile->setTitle($node->getValue());
			if (($node = $suppNode->getChildByName('creator'))) $suppFile->setCreator($node->getValue());
			if (($node = $suppNode->getChildByName('subject'))) $suppFile->setSubject($node->getValue());
			if (($node = $suppNode->getChildByName('type_other'))) $suppFile->setTypeOther($node->getValue());
			if (($node = $suppNode->getChildByName('description'))) $suppFile->setDescription($node->getValue());
			if (($node = $suppNode->getChildByName('publisher'))) $suppFile->setPublisher($node->getValue());
			if (($node = $suppNode->getChildByName('sponsor'))) $suppFile->setSponsor($node->getValue());
			if (($node = $suppNode->getChildByName('source'))) $suppFile->setSource($node->getValue());
			if (($node = $suppNode->getChildByName('date_created'))) {
				$createdDate = strtotime($node->getValue());
				if ($createdDate !== -1) $suppFile->setDateCreated($createdDate);
			}

			switch (($suppType = $suppNode->getAttribute('type'))) {
				case 'research_instrument': $suppFile->setType(Locale::translate('author.submit.suppFile.researchInstrument')); break;
				case 'research_materials': $suppFile->setType(Locale::translate('author.submit.suppFile.researchMaterials')); break;
				case 'research_results': $suppFile->setType(Locale::translate('author.submit.suppFile.researchResults')); break;
				case 'transcripts': $suppFile->setType(Locale::translate('author.submit.suppFile.transcripts')); break;
				case 'data_analysis': $suppFile->setType(Locale::translate('author.submit.suppFile.dataAnalysis')); break;
				case 'data_set': $suppFile->setType(Locale::translate('author.submit.suppFile.dataSet')); break;
				case 'source_text': $suppFile->setType(Locale::translate('author.submit.suppFile.sourceText')); break;
				case 'other': $suppFile->setType(''); break;
				default:
					$errors[] = array('plugins.importexport.native.import.error.unknownSuppFileType', array('suppFileType' => $suppType));
					return false;
			}

			$suppFile->setLanguage($suppNode->getAttribute('language'));
			$suppFile->setPublicSuppFileId($suppNode->getAttribute('public_id'));

			if (!($fileNode = $suppNode->getChildByName('file'))) {
				$errors[] = array('plugins.importexport.native.import.error.suppFileMissing', array('articleTitle' => $article->getTitle(), 'sectionTitle' => $section->getSectionTitle(), 'issueTitle' => $issue->getIssueIdentification()));
				return false;
			}

			if (($href = $fileNode->getChildByName('href'))) {
				$url = $href->getAttribute('src');
				if ($isCommandLine || NativeImportDom::isAllowedMethod($url)) {
					if ($isCommandLine && NativeImportDom::isRelativePath($url)) {
						// The command-line tool does a chdir; we need to prepend the original pathname to relative paths so we're not looking in the wrong place.
						$url = PWD . '/' . $url;
					}

					if (($fileId = $articleFileManager->copySuppFile($url, $href->getAttribute('mime_type')))===false) {
						$errors[] = array('plugins.importexport.native.import.error.couldNotCopy', array('url' => $url));
						return false;
					}
				}
			}
			if (($embed = $fileNode->getChildByName('embed'))) {
				if (($type = $embed->getAttribute('encoding')) !== 'base64') {
					$errors[] = array('plugins.importexport.native.import.error.unknownEncoding', array('type' => $type));
					return false;
				}
				$originalName = $embed->getAttribute('filename');
				if (($fileId = $articleFileManager->writeSuppFile($originalName, base64_decode($embed->getValue()), $embed->getAttribute('mime_type')))===false) {
					$errors[] = array('plugins.importexport.native.import.error.couldNotWriteFile', array('originalName' => $originalName));
					return false;
				}
			}

			if (!$fileId) {
				$errors[] = array('plugins.importexport.native.import.error.suppFileMissing', array('articleTitle' => $article->getTitle(), 'sectionTitle' => $section->getSectionTitle(), 'issueTitle' => $issue->getIssueIdentification()));
				return false;
			}

			$suppFile->setFileId($fileId);
			$suppFileDao->insertSuppFile($suppFile);
		}

		// Index the inserted article.
		import('search.ArticleSearchIndex');
		ArticleSearchIndex::indexArticleMetadata($article);
		ArticleSearchIndex::indexArticleFiles($article);

		return true;
	}

	/**
	 * Handle subnodes of a <galley> node specific to HTML galleys, such as stylesheet
	 * and image files. FIXME: The parameter lists, here and elsewhere, are getting
	 * ridiculous.
	 */
	function handleHtmlGalleyNodes(&$galleyNode, &$articleFileManager, &$galley, &$errors, &$isCommandLine) {
		$articleGalleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');

		foreach ($galleyNode->children as $node) {
			$isStylesheet = ($node->getName() == 'stylesheet');
			$isImage = ($node->getName() == 'image');
			if (!$isStylesheet && !$isImage) continue;

			if (($href = $node->getChildByName('href'))) {
				$url = $href->getAttribute('src');
				if ($isCommandLine || NativeImportDom::isAllowedMethod($url)) {
					if ($isCommandLine && NativeImportDom::isRelativePath($url)) {
						// The command-line tool does a chdir; we need to prepend the original pathname to relative paths so we're not looking in the wrong place.
						$url = PWD . '/' . $url;
					}

					if (($fileId = $articleFileManager->copyPublicFile($url, $href->getAttribute('mime_type')))===false) {
						$errors[] = array('plugins.importexport.native.import.error.couldNotCopy', array('url' => $url));
						return false;
					}
				}
			}
			if (($embed = $node->getChildByName('embed'))) {
				if (($type = $embed->getAttribute('encoding')) !== 'base64') {
					$errors[] = array('plugins.importexport.native.import.error.unknownEncoding', array('type' => $type));
					return false;
				}
				$originalName = $embed->getAttribute('filename');
				if (($fileId = $articleFileManager->writePublicFile($originalName, base64_decode($embed->getValue()), $embed->getAttribute('mime_type')))===false) {
					$errors[] = array('plugins.importexport.native.import.error.couldNotWriteFile', array('originalName' => $originalName));
					return false;
				}
			}

			if (!isset($fileId)) continue;

			if ($isStylesheet) {
				$galley->setStyleFileId($fileId);
				$articleGalleyDao->updateGalley($galley);
			} else {
				$articleGalleyDao->insertGalleyImage($galley->getGalleyId(), $fileId);
			}
		}
		return true;
	}

	function cleanupFailure (&$dependentItems) {
		$issueDao = &DAORegistry::getDAO('IssueDAO');
		$articleDao = &DAORegistry::getDAO('ArticleDAO');

		foreach ($dependentItems as $dependentItem) {
			$type = array_shift($dependentItem);
			$object = array_shift($dependentItem);

			switch ($type) {
				case 'issue':
					$issueDao->deleteIssue($object);
					break;
				case 'article':
					$articleDao->deleteArticle($object);
					break;
				default:
					fatalError ('cleanupFailure: Unimplemented type');
			}
		}
	}
}

?>
