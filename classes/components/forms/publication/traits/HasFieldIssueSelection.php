<?php
/**
 * @file classes/components/form/publication/traits/HasFieldIssueSelection.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2000-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class HasFieldIssueSelection
 *
 * @brief Add issue selection field to the form
 */

namespace APP\components\forms\publication\traits;

use APP\publication\Publication;
use PKP\components\forms\FieldText;
use APP\components\forms\publication\FieldIssueSelection;
use APP\facades\Repo;
use PKP\context\Context;

trait HasFieldIssueSelection
{
    public function addFieldIssueSelection(
        Publication $publication,
        Context $context,
        ?int $issueCount = null
    ): static
    {
        $issueCount ??= Repo::issue()->getCollector()
            ->filterByContextIds([$context->getId()])
            ->getCount();

        return $this /** @var \PKP\components\forms\FormComponent $this */
            ->addField(new FieldIssueSelection('issueId', [
                'label' => __('publication.assignToIssue.label'),
                'issueCount' => $issueCount,
                'publication' => $publication,
            ]))
            // FIXME: add hidden field for prePublishStatus and remove normal text field
            // ->addHiddenField('prePublishStatus', $publication->getData('status'))
            ->addField(new FieldText('prePublishStatus', [
                'value' => $publication->getData('status'),
            ]));
    }
}