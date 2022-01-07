<?php

/**
 * @file controllers/grid/pubIds/form/AssignPublicIdentifiersForm.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class AssignPublicIdentifiersForm
 * @ingroup controllers_grid_pubIds_form
 *
 * @brief Displays the assign pub id form.
 */

import('lib.pkp.controllers.grid.pubIds.form.PKPAssignPublicIdentifiersForm');

use APP\template\TemplateManager;

class AssignPublicIdentifiersForm extends PKPAssignPublicIdentifiersForm
{
    /**
     * @var array Parameters to configure the form template.
     */
    public $_formParams;

    /**
     * Constructor.
     *
     * @param string $template Form template
     * @param object $pubObject
     * @param bool $approval
     * @param string $confirmationText
     * @param array $formParams
     */
    public function __construct($template, $pubObject, $approval, $confirmationText, $formParams = null)
    {
        parent::__construct($template, $pubObject, $approval, $confirmationText);

        $this->_formParams = $formParams;
    }

    /**
     * @copydoc Form::fetch()
     *
     * @param null|mixed $template
     */
    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('formParams', $this->getFormParams());
        return parent::fetch($request, $template, $display);
    }

    //
    // Getters and Setters
    //
    /**
     * Get the extra form parameters.
     *
     * @return array
     */
    public function getFormParams()
    {
        return $this->_formParams;
    }
}
