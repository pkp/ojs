<?php

/**
 * @file classes/migration/upgrade/v3_6_0/I1660_ReviewerRecommendations.php
 *
 * Copyright (c) 2024 Simon Fraser University
 * Copyright (c) 2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I1660_ReviewerRecommendations.php
 *
 * @brief
 *
 */

namespace APP\migration\upgrade\v3_6_0;

class I1660_ReviewerRecommendations extends \PKP\migration\upgrade\v3_6_0\I1660_ReviewerRecommendations
{
    public const SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT = 1;
    public const SUBMISSION_REVIEWER_RECOMMENDATION_PENDING_REVISIONS = 2;
    public const SUBMISSION_REVIEWER_RECOMMENDATION_RESUBMIT_HERE = 3;
    public const SUBMISSION_REVIEWER_RECOMMENDATION_RESUBMIT_ELSEWHERE = 4;
    public const SUBMISSION_REVIEWER_RECOMMENDATION_DECLINE = 5;
    public const SUBMISSION_REVIEWER_RECOMMENDATION_SEE_COMMENTS = 6;

    protected function systemDefineNonRemovableRecommendations(): array
    {
        return [
            static::SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT => 'reviewer.article.decision.accept',
            static::SUBMISSION_REVIEWER_RECOMMENDATION_PENDING_REVISIONS => 'reviewer.article.decision.pendingRevisions',
            static::SUBMISSION_REVIEWER_RECOMMENDATION_RESUBMIT_HERE => 'reviewer.article.decision.resubmitHere',
            static::SUBMISSION_REVIEWER_RECOMMENDATION_RESUBMIT_ELSEWHERE => 'reviewer.article.decision.resubmitElsewhere',
            static::SUBMISSION_REVIEWER_RECOMMENDATION_DECLINE => 'reviewer.article.decision.decline',
            static::SUBMISSION_REVIEWER_RECOMMENDATION_SEE_COMMENTS => 'reviewer.article.decision.seeComments',
        ];
    }
}
