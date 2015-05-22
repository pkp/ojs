<?php

/**
 * @file classes/submission/sectionEditor/SectionEditorAction.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SectionEditorAction
 * @ingroup submission
 *
 * @brief SectionEditorAction class.
 */

import('classes.submission.common.Action');

class SectionEditorAction extends Action {

	/**
	 * Constructor.
	 */
	function SectionEditorAction() {
		parent::Action();
	}

	/**
	 * Actions.
	 */

	/**
	 * Changes the section an article belongs in.
	 * @param $sectionEditorSubmission int
	 * @param $sectionId int
	 */
	function changeSection($sectionEditorSubmission, $sectionId) {
		if (!HookRegistry::call('SectionEditorAction::changeSection', array(&$sectionEditorSubmission, $sectionId))) {
			$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');
			$sectionEditorSubmission->setSectionId($sectionId);
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		}
	}

	/**
	 * Records an editor's submission decision.
	 * @param $sectionEditorSubmission object
	 * @param $decision int
	 * @param $request object
	 */
	function recordDecision(&$sectionEditorSubmission, $decision, $request) {
		$editAssignments =& $sectionEditorSubmission->getEditAssignments();
		if (empty($editAssignments)) return;

		$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$user =& $request->getUser();
		$editorDecision = array(
			'editDecisionId' => null,
			'editorId' => $user->getId(),
			'decision' => $decision,
			'dateDecided' => date(Core::getCurrentDate())
		);

		if (!HookRegistry::call('SectionEditorAction::recordDecision', array(&$sectionEditorSubmission, $editorDecision))) {
			$sectionEditorSubmission->setStatus(STATUS_QUEUED);
			$sectionEditorSubmission->stampStatusModified();
			$sectionEditorSubmission->addDecision($editorDecision, $sectionEditorSubmission->getCurrentRound());
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);

			$decisions = SectionEditorSubmission::getEditorDecisionOptions();
			// Add log
			import('classes.article.log.ArticleLog');
			AppLocale::requireComponents(LOCALE_COMPONENT_APPLICATION_COMMON, LOCALE_COMPONENT_OJS_EDITOR);
			ArticleLog::logEvent($request, $sectionEditorSubmission, ARTICLE_LOG_EDITOR_DECISION, 'log.editor.decision', array('editorName' => $user->getFullName(), 'decision' => __($decisions[$decision])));
		}
	}

	/**
	 * Assigns a reviewer to a submission.
	 * @param $sectionEditorSubmission object
	 * @param $reviewerId int
	 * @param $round int or null to use current round
	 * @param $request object
	 */
	function addReviewer($sectionEditorSubmission, $reviewerId, $round, $request) {
		$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$user =& $request->getUser();

		$reviewer =& $userDao->getById($reviewerId);

		// Check to see if the requested reviewer is not already
		// assigned to review this article.
		if ($round == null) {
			$round = $sectionEditorSubmission->getCurrentRound();
		}

		$assigned = $sectionEditorSubmissionDao->reviewerExists($sectionEditorSubmission->getId(), $reviewerId, $round);

		// Only add the reviewer if he has not already
		// been assigned to review this article.
		if (!$assigned && isset($reviewer) && !HookRegistry::call('SectionEditorAction::addReviewer', array(&$sectionEditorSubmission, $reviewerId))) {
			$reviewAssignment = $reviewAssignmentDao->newDataObject();
			$reviewAssignment->setReviewerId($reviewerId);
			$reviewAssignment->setDateAssigned(Core::getCurrentDate());
			$reviewAssignment->setRound($round);
			$reviewAssignment->setDateDue(SectionEditorAction::getReviewDueDate());

			// Assign review form automatically if needed
			$journalId = $sectionEditorSubmission->getJournalId();
			$sectionDao =& DAORegistry::getDAO('SectionDAO');
			$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');

			$sectionId = $sectionEditorSubmission->getSectionId();
			$section =& $sectionDao->getSection($sectionId, $journalId);
			if ($section && ($reviewFormId = (int) $section->getReviewFormId())) {
				if ($reviewFormDao->reviewFormExists($reviewFormId, ASSOC_TYPE_JOURNAL, $journalId)) {
					$reviewAssignment->setReviewFormId($reviewFormId);
				}
			}

			$sectionEditorSubmission->addReviewAssignment($reviewAssignment);
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);

			$reviewAssignment = $reviewAssignmentDao->getReviewAssignment($sectionEditorSubmission->getId(), $reviewerId, $round);

			// Add log
			import('classes.article.log.ArticleLog');
			ArticleLog::logEvent($request, $sectionEditorSubmission, ARTICLE_LOG_REVIEW_ASSIGN, 'log.review.reviewerAssigned', array('reviewerName' => $reviewer->getFullName(), 'round' => $round, 'reviewId' => $reviewAssignment->getId()));
		}
	}

	/**
	 * Clears a review assignment from a submission.
	 * @param $sectionEditorSubmission object
	 * @param $reviewId int
	 * @param $request object
	 */
	function clearReview($sectionEditorSubmission, $reviewId, $request) {
		$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$user =& $request->getUser();

		$reviewAssignment =& $reviewAssignmentDao->getById($reviewId);

		if (isset($reviewAssignment) && $reviewAssignment->getSubmissionId() == $sectionEditorSubmission->getId() && !HookRegistry::call('SectionEditorAction::clearReview', array(&$sectionEditorSubmission, $reviewAssignment))) {
			$reviewer =& $userDao->getById($reviewAssignment->getReviewerId());
			if (!isset($reviewer)) return false;
			$sectionEditorSubmission->removeReviewAssignment($reviewId);
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);

			// Add log
			import('classes.article.log.ArticleLog');
			import('classes.article.log.ArticleEventLogEntry');
			ArticleLog::logEvent($request, $sectionEditorSubmission, ARTICLE_LOG_REVIEW_CLEAR, 'log.review.reviewCleared', array('reviewerName' => $reviewer->getFullName(), 'articleId' => $sectionEditorSubmission->getId(), 'round' => $reviewAssignment->getRound()));
		}
	}

	/**
	 * Notifies a reviewer about a review assignment.
	 * @param $sectionEditorSubmission object
	 * @param $reviewId int
	 * @param $send boolean
	 * @param $request object
	 * @return boolean true iff ready for redirect
	 */
	function notifyReviewer($sectionEditorSubmission, $reviewId, $send, $request) {
		$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');

		$journal =& $request->getJournal();
		$user =& $request->getUser();

		$reviewAssignment =& $reviewAssignmentDao->getById($reviewId);

		$isEmailBasedReview = $journal->getSetting('mailSubmissionsToReviewers')==1?true:false;
		$reviewerAccessKeysEnabled = $journal->getSetting('reviewerAccessKeysEnabled');

		// If we're using access keys, disable the address fields
		// for this message. (Prevents security issue: section editor
		// could CC or BCC someone else, or change the reviewer address,
		// in order to get the access key.)
		$preventAddressChanges = $reviewerAccessKeysEnabled;

		import('classes.mail.ArticleMailTemplate');

		// Determine which email template to use based on journal settings and current round
		switch (true) {
			case $isEmailBasedReview && $reviewAssignment->getRound() == 1:
				$emailTemplate = 'REVIEW_REQUEST_ATTACHED';
				break;
			case $isEmailBasedReview && $reviewAssignment->getRound() > 1:
				$emailTemplate = 'REVIEW_REQUEST_ATTACHED_SUBSEQUENT';
				break;
			case $reviewerAccessKeysEnabled && $reviewAssignment->getRound() == 1:
				$emailTemplate = 'REVIEW_REQUEST_ONECLICK';
				break;
			case $reviewerAccessKeysEnabled && $reviewAssignment->getRound() > 1:
				$emailTemplate = 'REVIEW_REQUEST_ONECLICK_SUBSEQUENT';
				break;
			case $reviewAssignment->getRound() == 1:
				$emailTemplate = 'REVIEW_REQUEST';
				break;
			case $reviewAssignment->getRound() > 1:
				$emailTemplate = 'REVIEW_REQUEST_SUBSEQUENT';
				break;
		}

		$email = new ArticleMailTemplate($sectionEditorSubmission, $emailTemplate, null, $isEmailBasedReview?true:null);

		if ($preventAddressChanges) {
			$email->setAddressFieldsEnabled(false);
		}

		if ($reviewAssignment->getSubmissionId() == $sectionEditorSubmission->getId() && $reviewAssignment->getReviewFileId()) {
			$reviewer =& $userDao->getById($reviewAssignment->getReviewerId());
			if (!isset($reviewer)) return true;

			if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
				HookRegistry::call('SectionEditorAction::notifyReviewer', array(&$sectionEditorSubmission, &$reviewAssignment, &$email));
				if ($email->isEnabled()) {
					if ($reviewerAccessKeysEnabled) {
						import('lib.pkp.classes.security.AccessKeyManager');
						import('pages.reviewer.ReviewerHandler');
						$accessKeyManager = new AccessKeyManager();

						// Key lifetime is the typical review period plus four weeks
						$keyLifetime = ($journal->getSetting('numWeeksPerReview') + 4) * 7;

						$email->addPrivateParam('ACCESS_KEY', $accessKeyManager->createKey('ReviewerContext', $reviewer->getId(), $reviewId, $keyLifetime));
					}

					if ($preventAddressChanges) {
						// Ensure that this messages goes to the reviewer, and the reviewer ONLY.
						$email->clearAllRecipients();
						$email->addRecipient($reviewer->getEmail(), $reviewer->getFullName());
					}
					$email->send($request);
				}

				$reviewAssignment->setDateNotified(Core::getCurrentDate());
				$reviewAssignment->setCancelled(0);
				$reviewAssignment->stampModified();
				$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
				return true;
			} else {
				if (!$request->getUserVar('continued') || $preventAddressChanges) {
					$email->addRecipient($reviewer->getEmail(), $reviewer->getFullName());
				}

				if (!$request->getUserVar('continued')) {
					$weekLaterDate = strftime(Config::getVar('general', 'date_format_short'), strtotime('+1 week'));

					if ($reviewAssignment->getDateDue() != null) {
						$reviewDueDate = strftime(Config::getVar('general', 'date_format_short'), strtotime($reviewAssignment->getDateDue()));
					} else {
						$numWeeks = max((int) $journal->getSetting('numWeeksPerReview'), 2);
						$reviewDueDate = strftime(Config::getVar('general', 'date_format_short'), strtotime('+' . $numWeeks . ' week'));
					}

					$submissionUrl = $request->url(null, 'reviewer', 'submission', $reviewId, $reviewerAccessKeysEnabled?array('key' => 'ACCESS_KEY'):array());

					$paramArray = array(
						'reviewerName' => $reviewer->getFullName(),
						'weekLaterDate' => $weekLaterDate,
						'reviewDueDate' => $reviewDueDate,
						'reviewerUsername' => $reviewer->getUsername(),
						'reviewerPassword' => $reviewer->getPassword(),
						'editorialContactSignature' => $user->getContactSignature(),
						'reviewGuidelines' => String::html2text($journal->getLocalizedSetting('reviewGuidelines')),
						'submissionReviewUrl' => $submissionUrl,
						'abstractTermIfEnabled' => ($sectionEditorSubmission->getLocalizedAbstract() == ''?'':__('article.abstract')),
						'passwordResetUrl' => $request->url(null, 'login', 'resetPassword', $reviewer->getUsername(), array('confirm' => Validation::generatePasswordResetHash($reviewer->getId())))
					);
					$email->assignParams($paramArray);
					if ($isEmailBasedReview) {
						// An email-based review process was selected. Attach
						// the current review version.
						import('classes.file.TemporaryFileManager');
						$temporaryFileManager = new TemporaryFileManager();
						$reviewVersion =& $sectionEditorSubmission->getReviewFile();
						if ($reviewVersion) {
							$temporaryFile = $temporaryFileManager->articleToTemporaryFile($reviewVersion, $user->getId());
							$email->addPersistAttachment($temporaryFile);
						}
					}
				}
				$email->displayEditForm($request->url(null, null, 'notifyReviewer'), array('reviewId' => $reviewId, 'articleId' => $sectionEditorSubmission->getId()));
				return false;
			}
		}
		return true;
	}

	/**
	 * Cancels a review.
	 * @param $sectionEditorSubmission object
	 * @param $reviewId int
	 * @return boolean true iff ready for redirect
	 */
	function cancelReview($sectionEditorSubmission, $reviewId, $send, $request) {
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');

		$journal =& $request->getJournal();
		$user =& $request->getUser();

		$reviewAssignment =& $reviewAssignmentDao->getById($reviewId);
		$reviewer =& $userDao->getById($reviewAssignment->getReviewerId());
		if (!isset($reviewer)) return true;

		if ($reviewAssignment->getSubmissionId() == $sectionEditorSubmission->getId()) {
			// Only cancel the review if it is currently not cancelled but has previously
			// been initiated, and has not been completed.
			if ($reviewAssignment->getDateNotified() != null && !$reviewAssignment->getCancelled() && ($reviewAssignment->getDateCompleted() == null || $reviewAssignment->getDeclined())) {
				import('classes.mail.ArticleMailTemplate');
				$email = new ArticleMailTemplate($sectionEditorSubmission, 'REVIEW_CANCEL');

				if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
					HookRegistry::call('SectionEditorAction::cancelReview', array(&$sectionEditorSubmission, &$reviewAssignment, &$email));
					if ($email->isEnabled()) {
						$email->send($request);
					}

					$reviewAssignment->setCancelled(1);
					$reviewAssignment->setDateCompleted(Core::getCurrentDate());
					$reviewAssignment->stampModified();

					$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);

					// Add log
					import('classes.article.log.ArticleLog');
					import('classes.article.log.ArticleEventLogEntry');
					ArticleLog::logEvent($request, $sectionEditorSubmission, ARTICLE_LOG_REVIEW_CANCEL, 'log.review.reviewCancelled', array('reviewerName' => $reviewer->getFullName(), 'articleId' => $sectionEditorSubmission->getId(), 'round' => $reviewAssignment->getRound()));
				} else {
					if (!$request->getUserVar('continued')) {
						$email->addRecipient($reviewer->getEmail(), $reviewer->getFullName());

						$paramArray = array(
							'reviewerName' => $reviewer->getFullName(),
							'reviewerUsername' => $reviewer->getUsername(),
							'reviewerPassword' => $reviewer->getPassword(),
							'editorialContactSignature' => $user->getContactSignature()
						);
						$email->assignParams($paramArray);
					}
					$email->displayEditForm($request->url(null, null, 'cancelReview', 'send'), array('reviewId' => $reviewId, 'articleId' => $sectionEditorSubmission->getId()));
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Reminds a reviewer about a review assignment.
	 * @param $sectionEditorSubmission object
	 * @param $reviewId int
	 * @param $send boolean
	 * @param $request object
	 * @return boolean true iff no error was encountered
	 */
	function remindReviewer($sectionEditorSubmission, $reviewId, $send, $request) {
		$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');

		$journal =& $request->getJournal();
		$user =& $request->getUser();
		$reviewAssignment =& $reviewAssignmentDao->getById($reviewId);
		$reviewerAccessKeysEnabled = $journal->getSetting('reviewerAccessKeysEnabled');

		// If we're using access keys, disable the address fields
		// for this message. (Prevents security issue: section editor
		// could CC or BCC someone else, or change the reviewer address,
		// in order to get the access key.)
		$preventAddressChanges = $reviewerAccessKeysEnabled;

		import('classes.mail.ArticleMailTemplate');
		$email = new ArticleMailTemplate($sectionEditorSubmission, $reviewerAccessKeysEnabled?'REVIEW_REMIND_ONECLICK':'REVIEW_REMIND');

		if ($preventAddressChanges) {
			$email->setAddressFieldsEnabled(false);
		}

		if ($send && !$email->hasErrors()) {
			HookRegistry::call('SectionEditorAction::remindReviewer', array(&$sectionEditorSubmission, &$reviewAssignment, &$email));
			$reviewer =& $userDao->getById($reviewAssignment->getReviewerId());

			if ($reviewerAccessKeysEnabled) {
				import('lib.pkp.classes.security.AccessKeyManager');
				import('pages.reviewer.ReviewerHandler');
				$accessKeyManager = new AccessKeyManager();

				// Key lifetime is the typical review period plus four weeks
				$keyLifetime = ($journal->getSetting('numWeeksPerReview') + 4) * 7;
				$email->addPrivateParam('ACCESS_KEY', $accessKeyManager->createKey('ReviewerContext', $reviewer->getId(), $reviewId, $keyLifetime));
			}

			if ($preventAddressChanges) {
				// Ensure that this messages goes to the reviewer, and the reviewer ONLY.
				$email->clearAllRecipients();
				$email->addRecipient($reviewer->getEmail(), $reviewer->getFullName());
			}

			$email->send($request);

			$reviewAssignment->setDateReminded(Core::getCurrentDate());
			$reviewAssignment->setReminderWasAutomatic(0);
			$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
			return true;
		} elseif ($reviewAssignment->getSubmissionId() == $sectionEditorSubmission->getId()) {
			$reviewer =& $userDao->getById($reviewAssignment->getReviewerId());

			if (!$request->getUserVar('continued')) {
				if (!isset($reviewer)) return true;
				$email->addRecipient($reviewer->getEmail(), $reviewer->getFullName());

				$submissionUrl = $request->url(null, 'reviewer', 'submission', $reviewId, $reviewerAccessKeysEnabled?array('key' => 'ACCESS_KEY'):array());

				// Format the review due date
				$reviewDueDate = strtotime($reviewAssignment->getDateDue());
				$dateFormatShort = Config::getVar('general', 'date_format_short');
				if ($reviewDueDate === -1 || $reviewDueDate === false) {
					// Default to something human-readable if no date specified
					$reviewDueDate = '_____';
				} else {
					$reviewDueDate = strftime($dateFormatShort, $reviewDueDate);
				}

				$paramArray = array(
					'reviewerName' => $reviewer->getFullName(),
					'reviewerUsername' => $reviewer->getUsername(),
					'reviewerPassword' => $reviewer->getPassword(),
					'reviewDueDate' => $reviewDueDate,
					'editorialContactSignature' => $user->getContactSignature(),
					'passwordResetUrl' => $request->url(null, 'login', 'resetPassword', $reviewer->getUsername(), array('confirm' => Validation::generatePasswordResetHash($reviewer->getId()))),
					'submissionReviewUrl' => $submissionUrl
				);
				$email->assignParams($paramArray);

			}
			$email->displayEditForm(
				$request->url(null, null, 'remindReviewer', 'send'),
				array(
					'reviewerId' => $reviewer->getId(),
					'articleId' => $sectionEditorSubmission->getId(),
					'reviewId' => $reviewId
				)
			);
			return false;
		}
		return true;
	}

	/**
	 * Thanks a reviewer for completing a review assignment.
	 * @param $sectionEditorSubmission object
	 * @param $reviewId int
	 * @param $send boolean
	 * @param $request object
	 * @return boolean true iff ready for redirect
	 */
	function thankReviewer($sectionEditorSubmission, $reviewId, $send, $request) {
		$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');

		$journal =& $request->getJournal();
		$user =& $request->getUser();

		$reviewAssignment =& $reviewAssignmentDao->getById($reviewId);

		import('classes.mail.ArticleMailTemplate');
		$email = new ArticleMailTemplate($sectionEditorSubmission, 'REVIEW_ACK');

		if ($reviewAssignment->getSubmissionId() == $sectionEditorSubmission->getId()) {
			$reviewer =& $userDao->getById($reviewAssignment->getReviewerId());
			if (!isset($reviewer)) return true;

			if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
				HookRegistry::call('SectionEditorAction::thankReviewer', array(&$sectionEditorSubmission, &$reviewAssignment, &$email));
				if ($email->isEnabled()) {
					$email->send($request);
				}

				$reviewAssignment->setDateAcknowledged(Core::getCurrentDate());
				$reviewAssignment->stampModified();
				$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
			} else {
				if (!$request->getUserVar('continued')) {
					$email->addRecipient($reviewer->getEmail(), $reviewer->getFullName());

					$paramArray = array(
						'reviewerName' => $reviewer->getFullName(),
						'editorialContactSignature' => $user->getContactSignature()
					);
					$email->assignParams($paramArray);
				}
				$email->displayEditForm($request->url(null, null, 'thankReviewer', 'send'), array('reviewId' => $reviewId, 'articleId' => $sectionEditorSubmission->getId()));
				return false;
			}
		}
		return true;
	}

	/**
	 * Rates a reviewer for quality of a review.
	 * @param $articleId int
	 * @param $reviewId int
	 * @param $quality int
	 * @param $request object
	 */
	function rateReviewer($articleId, $reviewId, $quality, $request) {
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$user =& $request->getUser();

		$reviewAssignment =& $reviewAssignmentDao->getById($reviewId);
		$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$sectionEditorSubmission =& $sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		$reviewer =& $userDao->getById($reviewAssignment->getReviewerId());
		if (!isset($reviewer)) return false;

		if ($reviewAssignment->getSubmissionId() == $articleId && !HookRegistry::call('SectionEditorAction::rateReviewer', array(&$reviewAssignment, &$reviewer, &$quality))) {
			// Ensure that the value for quality
			// is between 1 and 5.
			if ($quality != null && ($quality >= 1 && $quality <= 5)) {
				$reviewAssignment->setQuality($quality);
			}

			$reviewAssignment->setDateRated(Core::getCurrentDate());
			$reviewAssignment->stampModified();

			$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);

			// Add log
			import('classes.article.log.ArticleLog');
			import('classes.article.log.ArticleEventLogEntry');
			ArticleLog::logEvent($request, $sectionEditorSubmission, ARTICLE_LOG_REVIEW_RATE, 'log.review.reviewerRated', array('reviewerName' => $reviewer->getFullName(), 'articleId' => $articleId, 'round' => $reviewAssignment->getRound()));
		}
	}

	/**
	 * Makes a reviewer's annotated version of an article available to the author.
	 * @param $articleId int
	 * @param $reviewId int
	 * @param $viewable boolean
	 */
	function makeReviewerFileViewable($articleId, $reviewId, $fileId, $revision, $viewable = false) {
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');

		$reviewAssignment =& $reviewAssignmentDao->getById($reviewId);
		$articleFile =& $articleFileDao->getArticleFile($fileId, $revision);

		if ($reviewAssignment->getSubmissionId() == $articleId && $reviewAssignment->getReviewerFileId() == $fileId && !HookRegistry::call('SectionEditorAction::makeReviewerFileViewable', array(&$reviewAssignment, &$articleFile, &$viewable))) {
			$articleFile->setViewable($viewable);
			$articleFileDao->updateArticleFile($articleFile);
		}
	}

	/**
	 * Returns a formatted review due date
	 * @param $dueDate string
	 * @param $numWeeks int
	 * @return string
	 */
	function getReviewDueDate($dueDate = null, $numWeeks = null) {
		$today = getDate();
		$todayTimestamp = mktime(0, 0, 0, $today['mon'], $today['mday'], $today['year']);
		if ($dueDate) {
			$dueDateParts = explode('-', $dueDate);

			// Ensure that the specified due date is today or after today's date.
			if ($todayTimestamp <= strtotime($dueDate)) {
				return date('Y-m-d H:i:s', mktime(0, 0, 0, $dueDateParts[1], $dueDateParts[2], $dueDateParts[0]));
			} else {
				return date('Y-m-d H:i:s', $todayTimestamp);
			}
		} elseif ($numWeeks) {
			return date('Y-m-d H:i:s', $todayTimestamp + ($numWeeks * 7 * 24 * 60 * 60));
		} else {
			$journal =& Request::getJournal();
			$settingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
			$numWeeks =& $settingsDao->getSetting($journal->getId(), 'numWeeksPerReview');
			if (!isset($numWeeks) || (int) $numWeeks < 0) $numWeeks = 0;
			return date('Y-m-d H:i:s', $todayTimestamp + ($numWeeks * 7 * 24 * 60 * 60));
		}
	}

	/**
	 * Sets the due date for a review assignment.
	 * @param $articleId int
	 * @param $reviewId int
	 * @param $dueDate string
	 * @param $numWeeks int
	 * @param $logEntry boolean
	 * @param $request object
	 */
	function setDueDate($articleId, $reviewId, $dueDate, $numWeeks, $logEntry, $request) {
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$user =& $request->getUser();

		$reviewAssignment =& $reviewAssignmentDao->getById($reviewId);
		$reviewer =& $userDao->getById($reviewAssignment->getReviewerId());
		if (!isset($reviewer)) return false;

		if ($reviewAssignment->getSubmissionId() == $articleId && !HookRegistry::call('SectionEditorAction::setDueDate', array(&$reviewAssignment, &$reviewer, &$dueDate, &$numWeeks))) {
			$dueDate = SectionEditorAction::getReviewDueDate($dueDate, $numWeeks);
			$reviewAssignment->setDateDue($dueDate);

			$reviewAssignment->stampModified();
			$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);

			if ($logEntry) {
				// Add log
				$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');
				$sectionEditorSubmission =& $sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
				import('classes.article.log.ArticleLog');
				import('classes.article.log.ArticleEventLogEntry');
				ArticleLog::logEvent(
					$request,
					$sectionEditorSubmission,
					ARTICLE_LOG_REVIEW_SET_DUE_DATE,
					'log.review.reviewDueDateSet',
					array(
						'reviewerName' => $reviewer->getFullName(),
						'dueDate' => strftime(Config::getVar('general', 'date_format_short'),
						strtotime($reviewAssignment->getDateDue())),
						'articleId' => $articleId,
						'round' => $reviewAssignment->getRound()
					)
				);
			}
		}
	}

	/**
	 * Remove cover page from article
	 * @param $submission object
	 * @param $formLocale string
	 * @return boolean true iff ready for redirect
	 */
	function removeArticleCoverPage($submission, $formLocale) {
		$journal =& Request::getJournal();

		import('classes.file.PublicFileManager');
		$publicFileManager = new PublicFileManager();
		$publicFileManager->removeJournalFile($journal->getId(),$submission->getFileName($formLocale));
		$submission->setFileName('', $formLocale);
		$submission->setOriginalFileName('', $formLocale);
		$submission->setWidth('', $formLocale);
		$submission->setHeight('', $formLocale);

		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$articleDao->updateArticle($submission);

		return true;
	}

	/**
	 * Notifies an author that a submission was unsuitable.
	 * @param $sectionEditorSubmission object
	 * @param $send boolean true if an email should be sent
	 * @param $request object
	 * @return boolean true iff ready for redirect
	 */
	function unsuitableSubmission($sectionEditorSubmission, $send, $request) {
		$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');

		$journal =& $request->getJournal();
		$user =& $request->getUser();

		$author =& $userDao->getById($sectionEditorSubmission->getUserId());
		if (!isset($author)) return true;

		import('classes.mail.ArticleMailTemplate');
		$email = new ArticleMailTemplate($sectionEditorSubmission, 'SUBMISSION_UNSUITABLE');

		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
			HookRegistry::call('SectionEditorAction::unsuitableSubmission', array(&$sectionEditorSubmission, &$author, &$email));
			if ($email->isEnabled()) {
				$email->send($request);
			}
			SectionEditorAction::archiveSubmission($sectionEditorSubmission, $request);
			return true;
		} else {
			if (!$request->getUserVar('continued')) {
				$paramArray = array(
					'editorialContactSignature' => $user->getContactSignature(),
					'authorName' => $author->getFullName()
				);
				$email->assignParams($paramArray);
				$email->addRecipient($author->getEmail(), $author->getFullName());
			}
			$email->displayEditForm($request->url(null, null, 'unsuitableSubmission'), array('articleId' => $sectionEditorSubmission->getId()));
			return false;
		}
	}

	/**
	 * Sets the reviewer recommendation for a review assignment.
	 * Also concatenates the reviewer and editor comments from Peer Review and adds them to Editor Review.
	 * @param $article object
	 * @param $reviewId int
	 * @param $acceptOption int
	 * @param $request object
	 */
	function setReviewerRecommendation($article, $reviewId, $recommendation, $acceptOption, $request) {
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$user =& $request->getUser();

		$reviewAssignment =& $reviewAssignmentDao->getById($reviewId);
		$reviewer =& $userDao->getById($reviewAssignment->getReviewerId(), true);

		if ($reviewAssignment->getSubmissionId() == $article->getId() && !HookRegistry::call('SectionEditorAction::setReviewerRecommendation', array(&$reviewAssignment, &$reviewer, &$recommendation, &$acceptOption))) {
			$reviewAssignment->setRecommendation($recommendation);

			$nowDate = Core::getCurrentDate();
			if (!$reviewAssignment->getDateConfirmed()) {
				$reviewAssignment->setDateConfirmed($nowDate);
			}
			$reviewAssignment->setDateCompleted($nowDate);
			$reviewAssignment->stampModified();

			$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);

			// Add log
			import('classes.article.log.ArticleLog');
			ArticleLog::logEvent($request, $article, ARTICLE_LOG_REVIEW_RECOMMENDATION_BY_PROXY, 'log.review.reviewRecommendationSetByProxy', array('editorName' => $user->getFullName(), 'reviewerName' => $reviewer->getFullName(), 'reviewId' => $reviewAssignment->getId(), 'round' => $reviewAssignment->getRound()));
		}
	}

	/**
	 * Clear a review form
	 * @param $sectionEditorSubmission object
	 * @param $reviewId int
	 */
	function clearReviewForm($sectionEditorSubmission, $reviewId) {
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewAssignment =& $reviewAssignmentDao->getById($reviewId);

		if (HookRegistry::call('SectionEditorAction::clearReviewForm', array(&$sectionEditorSubmission, &$reviewAssignment, &$reviewId))) return $reviewId;

		if (isset($reviewAssignment) && $reviewAssignment->getSubmissionId() == $sectionEditorSubmission->getId()) {
			$reviewFormResponseDao =& DAORegistry::getDAO('ReviewFormResponseDAO');
			$responses = $reviewFormResponseDao->getReviewReviewFormResponseValues($reviewId);
			if (!empty($responses)) {
				$reviewFormResponseDao->deleteByReviewId($reviewId);
			}
			$reviewAssignment->setReviewFormId(null);
			$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
		}
	}

	/**
	 * Assigns a review form to a review.
	 * @param $sectionEditorSubmission object
	 * @param $reviewId int
	 * @param $reviewFormId int
	 */
	function addReviewForm($sectionEditorSubmission, $reviewId, $reviewFormId) {
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewAssignment =& $reviewAssignmentDao->getById($reviewId);

		if (HookRegistry::call('SectionEditorAction::addReviewForm', array(&$sectionEditorSubmission, &$reviewAssignment, &$reviewId, &$reviewFormId))) return $reviewFormId;

		if (isset($reviewAssignment) && $reviewAssignment->getSubmissionId() == $sectionEditorSubmission->getId()) {
			// Only add the review form if it has not already
			// been assigned to the review.
			if ($reviewAssignment->getReviewFormId() != $reviewFormId) {
				$reviewFormResponseDao =& DAORegistry::getDAO('ReviewFormResponseDAO');
				$responses = $reviewFormResponseDao->getReviewReviewFormResponseValues($reviewId);
				if (!empty($responses)) {
					$reviewFormResponseDao->deleteByReviewId($reviewId);
				}
				$reviewAssignment->setReviewFormId($reviewFormId);
				$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
			}
		}
	}

	/**
	 * View review form response.
	 * @param $sectionEditorSubmission object
	 * @param $reviewId int
	 */
	function viewReviewFormResponse($sectionEditorSubmission, $reviewId) {
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewAssignment =& $reviewAssignmentDao->getById($reviewId);

		if (HookRegistry::call('SectionEditorAction::viewReviewFormResponse', array(&$sectionEditorSubmission, &$reviewAssignment, &$reviewId))) return $reviewId;

		if (isset($reviewAssignment) && $reviewAssignment->getSubmissionId() == $sectionEditorSubmission->getId()) {
			$reviewFormId = $reviewAssignment->getReviewFormId();
			if ($reviewFormId != null) {
				import('classes.submission.form.ReviewFormResponseForm');
				$reviewForm = new ReviewFormResponseForm($reviewId, $reviewFormId);
				$reviewForm->initData();
				$reviewForm->display();
			}
		}
	}

	/**
	 * Set the file to use as the default copyedit file.
	 * @param $sectionEditorSubmission object
	 * @param $fileId int
	 * @param $revision int
	 * TODO: SECURITY!
	 */
	function setCopyeditFile($sectionEditorSubmission, $fileId, $revision, $request) {
		import('classes.file.ArticleFileManager');
		$articleFileManager = new ArticleFileManager($sectionEditorSubmission->getId());
		$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');
		$user =& $request->getUser();

		if (!HookRegistry::call('SectionEditorAction::setCopyeditFile', array(&$sectionEditorSubmission, &$fileId, &$revision))) {
			// Copy the file from the editor decision file folder to the copyedit file folder
			$newFileId = $articleFileManager->copyToCopyeditFile($fileId, $revision);

			$copyeditSignoff = $signoffDao->build('SIGNOFF_COPYEDITING_INITIAL', ASSOC_TYPE_ARTICLE, $sectionEditorSubmission->getId());

			$copyeditSignoff->setFileId($newFileId);
			$copyeditSignoff->setFileRevision(1);

			$signoffDao->updateObject($copyeditSignoff);

			// Add log
			import('classes.article.log.ArticleLog');
			import('classes.article.log.ArticleEventLogEntry');
			ArticleLog::logEvent($request, $sectionEditorSubmission, ARTICLE_LOG_COPYEDIT_SET_FILE, 'log.copyedit.copyeditFileSet');
		}
	}

	/**
	 * Resubmit the file for review.
	 * @param $sectionEditorSubmission object
	 * @param $fileId int
	 * @param $revision int
	 * TODO: SECURITY!
	 */
	function resubmitFile($sectionEditorSubmission, $fileId, $revision, $request) {
		import('classes.file.ArticleFileManager');
		$articleFileManager = new ArticleFileManager($sectionEditorSubmission->getId());
		$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');
		$user =& $request->getUser();

		if (!HookRegistry::call('SectionEditorAction::resubmitFile', array(&$sectionEditorSubmission, &$fileId, &$revision))) {
			// Increment the round
			$currentRound = $sectionEditorSubmission->getCurrentRound();
			$sectionEditorSubmission->setCurrentRound($currentRound + 1);
			$sectionEditorSubmission->stampStatusModified();

			// Copy the file from the editor decision file folder to the review file folder
			$newFileId = $articleFileManager->copyToReviewFile($fileId, $revision, $sectionEditorSubmission->getReviewFileId());
			$newReviewFile = $articleFileDao->getArticleFile($newFileId);
			$newReviewFile->setRound($sectionEditorSubmission->getCurrentRound());
			$articleFileDao->updateArticleFile($newReviewFile);

			// Copy the file from the editor decision file folder to the next-round editor file
			// $editorFileId may or may not be null after assignment
			$editorFileId = $sectionEditorSubmission->getEditorFileId() != null ? $sectionEditorSubmission->getEditorFileId() : null;

			// $editorFileId definitely will not be null after assignment
			$editorFileId = $articleFileManager->copyToEditorFile($newFileId, null, $editorFileId);
			$newEditorFile = $articleFileDao->getArticleFile($editorFileId);
			$newEditorFile->setRound($sectionEditorSubmission->getCurrentRound());
			$articleFileDao->updateArticleFile($newEditorFile);

			// The review revision is the highest revision for the review file.
			$reviewRevision = $articleFileDao->getRevisionNumber($newFileId);
			$sectionEditorSubmission->setReviewRevision($reviewRevision);

			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);

			// Now, reassign all reviewers that submitted a review for this new round of reviews.
			$previousRound = $sectionEditorSubmission->getCurrentRound() - 1;
			foreach ($sectionEditorSubmission->getReviewAssignments($previousRound) as $reviewAssignment) {
				if ($reviewAssignment->getRecommendation() !== null && $reviewAssignment->getRecommendation() !== '') {
					// Then this reviewer submitted a review.
					SectionEditorAction::addReviewer($sectionEditorSubmission, $reviewAssignment->getReviewerId(), $sectionEditorSubmission->getCurrentRound(), $request);
				}
			}


			// Add log
			import('classes.article.log.ArticleLog');
			ArticleLog::logEvent($request, $sectionEditorSubmission, ARTICLE_LOG_REVIEW_RESUBMIT, 'log.review.resubmit');
		}
	}

	/**
	 * Assigns a copyeditor to a submission.
	 * @param $sectionEditorSubmission object
	 * @param $copyeditorId int
	 */
	function selectCopyeditor($sectionEditorSubmission, $copyeditorId, $request) {
		$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$user =& $request->getUser();

		// Check to see if the requested copyeditor is not already
		// assigned to copyedit this article.
		$assigned = $sectionEditorSubmissionDao->copyeditorExists($sectionEditorSubmission->getId(), $copyeditorId);

		// Only add the copyeditor if he has not already
		// been assigned to review this article.
		if (!$assigned && !HookRegistry::call('SectionEditorAction::selectCopyeditor', array(&$sectionEditorSubmission, &$copyeditorId))) {
			$copyeditInitialSignoff = $signoffDao->build('SIGNOFF_COPYEDITING_INITIAL', ASSOC_TYPE_ARTICLE, $sectionEditorSubmission->getId());
			$copyeditInitialSignoff->setUserId($copyeditorId);
			$signoffDao->updateObject($copyeditInitialSignoff);

			$copyeditor =& $userDao->getById($copyeditorId);

			// Add log
			import('classes.article.log.ArticleLog');
			ArticleLog::logEvent($request, $sectionEditorSubmission, ARTICLE_LOG_COPYEDIT_ASSIGN, 'log.copyedit.copyeditorAssigned', array('copyeditorName' => $copyeditor->getFullName()));
		}
	}

	/**
	 * Notifies a copyeditor about a copyedit assignment.
	 * @param $sectionEditorSubmission object
	 * @param $send boolean
	 * @param $request object
	 * @return boolean true iff ready for redirect
	 */
	function notifyCopyeditor($sectionEditorSubmission, $send, $request) {
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$journal =& $request->getJournal();
		$user =& $request->getUser();

		import('classes.mail.ArticleMailTemplate');
		$email = new ArticleMailTemplate($sectionEditorSubmission, 'COPYEDIT_REQUEST');

		$copyeditor = $sectionEditorSubmission->getUserBySignoffType('SIGNOFF_COPYEDITING_INITIAL');
		if (!isset($copyeditor)) return true;

		if ($sectionEditorSubmission->getFileBySignoffType('SIGNOFF_COPYEDITING_INITIAL') && (!$email->isEnabled() || ($send && !$email->hasErrors()))) {
			HookRegistry::call('SectionEditorAction::notifyCopyeditor', array(&$sectionEditorSubmission, &$copyeditor, &$email));
			if ($email->isEnabled()) {
				$email->send($request);
			}

			$copyeditInitialSignoff = $signoffDao->build('SIGNOFF_COPYEDITING_INITIAL', ASSOC_TYPE_ARTICLE, $sectionEditorSubmission->getId());
			$copyeditInitialSignoff->setDateNotified(Core::getCurrentDate());
			$copyeditInitialSignoff->setDateUnderway(null);
			$copyeditInitialSignoff->setDateCompleted(null);
			$copyeditInitialSignoff->setDateAcknowledged(null);
			$signoffDao->updateObject($copyeditInitialSignoff);
		} else {
			if (!$request->getUserVar('continued')) {
				$email->addRecipient($copyeditor->getEmail(), $copyeditor->getFullName());
				$paramArray = array(
					'copyeditorName' => $copyeditor->getFullName(),
					'copyeditorUsername' => $copyeditor->getUsername(),
					'copyeditorPassword' => $copyeditor->getPassword(),
					'editorialContactSignature' => $user->getContactSignature(),
					'submissionCopyeditingUrl' => $request->url(null, 'copyeditor', 'submission', $sectionEditorSubmission->getId())
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm($request->url(null, null, 'notifyCopyeditor', 'send'), array('articleId' => $sectionEditorSubmission->getId()));
			return false;
		}
		return true;
	}

	/**
	 * Initiates the initial copyedit stage when the editor does the copyediting.
	 * @param $sectionEditorSubmission object
	 * @param $request object
	 */
	function initiateCopyedit($sectionEditorSubmission, $request) {
		$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$user =& $request->getUser();

		// Only allow copyediting to be initiated if a copyedit file exists.
		if ($sectionEditorSubmission->getFileBySignoffType('SIGNOFF_COPYEDITING_INITIAL') && !HookRegistry::call('SectionEditorAction::initiateCopyedit', array(&$sectionEditorSubmission))) {
			$signoffDao =& DAORegistry::getDAO('SignoffDAO');

			$copyeditSignoff = $signoffDao->build('SIGNOFF_COPYEDITING_INITIAL', ASSOC_TYPE_ARTICLE, $sectionEditorSubmission->getId());
			if (!$copyeditSignoff->getUserId()) {
				$copyeditSignoff->setUserId($user->getId());
			}
			$copyeditSignoff->setDateNotified(Core::getCurrentDate());

			$signoffDao->updateObject($copyeditSignoff);
		}
	}

	/**
	 * Thanks a copyeditor about a copyedit assignment.
	 * @param $sectionEditorSubmission object
	 * @param $send boolean
	 * @param $request object
	 * @return boolean true iff ready for redirect
	 */
	function thankCopyeditor($sectionEditorSubmission, $send, $request) {
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$journal =& $request->getJournal();
		$user =& $request->getUser();

		import('classes.mail.ArticleMailTemplate');
		$email = new ArticleMailTemplate($sectionEditorSubmission, 'COPYEDIT_ACK');

		$copyeditor = $sectionEditorSubmission->getUserBySignoffType('SIGNOFF_COPYEDITING_INITIAL');
		if (!isset($copyeditor)) return true;

		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
			HookRegistry::call('SectionEditorAction::thankCopyeditor', array(&$sectionEditorSubmission, &$copyeditor, &$email));
			if ($email->isEnabled()) {
				$email->send($request);
			}

			$initialSignoff = $signoffDao->build('SIGNOFF_COPYEDITING_INITIAL', ASSOC_TYPE_ARTICLE, $sectionEditorSubmission->getId());
			$initialSignoff->setDateAcknowledged(Core::getCurrentDate());
			$signoffDao->updateObject($initialSignoff);
		} else {
			if (!$request->getUserVar('continued')) {
				$email->addRecipient($copyeditor->getEmail(), $copyeditor->getFullName());
				$paramArray = array(
					'copyeditorName' => $copyeditor->getFullName(),
					'editorialContactSignature' => $user->getContactSignature()
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm($request->url(null, null, 'thankCopyeditor', 'send'), array('articleId' => $sectionEditorSubmission->getId()));
			return false;
		}
		return true;
	}

	/**
	 * Notifies the author that the copyedit is complete.
	 * @param $sectionEditorSubmission object
	 * @param $send boolean
	 * @param $request object
	 * @return true iff ready for redirect
	 */
	function notifyAuthorCopyedit($sectionEditorSubmission, $send, $request) {
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$journal =& $request->getJournal();
		$user =& $request->getUser();

		import('classes.mail.ArticleMailTemplate');
		$email = new ArticleMailTemplate($sectionEditorSubmission, 'COPYEDIT_AUTHOR_REQUEST');

		$author =& $userDao->getById($sectionEditorSubmission->getUserId());
		if (!isset($author)) return true;

		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
			HookRegistry::call('SectionEditorAction::notifyAuthorCopyedit', array(&$sectionEditorSubmission, &$author, &$email));
			if ($email->isEnabled()) {
				$email->send($request);
			}

			$authorSignoff = $signoffDao->build('SIGNOFF_COPYEDITING_AUTHOR', ASSOC_TYPE_ARTICLE, $sectionEditorSubmission->getId());
			$authorSignoff->setUserId($author->getId());
			$authorSignoff->setDateNotified(Core::getCurrentDate());
			$authorSignoff->setDateUnderway(null);
			$authorSignoff->setDateCompleted(null);
			$authorSignoff->setDateAcknowledged(null);
			$signoffDao->updateObject($authorSignoff);
		} else {
			if (!$request->getUserVar('continued')) {
				$email->addRecipient($author->getEmail(), $author->getFullName());
				$paramArray = array(
					'authorName' => $author->getFullName(),
					'authorUsername' => $author->getUsername(),
					'authorPassword' => $author->getPassword(),
					'editorialContactSignature' => $user->getContactSignature(),
					'submissionCopyeditingUrl' => $request->url(null, 'author', 'submissionEditing', $sectionEditorSubmission->getId())
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm($request->url(null, null, 'notifyAuthorCopyedit', 'send'), array('articleId' => $sectionEditorSubmission->getId()));
			return false;
		}
		return true;
	}

	/**
	 * Thanks an author for completing editor / author review.
	 * @param $sectionEditorSubmission object
	 * @param $send boolean
	 * @param $request object
	 * @return boolean true iff ready for redirect
	 */
	function thankAuthorCopyedit($sectionEditorSubmission, $send, $request) {
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$journal =& $request->getJournal();
		$user =& $request->getUser();

		import('classes.mail.ArticleMailTemplate');
		$email = new ArticleMailTemplate($sectionEditorSubmission, 'COPYEDIT_AUTHOR_ACK');

		$author =& $userDao->getById($sectionEditorSubmission->getUserId());
		if (!isset($author)) return true;

		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
			HookRegistry::call('SectionEditorAction::thankAuthorCopyedit', array(&$sectionEditorSubmission, &$author, &$email));
			if ($email->isEnabled()) {
				$email->send($request);
			}

			$signoff = $signoffDao->build('SIGNOFF_COPYEDITING_AUTHOR', ASSOC_TYPE_ARTICLE, $sectionEditorSubmission->getId());
			$signoff->setDateAcknowledged(Core::getCurrentDate());
			$signoffDao->updateObject($signoff);
		} else {
			if (!$request->getUserVar('continued')) {
				$email->addRecipient($author->getEmail(), $author->getFullName());
				$paramArray = array(
					'authorName' => $author->getFullName(),
					'editorialContactSignature' => $user->getContactSignature()
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm($request->url(null, null, 'thankAuthorCopyedit', 'send'), array('articleId' => $sectionEditorSubmission->getId()));
			return false;
		}
		return true;
	}

	/**
	 * Notify copyeditor about final copyedit.
	 * @param $sectionEditorSubmission object
	 * @param $send boolean
	 * @param $request object
	 * @return boolean true iff ready for redirect
	 */
	function notifyFinalCopyedit($sectionEditorSubmission, $send, $request) {
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$journal =& $request->getJournal();
		$user =& $request->getUser();

		import('classes.mail.ArticleMailTemplate');
		$email = new ArticleMailTemplate($sectionEditorSubmission, 'COPYEDIT_FINAL_REQUEST');

		$copyeditor = $sectionEditorSubmission->getUserBySignoffType('SIGNOFF_COPYEDITING_INITIAL');
		if (!isset($copyeditor)) return true;

		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
			HookRegistry::call('SectionEditorAction::notifyFinalCopyedit', array(&$sectionEditorSubmission, &$copyeditor, &$email));
			if ($email->isEnabled()) {
				$email->send($request);
			}

			$signoff = $signoffDao->build('SIGNOFF_COPYEDITING_FINAL', ASSOC_TYPE_ARTICLE, $sectionEditorSubmission->getId());
			$signoff->setUserId($copyeditor->getId());
			$signoff->setDateNotified(Core::getCurrentDate());
			$signoff->setDateUnderway(null);
			$signoff->setDateCompleted(null);
			$signoff->setDateAcknowledged(null);

			$signoffDao->updateObject($signoff);
		} else {
			if (!$request->getUserVar('continued')) {
				$email->addRecipient($copyeditor->getEmail(), $copyeditor->getFullName());
				$paramArray = array(
					'copyeditorName' => $copyeditor->getFullName(),
					'copyeditorUsername' => $copyeditor->getUsername(),
					'copyeditorPassword' => $copyeditor->getPassword(),
					'editorialContactSignature' => $user->getContactSignature(),
					'submissionCopyeditingUrl' => $request->url(null, 'copyeditor', 'submission', $sectionEditorSubmission->getId())
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm($request->url(null, null, 'notifyFinalCopyedit', 'send'), array('articleId' => $sectionEditorSubmission->getId()));
			return false;
		}
		return true;
	}

	/**
	 * Thank copyeditor for completing final copyedit.
	 * @param $sectionEditorSubmission object
	 * @param $send boolean
	 * @param $request object
	 * @return boolean true iff ready for redirect
	 */
	function thankFinalCopyedit($sectionEditorSubmission, $send, $request) {
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$journal =& $request->getJournal();
		$user =& $request->getUser();

		import('classes.mail.ArticleMailTemplate');
		$email = new ArticleMailTemplate($sectionEditorSubmission, 'COPYEDIT_FINAL_ACK');

		$copyeditor = $sectionEditorSubmission->getUserBySignoffType('SIGNOFF_COPYEDITING_INITIAL');
		if (!isset($copyeditor)) return true;

		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
			HookRegistry::call('SectionEditorAction::thankFinalCopyedit', array(&$sectionEditorSubmission, &$copyeditor, &$email));
			if ($email->isEnabled()) {
				$email->send($request);
			}

			$signoff = $signoffDao->build('SIGNOFF_COPYEDITING_FINAL', ASSOC_TYPE_ARTICLE, $sectionEditorSubmission->getId());
			$signoff->setDateAcknowledged(Core::getCurrentDate());
			$signoffDao->updateObject($signoff);
		} else {
			if (!$request->getUserVar('continued')) {
				$email->addRecipient($copyeditor->getEmail(), $copyeditor->getFullName());
				$paramArray = array(
					'copyeditorName' => $copyeditor->getFullName(),
					'editorialContactSignature' => $user->getContactSignature()
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm($request->url(null, null, 'thankFinalCopyedit', 'send'), array('articleId' => $sectionEditorSubmission->getId()));
			return false;
		}
		return true;
	}

	/**
	 * Upload the review version of an article.
	 * @param $sectionEditorSubmission object
	 */
	function uploadReviewVersion($sectionEditorSubmission) {
		import('classes.file.ArticleFileManager');
		$articleFileManager = new ArticleFileManager($sectionEditorSubmission->getId());
		$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');

		$fileName = 'upload';
		if ($articleFileManager->uploadedFileExists($fileName) && !HookRegistry::call('SectionEditorAction::uploadReviewVersion', array(&$sectionEditorSubmission))) {
			if ($sectionEditorSubmission->getReviewFileId() != null) {
				$reviewFileId = $articleFileManager->uploadReviewFile($fileName, $sectionEditorSubmission->getReviewFileId());
				// Increment the review revision.
				$sectionEditorSubmission->setReviewRevision($sectionEditorSubmission->getReviewRevision()+1);
			} else {
				$reviewFileId = $articleFileManager->uploadReviewFile($fileName);
				$sectionEditorSubmission->setReviewRevision(1);
			}
			$editorFileId = $articleFileManager->copyToEditorFile($reviewFileId, $sectionEditorSubmission->getReviewRevision(), $sectionEditorSubmission->getEditorFileId());
		}

		if (isset($reviewFileId) && $reviewFileId != 0 && isset($editorFileId) && $editorFileId != 0) {
			$sectionEditorSubmission->setReviewFileId($reviewFileId);
			$sectionEditorSubmission->setEditorFileId($editorFileId);

			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		}
	}

	/**
	 * Upload the post-review version of an article.
	 * @param $sectionEditorSubmission object
	 * @param $request object
	 */
	function uploadEditorVersion($sectionEditorSubmission, $request) {
		import('classes.file.ArticleFileManager');
		$articleFileManager = new ArticleFileManager($sectionEditorSubmission->getId());
		$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$user =& $request->getUser();

		$fileName = 'upload';
		if ($articleFileManager->uploadedFileExists($fileName) && !HookRegistry::call('SectionEditorAction::uploadEditorVersion', array(&$sectionEditorSubmission))) {
			if ($sectionEditorSubmission->getEditorFileId() != null) {
				$fileId = $articleFileManager->uploadEditorDecisionFile($fileName, $sectionEditorSubmission->getEditorFileId());
			} else {
				$fileId = $articleFileManager->uploadEditorDecisionFile($fileName);
			}
		}

		if (isset($fileId) && $fileId != 0) {
			$sectionEditorSubmission->setEditorFileId($fileId);

			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);

			// Add log
			import('classes.article.log.ArticleLog');
			ArticleLog::logEvent($request, $sectionEditorSubmission, ARTICLE_LOG_EDITOR_FILE, 'log.editor.editorFile', array('fileId' => $sectionEditorSubmission->getEditorFileId()));
		}
	}

	/**
	 * Upload the copyedit version of an article.
	 * @param $sectionEditorSubmission object
	 * @param $copyeditStage string
	 */
	function uploadCopyeditVersion($sectionEditorSubmission, $copyeditStage) {
		$articleId = $sectionEditorSubmission->getId();
		import('classes.file.ArticleFileManager');
		$articleFileManager = new ArticleFileManager($articleId);
		$articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');

		// Perform validity checks.
		$initialSignoff = $signoffDao->build('SIGNOFF_COPYEDITING_INITIAL', ASSOC_TYPE_ARTICLE, $articleId);
		$authorSignoff = $signoffDao->build('SIGNOFF_COPYEDITING_AUTHOR', ASSOC_TYPE_ARTICLE, $articleId);

		if ($copyeditStage == 'final' && $authorSignoff->getDateCompleted() == null) return;
		if ($copyeditStage == 'author' && $initialSignoff->getDateCompleted() == null) return;

		$fileName = 'upload';
		if ($articleFileManager->uploadedFileExists($fileName) && !HookRegistry::call('SectionEditorAction::uploadCopyeditVersion', array(&$sectionEditorSubmission))) {
			if ($sectionEditorSubmission->getFileBySignoffType('SIGNOFF_COPYEDITING_INITIAL', true) != null) {
				$copyeditFileId = $articleFileManager->uploadCopyeditFile($fileName, $sectionEditorSubmission->getFileBySignoffType('SIGNOFF_COPYEDITING_INITIAL', true));
			} else {
				$copyeditFileId = $articleFileManager->uploadCopyeditFile($fileName);
			}
		}

		if (isset($copyeditFileId) && $copyeditFileId != 0) {
			if ($copyeditStage == 'initial') {
				$signoff =& $initialSignoff;
				$signoff->setFileId($copyeditFileId);
				$signoff->setFileRevision($articleFileDao->getRevisionNumber($copyeditFileId));
			} elseif ($copyeditStage == 'author') {
				$signoff =& $authorSignoff;
				$signoff->setFileId($copyeditFileId);
				$signoff->setFileRevision($articleFileDao->getRevisionNumber($copyeditFileId));
			} elseif ($copyeditStage == 'final') {
				$signoff = $signoffDao->build('SIGNOFF_COPYEDITING_FINAL', ASSOC_TYPE_ARTICLE, $articleId);
				$signoff->setFileId($copyeditFileId);
				$signoff->setFileRevision($articleFileDao->getRevisionNumber($copyeditFileId));
			}

			$signoffDao->updateObject($signoff);
		}
	}

	/**
	 * Editor completes initial copyedit (copyeditors disabled).
	 * @param $sectionEditorSubmission object
	 * @param $request object
	 */
	function completeCopyedit($sectionEditorSubmission, $request) {
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$journal =& $request->getJournal();
		$user =& $request->getUser();

		// This is only allowed if copyeditors are disabled.
		if ($journal->getSetting('useCopyeditors')) return;

		if (HookRegistry::call('SectionEditorAction::completeCopyedit', array(&$sectionEditorSubmission))) return;

		$signoff = $signoffDao->build('SIGNOFF_COPYEDITING_INITIAL', ASSOC_TYPE_ARTICLE, $sectionEditorSubmission->getId());
		$signoff->setDateCompleted(Core::getCurrentDate());
		$signoffDao->updateObject($signoff);

		// Add log entry
		import('classes.article.log.ArticleLog');
		ArticleLog::logEvent($request, $sectionEditorSubmission, ARTICLE_LOG_COPYEDIT_INITIAL, 'log.copyedit.initialEditComplete', array('copyeditorName' => $user->getFullName()));
	}

	/**
	 * Section editor completes final copyedit (copyeditors disabled).
	 * @param $sectionEditorSubmission object
	 * @param $request object
	 */
	function completeFinalCopyedit($sectionEditorSubmission, $request) {
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$journal =& $request->getJournal();
		$user =& $request->getUser();

		// This is only allowed if copyeditors are disabled.
		if ($journal->getSetting('useCopyeditors')) return;

		if (HookRegistry::call('SectionEditorAction::completeFinalCopyedit', array(&$sectionEditorSubmission))) return;

		$copyeditSignoff = $signoffDao->build('SIGNOFF_COPYEDITING_FINAL', ASSOC_TYPE_ARTICLE, $sectionEditorSubmission->getId());
		$copyeditSignoff->setDateCompleted(Core::getCurrentDate());
		$signoffDao->updateObject($copyeditSignoff);

		if ($copyEdFile = $sectionEditorSubmission->getFileBySignoffType('SIGNOFF_COPYEDITING_FINAL')) {
			// Set initial layout version to final copyedit version
			$layoutSignoff = $signoffDao->build('SIGNOFF_LAYOUT', ASSOC_TYPE_ARTICLE, $sectionEditorSubmission->getId());

			if (!$layoutSignoff->getFileId()) {
				import('classes.file.ArticleFileManager');
				$articleFileManager = new ArticleFileManager($sectionEditorSubmission->getId());
				if ($layoutFileId = $articleFileManager->copyToLayoutFile($copyEdFile->getFileId(), $copyEdFile->getRevision())) {
					$layoutSignoff->setFileId($layoutFileId);
					$signoffDao->updateObject($layoutSignoff);
				}
			}
		}

		// Add log entry
		import('classes.article.log.ArticleLog');
		ArticleLog::logEvent($request, $sectionEditorSubmission, ARTICLE_LOG_COPYEDIT_FINAL, 'log.copyedit.finalEditComplete', array('copyeditorName' => $user->getFullName()));
	}

	/**
	 * Archive a submission.
	 * @param $sectionEditorSubmission object
	 */
	function archiveSubmission($sectionEditorSubmission, $request) {
		$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$user =& $request->getUser();

		if (HookRegistry::call('SectionEditorAction::archiveSubmission', array(&$sectionEditorSubmission))) return;

		$journal =& $request->getJournal();
		if ($sectionEditorSubmission->getStatus() == STATUS_PUBLISHED) {
			$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
			$publishedArticle =& $publishedArticleDao->getPublishedArticleByArticleId($sectionEditorSubmission->getId());
			$issueDao =& DAORegistry::getDAO('IssueDAO');
			$issue =& $issueDao->getIssueById($publishedArticle->getIssueId(), $publishedArticle->getJournalId());
			if ($issue->getPublished()) {
				// Insert article tombstone
				import('classes.article.ArticleTombstoneManager');
				$articleTombstoneManager = new ArticleTombstoneManager();
				$articleTombstoneManager->insertArticleTombstone($publishedArticle, $journal);
			}
		}

		$sectionEditorSubmission->setStatus(STATUS_ARCHIVED);
		$sectionEditorSubmission->stampStatusModified();

		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);

		// Add log
		import('classes.article.log.ArticleLog');
		ArticleLog::logEvent($request, $sectionEditorSubmission, ARTICLE_LOG_EDITOR_ARCHIVE, 'log.editor.archived');
	}

	/**
	 * Restores a submission to the queue.
	 * @param $sectionEditorSubmission object
	 */
	function restoreToQueue($sectionEditorSubmission, $request) {
		if (HookRegistry::call('SectionEditorAction::restoreToQueue', array(&$sectionEditorSubmission))) return;

		$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');

		// Determine which queue to return the article to: the
		// scheduling queue or the editing queue.
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle =& $publishedArticleDao->getPublishedArticleByArticleId($sectionEditorSubmission->getId());
		$articleSearchIndex = null;
		if ($publishedArticle) {
			$sectionEditorSubmission->setStatus(STATUS_PUBLISHED);
			$issueDao =& DAORegistry::getDAO('IssueDAO');
			$issue =& $issueDao->getIssueById($publishedArticle->getIssueId(), $publishedArticle->getJournalId());
			if ($issue->getPublished()) {
				// delete article tombstone
				$tombstoneDao =& DAORegistry::getDAO('DataObjectTombstoneDAO');
				$tombstoneDao->deleteByDataObjectId($sectionEditorSubmission->getId());
			}
			import('classes.search.ArticleSearchIndex');
			$articleSearchIndex = new ArticleSearchIndex();
			$articleSearchIndex->articleMetadataChanged($publishedArticle);
		} else {
			$sectionEditorSubmission->setStatus(STATUS_QUEUED);
		}
		unset($publishedArticle);

		$sectionEditorSubmission->stampStatusModified();

		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		if ($articleSearchIndex) $articleSearchIndex->articleChangesFinished();

		// Add log
		import('classes.article.log.ArticleLog');
		ArticleLog::logEvent($request, $sectionEditorSubmission, ARTICLE_LOG_EDITOR_RESTORE, 'log.editor.restored');
	}

	/**
	 * Changes the section.
	 * @param $submission object
	 * @param $sectionId int
	 */
	function updateSection($submission, $sectionId) {
		if (HookRegistry::call('SectionEditorAction::updateSection', array(&$submission, &$sectionId))) return;

		$submissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$submission->setSectionId($sectionId); // FIXME validate this ID?
		$submissionDao->updateSectionEditorSubmission($submission);

		// Reindex the submission (may be required to update section-specific ranking).
		$articleSearchIndex = new ArticleSearchIndex();
		$articleSearchIndex->articleMetadataChanged($submission);
		$articleSearchIndex->articleChangesFinished();
	}

	/**
	 * Changes the submission RT comments status.
	 * @param $submission object
	 * @param $commentsStatus int
	 */
	function updateCommentsStatus($submission, $commentsStatus) {
		if (HookRegistry::call('SectionEditorAction::updateCommentsStatus', array(&$submission, &$commentsStatus))) return;

		$submissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$submission->setCommentsStatus($commentsStatus); // FIXME validate this?
		$submissionDao->updateSectionEditorSubmission($submission);
	}

	//
	// Layout Editing
	//

	/**
	 * Upload the layout version of an article.
	 * @param $submission object
	 */
	function uploadLayoutVersion($submission) {
		import('classes.file.ArticleFileManager');
		$articleFileManager = new ArticleFileManager($submission->getId());
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');

		$layoutSignoff = $signoffDao->build('SIGNOFF_LAYOUT', ASSOC_TYPE_ARTICLE, $submission->getId());

		$fileName = 'layoutFile';
		if ($articleFileManager->uploadedFileExists($fileName) && !HookRegistry::call('SectionEditorAction::uploadLayoutVersion', array(&$submission, &$layoutAssignment))) {
			if ($layoutSignoff->getFileId() != null) {
				$layoutFileId = $articleFileManager->uploadLayoutFile($fileName, $layoutSignoff->getFileId());
			} else {
				$layoutFileId = $articleFileManager->uploadLayoutFile($fileName);
			}
			$layoutSignoff->setFileId($layoutFileId);
			$signoffDao->updateObject($layoutSignoff);
		}
	}

	/**
	 * Assign a layout editor to a submission.
	 * @param $submission object
	 * @param $editorId int user ID of the new layout editor
	 * @param $request object
	 */
	function assignLayoutEditor($submission, $editorId, $request) {
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		if (HookRegistry::call('SectionEditorAction::assignLayoutEditor', array(&$submission, &$editorId))) return;

		import('classes.article.log.ArticleLog');

		$layoutSignoff = $signoffDao->build('SIGNOFF_LAYOUT', ASSOC_TYPE_ARTICLE, $submission->getId());
		$layoutProofSignoff = $signoffDao->build('SIGNOFF_PROOFREADING_LAYOUT', ASSOC_TYPE_ARTICLE, $submission->getId());
		if ($layoutSignoff->getUserId()) {
			$layoutEditor =& $userDao->getById($layoutSignoff->getUserId());

			// Add log entry
			ArticleLog::logEvent($request, $submission, ARTICLE_LOG_LAYOUT_UNASSIGN, 'log.layout.layoutEditorUnassigned', array('layoutSignoffId' => $layoutSignoff->getId(), 'editorName' => $layoutEditor->getFullName()));
		}

		$layoutSignoff->setUserId($editorId);
		$layoutSignoff->setDateNotified(null);
		$layoutSignoff->setDateUnderway(null);
		$layoutSignoff->setDateCompleted(null);
		$layoutSignoff->setDateAcknowledged(null);
		$layoutProofSignoff->setUserId($editorId);
		$layoutProofSignoff->setDateNotified(null);
		$layoutProofSignoff->setDateUnderway(null);
		$layoutProofSignoff->setDateCompleted(null);
		$layoutProofSignoff->setDateAcknowledged(null);
		$signoffDao->updateObject($layoutSignoff);
		$signoffDao->updateObject($layoutProofSignoff);

		$layoutEditor =& $userDao->getById($layoutSignoff->getUserId());

		// Add log entry
		ArticleLog::logEvent($request, $submission, ARTICLE_LOG_LAYOUT_ASSIGN, 'log.layout.layoutEditorAssigned', array('layoutSignoffId' => $layoutSignoff->getId(), 'editorName' => $layoutEditor->getFullName()));
	}

	/**
	 * Notifies the current layout editor about an assignment.
	 * @param $submission object
	 * @param $send boolean
	 * @param $reque object
	 * @return boolean true iff ready for redirect
	 */
	function notifyLayoutEditor($submission, $send, $request) {
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');
		$submissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$journal =& $request->getJournal();
		$user =& $request->getUser();

		import('classes.mail.ArticleMailTemplate');
		$email = new ArticleMailTemplate($submission, 'LAYOUT_REQUEST');
		$layoutSignoff = $signoffDao->build('SIGNOFF_LAYOUT', ASSOC_TYPE_ARTICLE, $submission->getId());
		$layoutEditor =& $userDao->getById($layoutSignoff->getUserId());
		if (!isset($layoutEditor)) return true;

		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
			HookRegistry::call('SectionEditorAction::notifyLayoutEditor', array(&$submission, &$layoutEditor, &$email));
			if ($email->isEnabled()) {
				$email->send($request);
			}

			$layoutSignoff->setDateNotified(Core::getCurrentDate());
			$layoutSignoff->setDateUnderway(null);
			$layoutSignoff->setDateCompleted(null);
			$layoutSignoff->setDateAcknowledged(null);
			$signoffDao->updateObject($layoutSignoff);
		} else {
			if (!$request->getUserVar('continued')) {
				$email->addRecipient($layoutEditor->getEmail(), $layoutEditor->getFullName());
				$paramArray = array(
					'layoutEditorName' => $layoutEditor->getFullName(),
					'layoutEditorUsername' => $layoutEditor->getUsername(),
					'editorialContactSignature' => $user->getContactSignature(),
					'submissionLayoutUrl' => $request->url(null, 'layoutEditor', 'submission', $submission->getId())
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm($request->url(null, null, 'notifyLayoutEditor', 'send'), array('articleId' => $submission->getId()));
			return false;
		}
		return true;
	}

	/**
	 * Sends acknowledgement email to the current layout editor.
	 * @param $submission object
	 * @param $send boolean
	 * @param $request object
	 * @return boolean true iff ready for redirect
	 */
	function thankLayoutEditor($submission, $send, $request) {
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');
		$submissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$journal =& $request->getJournal();
		$user =& $request->getUser();

		import('classes.mail.ArticleMailTemplate');
		$email = new ArticleMailTemplate($submission, 'LAYOUT_ACK');

		$layoutSignoff = $signoffDao->build('SIGNOFF_LAYOUT', ASSOC_TYPE_ARTICLE, $submission->getId());
		$layoutEditor =& $userDao->getById($layoutSignoff->getUserId());
		if (!isset($layoutEditor)) return true;

		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
			HookRegistry::call('SectionEditorAction::thankLayoutEditor', array(&$submission, &$layoutEditor, &$email));
			if ($email->isEnabled()) {
				$email->send($request);
			}

			$layoutSignoff->setDateAcknowledged(Core::getCurrentDate());
			$signoffDao->updateObject($layoutSignoff);

		} else {
			if (!$request->getUserVar('continued')) {
				$email->addRecipient($layoutEditor->getEmail(), $layoutEditor->getFullName());
				$paramArray = array(
					'layoutEditorName' => $layoutEditor->getFullName(),
					'editorialContactSignature' => $user->getContactSignature()
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm($request->url(null, null, 'thankLayoutEditor', 'send'), array('articleId' => $submission->getId()));
			return false;
		}
		return true;
	}

	/**
	 * Change the sequence order of a galley.
	 * @param $article object
	 * @param $galleyId int
	 * @param $direction char u = up, d = down
	 */
	function orderGalley($article, $galleyId, $direction) {
		import('classes.submission.layoutEditor.LayoutEditorAction');
		LayoutEditorAction::orderGalley($article, $galleyId, $direction);
	}

	/**
	 * Delete a galley.
	 * @param $article object
	 * @param $galleyId int
	 */
	function deleteGalley($article, $galleyId) {
		import('classes.submission.layoutEditor.LayoutEditorAction');
		LayoutEditorAction::deleteGalley($article, $galleyId);
	}

	/**
	 * Change the sequence order of a supplementary file.
	 * @param $article object
	 * @param $suppFileId int
	 * @param $direction char u = up, d = down
	 */
	function orderSuppFile($article, $suppFileId, $direction) {
		import('classes.submission.layoutEditor.LayoutEditorAction');
		LayoutEditorAction::orderSuppFile($article, $suppFileId, $direction);
	}

	/**
	 * Delete a supplementary file.
	 * @param $article object
	 * @param $suppFileId int
	 */
	function deleteSuppFile($article, $suppFileId) {
		import('classes.submission.layoutEditor.LayoutEditorAction');
		LayoutEditorAction::deleteSuppFile($article, $suppFileId);
	}

	/**
	 * Delete a file from an article.
	 * @param $submission object
	 * @param $fileId int
	 * @param $revision int (optional)
	 */
	function deleteArticleFile($submission, $fileId, $revision) {
		import('classes.file.ArticleFileManager');
		$file =& $submission->getEditorFile();

		if (isset($file) && $file->getFileId() == $fileId && !HookRegistry::call('SectionEditorAction::deleteArticleFile', array(&$submission, &$fileId, &$revision))) {
			$articleFileManager = new ArticleFileManager($submission->getId());
			$articleFileManager->deleteFile($fileId, $revision);
		}
	}

	/**
	 * Delete an image from an article galley.
	 * @param $submission object
	 * @param $fileId int
	 * @param $revision int (optional)
	 */
	function deleteArticleImage($submission, $fileId, $revision) {
		import('classes.file.ArticleFileManager');
		$articleGalleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');
		if (HookRegistry::call('SectionEditorAction::deleteArticleImage', array(&$submission, &$fileId, &$revision))) return;
		foreach ($submission->getGalleys() as $galley) {
			$images =& $articleGalleyDao->getGalleyImages($galley->getId());
			foreach ($images as $imageFile) {
				if ($imageFile->getArticleId() == $submission->getId() && $fileId == $imageFile->getFileId() && $imageFile->getRevision() == $revision) {
					$articleFileManager = new ArticleFileManager($submission->getId());
					$articleFileManager->deleteFile($imageFile->getFileId(), $imageFile->getRevision());
				}
			}
			unset($images);
		}
	}

	/**
	 * Add Submission Note
	 * @param $articleId int
	 * @param $request object
	 */
	function addSubmissionNote($articleId, $request) {
		import('classes.file.ArticleFileManager');

		$noteDao =& DAORegistry::getDAO('NoteDAO');
		$user =& $request->getUser();
		$journal =& $request->getJournal();

		$note = $noteDao->newDataObject();
		$note->setAssocType(ASSOC_TYPE_ARTICLE);
		$note->setAssocId($articleId);
		$note->setUserId($user->getId());
		$note->setDateCreated(Core::getCurrentDate());
		$note->setDateModified(Core::getCurrentDate());
		$note->setTitle($request->getUserVar('title'));
		$note->setContents($request->getUserVar('note'));

		if (!HookRegistry::call('SectionEditorAction::addSubmissionNote', array(&$articleId, &$note))) {
			$articleFileManager = new ArticleFileManager($articleId);
			if ($articleFileManager->uploadedFileExists('upload')) {
				$fileId = $articleFileManager->uploadSubmissionNoteFile('upload');
			} else {
				$fileId = 0;
			}

			$note->setFileId($fileId);

			$noteDao->insertObject($note);
		}
	}

	/**
	 * Remove Submission Note
	 * @param $articleId int
	 * @param $request object
	 */
	function removeSubmissionNote($articleId, $noteId, $fileId) {
		if (HookRegistry::call('SectionEditorAction::removeSubmissionNote', array(&$articleId, &$noteId, &$fileId))) return;

		// if there is an attached file, remove it as well
		if ($fileId) {
			import('classes.file.ArticleFileManager');
			$articleFileManager = new ArticleFileManager($articleId);
			$articleFileManager->deleteFile($fileId);
		}

		$noteDao =& DAORegistry::getDAO('NoteDAO');
		$noteDao->deleteById($noteId);
	}

	/**
	 * Updates Submission Note
	 * @param $articleId int
	 */
	function updateSubmissionNote($articleId, $request) {
		import('classes.file.ArticleFileManager');

		$noteDao =& DAORegistry::getDAO('NoteDAO');

		$user =& $request->getUser();
		$journal =& $request->getJournal();

		$note = new Note();
		$note->setId($request->getUserVar('noteId'));
		$note->setAssocType(ASSOC_TYPE_ARTICLE);
		$note->setAssocId($articleId);
		$note->setUserId($user->getId());
		$note->setDateModified(Core::getCurrentDate());
		$note->setTitle($request->getUserVar('title'));
		$note->setContents($request->getUserVar('note'));
		$note->setFileId($request->getUserVar('fileId'));

		if (HookRegistry::call('SectionEditorAction::updateSubmissionNote', array(&$articleId, &$note))) return;

		$articleFileManager = new ArticleFileManager($articleId);

		// if there is a new file being uploaded
		if ($articleFileManager->uploadedFileExists('upload')) {
			// Attach the new file to the note, overwriting existing file if necessary
			$fileId = $articleFileManager->uploadSubmissionNoteFile('upload', $note->getFileId(), true);
			$note->setFileId($fileId);

		} else {
			if ($request->getUserVar('removeUploadedFile')) {
				$articleFileManager = new ArticleFileManager($articleId);
				$articleFileManager->deleteFile($note->getFileId());
				$note->setFileId(0);
			}
		}

		$noteDao->updateObject($note);
	}

	/**
	 * Clear All Submission Notes
	 * @param $articleId int
	 */
	function clearAllSubmissionNotes($articleId) {
		if (HookRegistry::call('SectionEditorAction::clearAllSubmissionNotes', array(&$articleId))) return;

		import('classes.file.ArticleFileManager');

		$noteDao =& DAORegistry::getDAO('NoteDAO');

		$fileIds = $noteDao->getAllFileIds(ASSOC_TYPE_ARTICLE, $articleId);

		if (!empty($fileIds)) {
			$articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');
			$articleFileManager = new ArticleFileManager($articleId);

			foreach ($fileIds as $fileId) {
				$articleFileManager->deleteFile($fileId);
			}
		}

		$noteDao->deleteByAssoc(ASSOC_TYPE_ARTICLE, $articleId);

	}

	//
	// Comments
	//

	/**
	 * View reviewer comments.
	 * @param $article object
	 * @param $reviewId int
	 */
	function viewPeerReviewComments(&$article, $reviewId) {
		if (HookRegistry::call('SectionEditorAction::viewPeerReviewComments', array(&$article, &$reviewId))) return;

		import('classes.submission.form.comment.PeerReviewCommentForm');

		$commentForm = new PeerReviewCommentForm($article, $reviewId, Validation::isEditor()?ROLE_ID_EDITOR:ROLE_ID_SECTION_EDITOR);
		$commentForm->initData();
		$commentForm->display();
	}

	/**
	 * Post reviewer comments.
	 * @param $article object
	 * @param $reviewId int
	 * @param $emailComment boolean
	 * @param $request Request
	 */
	function postPeerReviewComment(&$article, $reviewId, $emailComment, $request) {
		if (HookRegistry::call('SectionEditorAction::postPeerReviewComment', array(&$article, &$reviewId, &$emailComment))) return;

		import('classes.submission.form.comment.PeerReviewCommentForm');

		$commentForm = new PeerReviewCommentForm($article, $reviewId, Validation::isEditor()?ROLE_ID_EDITOR:ROLE_ID_SECTION_EDITOR);
		$commentForm->readInputData();

		if ($commentForm->validate()) {
			$commentForm->execute();

			// Send a notification to associated users
			import('classes.notification.NotificationManager');
				$notificationManager = new NotificationManager();
			$notificationUsers = $article->getAssociatedUserIds(false, false);
			foreach ($notificationUsers as $userRole) {
				$notificationManager->createNotification(
					$request, $userRole['id'], NOTIFICATION_TYPE_REVIEWER_COMMENT,
					$article->getJournalId(), ASSOC_TYPE_ARTICLE, $article->getId()
				);
			}

			if ($emailComment) {
				$commentForm->email($request);
			}

		} else {
			$commentForm->display();
			return false;
		}
		return true;
	}

	/**
	 * View editor decision comments.
	 * @param $article object
	 */
	function viewEditorDecisionComments($article) {
		if (HookRegistry::call('SectionEditorAction::viewEditorDecisionComments', array(&$article))) return;

		import('classes.submission.form.comment.EditorDecisionCommentForm');

		$commentForm = new EditorDecisionCommentForm($article, Validation::isEditor()?ROLE_ID_EDITOR:ROLE_ID_SECTION_EDITOR);
		$commentForm->initData();
		$commentForm->display();
	}

	/**
	 * Post editor decision comment.
	 * @param $article int
	 * @param $emailComment boolean
	 * @param $request Request
	 */
	function postEditorDecisionComment($article, $emailComment, $request) {
		if (HookRegistry::call('SectionEditorAction::postEditorDecisionComment', array(&$article, &$emailComment))) return;

		import('classes.submission.form.comment.EditorDecisionCommentForm');

		$commentForm = new EditorDecisionCommentForm($article, Validation::isEditor()?ROLE_ID_EDITOR:ROLE_ID_SECTION_EDITOR);
		$commentForm->readInputData();

		if ($commentForm->validate()) {
			$commentForm->execute();

			// Send a notification to associated users
			import('classes.notification.NotificationManager');
				$notificationManager = new NotificationManager();
			$notificationUsers = $article->getAssociatedUserIds(true, false);
			foreach ($notificationUsers as $userRole) {
				$notificationManager->createNotification(
					$request, $userRole['id'], NOTIFICATION_TYPE_EDITOR_DECISION_COMMENT,
					$article->getJournalId(), ASSOC_TYPE_ARTICLE, $article->getId()
				);
			}

			if ($emailComment) {
				$commentForm->email($request);
			}
		} else {
			$commentForm->display();
			return false;
		}
		return true;
	}

	/**
	 * Email editor decision comment.
	 * @param $sectionEditorSubmission object
	 * @param $send boolean
	 * @param $request object
	 */
	function emailEditorDecisionComment($sectionEditorSubmission, $send, $request) {
		$userDao =& DAORegistry::getDAO('UserDAO');
		$articleCommentDao =& DAORegistry::getDAO('ArticleCommentDAO');
		$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');

		$journal =& $request->getJournal();
		$user =& $request->getUser();

		import('classes.mail.ArticleMailTemplate');

		$decisionTemplateMap = array(
			SUBMISSION_EDITOR_DECISION_ACCEPT => 'EDITOR_DECISION_ACCEPT',
			SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS => 'EDITOR_DECISION_REVISIONS',
			SUBMISSION_EDITOR_DECISION_RESUBMIT => 'EDITOR_DECISION_RESUBMIT',
			SUBMISSION_EDITOR_DECISION_DECLINE => 'EDITOR_DECISION_DECLINE'
		);

		$decisions = $sectionEditorSubmission->getDecisions();
		$decisions = array_pop($decisions); // Rounds
		$decision = array_pop($decisions);
		$decisionConst = $decision?$decision['decision']:null;

		$email = new ArticleMailTemplate(
			$sectionEditorSubmission,
			isset($decisionTemplateMap[$decisionConst])?$decisionTemplateMap[$decisionConst]:null
		);

		$copyeditor = $sectionEditorSubmission->getUserBySignoffType('SIGNOFF_COPYEDITING_INITIAL');

		if ($send && !$email->hasErrors()) {
			HookRegistry::call('SectionEditorAction::emailEditorDecisionComment', array(&$sectionEditorSubmission, &$send, &$request));
			$email->send($request);

			if ($decisionConst == SUBMISSION_EDITOR_DECISION_DECLINE) {
				// If the most recent decision was a decline,
				// sending this email archives the submission.
				$sectionEditorSubmission->setStatus(STATUS_ARCHIVED);
				$sectionEditorSubmission->stampStatusModified();
				$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
			}

			$articleComment = new ArticleComment();
			$articleComment->setCommentType(COMMENT_TYPE_EDITOR_DECISION);
			$articleComment->setRoleId(Validation::isEditor()?ROLE_ID_EDITOR:ROLE_ID_SECTION_EDITOR);
			$articleComment->setArticleId($sectionEditorSubmission->getId());
			$articleComment->setAuthorId($sectionEditorSubmission->getUserId());
			$articleComment->setCommentTitle($email->getSubject());
			$articleComment->setComments($email->getBody());
			$articleComment->setDatePosted(Core::getCurrentDate());
			$articleComment->setViewable(true);
			$articleComment->setAssocId($sectionEditorSubmission->getId());
			$articleCommentDao->insertArticleComment($articleComment);

			return true;
		} else {
			if (!$request->getUserVar('continued')) {
				$authorUser =& $userDao->getById($sectionEditorSubmission->getUserId());
				$authorEmail = $authorUser->getEmail();
				$email->assignParams(array(
					'editorialContactSignature' => $user->getContactSignature(),
					'authorName' => $authorUser->getFullName(),
					'journalTitle' => $journal->getLocalizedTitle()
				));
				$email->addRecipient($authorEmail, $authorUser->getFullName());
				if ($journal->getSetting('notifyAllAuthorsOnDecision')) foreach ($sectionEditorSubmission->getAuthors() as $author) {
					if ($author->getEmail() != $authorEmail) {
						$email->addCc ($author->getEmail(), $author->getFullName());
					}
				}
			} elseif ($request->getUserVar('importPeerReviews')) {
				$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
				$reviewAssignments =& $reviewAssignmentDao->getBySubmissionId($sectionEditorSubmission->getId(), $sectionEditorSubmission->getCurrentRound());
				$reviewIndexes =& $reviewAssignmentDao->getReviewIndexesForRound($sectionEditorSubmission->getId(), $sectionEditorSubmission->getCurrentRound());

				$body = '';
				foreach ($reviewAssignments as $reviewAssignment) {
					// If the reviewer has completed the assignment, then import the review.
					if ($reviewAssignment->getDateCompleted() != null && !$reviewAssignment->getCancelled()) {
						// Get the comments associated with this review assignment
						$articleComments =& $articleCommentDao->getArticleComments($sectionEditorSubmission->getId(), COMMENT_TYPE_PEER_REVIEW, $reviewAssignment->getId());
						if($articleComments) {
							$body .= "------------------------------------------------------\n";
							$body .= __('submission.comments.importPeerReviews.reviewerLetter', array('reviewerLetter' => String::enumerateAlphabetically($reviewIndexes[$reviewAssignment->getReviewId()]))) . "\n";
							if (is_array($articleComments)) {
								foreach ($articleComments as $comment) {
									// If the comment is viewable by the author, then add the comment.
									if ($comment->getViewable()) $body .= String::html2text($comment->getComments()) . "\n\n";
								}
							}
							$body .= "------------------------------------------------------\n\n";
						}
						if ($reviewFormId = $reviewAssignment->getReviewFormId()) {
							$reviewId = $reviewAssignment->getId();
							$reviewFormResponseDao =& DAORegistry::getDAO('ReviewFormResponseDAO');
							$reviewFormElementDao =& DAORegistry::getDAO('ReviewFormElementDAO');
							$reviewFormElements =& $reviewFormElementDao->getReviewFormElements($reviewFormId);
							if(!$articleComments) {
								$body .= "------------------------------------------------------\n";
								$body .= __('submission.comments.importPeerReviews.reviewerLetter', array('reviewerLetter' => String::enumerateAlphabetically($reviewIndexes[$reviewAssignment->getReviewId()]))) . "\n\n";
							}
							foreach ($reviewFormElements as $reviewFormElement) if ($reviewFormElement->getIncluded()) {
								$body .= String::html2text($reviewFormElement->getLocalizedQuestion()) . ": \n";
								$reviewFormResponse = $reviewFormResponseDao->getReviewFormResponse($reviewId, $reviewFormElement->getId());

								if ($reviewFormResponse) {
									$possibleResponses = $reviewFormElement->getLocalizedPossibleResponses();
									if (in_array($reviewFormElement->getElementType(), $reviewFormElement->getMultipleResponsesElementTypes())) {
										if ($reviewFormElement->getElementType() == REVIEW_FORM_ELEMENT_TYPE_CHECKBOXES) {
											foreach ($reviewFormResponse->getValue() as $value) {
												$body .= "\t" . String::html2text($possibleResponses[$value-1]['content']) . "\n";
											}
										} else {
											$body .= "\t" . String::html2text($possibleResponses[$reviewFormResponse->getValue()-1]['content']) . "\n";
										}
										$body .= "\n";
									} else {
										$body .= "\t" . $reviewFormResponse->getValue() . "\n\n";
									}
								}
							}
							$body .= "------------------------------------------------------\n\n";
						}
					}
				}
				$oldBody = $email->getBody();
				if (!empty($oldBody)) $oldBody .= "\n";
				$email->setBody($oldBody . $body);
			}

			$email->displayEditForm($request->url(null, null, 'emailEditorDecisionComment', 'send'), array('articleId' => $sectionEditorSubmission->getId()), 'submission/comment/editorDecisionEmail.tpl', array('isAnEditor' => true));

			return false;
		}
	}

	/**
	 * Blind CC the editor decision email to reviewers.
	 * @param $article object
	 * @param $send boolean
	 * @return boolean true iff ready for redirect
	 */
	function bccEditorDecisionCommentToReviewers($article, $send, $request) {
		import('classes.mail.ArticleMailTemplate');
		$email = new ArticleMailTemplate($article, 'SUBMISSION_DECISION_REVIEWERS');

		if ($send && !$email->hasErrors()) {
			HookRegistry::call('SectionEditorAction::bccEditorDecisionCommentToReviewers', array(&$article, &$reviewAssignments, &$email));
			$email->send($request);
			return true;
		} else {
			if (!$request->getUserVar('continued')) {
				$userDao =& DAORegistry::getDAO('UserDAO');
				$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
				$reviewAssignments =& $reviewAssignmentDao->getBySubmissionId($article->getId(), $article->getCurrentRound());
				$email->clearRecipients();
				foreach ($reviewAssignments as $reviewAssignment) {
					if ($reviewAssignment->getDateCompleted() != null && !$reviewAssignment->getCancelled()) {
						$reviewer =& $userDao->getById($reviewAssignment->getReviewerId());
						if (isset($reviewer)) $email->addBcc($reviewer->getEmail(), $reviewer->getFullName());
					}
				}

				$commentsText = "";
				if ($article->getMostRecentEditorDecisionComment()) {
					$comment = $article->getMostRecentEditorDecisionComment();
					$commentsText = String::html2text($comment->getComments()) . "\n\n";
				}
				$user =& $request->getUser();

				$paramArray = array(
					'comments' => $commentsText,
					'editorialContactSignature' => $user->getContactSignature()
				);
				$email->assignParams($paramArray);
			}

			$email->displayEditForm($request->url(null, null, 'bccEditorDecisionCommentToReviewers', 'send'), array('articleId' => $article->getId()));
			return false;
		}
	}

	/**
	 * View copyedit comments.
	 * @param $article object
	 */
	function viewCopyeditComments($article) {
		if (HookRegistry::call('SectionEditorAction::viewCopyeditComments', array(&$article))) return;

		import('classes.submission.form.comment.CopyeditCommentForm');

		$commentForm = new CopyeditCommentForm($article, Validation::isEditor()?ROLE_ID_EDITOR:ROLE_ID_SECTION_EDITOR);
		$commentForm->initData();
		$commentForm->display();
	}

	/**
	 * Post copyedit comment.
	 * @param $article object
	 * @param $emailComment boolean
	 * @param $request object
	 */
	function postCopyeditComment($article, $emailComment, $request) {
		if (HookRegistry::call('SectionEditorAction::postCopyeditComment', array(&$article, &$emailComment))) return;

		import('classes.submission.form.comment.CopyeditCommentForm');

		$commentForm = new CopyeditCommentForm($article, Validation::isEditor()?ROLE_ID_EDITOR:ROLE_ID_SECTION_EDITOR);
		$commentForm->readInputData();

		if ($commentForm->validate()) {
			$commentForm->execute();

			// Send a notification to associated users
			import('classes.notification.NotificationManager');
				$notificationManager = new NotificationManager();
			$notificationUsers = $article->getAssociatedUserIds(true, false);
			foreach ($notificationUsers as $userRole) {
				$notificationManager->createNotification(
					$request, $userRole['id'], NOTIFICATION_TYPE_COPYEDIT_COMMENT,
					$article->getJournalId(), ASSOC_TYPE_ARTICLE, $article->getId()
				);
			}

			if ($emailComment) {
				$commentForm->email($request);
			}
		} else {
			$commentForm->display();
			return false;
		}
		return true;
	}

	/**
	 * View layout comments.
	 * @param $article object
	 */
	function viewLayoutComments($article) {
		if (HookRegistry::call('SectionEditorAction::viewLayoutComments', array(&$article))) return;

		import('classes.submission.form.comment.LayoutCommentForm');

		$commentForm = new LayoutCommentForm($article, Validation::isEditor()?ROLE_ID_EDITOR:ROLE_ID_SECTION_EDITOR);
		$commentForm->initData();
		$commentForm->display();
	}

	/**
	 * Post layout comment.
	 * @param $article object
	 * @param $emailComment boolean
	 * @param $request Request
	 */
	function postLayoutComment($article, $emailComment, $request) {
		if (HookRegistry::call('SectionEditorAction::postLayoutComment', array(&$article, &$emailComment))) return;

		import('classes.submission.form.comment.LayoutCommentForm');

		$commentForm = new LayoutCommentForm($article, Validation::isEditor()?ROLE_ID_EDITOR:ROLE_ID_SECTION_EDITOR);
		$commentForm->readInputData();

		if ($commentForm->validate()) {
			$commentForm->execute();

			// Send a notification to associated users
			import('classes.notification.NotificationManager');
			$notificationManager = new NotificationManager();
			$notificationUsers = $article->getAssociatedUserIds(true, false);
			foreach ($notificationUsers as $userRole) {
				$notificationManager->createNotification(
					$request, $userRole['id'], NOTIFICATION_TYPE_LAYOUT_COMMENT,
					$article->getJournalId(), ASSOC_TYPE_ARTICLE, $article->getId()
				);
			}

			if ($emailComment) {
				$commentForm->email($request);
			}
		} else {
			$commentForm->display();
			return false;
		}
		return true;
	}

	/**
	 * View proofread comments.
	 * @param $article object
	 */
	function viewProofreadComments($article) {
		if (HookRegistry::call('SectionEditorAction::viewProofreadComments', array(&$article))) return;

		import('classes.submission.form.comment.ProofreadCommentForm');

		$commentForm = new ProofreadCommentForm($article, Validation::isEditor()?ROLE_ID_EDITOR:ROLE_ID_SECTION_EDITOR);
		$commentForm->initData();
		$commentForm->display();
	}

	/**
	 * Post proofread comment.
	 * @param $article object
	 * @param $emailComment boolean
	 * @param $request Request
	 */
	function postProofreadComment($article, $emailComment, $request) {
		if (HookRegistry::call('SectionEditorAction::postProofreadComment', array(&$article, &$emailComment))) return;

		import('classes.submission.form.comment.ProofreadCommentForm');

		$commentForm = new ProofreadCommentForm($article, Validation::isEditor()?ROLE_ID_EDITOR:ROLE_ID_SECTION_EDITOR);
		$commentForm->readInputData();

		if ($commentForm->validate()) {
			$commentForm->execute();

			// Send a notification to associated users
			import('classes.notification.NotificationManager');
			$notificationManager = new NotificationManager();
			$notificationUsers = $article->getAssociatedUserIds(true, false);
			foreach ($notificationUsers as $userRole) {
				$notificationManager->createNotification(
					$request, $userRole['id'], NOTIFICATION_TYPE_PROOFREAD_COMMENT,
					$article->getJournalId(), ASSOC_TYPE_ARTICLE, $article->getId()
				);
			}

			if ($emailComment) {
				$commentForm->email($request);
			}

		} else {
			$commentForm->display();
			return false;
		}
		return true;
	}

	/**
	 * Confirms the review assignment on behalf of its reviewer.
	 * @param $reviewId int
	 * @param $accept boolean True === accept; false === decline
	 */
	function confirmReviewForReviewer($reviewId, $accept, $request) {
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$user =& $request->getUser();

		$reviewAssignment =& $reviewAssignmentDao->getById($reviewId);
		$reviewer =& $userDao->getById($reviewAssignment->getReviewerId(), true);

		if (HookRegistry::call('SectionEditorAction::acceptReviewForReviewer', array(&$reviewAssignment, &$reviewer, &$accept))) return;

		// Only confirm the review for the reviewer if
		// he has not previously done so.
		if ($reviewAssignment->getDateConfirmed() == null) {
			$reviewAssignment->setDateReminded(null);
			$reviewAssignment->setReminderWasAutomatic(null);
			$reviewAssignment->setDeclined($accept?0:1);
			$reviewAssignment->setDateConfirmed(Core::getCurrentDate());
			$reviewAssignment->stampModified();
			$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);

			$articleDao =& DAORegistry::getDAO('ArticleDAO');
			$article =& $articleDao->getArticle($reviewAssignment->getSubmissionId());

			// Add log
			import('classes.article.log.ArticleLog');
			ArticleLog::logEvent($request, $article, ARTICLE_LOG_REVIEW_CONFIRM_BY_PROXY, $accept?'log.review.reviewAcceptedByProxy':'log.review.reviewDeclinedByProxy', array('reviewerName' => $reviewer->getFullName(), 'round' => $reviewAssignment->getRound(), 'userName' => $user->getFullName(), 'reviewId' => $reviewAssignment->getId()));
		}
	}

	/**
	 * Upload a review on behalf of its reviewer.
	 * @param $reviewId int
	 */
	function uploadReviewForReviewer($reviewId, $article, $request) {
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$user =& $request->getUser();

		$reviewAssignment =& $reviewAssignmentDao->getById($reviewId);
		$reviewer =& $userDao->getById($reviewAssignment->getReviewerId(), true);

		if (HookRegistry::call('SectionEditorAction::uploadReviewForReviewer', array(&$reviewAssignment, &$reviewer))) return;

		// Upload the review file.
		import('classes.file.ArticleFileManager');
		$articleFileManager = new ArticleFileManager($reviewAssignment->getSubmissionId());
		// Only upload the file if the reviewer has yet to submit a recommendation
		if (($reviewAssignment->getRecommendation() === null || $reviewAssignment->getRecommendation() === '') && !$reviewAssignment->getCancelled()) {
			$fileName = 'upload';
			if ($articleFileManager->uploadedFileExists($fileName)) {
				if ($reviewAssignment->getReviewerFileId() != null) {
					$fileId = $articleFileManager->uploadReviewFile($fileName, $reviewAssignment->getReviewerFileId());
				} else {
					$fileId = $articleFileManager->uploadReviewFile($fileName);
				}
			}
		}

		if (isset($fileId) && $fileId != 0) {
			// Only confirm the review for the reviewer if
			// he has not previously done so.
			if ($reviewAssignment->getDateConfirmed() == null) {
				$reviewAssignment->setDeclined(0);
				$reviewAssignment->setDateConfirmed(Core::getCurrentDate());
			}

			$reviewAssignment->setReviewerFileId($fileId);
			$reviewAssignment->stampModified();
			$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);

			// Add log
			import('classes.article.log.ArticleLog');
			ArticleLog::logEvent($request, $article, ARTICLE_LOG_REVIEW_FILE_BY_PROXY, 'log.review.reviewFileByProxy', array('reviewerName' => $reviewer->getFullName(), 'round' => $reviewAssignment->getRound(), 'userName' => $user->getFullName(), 'reviewId' => $reviewAssignment->getId()));
		}
	}

	/**
	 * Helper method for building submission breadcrumb
	 * @param $articleId
	 * @param $parentPage name of submission component
	 * @return array
	 */
	function submissionBreadcrumb($articleId, $parentPage, $section) {
		$breadcrumb = array();
		if ($articleId) {
			$breadcrumb[] = array(Request::url(null, $section, 'submission', $articleId), "#$articleId", true);
		}

		if ($parentPage) {
			switch($parentPage) {
				case 'summary':
					$parent = array(Request::url(null, $section, 'submission', $articleId), 'submission.summary');
					break;
				case 'review':
					$parent = array(Request::url(null, $section, 'submissionReview', $articleId), 'submission.review');
					break;
				case 'editing':
					$parent = array(Request::url(null, $section, 'submissionEditing', $articleId), 'submission.editing');
					break;
				case 'history':
					$parent = array(Request::url(null, $section, 'submissionHistory', $articleId), 'submission.history');
					break;
			}
			if ($section != 'editor' && $section != 'sectionEditor') {
				$parent[0] = Request::url(null, $section, 'submission', $articleId);
			}
			$breadcrumb[] = $parent;
		}
		return $breadcrumb;
	}
}

?>
