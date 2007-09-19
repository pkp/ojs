<?php

/**
 * @file SectionEditorAction.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 * @class SectionEditorAction
 *
 * SectionEditorAction class.
 *
 * $Id$
 */

import('submission.common.Action');

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
			$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
			$sectionEditorSubmission->setSectionId($sectionId);
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		}
	}

	/**
	 * Records an editor's submission decision.
	 * @param $sectionEditorSubmission object
	 * @param $decision int
	 */
	function recordDecision($sectionEditorSubmission, $decision) {
		$editAssignments =& $sectionEditorSubmission->getEditAssignments();
		if (empty($editAssignments)) return;

		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$user = &Request::getUser();
		$editorDecision = array(
			'editDecisionId' => null,
			'editorId' => $user->getUserId(),
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
			import('article.log.ArticleLog');
			import('article.log.ArticleEventLogEntry');
			ArticleLog::logEvent($sectionEditorSubmission->getArticleId(), ARTICLE_LOG_EDITOR_DECISION, ARTICLE_LOG_TYPE_EDITOR, $user->getUserId(), 'log.editor.decision', array('editorName' => $user->getFullName(), 'articleId' => $sectionEditorSubmission->getArticleId(), 'decision' => Locale::translate($decisions[$decision])));
		}
	}

	/**
	 * Assigns a reviewer to a submission.
	 * @param $sectionEditorSubmission object
	 * @param $reviewerId int
	 */
	function addReviewer($sectionEditorSubmission, $reviewerId, $round = null) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$user = &Request::getUser();

		$reviewer = &$userDao->getUser($reviewerId);

		// Check to see if the requested reviewer is not already
		// assigned to review this article.
		if ($round == null) {
			$round = $sectionEditorSubmission->getCurrentRound();
		}

		$assigned = $sectionEditorSubmissionDao->reviewerExists($sectionEditorSubmission->getArticleId(), $reviewerId, $round);

		// Only add the reviewer if he has not already
		// been assigned to review this article.
		if (!$assigned && isset($reviewer) && !HookRegistry::call('SectionEditorAction::addReviewer', array(&$sectionEditorSubmission, $reviewerId))) {
			$reviewAssignment = &new ReviewAssignment();
			$reviewAssignment->setReviewerId($reviewerId);
			$reviewAssignment->setDateAssigned(Core::getCurrentDate());
			$reviewAssignment->setRound($round);

			$sectionEditorSubmission->addReviewAssignment($reviewAssignment);
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);

			$reviewAssignment = $reviewAssignmentDao->getReviewAssignment($sectionEditorSubmission->getArticleId(), $reviewerId, $round);

			$journal = &Request::getJournal();
			$settingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
			$settings = &$settingsDao->getJournalSettings($journal->getJournalId());
			if (isset($settings['numWeeksPerReview'])) SectionEditorAction::setDueDate($sectionEditorSubmission->getArticleId(), $reviewAssignment->getReviewId(), null, $settings['numWeeksPerReview']);

			// Add log
			import('article.log.ArticleLog');
			import('article.log.ArticleEventLogEntry');
			ArticleLog::logEvent($sectionEditorSubmission->getArticleId(), ARTICLE_LOG_REVIEW_ASSIGN, ARTICLE_LOG_TYPE_REVIEW, $reviewAssignment->getReviewId(), 'log.review.reviewerAssigned', array('reviewerName' => $reviewer->getFullName(), 'articleId' => $sectionEditorSubmission->getArticleId(), 'round' => $round));
		}
	}

	/**
	 * Clears a review assignment from a submission.
	 * @param $sectionEditorSubmission object
	 * @param $reviewId int
	 */
	function clearReview($sectionEditorSubmission, $reviewId) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$user = &Request::getUser();

		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);

		if (isset($reviewAssignment) && $reviewAssignment->getArticleId() == $sectionEditorSubmission->getArticleId() && !HookRegistry::call('SectionEditorAction::clearReview', array(&$sectionEditorSubmission, $reviewAssignment))) {
			$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
			if (!isset($reviewer)) return false;
			$sectionEditorSubmission->removeReviewAssignment($reviewId);
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);

			// Add log
			import('article.log.ArticleLog');
			import('article.log.ArticleEventLogEntry');
			ArticleLog::logEvent($sectionEditorSubmission->getArticleId(), ARTICLE_LOG_REVIEW_CLEAR, ARTICLE_LOG_TYPE_REVIEW, $reviewAssignment->getReviewId(), 'log.review.reviewCleared', array('reviewerName' => $reviewer->getFullName(), 'articleId' => $sectionEditorSubmission->getArticleId(), 'round' => $reviewAssignment->getRound()));
		}		
	}

	/**
	 * Notifies a reviewer about a review assignment.
	 * @param $sectionEditorSubmission object
	 * @param $reviewId int
	 * @return boolean true iff ready for redirect
	 */
	function notifyReviewer($sectionEditorSubmission, $reviewId, $send = false) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');

		$journal = &Request::getJournal();
		$user = &Request::getUser();

		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);

		$isEmailBasedReview = $journal->getSetting('mailSubmissionsToReviewers')==1?true:false;
		$reviewerAccessKeysEnabled = $journal->getSetting('reviewerAccessKeysEnabled');

		// If we're using access keys, disable the address fields
		// for this message. (Prevents security issue: section editor
		// could CC or BCC someone else, or change the reviewer address,
		// in order to get the access key.)
		$preventAddressChanges = $reviewerAccessKeysEnabled;

		import('mail.ArticleMailTemplate');

		$email = &new ArticleMailTemplate($sectionEditorSubmission, $isEmailBasedReview?'REVIEW_REQUEST_ATTACHED':($reviewerAccessKeysEnabled?'REVIEW_REQUEST_ONECLICK':'REVIEW_REQUEST'), null, $isEmailBasedReview?true:null);

		if ($preventAddressChanges) {
			$email->setAddressFieldsEnabled(false);
		}

		if ($reviewAssignment->getArticleId() == $sectionEditorSubmission->getArticleId() && $reviewAssignment->getReviewFileId()) {
			$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
			if (!isset($reviewer)) return true;

			if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
				HookRegistry::call('SectionEditorAction::notifyReviewer', array(&$sectionEditorSubmission, &$reviewAssignment, &$email));
				if ($email->isEnabled()) {
					$email->setAssoc(ARTICLE_EMAIL_REVIEW_NOTIFY_REVIEWER, ARTICLE_EMAIL_TYPE_REVIEW, $reviewId);
					if ($reviewerAccessKeysEnabled) {
						import('security.AccessKeyManager');
						import('pages.reviewer.ReviewerHandler');
						$accessKeyManager =& new AccessKeyManager();

						// Key lifetime is the typical review period plus four weeks
						$keyLifetime = ($journal->getSetting('numWeeksPerReview') + 4) * 7;

						$email->addPrivateParam('ACCESS_KEY', $accessKeyManager->createKey('ReviewerContext', $reviewer->getUserId(), $reviewId, $keyLifetime));
					}

					if ($preventAddressChanges) {
						// Ensure that this messages goes to the reviewer, and the reviewer ONLY.
						$email->clearAllRecipients();
						$email->addRecipient($reviewer->getEmail(), $reviewer->getFullName());
					}
					$email->send();
				}

				$reviewAssignment->setDateNotified(Core::getCurrentDate());
				$reviewAssignment->setCancelled(0);
				$reviewAssignment->stampModified();
				$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
				return true;
			} else {
				if (!Request::getUserVar('continued')) {
					$weekLaterDate = strftime(Config::getVar('general', 'date_format_short'), strtotime('+1 week'));

					if ($reviewAssignment->getDateDue() != null) {
						$reviewDueDate = strftime(Config::getVar('general', 'date_format_short'), strtotime($reviewAssignment->getDateDue()));
					} else {
						$numWeeks = max((int) $journal->getSetting('numWeeksPerReview'), 2);
						$reviewDueDate = strftime(Config::getVar('general', 'date_format_short'), strtotime('+' . $numWeeks . ' week'));
					}

					$submissionUrl = Request::url(null, 'reviewer', 'submission', $reviewId, $reviewerAccessKeysEnabled?array('key' => 'ACCESS_KEY'):array());

					$paramArray = array(
						'reviewerName' => $reviewer->getFullName(),
						'weekLaterDate' => $weekLaterDate,
						'reviewDueDate' => $reviewDueDate,
						'reviewerUsername' => $reviewer->getUsername(),
						'reviewerPassword' => $reviewer->getPassword(),
						'editorialContactSignature' => $user->getContactSignature(),
						'reviewGuidelines' => $journal->getLocalizedSetting('reviewGuidelines'),
						'submissionReviewUrl' => $submissionUrl,
						'abstractTermIfEnabled' => ($sectionEditorSubmission->getAbstract() == ''?'':Locale::translate('article.abstract')),
						'passwordResetUrl' => Request::url(null, 'login', 'resetPassword', $reviewer->getUsername(), array('confirm' => Validation::generatePasswordResetHash($reviewer->getUserId())))
					);
					$email->assignParams($paramArray);
					$email->addRecipient($reviewer->getEmail(), $reviewer->getFullName());
					if ($isEmailBasedReview) {
						// An email-based review process was selected. Attach
						// the current review version.
						import('file.TemporaryFileManager');
						$temporaryFileManager = &new TemporaryFileManager();
						$reviewVersion =& $sectionEditorSubmission->getReviewFile();
						if ($reviewVersion) {
							$temporaryFile = $temporaryFileManager->articleToTemporaryFile($reviewVersion, $user->getUserId());
							$email->addPersistAttachment($temporaryFile);
						}
					}
				}
				$email->displayEditForm(Request::url(null, null, 'notifyReviewer'), array('reviewId' => $reviewId, 'articleId' => $sectionEditorSubmission->getArticleId()));
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
	function cancelReview($sectionEditorSubmission, $reviewId, $send = false) {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');

		$journal = &Request::getJournal();
		$user = &Request::getUser();

		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
		if (!isset($reviewer)) return true;

		if ($reviewAssignment->getArticleId() == $sectionEditorSubmission->getArticleId()) {
			// Only cancel the review if it is currently not cancelled but has previously
			// been initiated, and has not been completed.
			if ($reviewAssignment->getDateNotified() != null && !$reviewAssignment->getCancelled() && ($reviewAssignment->getDateCompleted() == null || $reviewAssignment->getDeclined())) {
				import('mail.ArticleMailTemplate');
				$email = &new ArticleMailTemplate($sectionEditorSubmission, 'REVIEW_CANCEL');

				if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
					HookRegistry::call('SectionEditorAction::cancelReview', array(&$sectionEditorSubmission, &$reviewAssignment, &$email));
					if ($email->isEnabled()) {
						$email->setAssoc(ARTICLE_EMAIL_REVIEW_CANCEL, ARTICLE_EMAIL_TYPE_REVIEW, $reviewId);
						$email->send();
					}

					$reviewAssignment->setCancelled(1);
					$reviewAssignment->setDateCompleted(Core::getCurrentDate());
					$reviewAssignment->stampModified();

					$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);

					// Add log
					import('article.log.ArticleLog');
					import('article.log.ArticleEventLogEntry');
					ArticleLog::logEvent($sectionEditorSubmission->getArticleId(), ARTICLE_LOG_REVIEW_CANCEL, ARTICLE_LOG_TYPE_REVIEW, $reviewAssignment->getReviewId(), 'log.review.reviewCancelled', array('reviewerName' => $reviewer->getFullName(), 'articleId' => $sectionEditorSubmission->getArticleId(), 'round' => $reviewAssignment->getRound()));
				} else {
					if (!Request::getUserVar('continued')) {
						$email->addRecipient($reviewer->getEmail(), $reviewer->getFullName());

						$paramArray = array(
							'reviewerName' => $reviewer->getFullName(),
							'reviewerUsername' => $reviewer->getUsername(),
							'reviewerPassword' => $reviewer->getPassword(),
							'editorialContactSignature' => $user->getContactSignature()
						);
						$email->assignParams($paramArray);
					}
					$email->displayEditForm(Request::url(null, null, 'cancelReview', 'send'), array('reviewId' => $reviewId, 'articleId' => $sectionEditorSubmission->getArticleId()));
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
	 * @return boolean true iff no error was encountered
	 */
	function remindReviewer($sectionEditorSubmission, $reviewId, $send = false) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');

		$journal = &Request::getJournal();
		$user = &Request::getUser();
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$reviewerAccessKeysEnabled = $journal->getSetting('reviewerAccessKeysEnabled');

		// If we're using access keys, disable the address fields
		// for this message. (Prevents security issue: section editor
		// could CC or BCC someone else, or change the reviewer address,
		// in order to get the access key.)
		$preventAddressChanges = $reviewerAccessKeysEnabled;

		import('mail.ArticleMailTemplate');
		$email = &new ArticleMailTemplate($sectionEditorSubmission, $reviewerAccessKeysEnabled?'REVIEW_REMIND_ONECLICK':'REVIEW_REMIND');

		if ($preventAddressChanges) {
			$email->setAddressFieldsEnabled(false);
		}

		if ($send && !$email->hasErrors()) {
			HookRegistry::call('SectionEditorAction::remindReviewer', array(&$sectionEditorSubmission, &$reviewAssignment, &$email));
			$email->setAssoc(ARTICLE_EMAIL_REVIEW_REMIND, ARTICLE_EMAIL_TYPE_REVIEW, $reviewId);

			$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());

			if ($reviewerAccessKeysEnabled) {
				import('security.AccessKeyManager');
				import('pages.reviewer.ReviewerHandler');
				$accessKeyManager =& new AccessKeyManager();

				// Key lifetime is the typical review period plus four weeks
				$keyLifetime = ($journal->getSetting('numWeeksPerReview') + 4) * 7;
				$email->addPrivateParam('ACCESS_KEY', $accessKeyManager->createKey('ReviewerContext', $reviewer->getUserId(), $reviewId, $keyLifetime));
			}

			if ($preventAddressChanges) {
				// Ensure that this messages goes to the reviewer, and the reviewer ONLY.
				$email->clearAllRecipients();
				$email->addRecipient($reviewer->getEmail(), $reviewer->getFullName());
			}

			$email->send();

			$reviewAssignment->setDateReminded(Core::getCurrentDate());
			$reviewAssignment->setReminderWasAutomatic(0);
			$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
			return true;
		} elseif ($reviewAssignment->getArticleId() == $sectionEditorSubmission->getArticleId()) {
			$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());

			if (!Request::getUserVar('continued')) {
				if (!isset($reviewer)) return true;
				$email->addRecipient($reviewer->getEmail(), $reviewer->getFullName());

				$submissionUrl = Request::url(null, 'reviewer', 'submission', $reviewId, $reviewerAccessKeysEnabled?array('key' => 'ACCESS_KEY'):array());

				//
				// FIXME: Assign correct values!
				//
				$paramArray = array(
					'reviewerName' => $reviewer->getFullName(),
					'reviewerUsername' => $reviewer->getUsername(),
					'reviewerPassword' => $reviewer->getPassword(),
					'reviewDueDate' => strftime(Config::getVar('general', 'date_format_short'), strtotime($reviewAssignment->getDateDue())),
					'editorialContactSignature' => $user->getContactSignature(),
					'passwordResetUrl' => Request::url(null, 'login', 'resetPassword', $reviewer->getUsername(), array('confirm' => Validation::generatePasswordResetHash($reviewer->getUserId()))),
					'submissionReviewUrl' => $submissionUrl
				);
				$email->assignParams($paramArray);

			}
			$email->displayEditForm(
				Request::url(null, null, 'remindReviewer', 'send'),
				array(
					'reviewerId' => $reviewer->getUserId(),
					'articleId' => $sectionEditorSubmission->getArticleId(),
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
	 * @return boolean true iff ready for redirect
	 */
	function thankReviewer($sectionEditorSubmission, $reviewId, $send = false) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');

		$journal = &Request::getJournal();
		$user = &Request::getUser();

		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);

		import('mail.ArticleMailTemplate');
		$email = &new ArticleMailTemplate($sectionEditorSubmission, 'REVIEW_ACK');

		if ($reviewAssignment->getArticleId() == $sectionEditorSubmission->getArticleId()) {
			$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
			if (!isset($reviewer)) return true;

			if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
				HookRegistry::call('SectionEditorAction::thankReviewer', array(&$sectionEditorSubmission, &$reviewAssignment, &$email));
				if ($email->isEnabled()) {
					$email->setAssoc(ARTICLE_EMAIL_REVIEW_THANK_REVIEWER, ARTICLE_EMAIL_TYPE_REVIEW, $reviewId);
					$email->send();
				}

				$reviewAssignment->setDateAcknowledged(Core::getCurrentDate());
				$reviewAssignment->stampModified();
				$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
			} else {
				if (!Request::getUserVar('continued')) {
					$email->addRecipient($reviewer->getEmail(), $reviewer->getFullName());

					$paramArray = array(
						'reviewerName' => $reviewer->getFullName(),
						'editorialContactSignature' => $user->getContactSignature()
					);
					$email->assignParams($paramArray);
				}
				$email->displayEditForm(Request::url(null, null, 'thankReviewer', 'send'), array('reviewId' => $reviewId, 'articleId' => $sectionEditorSubmission->getArticleId()));
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
	 */
	function rateReviewer($articleId, $reviewId, $quality = null) {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$user = &Request::getUser();

		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
		if (!isset($reviewer)) return false;

		if ($reviewAssignment->getArticleId() == $articleId && !HookRegistry::call('SectionEditorAction::rateReviewer', array(&$reviewAssignment, &$reviewer, &$quality))) {
			// Ensure that the value for quality
			// is between 1 and 5.
			if ($quality != null && ($quality >= 1 && $quality <= 5)) {
				$reviewAssignment->setQuality($quality);
			}

			$reviewAssignment->setDateRated(Core::getCurrentDate());
			$reviewAssignment->stampModified();

			$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);

			// Add log
			import('article.log.ArticleLog');
			import('article.log.ArticleEventLogEntry');
			ArticleLog::logEvent($articleId, ARTICLE_LOG_REVIEW_RATE, ARTICLE_LOG_TYPE_REVIEW, $reviewAssignment->getReviewId(), 'log.review.reviewerRated', array('reviewerName' => $reviewer->getFullName(), 'articleId' => $articleId, 'round' => $reviewAssignment->getRound()));
		}
	}

	/**
	 * Makes a reviewer's annotated version of an article available to the author.
	 * @param $articleId int
	 * @param $reviewId int
	 * @param $viewable boolean
	 */
	function makeReviewerFileViewable($articleId, $reviewId, $fileId, $revision, $viewable = false) {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');

		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$articleFile = &$articleFileDao->getArticleFile($fileId, $revision);

		if ($reviewAssignment->getArticleId() == $articleId && $reviewAssignment->getReviewerFileId() == $fileId && !HookRegistry::call('SectionEditorAction::makeReviewerFileViewable', array(&$reviewAssignment, &$articleFile, &$viewable))) {
			$articleFile->setViewable($viewable);
			$articleFileDao->updateArticleFile($articleFile);				
		}
	}

	/**
	 * Sets the due date for a review assignment.
	 * @param $articleId int
	 * @param $reviewId int
	 * @param $dueDate string
	 * @param $numWeeks int
	 */
	function setDueDate($articleId, $reviewId, $dueDate = null, $numWeeks = null) {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$user = &Request::getUser();

		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
		if (!isset($reviewer)) return false;

		if ($reviewAssignment->getArticleId() == $articleId && !HookRegistry::call('SectionEditorAction::setDueDate', array(&$reviewAssignment, &$reviewer, &$dueDate, &$numWeeks))) {
			if ($dueDate != null) {
				$dueDateParts = explode('-', $dueDate);
				$today = getDate();

				// Ensure that the specified due date is today or after today's date.
				if ($dueDateParts[0] >= $today['year'] && ($dueDateParts[1] > $today['mon'] || ($dueDateParts[1] == $today['mon'] && $dueDateParts[2] >= $today['mday']))) {
					$reviewAssignment->setDateDue(date('Y-m-d H:i:s', mktime(0, 0, 0, $dueDateParts[1], $dueDateParts[2], $dueDateParts[0])));
				}
				else {
					$today = getDate();
					$todayTimestamp = mktime(0, 0, 0, $today['mon'], $today['mday'], $today['year']);
					$reviewAssignment->setDateDue(date('Y-m-d H:i:s', $todayTimestamp));
				}
			} else {
				$today = getDate();
				$todayTimestamp = mktime(0, 0, 0, $today['mon'], $today['mday'], $today['year']);

				// Add the equivilant of $numWeeks weeks, measured in seconds, to $todaysTimestamp.
				$newDueDateTimestamp = $todayTimestamp + ($numWeeks * 7 * 24 * 60 * 60);

				$reviewAssignment->setDateDue(date('Y-m-d H:i:s', $newDueDateTimestamp));
			}

			$reviewAssignment->stampModified();
			$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);

			// Add log
			import('article.log.ArticleLog');
			import('article.log.ArticleEventLogEntry');
			ArticleLog::logEvent(
				$articleId,
				ARTICLE_LOG_REVIEW_SET_DUE_DATE,
				ARTICLE_LOG_TYPE_REVIEW,
				$reviewAssignment->getReviewId(),
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

	/**
	 * Notifies an author that a submission was unsuitable.
	 * @param $sectionEditorSubmission object
	 * @return boolean true iff ready for redirect
	 */
	function unsuitableSubmission($sectionEditorSubmission, $send = false) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');

		$journal = &Request::getJournal();
		$user = &Request::getUser();

		$author = &$userDao->getUser($sectionEditorSubmission->getUserId());
		if (!isset($author)) return true;

		import('mail.ArticleMailTemplate');
		$email = &new ArticleMailTemplate($sectionEditorSubmission, 'SUBMISSION_UNSUITABLE');

		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
			HookRegistry::call('SectionEditorAction::unsuitableSubmission', array(&$sectionEditorSubmission, &$author, &$email));
			if ($email->isEnabled()) {
				$email->setAssoc(ARTICLE_EMAIL_EDITOR_NOTIFY_AUTHOR_UNSUITABLE, ARTICLE_EMAIL_TYPE_EDITOR, $user->getUserId());
				$email->send();
			}
			SectionEditorAction::archiveSubmission($sectionEditorSubmission);
			return true;
		} else {
			if (!Request::getUserVar('continued')) {
				$paramArray = array(
					'editorialContactSignature' => $user->getContactSignature(),
					'authorName' => $author->getFullName()
				);
				$email->assignParams($paramArray);
				$email->addRecipient($author->getEmail(), $author->getFullName());
			}
			$email->displayEditForm(Request::url(null, null, 'unsuitableSubmission'), array('articleId' => $sectionEditorSubmission->getArticleId()));
			return false;
		}
	}

	/**
	 * Sets the reviewer recommendation for a review assignment.
	 * Also concatenates the reviewer and editor comments from Peer Review and adds them to Editor Review.
	 * @param $articleId int
	 * @param $reviewId int
	 * @param $recommendation int
	 */
	function setReviewerRecommendation($articleId, $reviewId, $recommendation, $acceptOption) {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$user = &Request::getUser();

		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId(), true);

		if ($reviewAssignment->getArticleId() == $articleId && !HookRegistry::call('SectionEditorAction::setReviewerRecommendation', array(&$reviewAssignment, &$reviewer, &$recommendation, &$acceptOption))) {
			$reviewAssignment->setRecommendation($recommendation);

			$nowDate = Core::getCurrentDate();
			if (!$reviewAssignment->getDateConfirmed()) {
				$reviewAssignment->setDateConfirmed($nowDate);
			}
			$reviewAssignment->setDateCompleted($nowDate);
			$reviewAssignment->stampModified();

			$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);

			// Add log
			import('article.log.ArticleLog');
			import('article.log.ArticleEventLogEntry');
			ArticleLog::logEvent($articleId, ARTICLE_LOG_REVIEW_RECOMMENDATION_BY_PROXY, ARTICLE_LOG_TYPE_REVIEW, $reviewAssignment->getReviewId(), 'log.review.reviewRecommendationSetByProxy', array('editorName' => $user->getFullName(), 'reviewerName' => $reviewer->getFullName(), 'articleId' => $articleId, 'round' => $reviewAssignment->getRound()));
		}
	}

	/**
	 * Set the file to use as the default copyedit file.
	 * @param $sectionEditorSubmission object
	 * @param $fileId int
	 * @param $revision int
	 * TODO: SECURITY!
	 */
	function setCopyeditFile($sectionEditorSubmission, $fileId, $revision) {
		import('file.ArticleFileManager');
		$articleFileManager = &new ArticleFileManager($sectionEditorSubmission->getArticleId());
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');
		$user = &Request::getUser();

		if (!HookRegistry::call('SectionEditorAction::setCopyeditFile', array(&$sectionEditorSubmission, &$fileId, &$revision))) {
			// Copy the file from the editor decision file folder to the copyedit file folder
			$newFileId = $articleFileManager->copyToCopyeditFile($fileId, $revision);

			$sectionEditorSubmission->setCopyeditFileId($newFileId);
			$sectionEditorSubmission->setCopyeditorInitialRevision(1);

			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);

			// Add log
			import('article.log.ArticleLog');
			import('article.log.ArticleEventLogEntry');
			ArticleLog::logEvent($sectionEditorSubmission->getArticleId(), ARTICLE_LOG_COPYEDIT_SET_FILE, ARTICLE_LOG_TYPE_COPYEDIT, $sectionEditorSubmission->getCopyeditFileId(), 'log.copyedit.copyeditFileSet');
		}
	}

	/**
	 * Resubmit the file for review.
	 * @param $sectionEditorSubmission object
	 * @param $fileId int
	 * @param $revision int
	 * TODO: SECURITY!
	 */
	function resubmitFile($sectionEditorSubmission, $fileId, $revision) {
		import('file.ArticleFileManager');
		$articleFileManager = &new ArticleFileManager($sectionEditorSubmission->getArticleId());
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');
		$user = &Request::getUser();

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
				if ($reviewAssignment->getRecommendation() != null) {
					// Then this reviewer submitted a review.
					SectionEditorAction::addReviewer($sectionEditorSubmission, $reviewAssignment->getReviewerId(), $sectionEditorSubmission->getCurrentRound());
				}
			}


			// Add log
			import('article.log.ArticleLog');
			import('article.log.ArticleEventLogEntry');
			ArticleLog::logEvent($sectionEditorSubmission->getArticleId(), ARTICLE_LOG_REVIEW_RESUBMIT, ARTICLE_LOG_TYPE_EDITOR, $user->getUserId(), 'log.review.resubmit', array('articleId' => $sectionEditorSubmission->getArticleId()));
		}
	}

	/**
	 * Assigns a copyeditor to a submission.
	 * @param $sectionEditorSubmission object
	 * @param $copyeditorId int
	 */
	function selectCopyeditor($sectionEditorSubmission, $copyeditorId) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$user = &Request::getUser();

		// Check to see if the requested copyeditor is not already
		// assigned to copyedit this article.
		$assigned = $sectionEditorSubmissionDao->copyeditorExists($sectionEditorSubmission->getArticleId(), $copyeditorId);

		// Only add the copyeditor if he has not already
		// been assigned to review this article.
		if (!$assigned && !HookRegistry::call('SectionEditorAction::selectCopyeditor', array(&$sectionEditorSubmission, &$copyeditorId))) {
			$sectionEditorSubmission->setCopyeditorId($copyeditorId);
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);	

			$copyeditor = &$userDao->getUser($copyeditorId);

			// Add log
			import('article.log.ArticleLog');
			import('article.log.ArticleEventLogEntry');
			ArticleLog::logEvent($sectionEditorSubmission->getArticleId(), ARTICLE_LOG_COPYEDIT_ASSIGN, ARTICLE_LOG_TYPE_COPYEDIT, $copyeditorId, 'log.copyedit.copyeditorAssigned', array('copyeditorName' => $copyeditor->getFullName(), 'articleId' => $sectionEditorSubmission->getArticleId()));
		}
	}

	/**
	 * Notifies a copyeditor about a copyedit assignment.
	 * @param $sectionEditorSubmission object
	 * @return boolean true iff ready for redirect
	 */
	function notifyCopyeditor($sectionEditorSubmission, $send = false) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();

		import('mail.ArticleMailTemplate');
		$email = &new ArticleMailTemplate($sectionEditorSubmission, 'COPYEDIT_REQUEST');

		$copyeditor = &$userDao->getUser($sectionEditorSubmission->getCopyeditorId());
		if (!isset($copyeditor)) return true;

		if ($sectionEditorSubmission->getInitialCopyeditFile() && (!$email->isEnabled() || ($send && !$email->hasErrors()))) {
			HookRegistry::call('SectionEditorAction::notifyCopyeditor', array(&$sectionEditorSubmission, &$copyeditor, &$email));
			if ($email->isEnabled()) {
				$email->setAssoc(ARTICLE_EMAIL_COPYEDIT_NOTIFY_COPYEDITOR, ARTICLE_EMAIL_TYPE_COPYEDIT, $sectionEditorSubmission->getArticleId());
				$email->send();
			}

			$sectionEditorSubmission->setCopyeditorDateNotified(Core::getCurrentDate());
			$sectionEditorSubmission->setCopyeditorDateUnderway(null);
			$sectionEditorSubmission->setCopyeditorDateCompleted(null);
			$sectionEditorSubmission->setCopyeditorDateAcknowledged(null);
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		} else {
			if (!Request::getUserVar('continued')) {
				$email->addRecipient($copyeditor->getEmail(), $copyeditor->getFullName());
				$paramArray = array(
					'copyeditorName' => $copyeditor->getFullName(),
					'copyeditorUsername' => $copyeditor->getUsername(),
					'copyeditorPassword' => $copyeditor->getPassword(),
					'editorialContactSignature' => $user->getContactSignature(),
					'submissionCopyeditingUrl' => Request::url(null, 'copyeditor', 'submission', $sectionEditorSubmission->getArticleId())
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::url(null, null, 'notifyCopyeditor', 'send'), array('articleId' => $sectionEditorSubmission->getArticleId()));
			return false;
		}
		return true;
	}

	/**
	 * Initiates the initial copyedit stage when the editor does the copyediting.
	 * @param $sectionEditorSubmission object
	 */
	function initiateCopyedit($sectionEditorSubmission) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');

		// Only allow copyediting to be initiated if a copyedit file exists.
		if ($sectionEditorSubmission->getInitialCopyeditFile() && !HookRegistry::call('SectionEditorAction::initiateCopyedit', array(&$sectionEditorSubmission))) {
			$sectionEditorSubmission->setCopyeditorDateNotified(Core::getCurrentDate());
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		}
	}

	/**
	 * Thanks a copyeditor about a copyedit assignment.
	 * @param $sectionEditorSubmission object
	 * @return boolean true iff ready for redirect
	 */
	function thankCopyeditor($sectionEditorSubmission, $send = false) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();

		import('mail.ArticleMailTemplate');
		$email = &new ArticleMailTemplate($sectionEditorSubmission, 'COPYEDIT_ACK');

		$copyeditor = &$userDao->getUser($sectionEditorSubmission->getCopyeditorId());
		if (!isset($copyeditor)) return true;

		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
			HookRegistry::call('SectionEditorAction::thankCopyeditor', array(&$sectionEditorSubmission, &$copyeditor, &$email));
			if ($email->isEnabled()) {
				$email->setAssoc(ARTICLE_EMAIL_COPYEDIT_NOTIFY_ACKNOWLEDGE, ARTICLE_EMAIL_TYPE_COPYEDIT, $sectionEditorSubmission->getArticleId());
				$email->send();
			}

			$sectionEditorSubmission->setCopyeditorDateAcknowledged(Core::getCurrentDate());
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		} else {
			if (!Request::getUserVar('continued')) {
				$email->addRecipient($copyeditor->getEmail(), $copyeditor->getFullName());
				$paramArray = array(
					'copyeditorName' => $copyeditor->getFullName(),
					'editorialContactSignature' => $user->getContactSignature()
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::url(null, null, 'thankCopyeditor', 'send'), array('articleId' => $sectionEditorSubmission->getArticleId()));
			return false;
		}
		return true;
	}

	/**
	 * Notifies the author that the copyedit is complete.
	 * @param $sectionEditorSubmission object
	 * @return true iff ready for redirect
	 */
	function notifyAuthorCopyedit($sectionEditorSubmission, $send = false) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();

		import('mail.ArticleMailTemplate');
		$email = &new ArticleMailTemplate($sectionEditorSubmission, 'COPYEDIT_AUTHOR_REQUEST');

		$author = &$userDao->getUser($sectionEditorSubmission->getUserId());
		if (!isset($author)) return true;

		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
			HookRegistry::call('SectionEditorAction::notifyAuthorCopyedit', array(&$sectionEditorSubmission, &$author, &$email));
			if ($email->isEnabled()) {
				$email->setAssoc(ARTICLE_EMAIL_COPYEDIT_NOTIFY_AUTHOR, ARTICLE_EMAIL_TYPE_COPYEDIT, $sectionEditorSubmission->getArticleId());
				$email->send();
			}

			$sectionEditorSubmission->setCopyeditorDateAuthorNotified(Core::getCurrentDate());
			$sectionEditorSubmission->setCopyeditorDateAuthorUnderway(null);
			$sectionEditorSubmission->setCopyeditorDateAuthorCompleted(null);
			$sectionEditorSubmission->setCopyeditorDateAuthorAcknowledged(null);
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		} else {
			if (!Request::getUserVar('continued')) {
				$email->addRecipient($author->getEmail(), $author->getFullName());
				$paramArray = array(
					'authorName' => $author->getFullName(),
					'authorUsername' => $author->getUsername(),
					'authorPassword' => $author->getPassword(),
					'editorialContactSignature' => $user->getContactSignature(),
					'submissionCopyeditingUrl' => Request::url(null, 'author', 'submission', $sectionEditorSubmission->getArticleId())

				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::url(null, null, 'notifyAuthorCopyedit', 'send'), array('articleId' => $sectionEditorSubmission->getArticleId()));
			return false;
		}
		return true;
	}

	/**
	 * Thanks an author for completing editor / author review.
	 * @param $sectionEditorSubmission object
	 * @return boolean true iff ready for redirect
	 */
	function thankAuthorCopyedit($sectionEditorSubmission, $send = false) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();

		import('mail.ArticleMailTemplate');
		$email = &new ArticleMailTemplate($sectionEditorSubmission, 'COPYEDIT_AUTHOR_ACK');

		$author = &$userDao->getUser($sectionEditorSubmission->getUserId());
		if (!isset($author)) return true;

		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
			HookRegistry::call('SectionEditorAction::thankAuthorCopyedit', array(&$sectionEditorSubmission, &$author, &$email));
			if ($email->isEnabled()) {
				$email->setAssoc(ARTICLE_EMAIL_COPYEDIT_NOTIFY_AUTHOR_ACKNOWLEDGE, ARTICLE_EMAIL_TYPE_COPYEDIT, $sectionEditorSubmission->getArticleId());
				$email->send();
			}

			$sectionEditorSubmission->setCopyeditorDateAuthorAcknowledged(Core::getCurrentDate());
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		} else {
			if (!Request::getUserVar('continued')) {
				$email->addRecipient($author->getEmail(), $author->getFullName());
				$paramArray = array(
					'authorName' => $author->getFullName(),
					'editorialContactSignature' => $user->getContactSignature()
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::url(null, null, 'thankAuthorCopyedit', 'send'), array('articleId' => $sectionEditorSubmission->getArticleId()));
			return false;
		}
		return true;
	}

	/**
	 * Notify copyeditor about final copyedit.
	 * @param $sectionEditorSubmission object
	 * @param $send boolean
	 * @return boolean true iff ready for redirect
	 */
	function notifyFinalCopyedit($sectionEditorSubmission, $send = false) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();

		import('mail.ArticleMailTemplate');
		$email = &new ArticleMailTemplate($sectionEditorSubmission, 'COPYEDIT_FINAL_REQUEST');

		$copyeditor = &$userDao->getUser($sectionEditorSubmission->getCopyeditorId());
		if (!isset($copyeditor)) return true;

		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
			HookRegistry::call('SectionEditorAction::notifyFinalCopyedit', array(&$sectionEditorSubmission, &$copyeditor, &$email));
			if ($email->isEnabled()) {
				$email->setAssoc(ARTICLE_EMAIL_COPYEDIT_NOTIFY_FINAL, ARTICLE_EMAIL_TYPE_COPYEDIT, $sectionEditorSubmission->getArticleId());
				$email->send();
			}

			$sectionEditorSubmission->setCopyeditorDateFinalNotified(Core::getCurrentDate());
			$sectionEditorSubmission->setCopyeditorDateFinalUnderway(null);
			$sectionEditorSubmission->setCopyeditorDateFinalCompleted(null);
			$sectionEditorSubmission->setCopyeditorDateFinalAcknowledged(null);

			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		} else {
			if (!Request::getUserVar('continued')) {
				$email->addRecipient($copyeditor->getEmail(), $copyeditor->getFullName());
				$paramArray = array(
					'copyeditorName' => $copyeditor->getFullName(),
					'copyeditorUsername' => $copyeditor->getUsername(),
					'copyeditorPassword' => $copyeditor->getPassword(),
					'editorialContactSignature' => $user->getContactSignature(),
					'submissionCopyeditingUrl' => Request::url(null, 'copyeditor', 'submission', $sectionEditorSubmission->getArticleId())
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::url(null, null, 'notifyFinalCopyedit', 'send'), array('articleId' => $sectionEditorSubmission->getArticleId()));
			return false;
		}
		return true;
	}

	/**
	 * Thank copyeditor for completing final copyedit.
	 * @param $sectionEditorSubmission object
	 * @return boolean true iff ready for redirect
	 */
	function thankFinalCopyedit($sectionEditorSubmission, $send = false) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();

		import('mail.ArticleMailTemplate');
		$email = &new ArticleMailTemplate($sectionEditorSubmission, 'COPYEDIT_FINAL_ACK');

		$copyeditor = &$userDao->getUser($sectionEditorSubmission->getCopyeditorId());
		if (!isset($copyeditor)) return true;

		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
			HookRegistry::call('SectionEditorAction::thankFinalCopyedit', array(&$sectionEditorSubmission, &$copyeditor, &$email));
			if ($email->isEnabled()) {
				$email->setAssoc(ARTICLE_EMAIL_COPYEDIT_NOTIFY_FINAL_ACKNOWLEDGE, ARTICLE_EMAIL_TYPE_COPYEDIT, $sectionEditorSubmission->getArticleId());
				$email->send();
			}

			$sectionEditorSubmission->setCopyeditorDateFinalAcknowledged(Core::getCurrentDate());
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		} else {
			if (!Request::getUserVar('continued')) {
				$email->addRecipient($copyeditor->getEmail(), $copyeditor->getFullName());
				$paramArray = array(
					'copyeditorName' => $copyeditor->getFullName(),
					'editorialContactSignature' => $user->getContactSignature()
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::url(null, null, 'thankFinalCopyedit', 'send'), array('articleId' => $sectionEditorSubmission->getArticleId()));
			return false;
		}
		return true;
	}

	/**
	 * Upload the review version of an article.
	 * @param $sectionEditorSubmission object
	 */
	function uploadReviewVersion($sectionEditorSubmission) {
		import('file.ArticleFileManager');
		$articleFileManager = &new ArticleFileManager($sectionEditorSubmission->getArticleId());
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');

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
	 */
	function uploadEditorVersion($sectionEditorSubmission) {
		import('file.ArticleFileManager');
		$articleFileManager = &new ArticleFileManager($sectionEditorSubmission->getArticleId());
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$user = &Request::getUser();

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
			import('article.log.ArticleLog');
			import('article.log.ArticleEventLogEntry');
			ArticleLog::logEvent($sectionEditorSubmission->getArticleId(), ARTICLE_LOG_EDITOR_FILE, ARTICLE_LOG_TYPE_EDITOR, $sectionEditorSubmission->getEditorFileId(), 'log.editor.editorFile');
		}
	}

	/**
	 * Upload the copyedit version of an article.
	 * @param $sectionEditorSubmission object
	 * @param $copyeditStage string
	 */
	function uploadCopyeditVersion($sectionEditorSubmission, $copyeditStage) {
		import('file.ArticleFileManager');
		$articleFileManager = &new ArticleFileManager($sectionEditorSubmission->getArticleId());
		$articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');

		// Perform validity checks.
		if ($copyeditStage == 'final' && $sectionEditorSubmission->getCopyeditorDateAuthorCompleted() == null) return;
		if ($copyeditStage == 'author' && $sectionEditorSubmission->getCopyeditorDateCompleted() == null) return;

		$fileName = 'upload';
		if ($articleFileManager->uploadedFileExists($fileName) && !HookRegistry::call('SectionEditorAction::uploadCopyeditVersion', array(&$sectionEditorSubmission))) {
			if ($sectionEditorSubmission->getCopyeditFileId() != null) {
				$copyeditFileId = $articleFileManager->uploadCopyeditFile($fileName, $sectionEditorSubmission->getCopyeditFileId());
			} else {
				$copyeditFileId = $articleFileManager->uploadCopyeditFile($fileName);
			}
		}


		if (isset($copyeditFileId) && $copyeditFileId != 0) {
			$sectionEditorSubmission->setCopyeditFileId($copyeditFileId);

			if ($copyeditStage == 'initial') {
				$sectionEditorSubmission->setCopyeditorInitialRevision($articleFileDao->getRevisionNumber($copyeditFileId));
			} elseif ($copyeditStage == 'author') {
				$sectionEditorSubmission->setCopyeditorEditorAuthorRevision($articleFileDao->getRevisionNumber($copyeditFileId));
			} elseif ($copyeditStage == 'final') {
				$sectionEditorSubmission->setCopyeditorFinalRevision($articleFileDao->getRevisionNumber($copyeditFileId));
			}

			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		}
	}

	/**
	 * Editor completes initial copyedit (copyeditors disabled).
	 * @param $sectionEditorSubmission object
	 */
	function completeCopyedit($sectionEditorSubmission) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();

		// This is only allowed if copyeditors are disabled.
		if ($journal->getSetting('useCopyeditors')) return;

		if (HookRegistry::call('SectionEditorAction::completeCopyedit', array(&$sectionEditorSubmission))) return;

		$sectionEditorSubmission->setCopyeditorDateCompleted(Core::getCurrentDate());
		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		// Add log entry
		import('article.log.ArticleLog');
		import('article.log.ArticleEventLogEntry');
		ArticleLog::logEvent($sectionEditorSubmission->getArticleId(), ARTICLE_LOG_COPYEDIT_INITIAL, ARTICLE_LOG_TYPE_COPYEDIT, $user->getUserId(), 'log.copyedit.initialEditComplete', Array('copyeditorName' => $user->getFullName(), 'articleId' => $sectionEditorSubmission->getArticleId()));
	}

	/**
	 * Section editor completes final copyedit (copyeditors disabled).
	 * @param $sectionEditorSubmission object
	 */
	function completeFinalCopyedit($sectionEditorSubmission) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();

		// This is only allowed if copyeditors are disabled.
		if ($journal->getSetting('useCopyeditors')) return;

		if (HookRegistry::call('SectionEditorAction::completeFinalCopyedit', array(&$sectionEditorSubmission))) return;

		$sectionEditorSubmission->setCopyeditorDateFinalCompleted(Core::getCurrentDate());
		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);

		if ($copyEdFile =& $sectionEditorSubmission->getFinalCopyeditFile()) {
			// Set initial layout version to final copyedit version
			$layoutDao = &DAORegistry::getDAO('LayoutAssignmentDAO');
			$layoutAssignment = &$layoutDao->getLayoutAssignmentByArticleId($sectionEditorSubmission->getArticleId());

			if (isset($layoutAssignment) && !$layoutAssignment->getLayoutFileId()) {
				import('file.ArticleFileManager');
				$articleFileManager = &new ArticleFileManager($sectionEditorSubmission->getArticleId());
				if ($layoutFileId = $articleFileManager->copyToLayoutFile($copyEdFile->getFileId(), $copyEdFile->getRevision())) {
					$layoutAssignment->setLayoutFileId($layoutFileId);
					$layoutDao->updateLayoutAssignment($layoutAssignment);
				}
			}
		}
		// Add log entry
		import('article.log.ArticleLog');
		import('article.log.ArticleEventLogEntry');
		ArticleLog::logEvent($sectionEditorSubmission->getArticleId(), ARTICLE_LOG_COPYEDIT_FINAL, ARTICLE_LOG_TYPE_COPYEDIT, $user->getUserId(), 'log.copyedit.finalEditComplete', Array('copyeditorName' => $user->getFullName(), 'articleId' => $sectionEditorSubmission->getArticleId()));
	}

	/**
	 * Archive a submission.
	 * @param $sectionEditorSubmission object
	 */
	function archiveSubmission($sectionEditorSubmission) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$user = &Request::getUser();

		if (HookRegistry::call('SectionEditorAction::archiveSubmission', array(&$sectionEditorSubmission))) return;

		$sectionEditorSubmission->setStatus(STATUS_ARCHIVED);
		$sectionEditorSubmission->stampStatusModified();

		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);

		// Add log
		import('article.log.ArticleLog');
		import('article.log.ArticleEventLogEntry');
		ArticleLog::logEvent($sectionEditorSubmission->getArticleId(), ARTICLE_LOG_EDITOR_ARCHIVE, ARTICLE_LOG_TYPE_EDITOR, $sectionEditorSubmission->getArticleId(), 'log.editor.archived', array('articleId' => $sectionEditorSubmission->getArticleId()));
	}

	/**
	 * Restores a submission to the queue.
	 * @param $sectionEditorSubmission object
	 */
	function restoreToQueue($sectionEditorSubmission) {
		if (HookRegistry::call('SectionEditorAction::restoreToQueue', array(&$sectionEditorSubmission))) return;

		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');

		// Determine which queue to return the article to: the
		// scheduling queue or the editing queue.
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle =& $publishedArticleDao->getPublishedArticleByArticleId($sectionEditorSubmission->getArticleId());
		if ($publishedArticle) {
			$sectionEditorSubmission->setStatus(STATUS_PUBLISHED);
		} else {
			$sectionEditorSubmission->setStatus(STATUS_QUEUED);
		}
		unset($publishedArticle);

		$sectionEditorSubmission->stampStatusModified();

		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);

		// Add log
		import('article.log.ArticleLog');
		import('article.log.ArticleEventLogEntry');
		ArticleLog::logEvent($sectionEditorSubmission->getArticleId(), ARTICLE_LOG_EDITOR_RESTORE, ARTICLE_LOG_TYPE_EDITOR, $sectionEditorSubmission->getArticleId(), 'log.editor.restored', array('articleId' => $sectionEditorSubmission->getArticleId()));
	}

	/**
	 * Changes the section.
	 * @param $submission object
	 * @param $sectionId int
	 */
	function updateSection($submission, $sectionId) {
		if (HookRegistry::call('SectionEditorAction::updateSection', array(&$submission, &$sectionId))) return;

		$submissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$submission->setSectionId($sectionId); // FIXME validate this ID?
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
		import('file.ArticleFileManager');
		$articleFileManager = &new ArticleFileManager($submission->getArticleId());
		$submissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');

		$layoutAssignment = &$submission->getLayoutAssignment();

		$fileName = 'layoutFile';
		if ($articleFileManager->uploadedFileExists($fileName) && !HookRegistry::call('SectionEditorAction::uploadLayoutVersion', array(&$submission, &$layoutAssignment))) {
			$layoutFileId = $articleFileManager->uploadLayoutFile($fileName, $layoutAssignment->getLayoutFileId());

			$layoutAssignment->setLayoutFileId($layoutFileId);
			$submissionDao->updateSectionEditorSubmission($submission);
		}
	}

	/**
	 * Assign a layout editor to a submission.
	 * @param $submission object
	 * @param $editorId int user ID of the new layout editor
	 */
	function assignLayoutEditor($submission, $editorId) {
		if (HookRegistry::call('SectionEditorAction::assignLayoutEditor', array(&$submission, &$editorId))) return;

		$layoutAssignment = &$submission->getLayoutAssignment();

		import('article.log.ArticleLog');
		import('article.log.ArticleEventLogEntry');

		if ($layoutAssignment->getEditorId()) {
			ArticleLog::logEvent($submission->getArticleId(), ARTICLE_LOG_LAYOUT_UNASSIGN, ARTICLE_LOG_TYPE_LAYOUT, $layoutAssignment->getLayoutId(), 'log.layout.layoutEditorUnassigned', array('editorName' => $layoutAssignment->getEditorFullName(), 'articleId' => $submission->getArticleId()));
		}

		$layoutAssignment->setEditorId($editorId);
		$layoutAssignment->setDateNotified(null);
		$layoutAssignment->setDateUnderway(null);
		$layoutAssignment->setDateCompleted(null);
		$layoutAssignment->setDateAcknowledged(null);

		$layoutDao = &DAORegistry::getDAO('LayoutAssignmentDAO');
		$layoutDao->updateLayoutAssignment($layoutAssignment);
		$layoutAssignment =& $layoutDao->getLayoutAssignmentById($layoutAssignment->getLayoutId());

		ArticleLog::logEvent($submission->getArticleId(), ARTICLE_LOG_LAYOUT_ASSIGN, ARTICLE_LOG_TYPE_LAYOUT, $layoutAssignment->getLayoutId(), 'log.layout.layoutEditorAssigned', array('editorName' => $layoutAssignment->getEditorFullName(), 'articleId' => $submission->getArticleId()));
	}

	/**
	 * Notifies the current layout editor about an assignment.
	 * @param $submission object
	 * @param $send boolean
	 * @return boolean true iff ready for redirect
	 */
	function notifyLayoutEditor($submission, $send = false) {
		$submissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();

		import('mail.ArticleMailTemplate');
		$email = &new ArticleMailTemplate($submission, 'LAYOUT_REQUEST');
		$layoutAssignment = &$submission->getLayoutAssignment();
		$layoutEditor = &$userDao->getUser($layoutAssignment->getEditorId());
		if (!isset($layoutEditor)) return true;

		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
			HookRegistry::call('SectionEditorAction::notifyLayoutEditor', array(&$submission, &$layoutEditor, &$layoutAssignment, &$email));
			if ($email->isEnabled()) {
				$email->setAssoc(ARTICLE_EMAIL_LAYOUT_NOTIFY_EDITOR, ARTICLE_EMAIL_TYPE_LAYOUT, $layoutAssignment->getLayoutId());
				$email->send();
			}

			$layoutAssignment->setDateNotified(Core::getCurrentDate());
			$layoutAssignment->setDateUnderway(null);
			$layoutAssignment->setDateCompleted(null);
			$layoutAssignment->setDateAcknowledged(null);
			$submissionDao->updateSectionEditorSubmission($submission);

		} else {
			if (!Request::getUserVar('continued')) {
				$email->addRecipient($layoutEditor->getEmail(), $layoutEditor->getFullName());
				$paramArray = array(
					'layoutEditorName' => $layoutEditor->getFullName(),
					'layoutEditorUsername' => $layoutEditor->getUsername(),
					'editorialContactSignature' => $user->getContactSignature(),
					'submissionLayoutUrl' => Request::url(null, 'layoutEditor', 'submission', $submission->getArticleId())
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::url(null, null, 'notifyLayoutEditor', 'send'), array('articleId' => $submission->getArticleId()));
			return false;
		}
		return true;
	}

	/**
	 * Sends acknowledgement email to the current layout editor.
	 * @param $submission object
	 * @param $send boolean
	 * @return boolean true iff ready for redirect
	 */
	function thankLayoutEditor($submission, $send = false) {
		$submissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();

		import('mail.ArticleMailTemplate');
		$email = &new ArticleMailTemplate($submission, 'LAYOUT_ACK');

		$layoutAssignment = &$submission->getLayoutAssignment();
		$layoutEditor = &$userDao->getUser($layoutAssignment->getEditorId());
		if (!isset($layoutEditor)) return true;

		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
			HookRegistry::call('SectionEditorAction::thankLayoutEditor', array(&$submission, &$layoutEditor, &$layoutAssignment, &$email));
			if ($email->isEnabled()) {
				$email->setAssoc(ARTICLE_EMAIL_LAYOUT_THANK_EDITOR, ARTICLE_EMAIL_TYPE_LAYOUT, $layoutAssignment->getLayoutId());
				$email->send();
			}

			$layoutAssignment->setDateAcknowledged(Core::getCurrentDate());
			$submissionDao->updateSectionEditorSubmission($submission);

		} else {
			if (!Request::getUserVar('continued')) {
				$email->addRecipient($layoutEditor->getEmail(), $layoutEditor->getFullName());
				$paramArray = array(
					'layoutEditorName' => $layoutEditor->getFullName(),
					'editorialContactSignature' => $user->getContactSignature()
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::url(null, null, 'thankLayoutEditor', 'send'), array('articleId' => $submission->getArticleId()));
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
		import('submission.layoutEditor.LayoutEditorAction');
		LayoutEditorAction::orderGalley($article, $galleyId, $direction);
	}

	/**
	 * Delete a galley.
	 * @param $article object
	 * @param $galleyId int
	 */
	function deleteGalley($article, $galleyId) {
		import('submission.layoutEditor.LayoutEditorAction');
		LayoutEditorAction::deleteGalley($article, $galleyId);
	}

	/**
	 * Change the sequence order of a supplementary file.
	 * @param $article object
	 * @param $suppFileId int
	 * @param $direction char u = up, d = down
	 */
	function orderSuppFile($article, $suppFileId, $direction) {
		import('submission.layoutEditor.LayoutEditorAction');
		LayoutEditorAction::orderSuppFile($article, $suppFileId, $direction);
	}

	/**
	 * Delete a supplementary file.
	 * @param $article object
	 * @param $suppFileId int
	 */
	function deleteSuppFile($article, $suppFileId) {
		import('submission.layoutEditor.LayoutEditorAction');
		LayoutEditorAction::deleteSuppFile($article, $suppFileId);
	}

	/**
	 * Delete a file from an article.
	 * @param $submission object
	 * @param $fileId int
	 * @param $revision int (optional)
	 */
	function deleteArticleFile($submission, $fileId, $revision) {
		import('file.ArticleFileManager');
		$file =& $submission->getEditorFile();

		if (isset($file) && $file->getFileId() == $fileId && !HookRegistry::call('SectionEditorAction::deleteArticleFile', array(&$submission, &$fileId, &$revision))) {
			$articleFileManager = &new ArticleFileManager($submission->getArticleId());
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
		import('file.ArticleFileManager');
		$articleGalleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');
		if (HookRegistry::call('SectionEditorAction::deleteArticleImage', array(&$submission, &$fileId, &$revision))) return;
		foreach ($submission->getGalleys() as $galley) {
			$images =& $articleGalleyDao->getGalleyImages($galley->getGalleyId());
			foreach ($images as $imageFile) {
				if ($imageFile->getArticleId() == $submission->getArticleId() && $fileId == $imageFile->getFileId() && $imageFile->getRevision() == $revision) {
					$articleFileManager = &new ArticleFileManager($submission->getArticleId());
					$articleFileManager->deleteFile($imageFile->getFileId(), $imageFile->getRevision());
				}
			}
			unset($images);
		}
	}

	/**
	 * Add Submission Note
	 * @param $articleId int
	 */
	function addSubmissionNote($articleId) {
		import('file.ArticleFileManager');

		$articleNoteDao = &DAORegistry::getDAO('ArticleNoteDAO');
		$user = &Request::getUser();

		$articleNote = &new ArticleNote();
		$articleNote->setArticleId($articleId);
		$articleNote->setUserId($user->getUserId());
		$articleNote->setDateCreated(Core::getCurrentDate());
		$articleNote->setDateModified(Core::getCurrentDate());
		$articleNote->setTitle(Request::getUserVar('title'));
		$articleNote->setNote(Request::getUserVar('note'));

		if (!HookRegistry::call('SectionEditorAction::addSubmissionNote', array(&$articleId, &$articleNote))) {
			$articleFileManager = &new ArticleFileManager($articleId);
			if ($articleFileManager->uploadedFileExists('upload')) {
				$fileId = $articleFileManager->uploadSubmissionNoteFile('upload');
			} else {
				$fileId = 0;
			}

			$articleNote->setFileId($fileId);

			$articleNoteDao->insertArticleNote($articleNote);
		}
	}

	/**
	 * Remove Submission Note
	 * @param $articleId int
	 */
	function removeSubmissionNote($articleId) {
		$noteId = Request::getUserVar('noteId');
		$fileId = Request::getUserVar('fileId');

		if (HookRegistry::call('SectionEditorAction::removeSubmissionNote', array(&$articleId, &$noteId, &$fileId))) return;

		// if there is an attached file, remove it as well
		if ($fileId) {
			import('file.ArticleFileManager');
			$articleFileManager = &new ArticleFileManager($articleId);
			$articleFileManager->deleteFile($fileId);
		}

		$articleNoteDao = &DAORegistry::getDAO('ArticleNoteDAO');
		$articleNoteDao->deleteArticleNoteById($noteId);
	}

	/**
	 * Updates Submission Note
	 * @param $articleId int
	 */
	function updateSubmissionNote($articleId) {
		import('file.ArticleFileManager');

		$articleNoteDao = &DAORegistry::getDAO('ArticleNoteDAO');
		$user = &Request::getUser();

		$articleNote = &new ArticleNote();
		$articleNote->setNoteId(Request::getUserVar('noteId'));
		$articleNote->setArticleId($articleId);
		$articleNote->setUserId($user->getUserId());
		$articleNote->setDateModified(Core::getCurrentDate());
		$articleNote->setTitle(Request::getUserVar('title'));
		$articleNote->setNote(Request::getUserVar('note'));
		$articleNote->setFileId(Request::getUserVar('fileId'));

		if (HookRegistry::call('SectionEditorAction::updateSubmissionNote', array(&$articleId, &$articleNote))) return;

		$articleFileManager = &new ArticleFileManager($articleId);

		// if there is a new file being uploaded
		if ($articleFileManager->uploadedFileExists('upload')) {
			// Attach the new file to the note, overwriting existing file if necessary
			$fileId = $articleFileManager->uploadSubmissionNoteFile('upload', $articleNote->getFileId(), true);
			$articleNote->setFileId($fileId);

		} else {
			if (Request::getUserVar('removeUploadedFile')) {
				$articleFileManager = &new ArticleFileManager($articleId);
				$articleFileManager->deleteFile($articleNote->getFileId());
				$articleNote->setFileId(0);
			}
		}

		$articleNoteDao->updateArticleNote($articleNote);
	}

	/**
	 * Clear All Submission Notes
	 * @param $articleId int
	 */
	function clearAllSubmissionNotes($articleId) {
		if (HookRegistry::call('SectionEditorAction::clearAllSubmissionNotes', array(&$articleId))) return;

		import('file.ArticleFileManager');

		$articleNoteDao = &DAORegistry::getDAO('ArticleNoteDAO');

		$fileIds = $articleNoteDao->getAllArticleNoteFileIds($articleId);

		if (!empty($fileIds)) {
			$articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');
			$articleFileManager = &new ArticleFileManager($articleId);

			foreach ($fileIds as $fileId) {
				$articleFileManager->deleteFile($fileId);
			}			
		}

		$articleNoteDao->clearAllArticleNotes($articleId);

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

		import('submission.form.comment.PeerReviewCommentForm');

		$commentForm = &new PeerReviewCommentForm($article, $reviewId, Validation::isEditor()?ROLE_ID_EDITOR:ROLE_ID_SECTION_EDITOR);
		$commentForm->initData();
		$commentForm->display();
	}

	/**
	 * Post reviewer comments.
	 * @param $article object
	 * @param $reviewId int
	 * @param $emailComment boolean
	 */
	function postPeerReviewComment(&$article, $reviewId, $emailComment) {
		if (HookRegistry::call('SectionEditorAction::postPeerReviewComment', array(&$article, &$reviewId, &$emailComment))) return;

		import('submission.form.comment.PeerReviewCommentForm');

		$commentForm = &new PeerReviewCommentForm($article, $reviewId, Validation::isEditor()?ROLE_ID_EDITOR:ROLE_ID_SECTION_EDITOR);
		$commentForm->readInputData();

		if ($commentForm->validate()) {
			$commentForm->execute();

			if ($emailComment) {
				$commentForm->email();
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

		import('submission.form.comment.EditorDecisionCommentForm');

		$commentForm = &new EditorDecisionCommentForm($article, Validation::isEditor()?ROLE_ID_EDITOR:ROLE_ID_SECTION_EDITOR);
		$commentForm->initData();
		$commentForm->display();
	}

	/**
	 * Post editor decision comment.
	 * @param $article int
	 * @param $emailComment boolean
	 */
	function postEditorDecisionComment($article, $emailComment) {
		if (HookRegistry::call('SectionEditorAction::postEditorDecisionComment', array(&$article, &$emailComment))) return;

		import('submission.form.comment.EditorDecisionCommentForm');

		$commentForm = &new EditorDecisionCommentForm($article, Validation::isEditor()?ROLE_ID_EDITOR:ROLE_ID_SECTION_EDITOR);
		$commentForm->readInputData();

		if ($commentForm->validate()) {
			$commentForm->execute();

			if ($emailComment) {
				$commentForm->email();
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
	 */
	function emailEditorDecisionComment($sectionEditorSubmission, $send) {
		$userDao =& DAORegistry::getDAO('UserDAO');
		$articleCommentDao =& DAORegistry::getDAO('ArticleCommentDAO');
		$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');

		$journal =& Request::getJournal();

		$user =& Request::getUser();
		import('mail.ArticleMailTemplate');
		$email = &new ArticleMailTemplate($sectionEditorSubmission);

		$copyeditor =& $sectionEditorSubmission->getCopyeditor();

		if ($send && !$email->hasErrors()) {
			HookRegistry::call('SectionEditorAction::emailEditorDecisionComment', array(&$sectionEditorSubmission, &$send));
			$email->send();

			$decisions = $sectionEditorSubmission->getDecisions();
			$decisions = array_pop($decisions); // Rounds
			$decision = array_pop($decisions);

			if ($decision && $decision['decision'] == SUBMISSION_EDITOR_DECISION_DECLINE) {
				// If the most recent decision was a decline,
				// sending this email archives the submission.
				$sectionEditorSubmission->setStatus(STATUS_ARCHIVED);
				$sectionEditorSubmission->stampStatusModified();
				$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
			}

			$articleComment =& new ArticleComment();
			$articleComment->setCommentType(COMMENT_TYPE_EDITOR_DECISION);
			$articleComment->setRoleId(Validation::isEditor()?ROLE_ID_EDITOR:ROLE_ID_SECTION_EDITOR);
			$articleComment->setArticleId($sectionEditorSubmission->getArticleId());
			$articleComment->setAuthorId($sectionEditorSubmission->getUserId());
			$articleComment->setCommentTitle($email->getSubject());
			$articleComment->setComments($email->getBody());
			$articleComment->setDatePosted(Core::getCurrentDate());
			$articleComment->setViewable(true);
			$articleComment->setAssocId($sectionEditorSubmission->getArticleId());
			$articleCommentDao->insertArticleComment($articleComment);

			return true;
		} else {
			if (!Request::getUserVar('continued')) {
				$authorUser =& $userDao->getUser($sectionEditorSubmission->getUserId());
				$email->setSubject($sectionEditorSubmission->getArticleTitle());
				$authorEmail = $authorUser->getEmail();
				$email->addRecipient($authorEmail, $authorUser->getFullName());
				if ($journal->getSetting('notifyAllAuthorsOnDecision')) foreach ($sectionEditorSubmission->getAuthors() as $author) {
					if ($author->getEmail() != $authorEmail) {
						$email->addCc ($author->getEmail(), $author->getFullName());
					}
				}
			} else {
				if (Request::getUserVar('importPeerReviews')) {
					$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
					$reviewAssignments = &$reviewAssignmentDao->getReviewAssignmentsByArticleId($sectionEditorSubmission->getArticleId(), $sectionEditorSubmission->getCurrentRound());
					$reviewIndexes = &$reviewAssignmentDao->getReviewIndexesForRound($sectionEditorSubmission->getArticleId(), $sectionEditorSubmission->getCurrentRound());

					$body = '';
					foreach ($reviewAssignments as $reviewAssignment) {
						// If the reviewer has completed the assignment, then import the review.
						if ($reviewAssignment->getDateCompleted() != null && !$reviewAssignment->getCancelled()) {
							// Get the comments associated with this review assignment
							$articleComments = &$articleCommentDao->getArticleComments($sectionEditorSubmission->getArticleId(), COMMENT_TYPE_PEER_REVIEW, $reviewAssignment->getReviewId());
							$body .= "------------------------------------------------------\n";
							$body .= Locale::translate('submission.comments.importPeerReviews.reviewerLetter', array('reviewerLetter' => chr(ord('A') + $reviewIndexes[$reviewAssignment->getReviewId()]))) . "\n";
							if (is_array($articleComments)) {
								foreach ($articleComments as $comment) {
									// If the comment is viewable by the author, then add the comment.
									if ($comment->getViewable()) {
										$body .= $comment->getComments() . "\n\n";
									}
								}
							}
							$body .= "------------------------------------------------------\n\n";
						}
					}
					$oldBody = $email->getBody();
					if (!empty($oldBody)) $oldBody .= "\n";
					$email->setBody($oldBody . $body);
				}
			}

			$email->displayEditForm(Request::url(null, null, 'emailEditorDecisionComment', 'send'), array('articleId' => $sectionEditorSubmission->getArticleId()), 'submission/comment/editorDecisionEmail.tpl', array('isAnEditor' => true));

			return false;
		}
	}

	/**
	 * Blind CC the reviews to reviewers.
	 * @param $article object
	 * @param $send boolean
	 * @param $inhibitExistingEmail boolean
	 * @return boolean true iff ready for redirect
	 */
	function blindCcReviewsToReviewers($article, $send = false, $inhibitExistingEmail = false) {
		$commentDao = &DAORegistry::getDAO('ArticleCommentDAO');
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();

		$comments = &$commentDao->getArticleComments($article->getArticleId(), COMMENT_TYPE_EDITOR_DECISION);
		$reviewAssignments = &$reviewAssignmentDao->getReviewAssignmentsByArticleId($article->getArticleId(), $article->getCurrentRound());

		$commentsText = "";
		foreach ($comments as $comment) {
			$commentsText .= $comment->getComments() . "\n\n";
		}

		$user = &Request::getUser();
		import('mail.ArticleMailTemplate');
		$email = &new ArticleMailTemplate($article, 'SUBMISSION_DECISION_REVIEWERS');

		if ($send && !$email->hasErrors() && !$inhibitExistingEmail) {
			HookRegistry::call('SectionEditorAction::blindCcReviewsToReviewers', array(&$article, &$reviewAssignments, &$email));
			$email->send();
			return true;
		} else {
			if ($inhibitExistingEmail || !Request::getUserVar('continued')) {
				$email->clearRecipients();
				foreach ($reviewAssignments as $reviewAssignment) {
					if ($reviewAssignment->getDateCompleted() != null && !$reviewAssignment->getCancelled()) {
						$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());

						if (isset($reviewer)) $email->addBcc($reviewer->getEmail(), $reviewer->getFullName());
					}
				}

				$paramArray = array(
					'comments' => $commentsText,
					'editorialContactSignature' => $user->getContactSignature()
				);
				$email->assignParams($paramArray);
			}

			$email->displayEditForm(Request::url(null, null, 'blindCcReviewsToReviewers'), array('articleId' => $article->getArticleId()));
			return false;
		}
	}

	/**
	 * View copyedit comments.
	 * @param $article object
	 */
	function viewCopyeditComments($article) {
		if (HookRegistry::call('SectionEditorAction::viewCopyeditComments', array(&$article))) return;

		import('submission.form.comment.CopyeditCommentForm');

		$commentForm = &new CopyeditCommentForm($article, Validation::isEditor()?ROLE_ID_EDITOR:ROLE_ID_SECTION_EDITOR);
		$commentForm->initData();
		$commentForm->display();
	}

	/**
	 * Post copyedit comment.
	 * @param $article object
	 * @param $emailComment boolean
	 */
	function postCopyeditComment($article, $emailComment) {
		if (HookRegistry::call('SectionEditorAction::postCopyeditComment', array(&$article, &$emailComment))) return;

		import('submission.form.comment.CopyeditCommentForm');

		$commentForm = &new CopyeditCommentForm($article, Validation::isEditor()?ROLE_ID_EDITOR:ROLE_ID_SECTION_EDITOR);
		$commentForm->readInputData();

		if ($commentForm->validate()) {
			$commentForm->execute();

			if ($emailComment) {
				$commentForm->email();
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

		import('submission.form.comment.LayoutCommentForm');

		$commentForm = &new LayoutCommentForm($article, Validation::isEditor()?ROLE_ID_EDITOR:ROLE_ID_SECTION_EDITOR);
		$commentForm->initData();
		$commentForm->display();
	}

	/**
	 * Post layout comment.
	 * @param $article object
	 * @param $emailComment boolean
	 */
	function postLayoutComment($article, $emailComment) {
		if (HookRegistry::call('SectionEditorAction::postLayoutComment', array(&$article, &$emailComment))) return;

		import('submission.form.comment.LayoutCommentForm');

		$commentForm = &new LayoutCommentForm($article, Validation::isEditor()?ROLE_ID_EDITOR:ROLE_ID_SECTION_EDITOR);
		$commentForm->readInputData();

		if ($commentForm->validate()) {
			$commentForm->execute();

			if ($emailComment) {
				$commentForm->email();
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

		import('submission.form.comment.ProofreadCommentForm');

		$commentForm = &new ProofreadCommentForm($article, Validation::isEditor()?ROLE_ID_EDITOR:ROLE_ID_SECTION_EDITOR);
		$commentForm->initData();
		$commentForm->display();
	}

	/**
	 * Post proofread comment.
	 * @param $article object
	 * @param $emailComment boolean
	 */
	function postProofreadComment($article, $emailComment) {
		if (HookRegistry::call('SectionEditorAction::postProofreadComment', array(&$article, &$emailComment))) return;

		import('submission.form.comment.ProofreadCommentForm');

		$commentForm = &new ProofreadCommentForm($article, Validation::isEditor()?ROLE_ID_EDITOR:ROLE_ID_SECTION_EDITOR);
		$commentForm->readInputData();

		if ($commentForm->validate()) {
			$commentForm->execute();

			if ($emailComment) {
				$commentForm->email();
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
	function confirmReviewForReviewer($reviewId, $accept) {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$user = &Request::getUser();

		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId(), true);

		if (HookRegistry::call('SectionEditorAction::acceptReviewForReviewer', array(&$reviewAssignment, &$reviewer, &$accept))) return;

		// Only confirm the review for the reviewer if 
		// he has not previously done so.
		if ($reviewAssignment->getDateConfirmed() == null) {
			$reviewAssignment->setDeclined($accept?0:1);
			$reviewAssignment->setDateConfirmed(Core::getCurrentDate());
			$reviewAssignment->stampModified();
			$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);

			// Add log
			import('article.log.ArticleLog');
			import('article.log.ArticleEventLogEntry');

			$entry = &new ArticleEventLogEntry();
			$entry->setArticleId($reviewAssignment->getArticleId());
			$entry->setUserId($user->getUserId());
			$entry->setDateLogged(Core::getCurrentDate());
			$entry->setEventType(ARTICLE_LOG_REVIEW_CONFIRM_BY_PROXY);
			$entry->setLogMessage($accept?'log.review.reviewAcceptedByProxy':'log.review.reviewDeclinedByProxy', array('reviewerName' => $reviewer->getFullName(), 'articleId' => $reviewAssignment->getArticleId(), 'round' => $reviewAssignment->getRound(), 'userName' => $user->getFullName()));
			$entry->setAssocType(ARTICLE_LOG_TYPE_REVIEW);
			$entry->setAssocId($reviewAssignment->getReviewId());

			ArticleLog::logEventEntry($reviewAssignment->getArticleId(), $entry);
		}
	}

	/**
	 * Upload a review on behalf of its reviewer.
	 * @param $reviewId int
	 */
	function uploadReviewForReviewer($reviewId) {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$user = &Request::getUser();

		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId(), true);

		if (HookRegistry::call('SectionEditorAction::uploadReviewForReviewer', array(&$reviewAssignment, &$reviewer))) return;

		// Upload the review file.
		import('file.ArticleFileManager');
		$articleFileManager = &new ArticleFileManager($reviewAssignment->getArticleId());
		// Only upload the file if the reviewer has yet to submit a recommendation
		if ($reviewAssignment->getRecommendation() == null && !$reviewAssignment->getCancelled()) {
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
			import('article.log.ArticleLog');
			import('article.log.ArticleEventLogEntry');

			$entry = &new ArticleEventLogEntry();
			$entry->setArticleId($reviewAssignment->getArticleId());
			$entry->setUserId($user->getUserId());
			$entry->setDateLogged(Core::getCurrentDate());
			$entry->setEventType(ARTICLE_LOG_REVIEW_FILE_BY_PROXY);
			$entry->setLogMessage('log.review.reviewFileByProxy', array('reviewerName' => $reviewer->getFullName(), 'articleId' => $reviewAssignment->getArticleId(), 'round' => $reviewAssignment->getRound(), 'userName' => $user->getFullName()));
			$entry->setAssocType(ARTICLE_LOG_TYPE_REVIEW);
			$entry->setAssocId($reviewAssignment->getReviewId());

			ArticleLog::logEventEntry($reviewAssignment->getArticleId(), $entry);
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
