<?php

/**
 * @file plugins/pubIds/doi/DOIPubIdPlugin.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DOIPubIdPlugin
 * @ingroup plugins_pubIds_doi
 *
 * @brief DOI plugin class
 */

use APP\article\ArticleGalley;
use APP\facades\Repo;
use APP\issue\Issue;
use APP\issue\IssueGalley;
use APP\plugins\PubIdPlugin;
use APP\publication\Publication;

use PKP\linkAction\LinkAction;
use PKP\linkAction\request\RemoteActionConfirmationModal;

class DOIPubIdPlugin extends PubIdPlugin
{
    /**
     * @copydoc Plugin::register()
     *
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);
        if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) {
            return $success;
        }
        if ($success && $this->getEnabled($mainContextId)) {
            HookRegistry::register('CitationStyleLanguage::citation', [$this, 'getCitationData']);
            HookRegistry::register('Publication::getProperties::summaryProperties', [$this, 'modifyObjectProperties']);
            HookRegistry::register('Publication::getProperties::fullProperties', [$this, 'modifyObjectProperties']);
            HookRegistry::register('Publication::validate', [$this, 'validatePublicationDoi']);
            HookRegistry::register('Issue::getProperties::summaryProperties', [$this, 'modifyObjectProperties']);
            HookRegistry::register('Issue::getProperties::fullProperties', [$this, 'modifyObjectProperties']);
            HookRegistry::register('Galley::getProperties::summaryProperties', [$this, 'modifyObjectProperties']);
            HookRegistry::register('Galley::getProperties::fullProperties', [$this, 'modifyObjectProperties']);
            HookRegistry::register('Publication::getProperties::values', [$this, 'modifyObjectPropertyValues']);
            HookRegistry::register('Issue::getProperties::values', [$this, 'modifyObjectPropertyValues']);
            HookRegistry::register('Galley::getProperties::values', [$this, 'modifyObjectPropertyValues']);
            HookRegistry::register('Form::config::before', [$this, 'addPublicationFormFields']);
            HookRegistry::register('Form::config::before', [$this, 'addPublishFormNotice']);
        }
        return $success;
    }

    //
    // Implement template methods from Plugin.
    //
    /**
     * @copydoc Plugin::getDisplayName()
     */
    public function getDisplayName()
    {
        return __('plugins.pubIds.doi.displayName');
    }

    /**
     * @copydoc Plugin::getDescription()
     */
    public function getDescription()
    {
        return __('plugins.pubIds.doi.description');
    }


    //
    // Implement template methods from PubIdPlugin.
    //
    /**
     * @copydoc PKPPubIdPlugin::constructPubId()
     */
    public function constructPubId($pubIdPrefix, $pubIdSuffix, $contextId)
    {
        return $pubIdPrefix . '/' . $pubIdSuffix;
    }

    /**
     * @copydoc PKPPubIdPlugin::getPubIdType()
     */
    public function getPubIdType()
    {
        return 'doi';
    }

    /**
     * @copydoc PKPPubIdPlugin::getPubIdDisplayType()
     */
    public function getPubIdDisplayType()
    {
        return 'DOI';
    }

    /**
     * @copydoc PKPPubIdPlugin::getPubIdFullName()
     */
    public function getPubIdFullName()
    {
        return 'Digital Object Identifier';
    }

    /**
     * @copydoc PKPPubIdPlugin::getResolvingURL()
     */
    public function getResolvingURL($contextId, $pubId)
    {
        return 'https://doi.org/' . $this->_doiURLEncode($pubId);
    }

    /**
     * @copydoc PKPPubIdPlugin::getPubIdMetadataFile()
     */
    public function getPubIdMetadataFile()
    {
        return $this->getTemplateResource('doiSuffixEdit.tpl');
    }

    /**
     * @copydoc PKPPubIdPlugin::getPubIdAssignFile()
     */
    public function getPubIdAssignFile()
    {
        return $this->getTemplateResource('doiAssign.tpl');
    }

    /**
     * @copydoc PKPPubIdPlugin::instantiateSettingsForm()
     */
    public function instantiateSettingsForm($contextId)
    {
        $this->import('classes.form.DOISettingsForm');
        return new DOISettingsForm($this, $contextId);
    }

    /**
     * @copydoc PKPPubIdPlugin::getFormFieldNames()
     */
    public function getFormFieldNames()
    {
        return ['doiSuffix'];
    }

    /**
     * @copydoc PKPPubIdPlugin::getAssignFormFieldName()
     */
    public function getAssignFormFieldName()
    {
        return 'assignDoi';
    }

    /**
     * @copydoc PKPPubIdPlugin::getPrefixFieldName()
     */
    public function getPrefixFieldName()
    {
        return 'doiPrefix';
    }

    /**
     * @copydoc PKPPubIdPlugin::getSuffixFieldName()
     */
    public function getSuffixFieldName()
    {
        return 'doiSuffix';
    }

    /**
     * @copydoc PKPPubIdPlugin::getLinkActions()
     */
    public function getLinkActions($pubObject)
    {
        $linkActions = [];
        $request = Application::get()->getRequest();
        $userVars = $request->getUserVars();
        $classNameParts = explode('\\', get_class($this)); // Separate namespace info from class name
        $userVars['pubIdPlugIn'] = end($classNameParts);
        // Clear object pub id
        $linkActions['clearPubIdLinkActionDoi'] = new LinkAction(
            'clearPubId',
            new RemoteActionConfirmationModal(
                $request->getSession(),
                __('plugins.pubIds.doi.editor.clearObjectsDoi.confirm'),
                __('common.delete'),
                $request->url(null, null, 'clearPubId', null, $userVars),
                'modal_delete'
            ),
            __('plugins.pubIds.doi.editor.clearObjectsDoi'),
            'delete',
            __('plugins.pubIds.doi.editor.clearObjectsDoi')
        );

        if ($pubObject instanceof Issue) {
            // Clear issue objects pub ids
            $linkActions['clearIssueObjectsPubIdsLinkActionDoi'] = new LinkAction(
                'clearObjectsPubIds',
                new RemoteActionConfirmationModal(
                    $request->getSession(),
                    __('plugins.pubIds.doi.editor.clearIssueObjectsDoi.confirm'),
                    __('common.delete'),
                    $request->url(null, null, 'clearIssueObjectsPubIds', null, $userVars),
                    'modal_delete'
                ),
                __('plugins.pubIds.doi.editor.clearIssueObjectsDoi'),
                'delete',
                __('plugins.pubIds.doi.editor.clearIssueObjectsDoi')
            );
        }

        return $linkActions;
    }

    /**
     * @copydoc PKPPubIdPlugin::getSuffixPatternsFieldNames()
     */
    public function getSuffixPatternsFieldNames()
    {
        return  [
            'Issue' => 'doiIssueSuffixPattern',
            'Publication' => 'doiPublicationSuffixPattern',
            'Representation' => 'doiRepresentationSuffixPattern'
        ];
    }

    /**
     * @copydoc PKPPubIdPlugin::getDAOFieldNames()
     */
    public function getDAOFieldNames()
    {
        return ['pub-id::doi'];
    }

    /**
     * @copydoc PKPPubIdPlugin::isObjectTypeEnabled()
     */
    public function isObjectTypeEnabled($pubObjectType, $contextId)
    {
        return (bool) $this->getSetting($contextId, "enable${pubObjectType}Doi");
    }

    /**
     * @copydoc PKPPubIdPlugin::isObjectTypeEnabled()
     */
    public function getNotUniqueErrorMsg()
    {
        return __('plugins.pubIds.doi.editor.doiSuffixCustomIdentifierNotUnique');
    }

    /**
     * @copydoc PKPPubIdPlugin::validatePubId()
     */
    public function validatePubId($pubId)
    {
        return preg_match('/^\d+(.\d+)+\//', $pubId);
    }

    /*
     * Public methods
     */
    /**
     * Add DOI to citation data used by the CitationStyleLanguage plugin
     *
     * @see CitationStyleLanguagePlugin::getCitation()
     *
     * @param $hookname string
     * @param $args array
     *
     * @return false
     */
    public function getCitationData($hookname, $args)
    {
        $citationData = $args[0];
        $article = $args[2];
        $issue = $args[3];
        $journal = $args[4];

        if ($issue && $issue->getPublished()) {
            $pubId = $article->getStoredPubId($this->getPubIdType());
        } else {
            $pubId = $this->getPubId($article);
        }

        if (!$pubId) {
            return;
        }

        $citationData->DOI = $pubId;
    }


    /*
     * Private methods
     */
    /**
     * Encode DOI according to ANSI/NISO Z39.84-2005, Appendix E.
     *
     * @param $pubId string
     *
     * @return string
     */
    public function _doiURLEncode($pubId)
    {
        $search = ['%', '"', '#', ' ', '<', '>', '{'];
        $replace = ['%25', '%22', '%23', '%20', '%3c', '%3e', '%7b'];
        $pubId = str_replace($search, $replace, $pubId);
        return $pubId;
    }

    /**
     * Validate a publication's DOI against the plugin's settings
     *
     * @param $hookName string
     * @param $args array
     */
    public function validatePublicationDoi($hookName, $args)
    {
        $errors = & $args[0];
        $object = $args[1];
        $props = & $args[2];

        if (empty($props['pub-id::doi'])) {
            return;
        }

        if (is_null($object)) {
            $submission = Repo::submission()->get($props['submissionId']);
        } else {
            $publication = Repo::publication()->get($props['id']);
            $submission = Repo::submission()->get($publication->getData('submissionId'));
        }

        $contextId = $submission->getData('contextId');
        $doiPrefix = $this->getSetting($contextId, 'doiPrefix');

        $doiErrors = [];
        if (strpos($props['pub-id::doi'], $doiPrefix) !== 0) {
            $doiErrors[] = __('plugins.pubIds.doi.editor.missingPrefix', ['doiPrefix' => $doiPrefix]);
        }
        if (!$this->checkDuplicate($props['pub-id::doi'], 'Publication', $submission->getId(), $contextId)) {
            $doiErrors[] = $this->getNotUniqueErrorMsg();
        }
        if (!empty($doiErrors)) {
            $errors['pub-id::doi'] = $doiErrors;
        }
    }

    /**
     * Add DOI to submission, issue or galley properties
     *
     * @param $hookName string <Object>::getProperties::summaryProperties or
     *  <Object>::getProperties::fullProperties
     * @param $args array [
     * 		@option $props array Existing properties
     * 		@option $object Submission|Issue|Galley
     * 		@option $args array Request args
     * ]
     *
     * @return array
     */
    public function modifyObjectProperties($hookName, $args)
    {
        $props = & $args[0];

        $props[] = 'pub-id::doi';
    }

    /**
     * Add DOI submission, issue or galley values
     *
     * @param $hookName string <Object>::getProperties::values
     * @param $args array [
     * 		@option $values array Key/value store of property values
     * 		@option $object Submission|Issue|Galley
     * 		@option $props array Requested properties
     * 		@option $args array Request args
     * ]
     *
     * @return array
     */
    public function modifyObjectPropertyValues($hookName, $args)
    {
        $values = & $args[0];
        $object = $args[1];
        $props = $args[2];

        // DOIs are not supported for IssueGalleys
        if ($object instanceof IssueGalley) {
            return;
        }

        // DOIs are already added to property values for Publications and Galleys
        if ($object instanceof Publication || $object instanceof ArticleGalley) {
            return;
        }

        if (in_array('pub-id::doi', $props)) {
            $pubId = $this->getPubId($object);
            $values['pub-id::doi'] = $pubId ? $pubId : null;
        }
    }

    /**
     * Add DOI fields to the publication identifiers form
     *
     * @param $hookName string Form::config::before
     * @param $form FormComponent The form object
     */
    public function addPublicationFormFields($hookName, $form)
    {
        if ($form->id !== 'publicationIdentifiers') {
            return;
        }

        if (!$this->getSetting($form->submissionContext->getId(), 'enablePublicationDoi')) {
            return;
        }

        $prefix = $this->getSetting($form->submissionContext->getId(), 'doiPrefix');

        $suffixType = $this->getSetting($form->submissionContext->getId(), 'doiSuffix');
        $pattern = '';
        if ($suffixType === 'default') {
            $pattern = '%j.v%vi%i.%a';
        } elseif ($suffixType === 'pattern') {
            $pattern = $this->getSetting($form->submissionContext->getId(), 'doiPublicationSuffixPattern');
        }

        // Add a text field to enter the DOI if no pattern exists
        if (!$pattern) {
            $form->addField(new \PKP\components\forms\FieldText('pub-id::doi', [
                'label' => __('metadata.property.displayName.doi'),
                'description' => __('plugins.pubIds.doi.editor.doi.description', ['prefix' => $prefix]),
                'value' => $form->publication->getData('pub-id::doi'),
            ]));
        } else {
            $fieldData = [
                'label' => __('metadata.property.displayName.doi'),
                'value' => $form->publication->getData('pub-id::doi'),
                'prefix' => $prefix,
                'pattern' => $pattern,
                'contextInitials' => PKPString::regexp_replace('/[^A-Za-z0-9]/', '', PKPString::strtolower($form->submissionContext->getData('acronym', $form->submissionContext->getData('primaryLocale')))) ?? '',
                'separator' => '/',
                'submissionId' => $form->publication->getData('submissionId'),
                'assignIdLabel' => __('plugins.pubIds.doi.editor.doi.assignDoi'),
                'clearIdLabel' => __('plugins.pubIds.doi.editor.clearObjectsDoi'),
            ];
            if ($form->publication->getData('pub-id::publisher-id')) {
                $fieldData['publisherId'] = $form->publication->getData('pub-id::publisher-id');
            }
            if ($form->publication->getData('pages')) {
                $fieldData['pages'] = $form->publication->getData('pages');
            }
            if ($form->publication->getData('issueId')) {
                $issue = Services::get('issue')->get($form->publication->getData('issueId'));
                if ($issue) {
                    $fieldData['issueNumber'] = $issue->getNumber() ?? '';
                    $fieldData['issueVolume'] = $issue->getVolume() ?? '';
                    $fieldData['year'] = $issue->getYear() ?? '';
                }
            }
            if ($suffixType === 'default') {
                $fieldData['missingPartsLabel'] = __('plugins.pubIds.doi.editor.missingIssue');
            } else {
                $fieldData['missingPartsLabel'] = __('plugins.pubIds.doi.editor.missingParts');
            }
            $form->addField(new \PKP\components\forms\FieldPubId('pub-id::doi', $fieldData));
        }
    }

    /**
     * Show DOI during final publish step
     *
     * @param $hookName string Form::config::before
     * @param $form FormComponent The form object
     */
    public function addPublishFormNotice($hookName, $form)
    {
        if ($form->id !== 'publish' || !empty($form->errors)) {
            return;
        }

        $submission = Repo::submission()->get($form->publication->getData('submissionId'));
        $publicationDoiEnabled = $this->getSetting($submission->getData('contextId'), 'enablePublicationDoi');
        $galleyDoiEnabled = $this->getSetting($submission->getData('contextId'), 'enableRepresentationDoi');
        $warningIconHtml = '<span class="fa fa-exclamation-triangle pkpIcon--inline"></span>';

        if (!$publicationDoiEnabled && !$galleyDoiEnabled) {
            return;

        // Use a simplified view when only assigning to the publication
        } elseif (!$galleyDoiEnabled) {
            if ($form->publication->getData('pub-id::doi')) {
                $msg = __('plugins.pubIds.doi.editor.preview.publication', ['doi' => $form->publication->getData('pub-id::doi')]);
            } else {
                $msg = '<div class="pkpNotification pkpNotification--warning">' . $warningIconHtml . __('plugins.pubIds.doi.editor.preview.publication.none') . '</div>';
            }
            $form->addField(new \PKP\components\forms\FieldHTML('doi', [
                'description' => $msg,
                'groupId' => 'default',
            ]));
            return;

        // Show a table if more than one DOI is going to be created
        } else {
            $doiTableRows = [];
            if ($publicationDoiEnabled) {
                if ($form->publication->getData('pub-id::doi')) {
                    $doiTableRows[] = [$form->publication->getData('pub-id::doi'), 'Publication'];
                } else {
                    $doiTableRows[] = [$warningIconHtml . __('submission.status.unassigned'), 'Publication'];
                }
            }
            if ($galleyDoiEnabled) {
                foreach ((array) $form->publication->getData('galleys') as $galley) {
                    if ($galley->getStoredPubId('doi')) {
                        $doiTableRows[] = [$galley->getStoredPubId('doi'), __('plugins.pubIds.doi.editor.preview.galleys', ['galleyLabel' => $galley->getGalleyLabel()])];
                    } else {
                        $doiTableRows[] = [$warningIconHtml . __('submission.status.unassigned'),__('plugins.pubIds.doi.editor.preview.galleys', ['galleyLabel' => $galley->getGalleyLabel()])];
                    }
                }
            }
            if (!empty($doiTableRows)) {
                $table = '<table class="pkpTable"><thead><tr>' .
                    '<th>' . __('plugins.pubIds.doi.editor.doi') . '</th>' .
                    '<th>' . __('plugins.pubIds.doi.editor.preview.objects') . '</th>' .
                    '</tr></thead><tbody>';
                foreach ($doiTableRows as $doiTableRow) {
                    $table .= '<tr><td>' . $doiTableRow[0] . '</td><td>' . $doiTableRow[1] . '</td></tr>';
                }
                $table .= '</tbody></table>';
            }
            $form->addField(new \PKP\components\forms\FieldHTML('doi', [
                'description' => $table,
                'groupId' => 'default',
            ]));
        }
    }
}
