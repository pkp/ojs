<?php

/**
 * @file NativeImportDom.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.importexport.native
 * @class NativeImportDom
 *
 * Native import/export plugin DOM functions for import
 *
 * $Id$
 */

import('xml.XMLCustomWriter');

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
			$result = NativeImportDom::importIssue($journal, $issueNode, $issue, $issueErrors, $user, $isCommandLine, $dependentItems, false);
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

		$journalSupportedLocales = array_keys($journal->getSupportedLocaleNames()); // => journal locales must be set up before
		$journalPrimaryLocale = $journal->getPrimaryLocale();

		/* --- Set title, description, volume, number, and year --- */
		
		$titleExists = false;
		for ($index=0; ($node = $issueNode->getChildByName('title', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $journalPrimaryLocale;
			} elseif (!in_array($locale, $journalSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.issueTitleLocaleUnsupported', array('issueTitle' => $node->getValue(), 'locale' => $locale));
				$hasErrors = true;	
				continue;
			}
			$issue->setTitle($node->getValue(), $locale);
			$titleExists = true;
		}
		if (!$titleExists) {
			$errors[] = array('plugins.importexport.native.import.error.titleMissing', array());
			// Set a placeholder title so that further errors are
			// somewhat meaningful; this placeholder will not be
			// inserted into the database.
			$issue->setTitle(Locale::translate('plugins.importexport.native.import.error.defaultTitle'));
			$hasErrors = true;
		}	
		
		for ($index=0; ($node = $issueNode->getChildByName('description', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $journalPrimaryLocale;
			} elseif (!in_array($locale, $journalSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.issueDescriptionLocaleUnsupported', array('issueTitle' => $issue->getIssueTitle(), 'locale' => $locale));
				$hasErrors = true;	
				continue;
			}
			$issue->setDescription($node->getValue(), $locale);
		}		

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
				$issue->setShowVolume(1);
				$issue->setShowNumber(1);
				$issue->setShowYear(1);
				$issue->setShowTitle(0);
				break;
			case 'vol_year':
				$issue->setShowVolume(1);
				$issue->setShowNumber(0);
				$issue->setShowYear(1);
				$issue->setShowTitle(0);
				break;
			case 'year':
				$issue->setShowVolume(0);
				$issue->setShowNumber(0);
				$issue->setShowYear(1);
				$issue->setShowTitle(0);
				break;
			case 'title':
			case '':
			case null:
				$issue->setShowVolume(0);
				$issue->setShowNumber(0);
				$issue->setShowYear(0);
				$issue->setShowTitle(1);
				break;
			default:
				$errors[] = array('plugins.importexport.native.import.error.unknownIdentificationType', array('identificationType' => $value, 'issueTitle' => $issue->getIssueTitle()));
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
		$issue->setAccessStatus($node?OPEN_ACCESS:SUBSCRIPTION);

		if (($node = $issueNode->getChildByName('access_date'))) {
			$accessDate = strtotime($node->getValue());
			if ($accessDate === -1) {
				$errors[] = array('plugins.importexport.native.import.error.invalidDate', array('value' => $node->getValue()));
				$hasErrors = true;
			} else {
				$issue->setOpenAccessDate($accessDate);
			}
		}

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

		for ($index = 0; ($node = $issueNode->getChildByName('cover', $index)); $index++) {
			if (!NativeImportDom::handleCoverNode($journal, $node, $issue, $coverErrors, $isCommandLine)) {
				$errors = array_merge($errors, $coverErrors);
				$hasErrors = true;
			}
		}

		/* --- Handle sections --- */
		for ($index = 0; ($node = $issueNode->getChildByName('section', $index)); $index++) {
			if (!NativeImportDom::handleSectionNode($journal, $node, $issue, $sectionErrors, $user, $isCommandLine, $dependentItems, $index)) {
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

		$journalSupportedLocales = array_keys($journal->getSupportedLocaleNames()); // => journal locales must be set up before
		$journalPrimaryLocale = $journal->getPrimaryLocale();

		$locale = $coverNode->getAttribute('locale');
		if ($locale == '') {
			$locale = $journalPrimaryLocale;
		} elseif (!in_array($locale, $journalSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.coverLocaleUnsupported', array('issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
				return false;			
		}
		
		$issue->setShowCoverPage(1, $locale);

		if (($node = $coverNode->getChildByName('caption'))) $issue->setCoverPageDescription($node->getValue(), $locale);

		if (($node = $coverNode->getChildByName('image'))) {
			import('file.PublicFileManager');
			$publicFileManager = &new PublicFileManager();
			$newName = 'cover_' . $issue->getIssueId()."_{$locale}"  . '.';

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
					$issue->setFileName($newName, $locale);
					$issue->setOriginalFileName($publicFileManager->truncateFileName($originalName, 127), $locale);
				}
			}
			if (($embed = $node->getChildByName('embed'))) {
				if (($type = $embed->getAttribute('encoding')) !== 'base64') {
					$errors[] = array('plugins.importexport.native.import.error.unknownEncoding', array('type' => $type));
					$hasErrors = true;
				} else {
					$originalName = $embed->getAttribute('filename');
					$newName .= $publicFileManager->getExtension($originalName);
					$issue->setFileName($newName, $locale);
					$issue->setOriginalFileName($publicFileManager->truncateFileName($originalName, 127), $locale);
					if ($publicFileManager->writeJournalFile($journal->getJournalId(), $newName, base64_decode($embed->getValue()))===false) {
						$errors[] = array('plugins.importexport.native.import.error.couldNotWriteFile', array('originalName' => $originalName));
						$hasErrors = true;
					}
				}
			}
			// Store the image dimensions.
			list($width, $height) = getimagesize($publicFileManager->getJournalFilesPath($journal->getJournalId()) . '/' . $newName);
			$issue->setWidth($width, $locale);
			$issue->setHeight($height, $locale);	
			
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

	function handleSectionNode(&$journal, &$sectionNode, &$issue, &$errors, &$user, $isCommandLine, &$dependentItems, $sectionIndex = null) {
		$sectionDao = &DAORegistry::getDAO('SectionDAO');

		$errors = array();

		$journalSupportedLocales = array_keys($journal->getSupportedLocaleNames()); // => journal locales must be set up before
		$journalPrimaryLocale = $journal->getPrimaryLocale();

		// The following page or two is responsible for locating an
		// existing section based on title and/or abbrev, or, if none
		// can be found, creating a new one.

		$titles = array();
		for ($index=0; ($node = $sectionNode->getChildByName('title', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $journalPrimaryLocale;
			} elseif (!in_array($locale, $journalSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.sectionTitleLocaleUnsupported', array('sectionTitle' => $node->getValue(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
				return false; // or ignore this error?
			}
			$titles[$locale] = $node->getValue();
		}
		if (empty($titles)) {
			$errors[] = array('plugins.importexport.native.import.error.sectionTitleMissing', array('issueTitle' => $issue->getIssueIdentification()));
			return false;
		}	

		$abbrevs = array();
		for ($index=0; ($node = $sectionNode->getChildByName('abbrev', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $journalPrimaryLocale;
			} elseif (!in_array($locale, $journalSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.sectionAbbrevLocaleUnsupported', array('sectionAbbrev' => $node->getValue(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
				return false; // or ignore this error?
			}
			$abbrevs[$locale] = $node->getValue();
		}

		$identifyTypes = array();
		for ($index=0; ($node = $sectionNode->getChildByName('identify_type', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $journalPrimaryLocale;
			} elseif (!in_array($locale, $journalSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.sectionIdentifyTypeLocaleUnsupported', array('sectionIdentifyType' => $node->getValue(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
				return false; // or ignore this error?	
			}
			$identifyTypes[$locale] = $node->getValue();
		}
		
		$policies = array();
		for ($index=0; ($node = $sectionNode->getChildByName('policy', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $journalPrimaryLocale;
			} elseif (!in_array($locale, $journalSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.sectionPolicyLocaleUnsupported', array('sectionPolicy' => $node->getValue(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
				return false; // or ignore this error?
			}
			$policies[$locale] = $node->getValue();
		}
		
		// $title and, optionally, $abbrev contain information that can
		// be used to locate an existing section. Otherwise, we'll
		// create a new one. If $title and $abbrev each match an
		// existing section, but not the same section, throw an error.

		// problem in the earlier solution: e.g. no title matches, but an abbrev
		// what about if just one title or just one abbrev matches ?
		
		// check titles:
		$section = null;
		$foundSectionId = $foundSectionTitle = null;
		$index = 0;
		foreach($titles as $locale => $title) {
			$section = $sectionDao->getSectionByTitle($title, $journal->getJournalId());
			if ($section) {
				$sectionId = $section->getSectionId();
				if ($foundSectionId) { 
					if ($foundSectionId != $sectionId) {
						// Mismatching sections found. Throw an error.
						$errors[] = array('plugins.importexport.native.import.error.sectionTitleMismatch', array('section1Title' => $title, 'section2Title' => $foundSectionTitle, 'issueTitle' => $issue->getIssueIdentification()));
						return false;
					}
				} else if ($index > 0) { 
						// the current title matches, but the prev titles didn't => error
						$errors[] = array('plugins.importexport.native.import.error.sectionTitleMatch', array('sectionTitle' => $title, 'issueTitle' => $issue->getIssueIdentification()));
						return false;
				}
				$foundSectionId = $sectionId;
				$foundSectionTitle = $title;
			} else { 
				if ($foundSectionId) {
					// a prev title matched, but the current doesn't => error
					$errors[] = array('plugins.importexport.native.import.error.sectionTitleMatch', array('sectionTitle' => $foundSectionTitle, 'issueTitle' => $issue->getIssueIdentification()));
					return false;				
				}
			}
			$index++;
		}		
		// check abbrevs:
		$abbrevSection = null;
		$foundSectionId = $foundSectionAbbrev = null;
		$index = 0;
		foreach($abbrevs as $locale => $abbrev) {
			$abbrevSection = $sectionDao->getSectionByAbbrev($abbrev, $journal->getJournalId());
			if ($abbrevSection) {
				$sectionId = $abbrevSection->getSectionId();
				if ($foundSectionId) {
					if ($foundSectionId != $sectionId) {
						// Mismatching sections found. Throw an error.
						$errors[] = array('plugins.importexport.native.import.error.sectionAbbrevMismatch', array('section1Abbrev' => $abbrev, 'section2Abbrev' => $foundSectionAbbrev, 'issueTitle' => $issue->getIssueIdentification()));
						return false;
					}
				} else if ($index > 0) {
					// the current abbrev matches, but the prev abbrevs didn't => error
					$errors[] = array('plugins.importexport.native.import.error.sectionAbbrevMatch', array('sectionAbbrev' => $sectionAbbrev, 'issueTitle' => $issue->getIssueIdentification()));
					return false;	
				}
				$foundSectionId = $sectionId;
				$foundSectionAbbrev = $abbrev;
			} else {
				if ($foundSectionId) {
					// a prev abbrev matched, but the current doesn't => error
					$errors[] = array('plugins.importexport.native.import.error.sectionAbbrevMatch', array('sectionAbbrev' => $foundSectionAbbrev, 'issueTitle' => $issue->getIssueIdentification()));
					return false;				
				}
			}
			$index++;
		}		
		
		if (!$section && !$abbrevSection) {
			// The section was not matched. Create one.
			// Note that because sections are global-ish,
			// we're not maintaining a list of created
			// sections to delete in case the import fails.
			$section = &new Section();

			// FIXME: This should handle localized sections
			// with more dignity.
			$section->setTitle($titles, null);
			$section->setAbbrev($abbrevs, null);
			$section->setIdentifyType($identifyTypes, null);
			$section->setPolicy($policies, null);
			$section->setJournalId($journal->getJournalId());
			$section->setSequence(REALLY_BIG_NUMBER);
			$section->setMetaIndexed(1);
			$section->setEditorRestricted(1);
			$section->setSectionId($sectionDao->insertSection($section));
			$sectionDao->resequenceSections($journal->getJournalId());
		}

		// $section *must* now contain a valid section, whether it was
		// found amongst existing sections or created anew.

		// Handle custom ordering, if necessary.
		if ($sectionIndex !== null) {
			$sectionDao->insertCustomSectionOrder($issue->getIssueId(), $section->getSectionId(), $sectionIndex);
		}

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

		$journalSupportedLocales = array_keys($journal->getSupportedLocaleNames()); // => journal locales must be set up before
		$journalPrimaryLocale = $journal->getPrimaryLocale();

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

		$titleExists = false;
		for ($index=0; ($node = $articleNode->getChildByName('title', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $journalPrimaryLocale;
			} elseif (!in_array($locale, $journalSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.articleTitleLocaleUnsupported', array('articleTitle' => $node->getValue(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
				return false;
			}
			$article->setTitle($node->getValue(), $locale);
			$titleExists = true;
		}
		if (!$titleExists || $article->getTitle($journalPrimaryLocale) == "") {
			$errors[] = array('plugins.importexport.native.import.error.articleTitleMissing', array('issueTitle' => $issue->getIssueIdentification(), 'sectionTitle' => $section->getSectionTitle()));
			return false;
		}	

		for ($index=0; ($node = $articleNode->getChildByName('abstract', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $journalPrimaryLocale;
			} elseif (!in_array($locale, $journalSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.articleAbstractLocaleUnsupported', array('articleTitle' => $article->getArticleTitle(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
				return false;
			}
			$article->setAbstract($node->getValue(), $locale);
		}

		if (($indexingNode = $articleNode->getChildByName('indexing'))) {			
			for ($index=0; ($node = $articleNode->getChildByName('discipline', $index)); $index++) {
				$locale = $node->getAttribute('locale');
				if ($locale == '') {
					$locale = $journalPrimaryLocale;
				} elseif (!in_array($locale, $journalSupportedLocales)) {
					$errors[] = array('plugins.importexport.native.import.error.articleDisciplineLocaleUnsupported', array('articleTitle' => $article->getArticleTitle(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
					return false;
				}
				$article->setDiscipline($node->getValue(), $locale);
			}
			for ($index=0; ($node = $articleNode->getChildByName('type', $index)); $index++) {
				$locale = $node->getAttribute('locale');
				if ($locale == '') {
					$locale = $journalPrimaryLocale;
				} elseif (!in_array($locale, $journalSupportedLocales)) {
					$errors[] = array('plugins.importexport.native.import.error.articleTypeLocaleUnsupported', array('articleTitle' => $article->getArticleTitle(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
					return false;
				}
				$article->setType($node->getValue(), $locale);
			}
			for ($index=0; ($node = $articleNode->getChildByName('subject', $index)); $index++) {
				$locale = $node->getAttribute('locale');
				if ($locale == '') {
					$locale = $journalPrimaryLocale;
				} elseif (!in_array($locale, $journalSupportedLocales)) {
					$errors[] = array('plugins.importexport.native.import.error.articleSubjectLocaleUnsupported', array('articleTitle' => $article->getArticleTitle(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
					return false;
				}
				$article->setSubject($node->getValue(), $locale);
			}
			for ($index=0; ($node = $articleNode->getChildByName('subject_class', $index)); $index++) {
				$locale = $node->getAttribute('locale');
				if ($locale == '') {
					$locale = $journalPrimaryLocale;
				} elseif (!in_array($locale, $journalSupportedLocales)) {
					$errors[] = array('plugins.importexport.native.import.error.articleSubjectClassLocaleUnsupported', array('articleTitle' => $article->getArticleTitle(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
					return false;
				}
				$article->setSubjectClass($node->getValue(), $locale);
			}
			
			if (($coverageNode = $indexingNode->getChildByName('coverage'))) {
				for ($index=0; ($node = $articleNode->getChildByName('geographical', $index)); $index++) {
					$locale = $node->getAttribute('locale');
					if ($locale == '') {
						$locale = $journalPrimaryLocale;
					} elseif (!in_array($locale, $journalSupportedLocales)) {
						$errors[] = array('plugins.importexport.native.import.error.articleCoverageGeoLocaleUnsupported', array('articleTitle' => $article->getArticleTitle(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
						return false;
					}
					$article->setCoverageGeo($node->getValue(), $locale);
				}
				for ($index=0; ($node = $articleNode->getChildByName('chronological', $index)); $index++) {
					$locale = $node->getAttribute('locale');
					if ($locale == '') {
						$locale = $journalPrimaryLocale;
					} elseif (!in_array($locale, $journalSupportedLocales)) {
						$errors[] = array('plugins.importexport.native.import.error.articleCoverageChronLocaleUnsupported', array('articleTitle' => $article->getArticleTitle(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
						return false;
					}
					$article->setCoverageChron($node->getValue(), $locale);
				}
				for ($index=0; ($node = $articleNode->getChildByName('sample', $index)); $index++) {
					$locale = $node->getAttribute('locale');
					if ($locale == '') {
						$locale = $journalPrimaryLocale;
					} elseif (!in_array($locale, $journalSupportedLocales)) {
						$errors[] = array('plugins.importexport.native.import.error.articleCoverageSampleLocaleUnsupported', array('articleTitle' => $article->getArticleTitle(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
						return false;
					}
					$article->setCoverageSample($node->getValue(), $locale);
				}
			}
		}

		for ($index=0; ($node = $articleNode->getChildByName('sponsor', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $journalPrimaryLocale;
			} elseif (!in_array($locale, $journalSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.articleSponsorLocaleUnsupported', array('articleTitle' => $article->getArticleTitle(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
				return false;
			}
			$article->setSponsor($node->getValue(), $locale);
		}
		
		if (($node = $articleNode->getChildByName('pages'))) $article->setPages($node->getValue());
		if (($language = $articleNode->getAttribute('language'))) $article->setLanguage($language); 

		/* --- Handle authors --- */
		$hasErrors = false;
		for ($index = 0; ($node = $articleNode->getChildByName('author', $index)); $index++) {
			if (!NativeImportDom::handleAuthorNode($journal, $node, $issue, $section, $article, $authorErrors)) {
				$errors = array_merge($errors, $authorErrors);
				$hasErrors = true;
			}
		}
		if ($hasErrors) return false;

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
		$layoutAssignment->setDateAcknowledged(Core::getCurrentDate()); // Make sure that imported articles go directly into the Archive. FIXME?
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
		$publishedArticle->setSeq(REALLY_BIG_NUMBER);
		$publishedArticle->setViews(0);
		$publishedArticle->setPublicArticleId($articleNode->getAttribute('public_id'));

		$publishedArticle->setPubId($publishedArticleDao->insertPublishedArticle($publishedArticle));

		$publishedArticleDao->resequencePublishedArticles($section->getSectionId(), $issue->getIssueId());

		/* --- Galleys (html or otherwise handled simultaneously) --- */
		import('file.ArticleFileManager');
		$articleFileManager = &new ArticleFileManager($article->getArticleId());

		/* --- Handle galleys --- */
		$hasErrors = false;
		$galleyCount = 0;
		for ($index=0; $index < count($articleNode->children); $index++) {
			$node = &$articleNode->children[$index];

			if ($node->getName() == 'htmlgalley') $isHtml = true;
			elseif ($node->getName() == 'galley') $isHtml = false;
			else continue;
			
			if (!NativeImportDom::handleGalleyNode($journal, $node, $issue, $section, $article, $galleyErrors, $isCommandLine, $isHtml, $galleyCount, $articleFileManager)) {
				$errors = array_merge($errors, $galleyErrors);
				$hasErrors = true;
			}
			$galleyCount++;
		}
		if ($hasErrors) return false;

		/* --- Handle supplemental files --- */
		$hasErrors = false;
		for ($index = 0; ($node = $articleNode->getChildByName('supplemental_file', $index)); $index++) {
			if (!NativeImportDom::handleSuppFileNode($journal, $node, $issue, $section, $article, $suppFileErrors, $isCommandLine, $articleFileManager)) {
				$errors = array_merge($errors, $suppFileErrors);
				$hasErrors = true;
			}
		}
		if ($hasErrors) return false;

		// Index the inserted article.
		import('search.ArticleSearchIndex');
		ArticleSearchIndex::indexArticleMetadata($article);
		ArticleSearchIndex::indexArticleFiles($article);

		return true;
	}

	function handleAuthorNode(&$journal, &$authorNode, &$issue, &$section, &$article, &$errors) {
		$errors = array();

		$journalSupportedLocales = array_keys($journal->getSupportedLocaleNames()); // => journal locales must be set up before
		$journalPrimaryLocale = $journal->getPrimaryLocale();
		
		$author = &new Author();
		if (($node = $authorNode->getChildByName('firstname'))) $author->setFirstName($node->getValue());
		if (($node = $authorNode->getChildByName('middlename'))) $author->setMiddleName($node->getValue());
		if (($node = $authorNode->getChildByName('lastname'))) $author->setLastName($node->getValue());
		if (($node = $authorNode->getChildByName('affiliation'))) $author->setAffiliation($node->getValue());
		if (($node = $authorNode->getChildByName('country'))) $author->setCountry($node->getValue());
		if (($node = $authorNode->getChildByName('email'))) $author->setEmail($node->getValue());
		if (($node = $authorNode->getChildByName('url'))) $author->setUrl($node->getValue());
		for ($index=0; ($node = $authorNode->getChildByName('competing_interests', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $journalPrimaryLocale;
			} elseif (!in_array($locale, $journalSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.articleAuthorCompetingInterestsLocaleUnsupported', array('authorFullName' => $author->getFullName(), 'articleTitle' => $article->getArticleTitle(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
				return false;
			} 
			$author->setCompetingInterests($node->getValue(), $locale);
		}
		for ($index=0; ($node = $authorNode->getChildByName('biography', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $journalPrimaryLocale;
			} elseif (!in_array($locale, $journalSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.articleAuthorBiographyLocaleUnsupported', array('authorFullName' => $author->getFullName(), 'articleTitle' => $article->getArticleTitle(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
				return false;
			} 
			$author->setBiography($node->getValue(), $locale);
		}
		
		$author->setPrimaryContact($authorNode->getAttribute('primary_contact')==='true'?1:0);
		$article->addAuthor($author);		// instead of $author->setSequence($index+1);

		return true;

	}

	function handleGalleyNode(&$journal, &$galleyNode, &$issue, &$section, &$article, &$errors, $isCommandLine, $isHtml, $galleyCount, &$articleFileManager) {
		$errors = array();

		$journalSupportedLocales = array_keys($journal->getSupportedLocaleNames()); // => journal locales must be set up before
		$journalPrimaryLocale = $journal->getPrimaryLocale();

		$galleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');

		if ($isHtml) $galley = &new ArticleHtmlGalley();
		else $galley = &new ArticleGalley();

		$galley->setArticleId($article->getArticleId());
		$galley->setSequence($galleyCount);

		// just journal supported locales?
		$locale = $galleyNode->getAttribute('locale');
		if ($locale == '') {
			$locale = $journalPrimaryLocale;
		} elseif (!in_array($locale, $journalSupportedLocales)) {
			$errors[] = array('plugins.importexport.native.import.error.galleyLocaleUnsupported', array('articleTitle' => $article->getArticleTitle(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
			return false;
		} 
		$galley->setLocale($locale); 
		
		/* --- Galley Label --- */
		if (!($node = $galleyNode->getChildByName('label'))) {
			$errors[] = array('plugins.importexport.native.import.error.galleyLabelMissing', array('articleTitle' => $article->getArticleTitle(), 'sectionTitle' => $section->getSectionTitle(), 'issueTitle' => $issue->getIssueIdentification()));
			return false;
		}
		$galley->setLabel($node->getValue());

		/* --- Galley File --- */
		if (!($node = $galleyNode->getChildByName('file'))) {
			$errors[] = array('plugins.importexport.native.import.error.galleyFileMissing', array('articleTitle' => $article->getArticleTitle(), 'sectionTitle' => $section->getSectionTitle(), 'issueTitle' => $issue->getIssueIdentification()));
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
			$errors[] = array('plugins.importexport.native.import.error.galleyFileMissing', array('articleTitle' => $article->getArticleTitle(), 'sectionTitle' => $section->getSectionTitle(), 'issueTitle' => $issue->getIssueIdentification()));
			return false;
		}
		$galley->setFileId($fileId);
		$galleyDao->insertGalley($galley);

		if ($isHtml) {
			$result = NativeImportDom::handleHtmlGalleyNodes($galleyNode, $articleFileManager, $galley, $errors, $isCommandLine);
			if (!$result) return false;
		}

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

	function handleSuppFileNode(&$journal, &$suppNode, &$issue, &$section, &$article, &$errors, $isCommandLine, &$articleFileManager) {
		$errors = array();

		$journalSupportedLocales = array_keys($journal->getSupportedLocaleNames()); // => journal locales must be set up before
		$journalPrimaryLocale = $journal->getPrimaryLocale();

		$suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
			
		$suppFile = &new SuppFile();
		$suppFile->setArticleId($article->getArticleId());

		for ($index=0; ($node = $suppNode->getChildByName('title', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $journalPrimaryLocale;
			} elseif (!in_array($locale, $journalSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.articleSuppFileTitleLocaleUnsupported', array('suppFileTitle' => $node->getValue(), 'articleTitle' => $article->getArticleTitle(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
				return false;			
			}
			$suppFile->setTitle($node->getValue(), $locale);
		}
		for ($index=0; ($node = $suppNode->getChildByName('creator', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $journalPrimaryLocale;
			} elseif (!in_array($locale, $journalSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.articleSuppFileCreatorLocaleUnsupported', array('suppFileTitle' => $node->getValue(), 'articleTitle' => $article->getArticleTitle(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
				return false;			
			}
			$suppFile->setCreator($node->getValue(), $locale);
		}
		for ($index=0; ($node = $suppNode->getChildByName('subject', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $journalPrimaryLocale;
			} elseif (!in_array($locale, $journalSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.articleSuppFileSubjectLocaleUnsupported', array('suppFileTitle' => $node->getValue(), 'articleTitle' => $article->getArticleTitle(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
				return false;			
			}
			$suppFile->setSubject($node->getValue(), $locale);
		}
		for ($index=0; ($node = $suppNode->getChildByName('type_other', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $journalPrimaryLocale;
			} elseif (!in_array($locale, $journalSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.articleSuppFileTypeOtherLocaleUnsupported', array('suppFileTitle' => $node->getValue(), 'articleTitle' => $article->getArticleTitle(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
				return false;			
			}
			$suppFile->setTypeOther($node->getValue(), $locale);
		}
		for ($index=0; ($node = $suppNode->getChildByName('description', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $journalPrimaryLocale;
			} elseif (!in_array($locale, $journalSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.articleSuppFileDescriptionLocaleUnsupported', array('suppFileTitle' => $node->getValue(), 'articleTitle' => $article->getArticleTitle(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
				return false;			
			}
			$suppFile->setDescription($node->getValue(), $locale);
		}
		for ($index=0; ($node = $suppNode->getChildByName('publisher', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $journalPrimaryLocale;
			} elseif (!in_array($locale, $journalSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.articleSuppFilePublisherLocaleUnsupported', array('suppFileTitle' => $node->getValue(), 'articleTitle' => $article->getArticleTitle(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
				return false;			
			}
			$suppFile->setPublisher($node->getValue(), $locale);
		}
		for ($index=0; ($node = $suppNode->getChildByName('sponsor', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $journalPrimaryLocale;
			} elseif (!in_array($locale, $journalSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.articleSuppFileSponsorLocaleUnsupported', array('suppFileTitle' => $node->getValue(), 'articleTitle' => $article->getArticleTitle(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
				return false;			
			}
			$suppFile->setSponsor($node->getValue(), $locale);
		}
		for ($index=0; ($node = $suppNode->getChildByName('source', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $journalPrimaryLocale;
			} elseif (!in_array($locale, $journalSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.articleSuppFileSourceLocaleUnsupported', array('suppFileTitle' => $node->getValue(), 'articleTitle' => $article->getArticleTitle(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
				return false;			
			}
			$suppFile->setSource($node->getValue(), $locale);
		}
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
			$errors[] = array('plugins.importexport.native.import.error.suppFileMissing', array('articleTitle' => $article->getArticleTitle(), 'sectionTitle' => $section->getSectionTitle(), 'issueTitle' => $issue->getIssueIdentification()));
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
			$errors[] = array('plugins.importexport.native.import.error.suppFileMissing', array('articleTitle' => $article->getArticleTitle(), 'sectionTitle' => $section->getSectionTitle(), 'issueTitle' => $issue->getIssueIdentification()));
			return false;
		}

		$suppFile->setFileId($fileId);
		$suppFileDao->insertSuppFile($suppFile);
			
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
