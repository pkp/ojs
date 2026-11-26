<?php

/**
 * @file classes/components/form/issue/IssueEditorsForm.php
 *
 * Copyright (c) 2014-2026 Simon Fraser University
 * Copyright (c) 2000-2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IssueEditorsForm
 *
 * @brief A preset form for setting an issue's editors.
 */

namespace APP\components\forms\issue;

use APP\issue\Issue;
use PKP\components\forms\FieldAuthors;
use PKP\components\forms\FormComponent;

class IssueEditorsForm extends FormComponent
{
    public const FORM_ISSUE_EDITORS = 'issueEditors';
    public $id = self::FORM_ISSUE_EDITORS;
    public $method = 'PUT';

    /**
     * Constructor
     *
     * @param string $action URL to submit the form to
     * @param Issue $issue The issue to change settings for
     */
    public function __construct(string $action, Issue $issue)
    {
        $this->action = $action;

        $this->addField(new FieldAuthors('editors', [
            'label' => __('issue.editors'),
            'description' => __('issue.editors.description'),
            'value' => $issue->getData('editors') ?? [],
        ]));
    }
}
