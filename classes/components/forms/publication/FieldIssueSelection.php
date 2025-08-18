<?php
/**
 * @file classes/components/form/publication/FieldIssueSelection.php
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class FieldIssueSelection
 *
 * @brief An extension of the FieldSelect for issue assignment options.
 */

namespace APP\components\forms\publication;

use APP\core\Application;
use APP\facades\Repo;
use APP\publication\Publication;
use PKP\components\forms\FieldSelect;

class FieldIssueSelection extends FieldSelect
{
    /** @copydoc Field::$component */
    public $component = 'field-issue-selection';

    public int $issueCount;

    public Publication $publication;

    /**
     * @copydoc Field::getConfig()
     */
    public function getConfig()
    {
        $config = parent::getConfig();
        
        $config['isPhpForm'] = true;
        
        // always required it and let the dynamic nature to vue component to manage it
        $config['isRequired'] = true;
        
        $config['issueCount'] = $this->issueCount;
        $config['publication'] = $this->publication->getAllData();
        $config['assignmentType'] = Repo::publication()
            ->getIssueAssignmentStatus(
                $this->publication, 
                Application::get()->getRequest()->getContext()
            )
            ->value;

        return $config;
    }
}
