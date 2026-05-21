<?php

namespace APP\view\components;

use APP\core\Application;
use Illuminate\Support\Collection;
use PKP\context\Context;

class Layout extends \PKP\view\components\Layout
{
    /**
     * Add global template data
     */
    protected function addGlobalData(): void
    {
        parent::addGlobalData();
        view()->share('publicationIds', [$this, 'getPublicationIds']);
    }

    /**
     * Get an array of ISSNs and other publication IDs
     *
     */
    public function getPublicationIds(): Collection
    {
        $context = Application::get()->getRequest()->getContext();

        $ids = collect([]);

        if ($context->getData('printIssn')) {
            $ids->add([
                'name' => __('journal.issn'),
                'value' => $context->getData('printIssn'),
            ]);
        }

        if ($context->getData('onlineIssn')) {
            $ids->add([
                'name' => __('metadata.property.displayName.eissn'),
                'value' => $context->getData('onlineIssn'),
            ]);
        }

        if ($context->getData(Context::SETTING_DOI_PREFIX)) {
            $ids->add([
                'name' => __('manager.dois.title'),
                'value' => $context->getData(Context::SETTING_DOI_PREFIX),
            ]);
        }

        return $ids;
    }

    /**
     * Are we currently viewing the article, book or
     * preprint landing page?
     */
    public function isPublicationPage(): bool
    {
        $request = Application::get()->getRequest();

        return $request->getRequestedPage() === 'article'
            && $request->getRequestedOp() === 'view';
    }
}
