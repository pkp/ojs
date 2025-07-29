<?php

/**
 * @file classes/userComment/Repository.php
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Repository
 *
 * @ingroup userComment
 *
 * @brief A repository to manage comments.
 */

namespace APP\userComment;

use APP\core\Application;
use APP\facades\Repo;
use PKP\core\PKPApplication;
use PKP\userComment\UserComment;

class Repository extends \PKP\userComment\Repository
{
    /**
     * @inheritdoc
     */
    public function getPublicationUrl(UserComment $comment): string
    {
        $publication = Repo::publication()->get($comment->publicationId);
        $request = Application::get()->getRequest();


        // Build URL pointing to exact publication version that the comment is associated with.
        return $request->getDispatcher()->url(
            $request,
            PKPApplication::ROUTE_PAGE,
            null,
            'article',
            'view',
            [
                $publication->getData('submissionId'),
                'version',
                $publication->getId()
            ],
            null,
        );
    }
}
