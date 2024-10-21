<?php
/**
 * @file classes/components/form/dashboard/SubmissionFilters.php
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2000-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubmissionFilters
 *
 * @ingroup classes_controllers_form
 *
 * @brief A preset form to add and remove filters in the submissions dashboard
 */

namespace APP\components\forms\dashboard;

use APP\components\forms\FieldSelectIssues;
use APP\core\Application;
use Illuminate\Support\LazyCollection;
use PKP\components\forms\dashboard\PKPSubmissionFilters;
use PKP\context\Context;

class SubmissionFilters extends PKPSubmissionFilters
{
    public function __construct(
        public Context $context,
        public array $userRoles,
        public LazyCollection $sections,
        public LazyCollection $categories
    ) {
        $this
            ->addPage(['id' => 'default', 'submitButton' => null])
            ->addGroup(['id' => 'default', 'pageId' => 'default'])
            ->addSectionFields()
            ->addAssignedTo()
            ->addIssues()
            ->addCategories()
            ->addDaysSinceLastActivity()
        ;
    }

    protected function addIssues(): self
    {
        $request = Application::get()->getRequest();

        return $this->addField(new FieldSelectIssues('issueIds', [
            'groupId' => 'default',
            'label' => __('issue.issues'),
            'value' => [],
            'apiUrl' => $request->getDispatcher()->url($request, Application::ROUTE_API, $request->getContext()->getPath(), 'issues'),
        ]));
    }
}
