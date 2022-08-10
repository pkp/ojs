<?php

/**
 * @file controllers/grid/users/stageParticipant/form/StageParticipantNotifyForm.php
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

namespace APP\controllers\grid\users\stageParticipant\form;

use PKP\controllers\grid\users\stageParticipant\form\PKPStageParticipantNotifyForm;

class StageParticipantNotifyForm extends PKPStageParticipantNotifyForm
{
    /**
     * FIXME should be retrieved from a database based on a record in email_template_assignments table after
     * API implementation pkp/pkp-lib#7706
     */
    protected function getStageTemplates(): array
    {
        $map = [
            WORKFLOW_STAGE_ID_SUBMISSION => ['EDITOR_ASSIGN'],
            WORKFLOW_STAGE_ID_INTERNAL_REVIEW => ['EDITOR_ASSIGN'],
            WORKFLOW_STAGE_ID_EXTERNAL_REVIEW => ['EDITOR_ASSIGN'],
            WORKFLOW_STAGE_ID_EDITING => ['EDITOR_ASSIGN', 'COPYEDIT_REQUEST'],
            WORKFLOW_STAGE_ID_PRODUCTION => ['EDITOR_ASSIGN', 'LAYOUT_REQUEST', 'LAYOUT_COMPLETE'],
        ];
        return $map[$this->getStageId()];
    }
}
