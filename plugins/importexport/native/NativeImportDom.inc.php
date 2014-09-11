<?php

/**
 * @file plugins/importexport/native/NativeImportDom.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NativeImportDom
 * @ingroup plugins_importexport_native
 *
 * @brief Native import/export plugin DOM functions for import
 */

import('lib.pkp.classes.xml.XMLCustomWriter');

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
			$issueDao =& DAORegistry::getDAO('IssueDAO');
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

		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$issue = new Issue();
		$issue->setJournalId($journal->getId());

		$journalSupportedLocales = array_keys($journal->getSupportedLocaleNames()); // => journal locales must be set up before
		$journalPrimaryLocale = $journal->getPrimaryLocale();

		/* --- Set IDs --- */
		if (!NativeImportDom::handlePubIds($issueNode, $issue, $journal, $issue, $article, $errors)) $hasErrors = true;

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

		for ($index=0; ($node = $issueNode->getChildByName('description', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $journalPrimaryLocale;
			} elseif (!in_array($locale, $journalSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.issueDescriptionLocaleUnsupported', array('issueTitle' => $issue->getLocalizedTitle(), 'locale' => $locale));
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
			case 'num_vol_year_title':
				$issue->setShowVolume(1);
				$issue->setShowNumber(1);
				$issue->setShowYear(1);
				$issue->setShowTitle(1);
				break;
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
			case 'num_year_title':
				$issue->setShowVolume(0);
				$issue->setShowNumber(1);
				$issue->setShowYear(1);
				$issue->setShowTitle(1);
				break;
			case 'vol':
				$issue->setShowVolume(1);
				$issue->setShowNumber(0);
				$issue->setShowYear(0);
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
				$errors[] = array('plugins.importexport.native.import.error.unknownIdentificationType', array('identificationType' => $value, 'issueTitle' => $issue->getLocalizedTitle()));
				$hasErrors = true;
				break;
		}

		if (($issueNode->getAttribute('identification') == 'title' || $issueNode->getAttribute('identification') == '') && (!$titleExists)) {
			$errors[] = array('plugins.importexport.native.import.error.titleMissing', array());
			// Set a placeholder title so that further errors are
			// somewhat meaningful; this placeholder will not be
			// inserted into the database.
			$issue->setTitle(__('plugins.importexport.native.import.error.defaultTitle'), $journalPrimaryLocale);
			$hasErrors = true;
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
			$anotherIssue = $issueDao->getIssueByPubId('publisher-id', $value, $journal->getId());
			if ($anotherIssue) {
				$errors[] = array('plugins.importexport.native.import.error.duplicatePublicIssueId', array('issueTitle' => $issue->getIssueIdentification(), 'otherIssueTitle' => $anotherIssue->getIssueIdentification()));
				$hasErrors = true;
			} else {
				$issue->setStoredPubId('publisher-id', $value);
			}
		}

		/* --- Access Status --- */

		$node = $issueNode->getChildByName('open_access');
		$issue->setAccessStatus($node?ISSUE_ACCESS_OPEN:ISSUE_ACCESS_SUBSCRIPTION);

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
				$issueDao->updateCurrentIssue($journal->getId());
			}
			$issue->setId($issueDao->insertIssue($issue));
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
			$issue = null; // Don't pass back a reference to a dead issue
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
			import('classes.file.PublicFileManager');
			$publicFileManager = new PublicFileManager();
			$newName = 'cover_issue_' . $issue->getId()."_{$locale}"  . '.';

			if (($href = $node->getChildByName('href'))) {
				$url = $href->getAttribute('src');
				if ($isCommandLine || NativeImportDom::isAllowedMethod($url)) {
					if ($isCommandLine && NativeImportDom::isRelativePath($url)) {
						// The command-line tool does a chdir; we need to prepend the original pathname to relative paths so we're not looking in the wrong place.
						$url = PWD . '/' . $url;
					}

					$originalName = basename($url);
					$newName .= $publicFileManager->getExtension($originalName);
					if (!$publicFileManager->copyJournalFile($journal->getId(), $url, $newName)) {
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
					if ($publicFileManager->writeJournalFile($journal->getId(), $newName, base64_decode($embed->getValue()))===false) {
						$errors[] = array('plugins.importexport.native.import.error.couldNotWriteFile', array('originalName' => $originalName));
						$hasErrors = true;
					}
				}
			}
			// Store the image dimensions.
			list($width, $height) = getimagesize($publicFileManager->getJournalFilesPath($journal->getId()) . '/' . $newName);
			$issue->setWidth($width, $locale);
			$issue->setHeight($height, $locale);

		}

		if ($hasErrors) {
			return false;
		}
		return true;
	}

	function handleArticleCoverNode(&$journal, &$coverNode, &$article, &$errors, $isCommandLine) {
		$errors = array();
		$hasErrors = false;

		$journalSupportedLocales = array_keys($journal->getSupportedLocaleNames()); // => journal locales must be set up before

		$locale = $coverNode->getAttribute('locale');
		if ($locale == '') {
			$locale = $article->getLocale();
		} elseif (!in_array($locale, $journalSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.coverLocaleUnsupported', array('issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
				return false;
		}

		$article->setShowCoverPage(1, $locale);

		if (($node = $coverNode->getChildByName('altText'))) $article->setCoverPageAltText($node->getValue(), $locale);

		if (($node = $coverNode->getChildByName('image'))) {
			import('classes.file.PublicFileManager');
			$publicFileManager = new PublicFileManager();
			$newName = 'cover_article_' . $article->getId()."_{$locale}"  . '.';

			if (($href = $node->getChildByName('href'))) {
				$url = $href->getAttribute('src');
				if ($isCommandLine || NativeImportDom::isAllowedMethod($url)) {
					if ($isCommandLine && NativeImportDom::isRelativePath($url)) {
						// The command-line tool does a chdir; we need to prepend the original pathname to relative paths so we're not looking in the wrong place.
						$url = PWD . '/' . $url;
					}

					$originalName = basename($url);
					$newName .= $publicFileManager->getExtension($originalName);
					if (!$publicFileManager->copyJournalFile($journal->getId(), $url, $newName)) {
						$errors[] = array('plugins.importexport.native.import.error.couldNotCopy', array('url' => $url));
						$hasErrors = true;
					}
					$article->setFileName($newName, $locale);
					$article->setOriginalFileName($publicFileManager->truncateFileName($originalName, 127), $locale);
				}
			}
			if (($embed = $node->getChildByName('embed'))) {
				if (($type = $embed->getAttribute('encoding')) !== 'base64') {
					$errors[] = array('plugins.importexport.native.import.error.unknownEncoding', array('type' => $type));
					$hasErrors = true;
				} else {
					$originalName = $embed->getAttribute('filename');
					$newName .= $publicFileManager->getExtension($originalName);
					$article->setFileName($newName, $locale);
					$article->setOriginalFileName($publicFileManager->truncateFileName($originalName, 127), $locale);
					if ($publicFileManager->writeJournalFile($journal->getId(), $newName, base64_decode($embed->getValue()))===false) {
						$errors[] = array('plugins.importexport.native.import.error.couldNotWriteFile', array('originalName' => $originalName));
						$hasErrors = true;
					}
				}
			}
			// Store the image dimensions.
			list($width, $height) = getimagesize($publicFileManager->getJournalFilesPath($journal->getId()) . '/' . $newName);
			$article->setWidth($width, $locale);
			$article->setHeight($height, $locale);

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
		$sectionDao =& DAORegistry::getDAO('SectionDAO');

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
		$section = null;
		$foundSectionId = $foundSectionTitle = null;
		$index = 0;
		foreach($titles as $locale => $title) {
			$section = $sectionDao->getSectionByTitle($title, $journal->getId());
			if ($section) {
				$sectionId = $section->getId();
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
			$abbrevSection = $sectionDao->getSectionByAbbrev($abbrev, $journal->getId());
			if ($abbrevSection) {
				$sectionId = $abbrevSection->getId();
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
			unset($section);
			$section = new Section();

			$section->setTitle($titles, null);
			$section->setAbbrev($abbrevs, null);
			$section->setIdentifyType($identifyTypes, null);
			$section->setPolicy($policies, null);
			$section->setJournalId($journal->getId());
			$section->setSequence(REALLY_BIG_NUMBER);
			$section->setMetaIndexed(1);
			$section->setEditorRestricted(1);
			$section->setId($sectionDao->insertSection($section));
			$sectionDao->resequenceSections($journal->getId());
		}

		if (!$section && $abbrevSection) {
			unset($section);
			$section =& $abbrevSection;
		}

		// $section *must* now contain a valid section, whether it was
		// found amongst existing sections or created anew.

		// Handle custom ordering, if necessary.
		if ($sectionIndex !== null) {
			$sectionDao->insertCustomSectionOrder($issue->getId(), $section->getId(), $sectionIndex);
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
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$articleDao =& DAORegistry::getDAO('ArticleDAO');

		$article = new Article();
		if ($locale = $articleNode->getAttribute('locale')) {
			$article->setLocale($locale);
		} else {
			$article->setLocale($journal->getPrimaryLocale());
		}
		if (($value = $articleNode->getAttribute('public_id')) != '') {
			$anotherArticle = $publishedArticleDao->getPublishedArticleByPubId('publisher-id', $value, $journal->getId());
			if ($anotherArticle) {
				$errors[] = array('plugins.importexport.native.import.error.duplicatePublicArticleId', array('articleTitle' => $article->getLocalizedTitle(), 'otherArticleTitle' => $anotherArticle->getLocalizedTitle()));
				$hasErrors = true;
			} else {
				$article->setStoredPubId('publisher-id', $value);
			}
		}

		$article->setJournalId($journal->getId());
		$article->setUserId($user->getId());
		$article->setSectionId($section->getId());
		$article->setStatus(STATUS_PUBLISHED);
		$article->setSubmissionProgress(0);
		$article->setDateSubmitted(Core::getCurrentDate());
		$article->stampStatusModified();

		$titleExists = false;
		for ($index=0; ($node = $articleNode->getChildByName('title', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $article->getLocale();
			} elseif (!in_array($locale, $journalSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.articleTitleLocaleUnsupported', array('articleTitle' => $node->getValue(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
				return false;
			}
			$article->setTitle($node->getValue(), $locale);
			$titleExists = true;
		}
		if (!$titleExists || $article->getTitle($article->getLocale()) == '') {
			$errors[] = array('plugins.importexport.native.import.error.articleTitleMissing', array('issueTitle' => $issue->getIssueIdentification(), 'sectionTitle' => $section->getLocalizedTitle()));
			return false;
		}

		for ($index=0; ($node = $articleNode->getChildByName('abstract', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $article->getLocale();
			} elseif (!in_array($locale, $journalSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.articleAbstractLocaleUnsupported', array('articleTitle' => $article->getLocalizedTitle(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
				return false;
			}
			$article->setAbstract($node->getValue(), $locale);
		}

		if (($indexingNode = $articleNode->getChildByName('indexing'))) {
			for ($index=0; ($node = $indexingNode->getChildByName('discipline', $index)); $index++) {
				$locale = $node->getAttribute('locale');
				if ($locale == '') {
					$locale = $article->getLocale();
				} elseif (!in_array($locale, $journalSupportedLocales)) {
					$errors[] = array('plugins.importexport.native.import.error.articleDisciplineLocaleUnsupported', array('articleTitle' => $article->getLocalizedTitle(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
					return false;
				}
				$article->setDiscipline($node->getValue(), $locale);
			}
			for ($index=0; ($node = $indexingNode->getChildByName('type', $index)); $index++) {
				$locale = $node->getAttribute('locale');
				if ($locale == '') {
					$locale = $article->getLocale();
				} elseif (!in_array($locale, $journalSupportedLocales)) {
					$errors[] = array('plugins.importexport.native.import.error.articleTypeLocaleUnsupported', array('articleTitle' => $article->getLocalizedTitle(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
					return false;
				}
				$article->setType($node->getValue(), $locale);
			}
			for ($index=0; ($node = $indexingNode->getChildByName('subject', $index)); $index++) {
				$locale = $node->getAttribute('locale');
				if ($locale == '') {
					$locale = $article->getLocale();
				} elseif (!in_array($locale, $journalSupportedLocales)) {
					$errors[] = array('plugins.importexport.native.import.error.articleSubjectLocaleUnsupported', array('articleTitle' => $article->getLocalizedTitle(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
					return false;
				}
				$article->setSubject($node->getValue(), $locale);
			}
			for ($index=0; ($node = $indexingNode->getChildByName('subject_class', $index)); $index++) {
				$locale = $node->getAttribute('locale');
				if ($locale == '') {
					$locale = $article->getLocale();
				} elseif (!in_array($locale, $journalSupportedLocales)) {
					$errors[] = array('plugins.importexport.native.import.error.articleSubjectClassLocaleUnsupported', array('articleTitle' => $article->getLocalizedTitle(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
					return false;
				}
				$article->setSubjectClass($node->getValue(), $locale);
			}

			if (($coverageNode = $indexingNode->getChildByName('coverage'))) {
				for ($index=0; ($node = $coverageNode->getChildByName('geographical', $index)); $index++) {
					$locale = $node->getAttribute('locale');
					if ($locale == '') {
						$locale = $article->getLocale();
					} elseif (!in_array($locale, $journalSupportedLocales)) {
						$errors[] = array('plugins.importexport.native.import.error.articleCoverageGeoLocaleUnsupported', array('articleTitle' => $article->getLocalizedTitle(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
						return false;
					}
					$article->setCoverageGeo($node->getValue(), $locale);
				}
				for ($index=0; ($node = $coverageNode->getChildByName('chronological', $index)); $index++) {
					$locale = $node->getAttribute('locale');
					if ($locale == '') {
						$locale = $article->getLocale();
					} elseif (!in_array($locale, $journalSupportedLocales)) {
						$errors[] = array('plugins.importexport.native.import.error.articleCoverageChronLocaleUnsupported', array('articleTitle' => $article->getLocalizedTitle(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
						return false;
					}
					$article->setCoverageChron($node->getValue(), $locale);
				}
				for ($index=0; ($node = $coverageNode->getChildByName('sample', $index)); $index++) {
					$locale = $node->getAttribute('locale');
					if ($locale == '') {
						$locale = $article->getLocale();
					} elseif (!in_array($locale, $journalSupportedLocales)) {
						$errors[] = array('plugins.importexport.native.import.error.articleCoverageSampleLocaleUnsupported', array('articleTitle' => $article->getLocalizedTitle(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
						return false;
					}
					$article->setCoverageSample($node->getValue(), $locale);
				}
			}
		}

		for ($index=0; ($node = $articleNode->getChildByName('sponsor', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $article->getLocale();
			} elseif (!in_array($locale, $journalSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.articleSponsorLocaleUnsupported', array('articleTitle' => $article->getLocalizedTitle(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
				return false;
			}
			$article->setSponsor($node->getValue(), $locale);
		}

		if (($node = $articleNode->getChildByName('pages'))) $article->setPages($node->getValue());
		if (($language = $articleNode->getAttribute('language'))) $article->setLanguage($language);

		/* --- Handle covers --- */
		$hasErrors = false;
		for ($index = 0; ($node = $articleNode->getChildByName('cover', $index)); $index++) {
			if (!NativeImportDom::handleArticleCoverNode($journal, $node, $article, $coverErrors, $isCommandLine)) {
				$errors = array_merge($errors, $coverErrors);
				$hasErrors = true;
			}
		}

		/* --- Set IDs --- */
		if (!NativeImportDom::handlePubIds($articleNode, $article, $journal, $issue, $article, $errors)) $hasErrors = true;

		$articleDao->insertArticle($article);

		$dependentItems[] = array('article', $article);

		/* --- Handle authors --- */
		for ($index = 0; ($node = $articleNode->getChildByName('author', $index)); $index++) {
			if (!NativeImportDom::handleAuthorNode($journal, $node, $issue, $section, $article, $authorErrors, $index)) {
				$errors = array_merge($errors, $authorErrors);
				$hasErrors = true;
			}
		}
		if ($hasErrors) return false;
		
		// Create submission mangement records
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');

		$initialCopyeditSignoff = $signoffDao->build('SIGNOFF_COPYEDITING_INITIAL', ASSOC_TYPE_ARTICLE, $article->getId());
		$initialCopyeditSignoff->setUserId(0);
		$signoffDao->updateObject($initialCopyeditSignoff);

		$authorCopyeditSignoff = $signoffDao->build('SIGNOFF_COPYEDITING_AUTHOR', ASSOC_TYPE_ARTICLE, $article->getId());
		$authorCopyeditSignoff->setUserId(0);
		$signoffDao->updateObject($authorCopyeditSignoff);

		$finalCopyeditSignoff = $signoffDao->build('SIGNOFF_COPYEDITING_FINAL', ASSOC_TYPE_ARTICLE, $article->getId());
		$finalCopyeditSignoff->setUserId(0);
		$signoffDao->updateObject($finalCopyeditSignoff);

		$layoutSignoff = $signoffDao->build('SIGNOFF_LAYOUT', ASSOC_TYPE_ARTICLE, $article->getId());
		$layoutSignoff->setUserId(0);
		$signoffDao->updateObject($layoutSignoff);

		$authorProofSignoff = $signoffDao->build('SIGNOFF_PROOFREADING_AUTHOR', ASSOC_TYPE_ARTICLE, $article->getId());
		$authorProofSignoff->setUserId(0);
		$signoffDao->updateObject($authorProofSignoff);

		$proofreaderProofSignoff = $signoffDao->build('SIGNOFF_PROOFREADING_PROOFREADER', ASSOC_TYPE_ARTICLE, $article->getId());
		$proofreaderProofSignoff->setUserId(0);
		$signoffDao->updateObject($proofreaderProofSignoff);

		$layoutProofSignoff = $signoffDao->build('SIGNOFF_PROOFREADING_LAYOUT', ASSOC_TYPE_ARTICLE, $article->getId());
		$layoutProofSignoff->setUserId(0);
		$signoffDao->updateObject($layoutProofSignoff);

		// Log the import in the article event log.
		import('classes.article.log.ArticleLog');
		ArticleLog::logEventHeadless(
			$journal, $user->getId(), $article,
			ARTICLE_LOG_ARTICLE_IMPORT,
			'log.imported',
			array('userName' => $user->getFullName(), 'articleId' => $article->getId())
		);

		// Insert published article entry.
		$publishedArticle = new PublishedArticle();
		$publishedArticle->setId($article->getId());
		$publishedArticle->setIssueId($issue->getId());

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
		$publishedArticle->setAccessStatus($node?ARTICLE_ACCESS_OPEN:ARTICLE_ACCESS_ISSUE_DEFAULT);
		$publishedArticle->setSeq(REALLY_BIG_NUMBER);

		$publishedArticle->setPublishedArticleId($publishedArticleDao->insertPublishedArticle($publishedArticle));

		$publishedArticleDao->resequencePublishedArticles($section->getId(), $issue->getId());

		// Setup default copyright/license metadata after status is set and authors are attached.
		// This handles the case where the XML is not providing it
		$article->initializePermissions();

		// Get XML-specified overrides for permissions data
		if ($permissionsNode = $articleNode->getChildByName('permissions')) {
			if ($node = $permissionsNode->getChildByName('copyright_year')) {
				$article->setCopyrightYear($node->getValue());
			}
			for ($index=0; ($node = $permissionsNode->getChildByName('copyright_holder', $index)); $index++) {
				$locale = $node->getAttribute('locale');
				$article->setCopyrightHolder($node->getValue(), $locale);
			}
			if ($node = $permissionsNode->getChildByName('license_url')) {
				$article->setLicenseURL($node->getValue());
			}
		}

		// Save permissions data
		$articleDao->updateLocaleFields($article);

		/* --- Galleys (html or otherwise handled simultaneously) --- */
		import('classes.file.ArticleFileManager');
		$articleFileManager = new ArticleFileManager($article->getId());

		/* --- Handle galleys --- */
		$hasErrors = false;
		$galleyCount = 0;
		for ($index=0; $index < count($articleNode->children); $index++) {
			$node = $articleNode->children[$index];

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
		import('classes.search.ArticleSearchIndex');
		$articleSearchIndex = new ArticleSearchIndex();
		$articleSearchIndex->articleMetadataChanged($article);
		$articleSearchIndex->articleFilesChanged($article);
		$articleSearchIndex->articleChangesFinished();

		return true;
	}

	/**
	 * Handle an author node (i.e. convert an author from DOM to DAO).
	 * @param $journal Journal
	 * @param $authorNode DOMElement
	 * @param $issue Issue
	 * @param $section Section
	 * @param $article Article
	 * @param $errors array
	 * @param $authorIndex int 0 for first author, 1 for second, ...
	 */
	function handleAuthorNode(&$journal, &$authorNode, &$issue, &$section, &$article, &$errors, $authorIndex) {
		$errors = array();

		$journalSupportedLocales = array_keys($journal->getSupportedLocaleNames()); // => journal locales must be set up before
		$author = new Author();
		if (($node = $authorNode->getChildByName('firstname'))) $author->setFirstName((string)$node->getValue());
		if (($node = $authorNode->getChildByName('middlename'))) $author->setMiddleName($node->getValue());
		if (($node = $authorNode->getChildByName('lastname'))) $author->setLastName((string)$node->getValue());
		$author->setSequence($authorIndex+1); // 1-based
		for ($index=0; ($node = $authorNode->getChildByName('affiliation', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $article->getLocale();
			} elseif (!in_array($locale, $journalSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.articleAuthorAffiliationLocaleUnsupported', array('authorFullName' => $author->getFullName(), 'articleTitle' => $article->getLocalizedTitle(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
				return false;
			}
			$author->setAffiliation($node->getValue(), $locale);
		}
		if (($node = $authorNode->getChildByName('country'))) $author->setCountry($node->getValue());
		if (($node = $authorNode->getChildByName('email'))) $author->setEmail((string)$node->getValue());
		if (($node = $authorNode->getChildByName('url'))) $author->setUrl($node->getValue());
		for ($index=0; ($node = $authorNode->getChildByName('competing_interests', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $article->getLocale();
			} elseif (!in_array($locale, $journalSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.articleAuthorCompetingInterestsLocaleUnsupported', array('authorFullName' => $author->getFullName(), 'articleTitle' => $article->getLocalizedTitle(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
				return false;
			}
			$author->setCompetingInterests($node->getValue(), $locale);
		}
		for ($index=0; ($node = $authorNode->getChildByName('biography', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $article->getLocale();
			} elseif (!in_array($locale, $journalSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.articleAuthorBiographyLocaleUnsupported', array('authorFullName' => $author->getFullName(), 'articleTitle' => $article->getLocalizedTitle(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
				return false;
			}
			$author->setBiography($node->getValue(), $locale);
		}

		$author->setSubmissionId($article->getId());
		$author->setPrimaryContact($authorNode->getAttribute('primary_contact')==='true'?1:0);
		$authorDao =& DAORegistry::getDAO('AuthorDAO'); /* @var $authorDao AuthorDAO */
		$authorDao->insertAuthor($author);

		return true;

	}

	function handleGalleyNode(&$journal, &$galleyNode, &$issue, &$section, &$article, &$errors, $isCommandLine, $isHtml, $galleyCount, &$articleFileManager) {
		$errors = array();

		$journalSupportedLocales = array_keys($journal->getSupportedLocaleNames()); // => journal locales must be set up before
		$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');

		if ($isHtml) $galley = new ArticleHtmlGalley();
		else $galley = new ArticleGalley();

		if (($value = $galleyNode->getAttribute('public_id')) != '') {
			$anotherGalley = $galleyDao->getGalleyByPubId('publisher-id', $value, $article->getId());
			if ($anotherGalley) {
				$errors[] = array('plugins.importexport.native.import.error.duplicatePublicGalleyId', array('publicId' => $value, 'articleTitle' => $article->getLocalizedTitle()));
				$hasErrors = true;
			} else {
				$galley->setStoredPubId('publisher-id', $value);
			}
		}

		$galley->setArticleId($article->getId());
		$galley->setSequence($galleyCount);

		/* --- Set IDs --- */
		if (!NativeImportDom::handlePubIds($galleyNode, $galley, $journal, $issue, $article, $errors)) return false;

		// just journal supported locales?
		$locale = $galleyNode->getAttribute('locale');
		if ($locale == '') {
			$locale = $article->getLocale();
		} elseif (!in_array($locale, $journalSupportedLocales)) {
			$errors[] = array('plugins.importexport.native.import.error.galleyLocaleUnsupported', array('articleTitle' => $article->getLocalizedTitle(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
			return false;
		}
		$galley->setLocale($locale);

		/* --- Galley Label --- */
		if (!($node = $galleyNode->getChildByName('label'))) {
			$errors[] = array('plugins.importexport.native.import.error.galleyLabelMissing', array('articleTitle' => $article->getLocalizedTitle(), 'sectionTitle' => $section->getLocalizedTitle(), 'issueTitle' => $issue->getIssueIdentification()));
			return false;
		}
		$galley->setLabel($node->getValue());

		/* --- Galley File --- */
		if (!($node = $galleyNode->getChildByName('file'))) {
			$errors[] = array('plugins.importexport.native.import.error.galleyFileMissing', array('articleTitle' => $article->getLocalizedTitle(), 'sectionTitle' => $section->getLocalizedTitle(), 'issueTitle' => $issue->getIssueIdentification()));
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
		if (($remote = $node->getChildByName('remote'))) {
			$url = $remote->getAttribute('src');
			$galley->setRemoteURL($url);
			$fileId = 0;
		}
		if (!isset($fileId)) {
			$errors[] = array('plugins.importexport.native.import.error.galleyFileMissing', array('articleTitle' => $article->getLocalizedTitle(), 'sectionTitle' => $section->getLocalizedTitle(), 'issueTitle' => $issue->getIssueIdentification()));
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
		$articleGalleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');

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
				$articleGalleyDao->insertGalleyImage($galley->getId(), $fileId);
			}
		}
		return true;
	}

	/**
	 * Import a public ID from the XML node to the given publication object.
	 * @param $node DOMNode
	 * @param $pubObject object
	 * @param $journal Journal
	 * @param $issue Issue
	 * @param $article Article
	 * @param $errors array
	 */
	function handlePubIds(&$node, &$pubObject, &$journal, &$issue, &$article, &$errors) {
		for ($index=0; ($idNode = $node->getChildByName('id', $index)); $index++) {
			$pubIdType = $idNode->getAttribute('type');

			// Ignore legacy-type id nodes - this was used to export
			// issue IDs but was never really imported.
			if (is_null($pubIdType)) continue;

			$errorParams = array(
				'pubIdType' => $pubIdType
			);

			$pubIdPlugins =& PluginRegistry::loadCategory('pubIds', true, $journal->getId());
			$pubIdPluginFound = false;
			if (is_array($pubIdPlugins)) foreach ($pubIdPlugins as $pubIdPlugin) {
				if ($pubIdPlugin->getPubIdType() == $pubIdType) {
					$pubId = $idNode->getValue();
					$errorParams['pubId'] = $pubId;
					if (!$pubIdPlugin->validatePubId($pubId)) {
						$errors[] = array('plugins.importexport.native.import.error.invalidPubId', $errorParams);
						return false;
					}
					if (!$pubIdPlugin->checkDuplicate($pubId, $pubObject, $journal->getId())) {
						$errors[] = array('plugins.importexport.native.import.error.duplicatePubId', $errorParams);
						return false;
					}
					$pubObject->setStoredPubId($pubIdType, $pubId);
					$pubIdPluginFound = true;
					break;
				}
			}
			if (!$pubIdPluginFound) {
				$errors[] = array('plugins.importexport.native.import.error.unknownPubId', $errorParams);
				return false;
			}
		}
		return true;
	}

	function handleSuppFileNode(&$journal, &$suppNode, &$issue, &$section, &$article, &$errors, $isCommandLine, &$articleFileManager) {
		$errors = array();

		$journalSupportedLocales = array_keys($journal->getSupportedLocaleNames()); // => journal locales must be set up before
		$suppFileDao =& DAORegistry::getDAO('SuppFileDAO');

		$suppFile = new SuppFile();
		$suppFile->setArticleId($article->getId());

		/* --- Set IDs --- */
		if (!NativeImportDom::handlePubIds($suppNode, $suppFile, $journal, $issue, $article, $errors)) return false;

		for ($index=0; ($node = $suppNode->getChildByName('title', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $article->getLocale();
			} elseif (!in_array($locale, $journalSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.articleSuppFileTitleLocaleUnsupported', array('suppFileTitle' => $node->getValue(), 'articleTitle' => $article->getLocalizedTitle(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
				return false;
			}
			$suppFile->setTitle($node->getValue(), $locale);
		}
		for ($index=0; ($node = $suppNode->getChildByName('creator', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $article->getLocale();
			} elseif (!in_array($locale, $journalSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.articleSuppFileCreatorLocaleUnsupported', array('suppFileTitle' => $node->getValue(), 'articleTitle' => $article->getLocalizedTitle(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
				return false;
			}
			$suppFile->setCreator($node->getValue(), $locale);
		}
		for ($index=0; ($node = $suppNode->getChildByName('subject', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $article->getLocale();
			} elseif (!in_array($locale, $journalSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.articleSuppFileSubjectLocaleUnsupported', array('suppFileTitle' => $node->getValue(), 'articleTitle' => $article->getLocalizedTitle(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
				return false;
			}
			$suppFile->setSubject($node->getValue(), $locale);
		}
		for ($index=0; ($node = $suppNode->getChildByName('type_other', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $article->getLocale();
			} elseif (!in_array($locale, $journalSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.articleSuppFileTypeOtherLocaleUnsupported', array('suppFileTitle' => $node->getValue(), 'articleTitle' => $article->getLocalizedTitle(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
				return false;
			}
			$suppFile->setTypeOther($node->getValue(), $locale);
		}
		for ($index=0; ($node = $suppNode->getChildByName('description', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $article->getLocale();
			} elseif (!in_array($locale, $journalSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.articleSuppFileDescriptionLocaleUnsupported', array('suppFileTitle' => $node->getValue(), 'articleTitle' => $article->getLocalizedTitle(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
				return false;
			}
			$suppFile->setDescription($node->getValue(), $locale);
		}
		for ($index=0; ($node = $suppNode->getChildByName('publisher', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $article->getLocale();
			} elseif (!in_array($locale, $journalSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.articleSuppFilePublisherLocaleUnsupported', array('suppFileTitle' => $node->getValue(), 'articleTitle' => $article->getLocalizedTitle(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
				return false;
			}
			$suppFile->setPublisher($node->getValue(), $locale);
		}
		for ($index=0; ($node = $suppNode->getChildByName('sponsor', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $article->getLocale();
			} elseif (!in_array($locale, $journalSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.articleSuppFileSponsorLocaleUnsupported', array('suppFileTitle' => $node->getValue(), 'articleTitle' => $article->getLocalizedTitle(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
				return false;
			}
			$suppFile->setSponsor($node->getValue(), $locale);
		}
		for ($index=0; ($node = $suppNode->getChildByName('source', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $article->getLocale();
			} elseif (!in_array($locale, $journalSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.articleSuppFileSourceLocaleUnsupported', array('suppFileTitle' => $node->getValue(), 'articleTitle' => $article->getLocalizedTitle(), 'issueTitle' => $issue->getIssueIdentification(), 'locale' => $locale));
				return false;
			}
			$suppFile->setSource($node->getValue(), $locale);
		}
		if (($node = $suppNode->getChildByName('date_created'))) {
			$createdDate = strtotime($node->getValue());
			if ($createdDate !== -1) $suppFile->setDateCreated($createdDate);
		}

		switch (($suppType = $suppNode->getAttribute('type'))) {
			case 'research_instrument': $suppFile->setType(__('author.submit.suppFile.researchInstrument')); break;
			case 'research_materials': $suppFile->setType(__('author.submit.suppFile.researchMaterials')); break;
			case 'research_results': $suppFile->setType(__('author.submit.suppFile.researchResults')); break;
			case 'transcripts': $suppFile->setType(__('author.submit.suppFile.transcripts')); break;
			case 'data_analysis': $suppFile->setType(__('author.submit.suppFile.dataAnalysis')); break;
			case 'data_set': $suppFile->setType(__('author.submit.suppFile.dataSet')); break;
			case 'source_text': $suppFile->setType(__('author.submit.suppFile.sourceText')); break;
			case 'other': $suppFile->setType(''); break;
			default:
				$errors[] = array('plugins.importexport.native.import.error.unknownSuppFileType', array('suppFileType' => $suppType));
				return false;
		}

		$suppFile->setShowReviewers($suppNode->getAttribute('show_reviewers')=='true');
		$suppFile->setLanguage($suppNode->getAttribute('language'));

		if (($value = $suppNode->getAttribute('public_id')) != '') {
			$anotherSuppFile = $suppFileDao->getSuppFileByPubId('publisher-id', $value, $article->getId());
			if ($anotherSuppFile) {
				$errors[] = array('plugins.importexport.native.import.error.duplicatePublicSuppFileId', array('suppFileTitle' => $suppFile->getLocalizedTitle(), 'otherSuppFileTitle' => $anotherSuppFile->getLocalizedTitle()));
				$hasErrors = true;
			} else {
				$suppFile->setStoredPubId('publisher-id', $value);
			}
		}

		if (!($fileNode = $suppNode->getChildByName('file'))) {
			$errors[] = array('plugins.importexport.native.import.error.suppFileMissing', array('articleTitle' => $article->getLocalizedTitle(), 'sectionTitle' => $section->getLocalizedTitle(), 'issueTitle' => $issue->getIssueIdentification()));
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
		if (($remote = $fileNode->getChildByName('remote'))) {
			$url = $remote->getAttribute('src');
			$suppFile->setRemoteURL($url);
			$fileId = 0;
		}

		if (!isset($fileId)) {
			$errors[] = array('plugins.importexport.native.import.error.suppFileMissing', array('articleTitle' => $article->getLocalizedTitle(), 'sectionTitle' => $section->getLocalizedTitle(), 'issueTitle' => $issue->getIssueIdentification()));
			return false;
		}

		$suppFile->setFileId($fileId);
		$suppFileDao->insertSuppFile($suppFile);

		return true;

	}

	function cleanupFailure (&$dependentItems) {
		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$articleDao =& DAORegistry::getDAO('ArticleDAO');

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
