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

use PKP\view\MetadataBlock;

class MetadataBlocksRegistry extends \PKP\view\MetadataBlocksRegistry
{
    protected function registerDefaultBlocks(): void
    {
        parent::registerDefaultBlocks();

        $this->register(
            new MetadataBlock(
                component: 'metadata.pages',
                title: __('editor.issues.pages'),
            )
        );
        $this->register(
            new MetadataBlock(
                component: 'metadata.issue',
                title: __('issue.issue'),
            )
        );
        $this->register(
            new MetadataBlock(
                component: 'metadata.section',
                title: __('section.section'),
            )
        );
        $this->register(
            new MetadataBlock(
                component: 'metadata.article-number',
                title: __('submission.articleNumber'),
            )
        );
    }
}
