<?php

/**
 * @file controllers/grid/users/stageParticipant/form/StageParticipantNotifyForm.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class StageParticipantNotifyForm
 * @ingroup grid_users_stageParticipant_form
 *
 * @brief Form to notify a user regarding a file
 */

use APP\mail\ArticleMailTemplate;

import('lib.pkp.controllers.grid.users.stageParticipant.form.PKPStageParticipantNotifyForm');

class StageParticipantNotifyForm extends PKPStageParticipantNotifyForm
{
    /**
     * Return app-specific stage templates.
     *
     * @return array
     */
    protected function _getStageTemplates()
    {
        return [
            WORKFLOW_STAGE_ID_SUBMISSION => ['EDITOR_ASSIGN'],
            WORKFLOW_STAGE_ID_EXTERNAL_REVIEW => ['EDITOR_ASSIGN'],
            WORKFLOW_STAGE_ID_EDITING => ['COPYEDIT_REQUEST'],
            WORKFLOW_STAGE_ID_PRODUCTION => ['LAYOUT_REQUEST', 'LAYOUT_COMPLETE', 'INDEX_REQUEST', 'INDEX_COMPLETE', 'EDITOR_ASSIGN']
        ];
    }

    /**
     * return app-specific mail template.
     *
     * @param Submission $submission
     * @param string $templateKey
     * @param bool $includeSignature optional
     *
     * @return ArticleMailTemplate
     */
    protected function _getMailTemplate($submission, $templateKey, $includeSignature = true)
    {
        if ($includeSignature) {
            return new ArticleMailTemplate($submission, $templateKey);
        } else {
            return new ArticleMailTemplate($submission, $templateKey, null, null, false);
        }
    }
}
