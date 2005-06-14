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
	function importIssues(&$journal, &$issueNodes, &$issues, &$errors) {
		$errors = array();
		$issues = array();
		$hasErrors = false;
		foreach ($issueNodes as $issueNode) {
			$result = &NativeImportDom::importIssue(&$journal, &$issueNode, &$issue, &$issueErrors);
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
			$issueDao = &DAORegistry::getDAO('IssueDAO');
			foreach ($issues as $issue) {
				$issueDao->deleteIssue($issue);
			}
			return false;
		}
		return true;
	}

	function importIssue(&$journal, &$issueNode, &$issue, &$errors) {
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
				$issue->setPublished(true);
				break;
			case 'false':
			case '':
			case null:
				$issue->setPublished(false);
				break;
			default:
				$errors[] = array('plugins.importexport.native.import.error.invalidBooleanValue', array('value' => $value));
				$hasErrors = true;
				break;
		}

		switch(($value = $issueNode->getAttribute('current'))) {
			case 'true':
				$issue->setCurrent(true);
				break;
			case 'false':
			case '':
			case null:
				$issue->setCurrent(false);
				break;
			default:
				$errors[] = array('plugins.importexport.native.import.error.invalidBooleanValue', array('value' => $value));
				$hasErrors = true;
				break;
		}

		if (($value = $issueNode->getAttribute('public_id')) != '') $issue->setPublicIssueId($value);

		/* --- Access Status --- */

		$node = $issueNode->getChildByName('open_access');
		$issue->setAccessStatus($node?'true':'false');

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

		$issue->setShowCoverPage(false);

		/* --- All processing that does not require an inserted issue ID
		   --- has been performed by this point. If there were no errors
		   --- then insert the issue and carry on. If there were errors,
		   --- then abort without performing the insertion. */

		if ($hasErrors) {
			$issue = null;
			return false;
		} else {
			if ($issue->getCurrent()) {
				$issueDao->updateCurrentIssue($journal->getJournalId());
			}
			$issue->setIssueId($issueDao->insertIssue(&$issue));
		}

		/* --- Handle cover --- */

		if (($node = $issueNode->getChildByName('cover'))) {
			if (!NativeImportDom::handleCoverNode(&$journal, &$node, &$issue, &$coverErrors)) {
				$errors = array_merge($errors, $coverErrors);
				$hasErrors = true;
			}
		}

		/* --- See if any errors occurred since last time we checked.
		   --- If so, delete the created issue and return failure.
		   --- Otherwise, the whole process was successful. */

		if ($hasErrors) {
			$issueDao->deleteIssue($issue);
			$issue = null;
			return false;
		}

		$issueDao->updateIssue($issue);
		return true;
	}

	function handleCoverNode(&$journal, &$coverNode, &$issue, &$errors) {
		$errors = array();
		$hasErrors = false;

		$issue->setShowCoverPage(true);

		if (($node = $coverNode->getChildByName('caption'))) $issue->setCoverPageDescription($node->getValue());

		if (($node = $coverNode->getChildByName('image'))) {
			if (($href = $coverNode->getChildByName('href'))) {
				// The file is linked with an href tag.
				echo 'FIXME: HREFs are not yet supported.';
			}
			if (($embed = $node->getChildByName('embed'))) {
				// The file is inline, base64-encoded.
				if (($type = $embed->getAttribute('encoding')) !== 'base64') {
					$errors[] = array('plugins.importexport.native.import.error.unknownEncoding', array('type' => $type, 'issueTitle' => $issue->getIssueIdentification()));
					$hasErrors = true;
				} else {
					import('file.PublicFileManager');
					$publicFileManager = new PublicFileManager();
					$originalName = $embed->getAttribute('filename');
					$newName = 'cover_' . $issue->getIssueId() . '.' . $publicFileManager->getExtension($originalName);
					$issue->setFileName($newName);
					$issue->setOriginalFileName($originalName);
					if ($publicFileManager->writeJournalFile($journal->getJournalId(), $newName, base64_decode($embed->getValue()))===false) {
						$errors[] = array('plugins.importexport.native.import.error.couldNotWriteFile', array('originalName' => $originalName, 'newName' => $newName, 'issueTitle' => $issue->getIssueIdentification()));
						$hasErrors = true;
					}
				}
			}
		}
		// FIXME Should handle cover image here!

		if ($hasErrors) {
			return false;
		}
		return true;
	}

}

?>
