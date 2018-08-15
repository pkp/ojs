<?php

/**
 * @file classes/user/UserAction.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserAction
 * @ingroup user
 * @see User
 *
 * @brief UserAction class.
 */

class UserAction {

	/**
	 * Constructor.
	 */
	function __construct() {
	}

	/**
	 * Actions.
	 */

	/**
	 * Merge user accounts, including attributed articles etc.
	 */
	function mergeUsers($oldUserId, $newUserId) {
		// Need both user ids for merge
		if (empty($oldUserId) || empty($newUserId)) {
			return false;
		}

		HookRegistry::call('UserAction::mergeUsers', array(&$oldUserId, &$newUserId));

		$noteDao = DAORegistry::getDAO('NoteDAO');
		$notes = $noteDao->getByUserId($oldUserId);
		while ($note = $notes->next()) {
			$note->setUserId($newUserId);
			$noteDao->updateObject($note);
		}

		$editDecisionDao = DAORegistry::getDAO('EditDecisionDAO');
		$editDecisionDao->transferEditorDecisions($oldUserId, $newUserId);

		$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
		foreach ($reviewAssignmentDao->getByUserId($oldUserId) as $reviewAssignment) {
			$reviewAssignment->setReviewerId($newUserId);
			$reviewAssignmentDao->updateObject($reviewAssignment);
		}

		$articleEmailLogDao = DAORegistry::getDAO('SubmissionEmailLogDAO');
		$articleEmailLogDao->changeUser($oldUserId, $newUserId);
		$articleEventLogDao = DAORegistry::getDAO('SubmissionEventLogDAO');
		$articleEventLogDao->changeUser($oldUserId, $newUserId);

		$submissionCommentDao = DAORegistry::getDAO('SubmissionCommentDAO');
		$submissionComments = $submissionCommentDao->getByUserId($oldUserId);

		while ($submissionComment = $submissionComments->next()) {
			$submissionComment->setAuthorId($newUserId);
			$submissionCommentDao->updateObject($submissionComment);
		}

		$accessKeyDao = DAORegistry::getDAO('AccessKeyDAO');
		$accessKeyDao->transferAccessKeys($oldUserId, $newUserId);

		// Transfer old user's individual subscriptions for each journal if new user
		// does not have a valid individual subscription for a given journal.
		$individualSubscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO');
		$oldUserSubscriptions = $individualSubscriptionDao->getByUserId($oldUserId);

		while ($oldUserSubscription = $oldUserSubscriptions->next()) {
			$subscriptionJournalId = $oldUserSubscription->getJournalId();
			$oldUserValidSubscription = $individualSubscriptionDao->isValidIndividualSubscription($oldUserId, $subscriptionJournalId);
			if ($oldUserValidSubscription) {
				// Check if new user has a valid subscription for current journal
				$newUserSubscription = $individualSubscriptionDao->getByUserIdForJournal($newUserId, $subscriptionJournalId);
				if (!$newUserSubscription) {
					// New user does not have this subscription, transfer old user's
					$oldUserSubscription->setUserId($newUserId);
					$individualSubscriptionDao->updateObject($oldUserSubscription);
				} elseif (!$individualSubscriptionDao->isValidIndividualSubscription($newUserId, $subscriptionJournalId)) {
					// New user has a subscription but it's invalid. Delete it and
					// transfer old user's valid one
					$individualSubscriptionDao->deleteByUserIdForJournal($newUserId, $subscriptionJournalId);
					$oldUserSubscription->setUserId($newUserId);
					$individualSubscriptionDao->updateObject($oldUserSubscription);
				}
			}
		}

		// Delete any remaining old user's subscriptions not transferred to new user
		$individualSubscriptionDao->deleteByUserId($oldUserId);

		// Transfer all old user's institutional subscriptions for each journal to
		// new user. New user now becomes the contact person for these.
		$institutionalSubscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO');
		$oldUserSubscriptions = $institutionalSubscriptionDao->getByUserId($oldUserId);

		while ($oldUserSubscription = $oldUserSubscriptions->next()) {
			$oldUserSubscription->setUserId($newUserId);
			$institutionalSubscriptionDao->updateObject($oldUserSubscription);
		}

		// Transfer completed payments.
		$paymentDao = DAORegistry::getDAO('OJSCompletedPaymentDAO');
		$paymentFactory = $paymentDao->getByUserId($oldUserId);
		while ($payment = next($paymentFactory)) {
			$payment->setUserId($newUserId);
			$paymentDao->updateObject($payment);
		}

		// Delete the old user and associated info.
		$sessionDao = DAORegistry::getDAO('SessionDAO');
		$sessionDao->deleteByUserId($oldUserId);
		$temporaryFileDao = DAORegistry::getDAO('TemporaryFileDAO');
		$temporaryFileDao->deleteByUserId($oldUserId);
		$userSettingsDao = DAORegistry::getDAO('UserSettingsDAO');
		$userSettingsDao->deleteSettings($oldUserId);
		$subEditorsDao = DAORegistry::getDAO('SubEditorsDAO');
		$subEditorsDao->deleteByUserId($oldUserId);

		// Transfer old user's roles
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$userGroups = $userGroupDao->getByUserId($oldUserId);
		while(!$userGroups->eof()) {
			$userGroup = $userGroups->next();
			if (!$userGroupDao->userInGroup($newUserId, $userGroup->getId())) {
				$userGroupDao->assignUserToGroup($newUserId, $userGroup->getId());
			}
		}
		$userGroupDao->deleteAssignmentsByUserId($oldUserId);

		// Transfer stage assignments.
		$stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
		$stageAssignments = $stageAssignmentDao->getByUserId($oldUserId);
		while ($stageAssignment = $stageAssignments->next()) {
			$duplicateAssignments = $stageAssignmentDao->getBySubmissionAndStageId($stageAssignment->getSubmissionId(), null, $stageAssignment->getUserGroupId(), $newUserId);
			if (!$duplicateAssignments->next()) {
				// If no similar assignments already exist, transfer this one.
				$stageAssignment->setUserId($newUserId);
				$stageAssignmentDao->updateObject($stageAssignment);
			} else {
				// There's already a stage assignment for the new user; delete.
				$stageAssignmentDao->deleteObject($stageAssignment);
			}
		}

		$userDao = DAORegistry::getDAO('UserDAO');
		$userDao->deleteUserById($oldUserId);

		return true;
	}
}


