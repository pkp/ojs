<?php

/**
 * @file classes/issue/IssueAction.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IssueAction
 *
 * @ingroup issue
 *
 * @see Issue
 *
 * @brief IssueAction class.
 */

namespace APP\issue;

use APP\facades\Repo;
use APP\subscription\IndividualSubscriptionDAO;
use APP\subscription\InstitutionalSubscriptionDAO;
use APP\subscription\Subscription;
use PKP\db\DAORegistry;
use PKP\plugins\Hook;
use PKP\security\Role;
use PKP\security\RoleDAO;
use PKP\submission\PKPSubmission;

class IssueAction
{
    /**
     * Actions.
     */

    /**
     * Checks if subscription is required for viewing the issue
     *
     * @param Issue $issue
     * @param \APP\journal\Journal $journal
     *
     * @return bool
     */
    public function subscriptionRequired($issue, $journal)
    {
        assert($issue instanceof \APP\issue\Issue);
        assert($journal instanceof \APP\journal\Journal);
        assert($journal->getId() == $issue->getJournalId());

        // Check subscription state.
        $result = $journal->getData('publishingMode') == \APP\journal\Journal::PUBLISHING_MODE_SUBSCRIPTION &&
            $issue->getAccessStatus() != \APP\issue\Issue::ISSUE_ACCESS_OPEN && (
                is_null($issue->getOpenAccessDate()) ||
                strtotime($issue->getOpenAccessDate()) > time()
            );
        Hook::call('IssueAction::subscriptionRequired', [&$journal, &$issue, &$result]);
        return $result;
    }

    /**
     * Checks if this user is granted access to pre-publication issue galleys
     * based on their roles in the journal (i.e. Manager, Editor, etc).
     *
     * @param \APP\journal\Journal $journal
     *
     * @return bool
     */
    public function allowedIssuePrePublicationAccess($journal, $user)
    {
        /** @var RoleDAO */
        $roleDao = DAORegistry::getDAO('RoleDAO');
        if ($user && $journal) {
            $journalId = $journal->getId();
            $userId = $user->getId();
            $subscriptionAssumedRoles = [
                Role::ROLE_ID_MANAGER,
                Role::ROLE_ID_SUB_EDITOR,
                Role::ROLE_ID_ASSISTANT,
                Role::ROLE_ID_SUBSCRIPTION_MANAGER
            ];

            $roles = $roleDao->getByUserId($userId, $journalId);
            foreach ($roles as $role) {
                if (in_array($role->getRoleId(), $subscriptionAssumedRoles)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Checks if user has subscription
     *
     * @param \PKP\user\User $user
     * @param \APP\journal\Journal $journal
     * @param int $issueId Issue ID (optional)
     * @param int $articleId Article ID (optional)
     *
     * @return bool
     */
    public function subscribedUser($user, $journal, $issueId = null, $articleId = null)
    {
        $subscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO'); /** @var IndividualSubscriptionDAO $subscriptionDao */
        $submission = Repo::submission()->get((int) $articleId);
        $result = false;
        if (isset($user) && isset($journal)) {
            if ($submission && Repo::submission()->canPreview($user, $submission)) {
                $result = true;
            } else {
                $result = $subscriptionDao->isValidIndividualSubscription($user->getId(), $journal->getId());
            }

            // If no valid subscription, check if there is an expired subscription
            // that was valid during publication date of any one of the submission's
            // publications
            if (!$result && $journal->getData('subscriptionExpiryPartial')) {
                if (isset($submission) && !empty($submission->getData('publications'))) {
                    foreach ($submission->getData('publications') as $publication) {
                        if ($subscriptionDao->isValidIndividualSubscription($user->getId(), $journal->getId(), Subscription::SUBSCRIPTION_DATE_END, $publication->getData('datePublished'))) {
                            $result = true;
                            break;
                        }
                    }
                } elseif (isset($issueId)) {
                    $issue = Repo::issue()->get($issueId);
                    if (isset($issue) && $issue->getPublished()) {
                        $result = $subscriptionDao->isValidIndividualSubscription($user->getId(), $journal->getId(), Subscription::SUBSCRIPTION_DATE_END, $issue->getDatePublished());
                    }
                }
            }
        }
        Hook::call('IssueAction::subscribedUser', [&$user, &$journal, &$issueId, &$articleId, &$result]);
        return $result;
    }

    /**
     * Checks if remote client domain or ip is allowed
     *
     * @param \APP\core\Request $request
     * @param \APP\journal\Journal $journal
     * @param int $issueId Issue ID (optional)
     * @param int $articleId Article ID (optional)
     *
     * @return bool
     */
    public function subscribedDomain($request, $journal, $issueId = null, $articleId = null)
    {
        $subscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO'); /** @var InstitutionalSubscriptionDAO $subscriptionDao */
        $result = false;
        if (isset($journal)) {
            $result = $subscriptionDao->isValidInstitutionalSubscription($request->getRemoteDomain(), $request->getRemoteAddr(), $journal->getId());

            // If no valid subscription, check if there is an expired subscription
            // that was valid during publication date of requested content
            if (!$result && $journal->getData('subscriptionExpiryPartial')) {
                if (isset($articleId)) {
                    $submission = Repo::submission()->get($articleId);
                    if ($submission->getData('status') === PKPSubmission::STATUS_PUBLISHED) {
                        $result = $subscriptionDao->isValidInstitutionalSubscription($request->getRemoteDomain(), $request->getRemoteAddr(), $journal->getId(), Subscription::SUBSCRIPTION_DATE_END, $submission->getDatePublished());
                    }
                } elseif (isset($issueId)) {
                    $issue = Repo::issue()->get($issueId);
                    if (isset($issue) && $issue->getPublished()) {
                        $result = $subscriptionDao->isValidInstitutionalSubscription($request->getRemoteDomain(), $request->getRemoteAddr(), $journal->getId(), Subscription::SUBSCRIPTION_DATE_END, $issue->getDatePublished());
                    }
                }
            }
        }
        Hook::call('IssueAction::subscribedDomain', [&$request, &$journal, &$issueId, &$articleId, &$result]);
        return (bool) $result;
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\issue\IssueAction', '\IssueAction');
}
