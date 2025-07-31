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

class Repository extends \PKP\userComment\Repository
{
    /**
     * @inheritdoc
     */
    public function getPublicationType(): string
    {
        return 'article';
    }
}
