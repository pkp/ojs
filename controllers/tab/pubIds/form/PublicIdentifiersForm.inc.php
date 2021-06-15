<?php

/**
 * @file controllers/tab/pubIds/form/PublicIdentifiersForm.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PublicIdentifiersForm
 * @ingroup controllers_tab_pubIds_form
 *
 * @brief Displays a pub ids form.
 */

import('lib.pkp.controllers.tab.pubIds.form.PKPPublicIdentifiersForm');

use APP\article\ArticleGalley;
use APP\issue\Issue;
use APP\issue\IssueGalley;
use APP\template\TemplateManager;

class PublicIdentifiersForm extends PKPPublicIdentifiersForm
{
    /**
     * Constructor.
     *
     * @param $pubObject object
     * @param $stageId integer
     * @param $formParams array
     */
    public function __construct($pubObject, $stageId = null, $formParams = null)
    {
        parent::__construct($pubObject, $stageId, $formParams);
    }

    /**
     * @copydoc Form::fetch()
     *
     * @param null|mixed $template
     */
    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager($request);
        $enablePublisherId = (array) $request->getContext()->getData('enablePublisherId');
        $templateMgr->assign([
            'enablePublisherId' => ($this->getPubObject() instanceof ArticleGalley && in_array('galley', $enablePublisherId)) ||
                    ($this->getPubObject() instanceof Issue && in_array('issue', $enablePublisherId)) ||
                    ($this->getPubObject() instanceof IssueGalley && in_array('issueGalley', $enablePublisherId)),
        ]);

        return parent::fetch($request, $template, $display);
    }

    /**
     * @copydoc Form::execute()
     */
    public function execute(...$functionArgs)
    {
        parent::execute(...$functionArgs);
        $pubObject = $this->getPubObject();
        if ($pubObject instanceof Issue) {
            $issueDao = DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
            $issueDao->updateObject($pubObject);
        }
    }

    /**
     * Clear issue objects pub ids.
     *
     * @param $pubIdPlugInClassName string
     */
    public function clearIssueObjectsPubIds($pubIdPlugInClassName)
    {
        $pubIdPlugins = PluginRegistry::loadCategory('pubIds', true);
        foreach ($pubIdPlugins as $pubIdPlugin) {
            $classNameParts = explode('\\', get_class($pubIdPlugin)); // Separate namespace info from class name
            if (end($classNameParts) == $pubIdPlugInClassName) {
                $pubIdPlugin->clearIssueObjectsPubIds($this->getPubObject());
            }
        }
    }

    /**
     * @copydoc PKPPublicIdentifiersForm::getAssocType()
     */
    public function getAssocType($pubObject)
    {
        if ($pubObject instanceof Issue) {
            return ASSOC_TYPE_ISSUE;
        }
        return parent::getAssocType($pubObject);
    }
}
