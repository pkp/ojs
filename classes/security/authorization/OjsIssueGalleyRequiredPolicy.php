<?php
/**
 * @file classes/security/authorization/OjsIssueGalleyRequiredPolicy.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OjsIssueGalleyRequiredPolicy
 *
 * @ingroup security_authorization_internal
 *
 * @brief Policy that ensures that the request contains a valid issue galley.
 */

namespace APP\security\authorization;

use APP\core\Application;
use APP\core\Request;
use APP\issue\IssueGalley;
use APP\issue\IssueGalleyDAO;
use PKP\db\DAORegistry;
use PKP\security\authorization\AuthorizationPolicy;
use PKP\security\authorization\DataObjectRequiredPolicy;

class OjsIssueGalleyRequiredPolicy extends DataObjectRequiredPolicy
{
    /**
     * Constructor
     *
     * @param Request $request
     * @param array $args request parameters
     * @param array $operations
     */
    public function __construct($request, &$args, $operations = null)
    {
        parent::__construct($request, $args, 'issueGalleyId', 'user.authorization.invalidIssueGalley', $operations);
    }

    //
    // Implement template methods from AuthorizationPolicy
    //
    /**
     * @see DataObjectRequiredPolicy::dataObjectEffect()
     */
    public function dataObjectEffect()
    {
        $issueGalleyId = (int)$this->getDataObjectId();
        if (!$issueGalleyId) {
            return AuthorizationPolicy::AUTHORIZATION_DENY;
        }

        // Make sure the issue galley belongs to the journal.
        $issue = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_ISSUE);
        $issueGalleyDao = DAORegistry::getDAO('IssueGalleyDAO'); /** @var IssueGalleyDAO $issueGalleyDao */
        $issueGalley = $issueGalleyDao->getById($issueGalleyId, $issue->getId());
        if (!$issueGalley instanceof IssueGalley) {
            return AuthorizationPolicy::AUTHORIZATION_DENY;
        }

        // Save the publication format to the authorization context.
        $this->addAuthorizedContextObject(Application::ASSOC_TYPE_ISSUE_GALLEY, $issueGalley);
        return AuthorizationPolicy::AUTHORIZATION_PERMIT;
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\security\authorization\OjsIssueGalleyRequiredPolicy', '\OjsIssueGalleyRequiredPolicy');
}
