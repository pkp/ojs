<?php

/**
 * @file classes/view/MetadataBlockRepository.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Repository
 *
 * @brief A repository to register and load metadata blocks.
 */

namespace APP\view;

use PKP\view\HomepageBlock;

class HomepageBlocksRegistry extends \PKP\view\HomepageBlocksRegistry
{
    protected function registerDefaultBlocks(): void
    {
        parent::registerDefaultBlocks();

        $this->register(
            new HomepageBlock(
                component: 'homepage.issue-summary',
                title: __('manager.homepageBlocks.issueSummary'),
            )
        );
        $this->register(
            new HomepageBlock(
                component: 'homepage.issue-toc',
                title: __('manager.homepageBlocks.issueToc'),
            )
        );
    }
}
