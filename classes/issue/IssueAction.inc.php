<?php

/**
 * @file classes/issue/IssueAction.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IssueAction
 * @ingroup issue
 * @see Issue
 *
 * @brief IssueAction class.
 */

class IssueAction {
	/**
	 * Actions.
	 */

	/**
	 * Checks if subscription is required for viewing the issue
	 * @param $issue Issue
	 * @param $journal Journal
	 * @return bool
	 */
	function subscriptionRequired($issue, $journal) {
		assert(is_a($issue, 'Issue'));
		assert(is_a($journal, 'Journal'));
		assert($journal->getId() == $issue->getJournalId());

		// Check subscription state.
		$result = $journal->getData('publishingMode') == PUBLISHING_MODE_SUBSCRIPTION &&
			$issue->getAccessStatus() != ISSUE_ACCESS_OPEN && (
				is_null($issue->getOpenAccessDate()) ||
				strtotime($issue->getOpenAccessDate()) > time()
			);
		HookRegistry::call('IssueAction::subscriptionRequired', array(&$journal, &$issue, &$result));
		return $result;
	}

	/**
	 * Checks if this user is granted reader access to pre-publication articles
	 * based on their roles in the journal (i.e. Manager, Editor, etc).
	 * @param $journal Journal
	 * @param $submission Submission
	 * @param $user User
	 * @return bool
	 */
	function allowedPrePublicationAccess($journal, $submission, $user) {
		// Don't grant access until submission reaches Copyediting stage
		if ($submission->getData('stageId') < WORKFLOW_STAGE_ID_EDITING) return false;

		if ($this->_roleAllowedPrePublicationAccess($journal, $user)) return true;

		if ($user && $journal) {
			$journalId = $journal->getId();
			$userId = $user->getId();

			$stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO'); /* @var $stageAssignmentDao StageAssignmentDAO */
			$stageAssignments = $stageAssignmentDao->getBySubmissionAndRoleId($submission->getId(), ROLE_ID_AUTHOR, null, $userId);
			$stageAssignment = $stageAssignments->next();
			if ($stageAssignment) return true;
		}
		return false;
	}

	/**
	 * Checks if this user is granted access to pre-publication issue galleys
	 * based on their roles in the journal (i.e. Manager, Editor, etc).
	 * @param $journal object
	 * @param $issue object
	 * @return bool
	 */
	function allowedIssuePrePublicationAccess($journal, $user) {
		return $this->_roleAllowedPrePublicationAccess($journal, $user);
	}

	/**
	 * Checks if user has subscription
	 * @param $user User
	 * @param $journal Journal
	 * @param $issueId int Issue ID (optional)
	 * @param $articleId int Article ID (optional)
	 * @return bool
	 */
	function subscribedUser($user, $journal, $issueId = null, $articleId = null) {
		$subscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO'); /* @var $subscriptionDao IndividualSubscriptionDAO */
		$submissionDao = DAORegistry::getDAO('SubmissionDAO'); /* @var $submissionDao SubmissionDAO */
		$submission = $submissionDao->getById($articleId);
		$result = false;
		if (isset($user) && isset($journal)) {
			if ($submission && $this->allowedPrePublicationAccess($journal, $submission, $user)) {
				 $result = true;
			} else {
				$result = $subscriptionDao->isValidIndividualSubscription($user->getId(), $journal->getId());
			}

			// If no valid subscription, check if there is an expired subscription
			// that was valid during publication date of any one of the submission's
			// publications
			if (!$result && $journal->getData('subscriptionExpiryPartial')) {
				if (isset($submission) && !empty($submission->getData('publications'))) {
					import('classes.subscription.SubscriptionDAO');
					foreach ($submission->getData('publications') as $publication) {
						if ($subscriptionDao->isValidIndividualSubscription($user->getId(), $journal->getId(), SUBSCRIPTION_DATE_END, $publication->getData('datePublished'))) {
							$result = true;
							break;
						}
					}
				} else if (isset($issueId)) {
					$issueDao = DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
					$issue = $issueDao->getById($issueId);
					if (isset($issue) && $issue->getPublished()) {
						import('classes.subscription.SubscriptionDAO');
						$result = $subscriptionDao->isValidIndividualSubscription($user->getId(), $journal->getId(), SUBSCRIPTION_DATE_END, $issue->getDatePublished());
					}
				}
			}
		}
		HookRegistry::call('IssueAction::subscribedUser', array(&$user, &$journal, &$issueId, &$articleId, &$result));
		return $result;
	}

	/**
	 * Checks if remote client domain or ip is allowed
	 * @param $request PKPRequest
	 * @param $journal Journal
	 * @param $issueId int Issue ID (optional)
	 * @param $articleId int Article ID (optional)
	 * @return bool
	 */
	function subscribedDomain($request, $journal, $issueId = null, $articleId = null) {
		$subscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO'); /* @var $subscriptionDao InstitutionalSubscriptionDAO */
		$result = false;
		if (isset($journal)) {
			$result = $subscriptionDao->isValidInstitutionalSubscription($request->getRemoteDomain(), $request->getRemoteAddr(), $journal->getId());

			// If no valid subscription, check if there is an expired subscription
			// that was valid during publication date of requested content
			if (!$result && $journal->getData('subscriptionExpiryPartial')) {
				if (isset($articleId)) {
					$submission = Services::get('submission')->get($articleId);
					if ($submission->getData('status') === STATUS_PUBLISHED) {
						import('classes.subscription.SubscriptionDAO');
						$result = $subscriptionDao->isValidInstitutionalSubscription($request->getRemoteDomain(), $request->getRemoteAddr(), $journal->getId(), SUBSCRIPTION_DATE_END, $submission->getDatePublished());
					}
				} else if (isset($issueId)) {
					$issueDao = DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
					$issue = $issueDao->getById($issueId);
					if (isset($issue) && $issue->getPublished()) {
						import('classes.subscription.SubscriptionDAO');
						$result = $subscriptionDao->isValidInstitutionalSubscription($request->getRemoteDomain(), $request->getRemoteAddr(), $journal->getId(), SUBSCRIPTION_DATE_END, $issue->getDatePublished());
					}
				}
			}
		}
		HookRegistry::call('IssueAction::subscribedDomain', array(&$request, &$journal, &$issueId, &$articleId, &$result));
		return (boolean) $result;
	}

	/**
	 * Checks if this user is granted access to pre-publication galleys based on role
	 * based on their roles in the journal (i.e. Manager, Editor, etc).
	 * @param $journal Journal
	 * @param $user User
	 * @return bool
	 */
	function _roleAllowedPrePublicationAccess($journal, $user) {
		$roleDao = DAORegistry::getDAO('RoleDAO'); /* @var $roleDao RoleDAO */
		if ($user && $journal) {
			$journalId = $journal->getId();
			$userId = $user->getId();
			$subscriptionAssumedRoles = array(
				ROLE_ID_MANAGER,
				ROLE_ID_SUB_EDITOR,
				ROLE_ID_ASSISTANT,
				ROLE_ID_SUBSCRIPTION_MANAGER
			);

			$roles = $roleDao->getByUserId($userId, $journalId);
			foreach ($roles as $role) {
				if (in_array($role->getRoleId(), $subscriptionAssumedRoles)) return true;
			}
		}
		return false;
	}
}


