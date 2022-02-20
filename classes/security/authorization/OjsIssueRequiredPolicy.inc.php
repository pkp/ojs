<?php
/**
 * @file classes/security/authorization/OjsIssueRequiredPolicy.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OjsIssueRequiredPolicy
 * @ingroup security_authorization_internal
 *
 * @brief Policy that ensures that the request contains a valid issue.
 */

namespace APP\security\authorization;

use APP\facades\Repo;
use APP\issue\Issue;
use PKP\security\authorization\AuthorizationPolicy;
use PKP\security\authorization\DataObjectRequiredPolicy;

use PKP\security\Role;

class OjsIssueRequiredPolicy extends DataObjectRequiredPolicy
{
    /** @var Journal */
    public $journal;

    /**
     * Constructor
     *
     * @param PKPRequest $request
     * @param array $args request parameters
     * @param array $operations
     */
    public function __construct($request, &$args, $operations = null)
    {
        parent::__construct($request, $args, 'issueId', 'user.authorization.invalidIssue', $operations);
        $this->journal = $request->getJournal();
    }

    //
    // Implement template methods from AuthorizationPolicy
    //
    /**
     * @see DataObjectRequiredPolicy::dataObjectEffect()
     */
    public function dataObjectEffect()
    {
        $issueId = $this->getDataObjectId();
        if (!$issueId) {
            return AuthorizationPolicy::AUTHORIZATION_DENY;
        }

        // Make sure the issue belongs to the journal.
        $issue = Repo::issue()->getByBestId($issueId, $this->journal->getId());

        if (!$issue instanceof Issue) {
            return AuthorizationPolicy::AUTHORIZATION_DENY;
        }

        // The issue must be published, or we must have pre-publication
        // access to it.
        $userRoles = $this->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES);
        if (!$issue->getPublished() && count(array_intersect(
            $userRoles,
            [
                Role::ROLE_ID_SITE_ADMIN,
                Role::ROLE_ID_MANAGER,
                Role::ROLE_ID_SUB_EDITOR,
                Role::ROLE_ID_ASSISTANT,
            ]
        )) == 0) {
            return AuthorizationPolicy::AUTHORIZATION_DENY;
        }

        // Save the issue to the authorization context.
        $this->addAuthorizedContextObject(ASSOC_TYPE_ISSUE, $issue);
        return AuthorizationPolicy::AUTHORIZATION_PERMIT;
    }

    /**
     * @copydoc DataObjectRequiredPolicy::getDataObjectId()
     * Considers a not numeric public URL identifier
     */
    public function getDataObjectId($lookOnlyByParameterName = false)
    {
        if ($lookOnlyByParameterName) {
            throw new Exception('lookOnlyByParameterName not supported for issues.');
        }
        // Identify the data object id.
        $router = $this->_request->getRouter();
        switch (true) {
            case $router instanceof \PKP\core\PKPPageRouter:
                if (ctype_digit((string) $this->_request->getUserVar($this->_parameterName))) {
                    // We may expect a object id in the user vars
                    return (int) $this->_request->getUserVar($this->_parameterName);
                } elseif (isset($this->_args[0])) {
                    // Or the object id can be expected as the first path in the argument list
                    return $this->_args[0];
                }
                break;

            default:
                return parent::getDataObjectId();
        }

        return false;
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\security\authorization\OjsIssueRequiredPolicy', '\OjsIssueRequiredPolicy');
}
