<?php

/**
 * @file plugins/generic/dataverse/DataversePlugin.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class dataversePlugin
 * @ingroup plugins_generic_dataverse
 *
 * @brief dataverse plugin class
 */

import('lib.pkp.classes.plugins.GenericPlugin');
import('plugins.generic.dataverse.classes.DataversePackager');

require('lib/pkp/lib/swordappv2/swordappclient.php');

define('DATAVERSE_PLUGIN_HTTP_STATUS_OK', 200);
define('DATAVERSE_PLUGIN_HTTP_STATUS_CREATED', 201);
define('DATAVERSE_PLUGIN_HTTP_STATUS_NO_CONTENT', 204);
define('DATAVERSE_PLUGIN_TOU_POLICY_SEPARATOR', '---');
define('DATAVERSE_PLUGIN_SUBJECT_SEPARATOR', ';');
define('DATAVERSE_PLUGIN_CITATION_FORMAT_APA', 'APA');

// Study release options
define('DATAVERSE_PLUGIN_RELEASE_ARTICLE_ACCEPTED',  0x01);
define('DATAVERSE_PLUGIN_RELEASE_ARTICLE_PUBLISHED', 0x02);

// Notification types
define('NOTIFICATION_TYPE_DATAVERSE_STUDY_CREATED',  NOTIFICATION_TYPE_PLUGIN_BASE + 0x0001001);
define('NOTIFICATION_TYPE_DATAVERSE_STUDY_UPDATED',  NOTIFICATION_TYPE_PLUGIN_BASE + 0x0001002);
define('NOTIFICATION_TYPE_DATAVERSE_FILE_ADDED',     NOTIFICATION_TYPE_PLUGIN_BASE + 0x0001003);
define('NOTIFICATION_TYPE_DATAVERSE_FILE_DELETED',   NOTIFICATION_TYPE_PLUGIN_BASE + 0x0001004);
define('NOTIFICATION_TYPE_DATAVERSE_STUDY_DELETED',  NOTIFICATION_TYPE_PLUGIN_BASE + 0x0001005);
define('NOTIFICATION_TYPE_DATAVERSE_STUDY_RELEASED', NOTIFICATION_TYPE_PLUGIN_BASE + 0x0001006);
define('NOTIFICATION_TYPE_DATAVERSE_UNRELEASED',     NOTIFICATION_TYPE_PLUGIN_BASE + 0x0001007);

class DataversePlugin extends GenericPlugin {

  /**
   * Called as a plugin is registered to the registry
   * @param $category String Name of category plugin was registered to
   * @param $path String
   * @return boolean True iff plugin initialized successfully; if false,
   * 	the plugin will not be registered.
   */
  function register($category, $path) {
    $success = parent::register($category, $path);
    if ($success && $this->getEnabled()) {
      // Dataverse Study objects
      $this->import('classes.DataverseStudyDAO');
      $dataverseStudyDao = new DataverseStudyDAO($this->getName());
      $returner =& DAORegistry::registerDAO('DataverseStudyDAO', $dataverseStudyDao);

      // Files associated with Dataverse studies
      $this->import('classes.DataverseFileDAO');      
      $dataverseFileDao = new DataverseFileDAO($this->getName());      
      $returner =& DAORegistry::registerDAO('DataverseFileDAO', $dataverseFileDao);
          
      // Handler for public (?) access to Dataverse-related information (i.e., terms of Use)
      HookRegistry::register('LoadHandler', array(&$this, 'setupPublicHandler'));
      // Add data citation to submissions & reading tools  
      HookRegistry::register('TemplateManager::display', array(&$this, 'handleTemplateDisplay'));
      // Add data citation to article landing page
      HookRegistry::register('Templates::Article::MoreInfo', array(&$this, 'addDataCitationArticle'));
      // Enable TinyMCEditor in textarea fields
      HookRegistry::register('TinyMCEPlugin::getEnableFields', array(&$this, 'getTinyMCEEnabledFields'));
      // Include data policy in About page
      HookRegistry::register('Templates::About::Index::Policies', array(&$this, 'addPolicyLinks'));
      
      // Add data publication options to author submission suppfile form: 
      HookRegistry::register('Templates::Author::Submit::SuppFile::AdditionalMetadata', array(&$this, 'suppFileAdditionalMetadata'));
      HookRegistry::register('authorsubmitsuppfileform::initdata', array(&$this, 'suppFileFormInitData'));
      HookRegistry::register('authorsubmitsuppfileform::readuservars', array(&$this, 'suppFileFormReadUserVars'));
      HookRegistry::register('authorsubmitsuppfileform::execute', array(&$this, 'authorSubmitSuppFileFormExecute'));
      
      // Add Dataverse deposit options to suppfile form for completed submissions
      HookRegistry::register('Templates::Submission::SuppFile::AdditionalMetadata', array(&$this, 'suppFileAdditionalMetadata'));
      HookRegistry::register('suppfileform::initdata', array(&$this, 'suppFileFormInitData'));
      HookRegistry::register('suppfileform::readuservars', array(&$this, 'suppFileFormReadUserVars'));
      HookRegistry::register('suppfileform::execute', array(&$this, 'suppFileFormExecute'));
      
      // Notify ArticleDAO of article metadata field (external data citation) in suppfile form
      HookRegistry::register('articledao::getAdditionalFieldNames', array(&$this, 'articleMetadataFormFieldNames'));
      
      // Validate suppfile forms: warn if Dataverse deposit selected but no file uploaded
      HookRegistry::register('authorsubmitsuppfileform::Constructor', array(&$this, 'suppFileFormConstructor'));
      HookRegistry::register('suppfileform::Constructor', array(&$this, 'suppFileFormConstructor'));  
      
      // Metadata form: update cataloguing information. Prevent update if study locked.
      HookRegistry::register('metadataform::Constructor', array(&$this, 'metadataFormConstructor'));
      HookRegistry::register('metadataform::execute', array(&$this, 'metadataFormExecute'));
      
      // Handle suppfile insertion: prevent duplicate insertion of a suppfile
      HookRegistry::register('suppfiledao::_insertsuppfile', array(&$this, 'handleSuppFileInsertion'));
      // Handle suppfile deletion: only necessary for completed submissions
      HookRegistry::register('suppfiledao::_deletesuppfilebyid', array(&$this, 'handleSuppFileDeletion'));
      // Add form validator to check whether submission includes data files 
      HookRegistry::register('authorsubmitstep4form::Constructor', array(&$this, 'addAuthorSubmitFormValidator'));
      // Create study for author submissions
      HookRegistry::register('Author::SubmitHandler::saveSubmit', array(&$this, 'handleAuthorSubmission'));
      // Release or delete studies according to editor decision
      HookRegistry::register('SectionEditorAction::unsuitableSubmission', array(&$this, 'handleUnsuitableSubmission'));
      HookRegistry::register('SectionEditorAction::recordDecision', array(&$this, 'handleEditorDecision'));
      // Release studies on article publication
      HookRegistry::register('articledao::_updatearticle', array(&$this, 'handleArticleUpdate'));
      
      // Get content for plugin notifications
      HookRegistry::register('NotificationManager::getNotificationContents', array(&$this, 'getNotificationContents'));
    }
    return $success;
  }

	function getDisplayName() {
		return __('plugins.generic.dataverse.displayName');
	}

	function getDescription() {
		return __('plugins.generic.dataverse.description');
	}

	function getInstallSchemaFile() {
		return $this->getPluginPath() . '/schema.xml';
	}

	function getHandlerPath() {
		return $this->getPluginPath() . '/pages/';
	}
  
	function getTemplatePath() {
		return parent::getTemplatePath() . 'templates/';
	}  
	
	/**
	 * Display verbs for the management interface.
	 */
  function getManagementVerbs() {
    $verbs = array();
    if ($this->getEnabled()) {
      $verbs[] = array('connect', __('plugins.generic.dataverse.settings.connect'));
      $verbs[] = array('select', __('plugins.generic.dataverse.settings.selectDataverse')); 
      $verbs[] = array('settings', __('plugins.generic.dataverse.settings'));
    }
    return parent::getManagementVerbs($verbs);
  }

	/**
     * Execute a management verb on this plugin
     * @param $verb string
     * @param $args array
     * @param $message string Result status message
     * @param $messageParams array Parameters for the message key
     * @return boolean
     */
    function manage($verb, $args, &$message, &$messageParams) {
      if (!parent::manage($verb, $args, $message, $messageParams)) return false;

      $templateMgr =& TemplateManager::getManager();
      $templateMgr->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));
      $journal =& Request::getJournal();
      
      switch ($verb) {
        case 'connect':
          $this->import('classes.form.DataverseAuthForm');
          $form = new DataverseAuthForm($this, $journal->getId());
          if (Request::getUserVar('save')) {
            $form->readInputData();
            if ($form->validate()) {
              $form->execute();
               Request::redirect(null, 'manager', 'plugin', array('generic', $this->getName(), 'select'));
              return false;
            } else {
              $form->display();
            }
          } else {
            $form->initData();
            $form->display();
          }
          return true;
        case 'select':
          $this->import('classes.form.DataverseSelectForm');
          $form = new DataverseSelectForm($this, $journal->getId());
          if (Request::getUserVar('save')) {
            $form->readInputData();
            if ($form->validate()) {
              $form->execute();
               Request::redirect(null, 'manager', 'plugin', array('generic', $this->getName(), 'settings'));
              return false;
            } else {
              $form->display();
            }
          } else {
            $form->initData();
            $form->display();
          }          
          return true;
        case 'settings':
          $this->import('classes.form.SettingsForm');
          $form = new SettingsForm($this, $journal->getId());
          if (Request::getUserVar('save')) {
            $form->readInputData();
            if ($form->validate()) {
              $form->execute();
              Request::redirect(null, 'manager', 'plugin', array('generic'));
              return false;
            } else {
              $form->display();
            }
          } else {
            $form->initData();
            $form->display();
          }
          return true;
      }

    }

	function smartyPluginUrl($params, &$smarty) {
		$path = array($this->getCategory(), $this->getName());
		if (is_array($params['path'])) {
			$params['path'] = array_merge($path, $params['path']);
		} elseif (!empty($params['path'])) {
			$params['path'] = array_merge($path, array($params['path']));
		} else {
			$params['path'] = $path;
		}

		if (!empty($params['id'])) {
			$params['path'] = array_merge($params['path'], array($params['id']));
			unset($params['id']);
		}
		return $smarty->smartyUrl($params, $smarty);
	}
  
  /**
   * Hook callback: register pages for terms of use & data policy
	 */
	function setupPublicHandler($hookName, $params) {
		$page =& $params[0];
		if ($page == 'dataverse') {
			$op =& $params[1];
			if ($op) {
				$publicPages = array(
					'index',
					'dataAvailabilityPolicy',
					'termsOfUse',
				);

				if (in_array($op, $publicPages)) {
					define('HANDLER_CLASS', 'DataverseHandler');
					define('DATAVERSE_PLUGIN_NAME', $this->getName());
					AppLocale::requireComponents(LOCALE_COMPONENT_APPLICATION_COMMON);
					$handlerFile =& $params[2];
					$handlerFile = $this->getHandlerPath() . 'DataverseHandler.inc.php';
				}
			}
		}
	}  
  
	/**
	 * Hook callback: add data citation to submissions, published articles, and
   * reading tools.
	 */
  function handleTemplateDisplay($hookName, $args) {
    $templateMgr =& $args[0];
    $template =& $args[1];

    switch ($template) {
      case 'author/submission.tpl':      
      case 'sectionEditor/submission.tpl':
        $templateMgr->register_outputfilter(array(&$this, 'submissionOutputFilter'));
        break;      
      case 'rt/suppFiles.tpl':
      case 'rt/suppFilesView.tpl':        
      case 'rt/metadata.tpl':
        $dataverseStudyDao =& DAORegistry::getDAO('DataverseStudyDAO');        
        $article =& $templateMgr->get_template_vars('article');
        $study =& $dataverseStudyDao->getStudyBySubmissionId($article->getId());
        if (!isset($study)) return false;
        $dataverseFileDao =& DAORegistry::getDAO('DataverseFileDAO');
        $dvFiles =& $dataverseFileDao->getDataverseFilesBySubmissionId($article->getId());
        $dvFileIndex = array();
        foreach ($dvFiles as $dvFile) {
          $dvFileIndex[$dvFile->getSuppFileId()] = true;
        }
        $templateMgr->assign_by_ref('study', $study);        
        $templateMgr->assign('dvFileIndex', $dvFileIndex);
        $templateMgr->assign('dataCitation', $study->getDataCitation());
        $templateMgr->display($this->getTemplatePath() .'/'. $template);
        return true;
    }
    return false;
  }

  /**
   * Output filter: add data citation to editor & author view of submission summary
   */
  function submissionOutputFilter($output, &$smarty) {
    $submission =& $smarty->get_template_vars('submission');
    if (!isset($submission)) return $output;
      
    $dataverseStudyDao =& DAORegistry::getDAO('DataverseStudyDAO');
    $study =& $dataverseStudyDao->getStudyBySubmissionId($submission->getId());

    $dataCitation = '';
    if (isset($study)) {
      $dataCitation = $study->getDataCitation();
    }
    else {
      // There may be an external data citation
      $dataCitation = $submission->getLocalizedData('externalDataCitation');
    }
    if (!$dataCitation) return $output;

    $index = strpos($output, '<td class="label">'. __('submission.submitter'));
    if ($index !== false) {
      $newOutput = substr($output,0,$index);
      $newOutput .= '<td class="label">'.  __('plugins.generic.dataverse.dataCitation') .'</td>';
      $newOutput .= '<td class="value" colspan="2">'. $dataCitation .'</td></tr><tr>';
      $newOutput .= substr($output, $index);
      $output =& $newOutput;
    }
      $smarty->unregister_outputfilter('submissionSummaryOutputFilter');
    return $output;
	}
  
  /**
   * Add data citation to article landing page.
   * @param String $hookName
   * @param array $args
   */
  function addDataCitationArticle($hookName, $args) {
    $smarty =& $args[1];
    $output =& $args[2];
    
    $templateMgr =& TemplateManager::getManager();    
    $article =& $templateMgr->get_template_vars('article');
    
    $dataverseStudyDao =& DAORegistry::getDAO('DataverseStudyDAO');
    $study =& $dataverseStudyDao->getStudyBySubmissionId($article->getId());
    if (isset($study)) {
      $templateMgr->assign('dataCitation', $study->getDataCitation());
    }
    else {
      // Article may have an external data citation
      $templateMgr->assign('dataCitation', $article->getLocalizedData('externalDataCitation'));
    }
    $output .= $templateMgr->fetch($this->getTemplatePath() . 'dataCitationArticle.tpl');
    return false;
  }  
  
  /**
   * Hook into TinyMCE for the text areas on the settings form.
   * @param String $hookName
   * @param array $args
   * @return boolean
   */
  function getTinyMCEEnabledFields($hookName, $args) {
    $fields =& $args[1];
    $fields = array(
        'dataAvailability',
        'termsOfUse',
        );
    return false;
  }

  /**
   * Add link to data availability policy
   * @param String $hookName
   * @param array $args
   */
  function addPolicyLinks($hookName, $args) {
    $journal =& Request::getJournal();
    $dataPAvailability = $this->getSetting($journal->getId(), 'dataAvailability');
    if (!empty($dataPAvailability)) {
      $smarty =& $args[1];
      $output =& $args[2];
      $templateMgr =& TemplateManager::getManager();    
      $output .= '<li>&#187; <a href="'. $templateMgr->smartyUrl(array('page' => 'dataverse', 'op'=>'dataAvailabilityPolicy'), $smarty) .'">';
      $output .= __('plugins.generic.dataverse.settings.dataAvailabilityPolicy');
      $output .= '</a></li>';
    }
    return false;
  }
  
  /**
   * Hook callback: metadata form constructors: add validators
   */
  function metadataFormConstructor($hookName, $args) {
    $form =& $args[0];
    $form->addCheck(new FormValidatorCustom($this, 'metadata', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.generic.dataverse.metadataForm.studyLocked', array(&$this, 'formValidateStudyState'), array(&$form)));
    return false;
  }  
  
  /**
   * Hook callback: if submission has a Dataverse study, update cataloguing information
   */
  function metadataFormExecute($hookName, $args) {
    $form =& $args[0];
    $dataverseStudyDao =& DAORegistry::getDAO('DataverseStudyDAO');
    $study =& $dataverseStudyDao->getStudyBySubmissionId($form->article->getId());
    if (!isset($study)) return false;
    
    // Update & notify
    $study =& $this->updateStudy($form->article, $study);
    
    /** @fixme clean up study create, update & move notifications. */
    $user =& Request::getUser();
    import('classes.notification.NotificationManager');
    $notificationManager = new NotificationManager();
    $notificationManager->createTrivialNotification($user->getId(), isset($study) ? NOTIFICATION_TYPE_DATAVERSE_STUDY_UPDATED : NOTIFICATION_TYPE_ERROR);

    return false;
  }  
  
  /**
   * Form validator: do not submit metadata, suppfile forms if submission has
   * files in Dataverse & study is locked for processing
   * @return boolean 
   */
  function formValidateStudyState($field, $form) {
    $articleId = isset($form->article) ? $form->article->getId() : $form->articleId;    
    $dvStudyDao = DAORegistry::getDAO('DataverseStudyDAO');
    $study = $dvStudyDao->getStudyBySubmissionId($articleId);    

    // No study for this submission
    if (!isset($study)) return true;

    // Prevent submission if study is locked
    return $this->studyIsLocked($study) ? false : true;
  }
  
  /**
   * Hook callback: notify ArticleDAO of external data citation field added to
   * suppfile forms
   */
  function articleMetadataFormFieldNames($hookName, $args) {
    $fields =& $args[1];
    $fields[] = 'externalDataCitation';
    return false;    
  }
  
  /**
   * Hook callback: add data publication options to suppfile forms (for initial
   * author submission & completed submissions)
   */
  function suppFileAdditionalMetadata($hookName, $args) {
    $smarty =& $args[1];
    $output =& $args[2];
    $articleId = $smarty->get_template_vars('articleId');        

    // Include Dataverse data citation, if a study exists for this submission.
    $dvStudyDao = DAORegistry::getDAO('DataverseStudyDAO');
    $study = $dvStudyDao->getStudyBySubmissionId($articleId);

    if (isset($study)) {
      $smarty->assign('dataCitation', $study->getDataCitation());
      $smarty->assign('studyLocked', $this->studyIsLocked($study));
    }
    $output .= $smarty->fetch($this->getTemplatePath() . 'suppFileAdditionalMetadata.tpl');
    return false;
  }
  
  /**
   * Hook callback: suppfile form constructors: add validators
   */
  function suppFileFormConstructor($hookName, $args) {
    $form =& $args[0];
    $form->addCheck(new FormValidatorCustom($this, 'publishData', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.generic.dataverse.suppFile.publishData.error', array(&$this, 'suppFileFormValidateDeposit'), array(&$form)));
    $form->addCheck(new FormValidatorCustom($this, 'publishData', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.generic.dataverse.suppFile.studyLocked', array(&$this, 'formValidateStudyState'), array(&$form)));
    $form->addCheck(new FormValidatorCustom($this, 'externalDataCitation', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.generic.dataverse.suppFile.externalDataCitation.error', array(&$this, 'suppFileFormValidateCitations'), array(&$form))); 
    return false;
  }
  
  /**
   * Form validator: return false if Dataverse deposit selected but no suppfile
   * has been uploaded
   * @return boolean
   */
  function suppFileFormValidateDeposit($publishData, $form) {
    if ($publishData == 'dataverse') {
      // If suppfile exists & has a non-zero file ID, a file has been uploaded previously
      if ($form->suppFile && $form->suppFile->getFileId()) return true;
      
      // If no file has been uploaded, return false
      import('classes.file.ArticleFileManager');
      $articleId = isset($form->article) ? $form->article->getId() : $form->articleId;
      $articleFileManager = new ArticleFileManager($articleId);
      if (!$articleFileManager->uploadedFileExists('uploadSuppFile')) return false;
    }
    return true;
  }
  
  /**
   * Form validator: return false if Dataverse deposit selected and and an 
   * external citation has been provided (must choose one or the other)
   * @return boolean
   */
  function suppFileFormValidateCitations($externalCitation, $form) {
    if ($externalCitation && $form->getData('publishData') == 'dataverse') {
      return false;
    }
    return true;
  }
  
  /**
   * Hook callback: initialize metadata fields added to suppfile form
   */
  function suppFileFormInitData($hookName, $args) {
    $form =& $args[0];

    $journal =& Request::getJournal();    
    $articleDao =& DAORegistry::getDAO('ArticleDAO');
    $article =& $form->article;    
    if (!isset($article)) {
      $article = $articleDao->getArticle($form->articleId, $journal->getId());
    }
    // Add or edit external data citation field, if missed in previous step
    $form->setData('externalDataCitation', $article->getLocalizedData('externalDataCitation'));
    
    // Set data publishing option for this suppfile:
    // 'none'      -- supplementary file, not published (default)
    // 'dataverse' -- deposit file in Dataverse and publish data citation with article
    $publishData = 'none';

    if (isset($form->suppFile) && $form->suppFile->getId()) {
      // Check if uploaded file has been deposited in Dataverse
      $dvFileDao =& DAORegistry::getDAO('DataverseFileDAO');
      $dvFile =& $dvFileDao->getDataverseFileBySuppFileId($form->suppFile->getId(), $article->getId());
      if (!is_null($dvFile)) { $publishData = 'dataverse'; }
    }
    $form->setData('publishData', $publishData);
    return false;
  }

  /**
   * Hook callback: read values submitted in metadata fields added to suppfile form
   */
  function suppFileFormReadUserVars($hookName, $args) {
    $form =& $args[0];
    $vars =& $args[1];
    $vars[] = 'externalDataCitation';
    $vars[] = 'publishData';
    return false;
  }

  /**
   * Hook callback: suppfile form execute for in-progress submissions
   */
  function authorSubmitSuppFileFormExecute($hookName, $args) {
    $form =& $args[0];

    // External data citation: field is article metadata, but provided in
    // suppfile form as well, at point of data file deposit, to help support
    // data publishing decisions.
    $journal =& Request::getJournal();    
    $articleDao =& DAORegistry::getDAO('ArticleDAO');
    $article = $articleDao->getArticle($form->articleId, $journal->getId());
    $article->setData('externalDataCitation', $form->getData('externalDataCitation'), $form->getFormLocale());
    $articleDao->updateArticle($article);
    
    if (!isset($form->suppFile) || !$form->suppFile->getId()) {
      // Suppfile metadata may exist but no file has been uploaded. 
      return false;
    }
    
    $dvFileDao =& DAORegistry::getDAO('DataverseFileDAO');
    $dvFile = $dvFileDao->getDataverseFileBySuppFileId($form->suppFile->getId(), $form->articleId);      

    switch ($form->getData('publishData')) {
      case 'none':
        /**
         * Treat uploaded file as supplementary. If previously marked for 
         * Dataverse deposit, unmark it.
         */
        if (isset($dvFile)) {
          /** @todo warn user file will be removed from Dataverse */
          $dvFileDao->deleteDataverseFile($dvFile);
        }
        break;

      case 'dataverse':
        /**
         *  Mark file for deposit, if not marked already. File will be deposited
         * in Dataverse when submission is completed or accepted for publication.
         * @see handleAuthorSubmission, handleEditorDecision
         */
        if (!isset($dvFile)) {
          $this->import('classes.DataverseFile');
          $dvFile = new DataverseFile();
          $dvFile->setSuppFileId($form->suppFile->getId());
          $dvFile->setSubmissionId($form->articleId);
          $dvFileDao->insertDataverseFile($dvFile);            
        }
        break;
    }
    return false;
  }
  
  /**
   * Hook callback: suppfile form execute for completed submissions
   */
  function suppFileFormExecute($hookName, $args) {   
    $form =& $args[0];
    $articleDao =& DAORegistry::getDAO('ArticleDAO');
    $article =& $form->article;
    
    // External data citation: field is article metadata, but provided in 
    // suppfile form as well, at point of data file deposit, to help support
    // data publishing decisions
    $article->setData('externalDataCitation', $form->getData('externalDataCitation'), $form->getFormLocale());
    $articleDao->updateArticle($article);
    
    // Form executed for completed submissions. Draft studies are created on 
    // submission completion. A study may or may not exist for this submission.
    $dvStudyDao =& DAORegistry::getDAO('DataverseStudyDAO');
    $dvFileDao =& DAORegistry::getDAO('DataverseFileDAO');    
    
    switch ($form->getData('publishData')) {
      case 'none':
        // Supplementary file: do not deposit. 
        if (!$form->suppFile->getId()) return false; // New suppfile: not in Dataverse

        $dvFile =& $dvFileDao->getDataverseFileBySuppFileId($form->suppFile->getId(), $article->getId());
        if (!isset($dvFile)) return false; // Edited suppfile, but not in Dataverse
          
        // Remove the file from Dataverse
        $this->deleteFile($dvFile);

        // Deleting a file may affect study cataloguing information
        $study =& $dvStudyDao->getStudyBySubmissionId($article->getId());
        $this->updateStudy($article, $study);
        break;

      case 'dataverse':
        // Deposit file. If needed, insert/update suppfile on behalf of form
        $suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
        if (!$form->suppFile->getId()) {
          // Suppfile is new, but inserted in db after hook is called. Handle
          // insertion here & prevent duplicates in handleSuppFileInsertion() callback
          $form->setSuppFileData($form->suppFile);
          $suppFileDao->insertSuppFile($form->suppFile);
          $form->suppFileId = $form->suppFile->getId();
          $form->suppFile =& $suppFileDao->getSuppFile($form->suppFileId, $article->getId());
        }
        else {
          // Suppfile exists, but uploaded file may be new, replaced, or non-existent. 
          // Hook called before suppfile object updated with details of uploaded file,
          // so refresh suppfile object here. 
          import('classes.file.ArticleFileManager');
          $fileName = 'uploadSuppFile';                    
          $articleFileManager = new ArticleFileManager($article->getId());
          if ($articleFileManager->uploadedFileExists($fileName)) {
            $fileId = $form->suppFile->getFileId();
            if ($fileId != 0) {
              $articleFileManager->uploadSuppFile($fileName, $fileId);
            }
            else {
              $fileId = $articleFileManager->uploadSuppFile($fileName);  
              $form->suppFile->setFileId($fileId);          
            }
          }
          // Store form metadata. It may be used to update study cataloguing information.
          $form->suppFile =& $suppFileDao->getSuppFile($form->suppFileId, $article->getId());
          $form->setSuppFileData($form->suppFile);
          $suppFileDao->updateSuppFile($form->suppFile);
        }
        // If, at this point, there is no file id, there is nothing to deposit
        /** @fixme add a form validator to prevent deposit-in-Dataverse-but-no-file */
        if (!$form->suppFile->getFileId()) return false;     
        
        /** @fixme clean up study create, update & move notifications. */
        $user =& Request::getUser();
        import('classes.notification.NotificationManager');
        $notificationManager = new NotificationManager();
        
        // Study may not exist, if this is the first file deposited
        $study =& $dvStudyDao->getStudyBySubmissionId($article->getId());  
        if (!isset($study)) {
          $study =& $this->createStudy($article);
          $notificationManager->createTrivialNotification($user->getId(), isset($study) ? NOTIFICATION_TYPE_DATAVERSE_STUDY_CREATED : NOTIFICATION_TYPE_ERROR);          
        }

        if (!isset($study)) return false;
        
        // File already in Dataverse?
        $dvFile =& $dvFileDao->getDataverseFileBySuppFileId($form->suppFile->getId(), $article->getId());     

        if (isset($dvFile)) {
          // File is already in Dataverse. Update study with suppfile metadata. 
          $studyUpdated = $this->updateStudy($article, $study);
          $notificationManager->createTrivialNotification($user->getId(), $studyUpdated ? NOTIFICATION_TYPE_DATAVERSE_STUDY_UPDATED : NOTIFICATION_TYPE_ERROR);
        }
        else {
          // Add file to study
          $fileAdded = $this->addFileToStudy($study, $form->suppFile);
          $notificationManager->createTrivialNotification($user->getId(), $fileAdded ? NOTIFICATION_TYPE_DATAVERSE_FILE_ADDED : NOTIFICATION_TYPE_ERROR);
        }
        break;
    }
    return false;
  }

  /**
   * Prevent re-insertion of suppfile inserted by SuppFileForm::execute callback
   * @param string $hookName
   * @param array $args
   * @return boolean
   */
  function handleSuppFileInsertion($hookName, $args) {
    $params =& $args[1];
    $fileId = $params[1];    
    $articleId = $params[2];
    
    $suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
    return $suppFileDao->suppFileExistsByFileId($articleId, $fileId);
  }
  
  /**
   * Remove data file from Dataverse study, if present
   * @param type $hookName
   * @param type $args
   */
  function handleSuppFileDeletion($hookName, $args) {
    $params =& $args[1];
    $suppFileId = is_array($params) ? $params[0] : $params;
    $submissionId = is_array($params) ? $params[1] : '';
    
    // Suppfile deposited in / marked for deposit in Dataverse?
    $dvFileDao =& DAORegistry::getDAO('DataverseFileDAO');
    $dvFile =& $dvFileDao->getDataverseFileBySuppFileId($suppFileId, $submissionId ? $submissionId : '');
    if (!isset($dvFile)) return false; // nope. 
    
    $this->deleteFile($dvFile);

    // Deleting the file may require an update to study metadata
    $dvStudyDao =& DAORegistry::getDAO('DataverseStudyDAO');
    $study =& $dvStudyDao->getStudyBySubmissionId($dvFile->getSubmissionId());
    if (isset($study)) {
      $articleDao =& DAORegistry::getDAO('ArticleDAO');
      $journal =& Request::getJournal();    
      $article =& $articleDao->getArticle($study->getSubmissionId(), $journal->getId(), true);
      $this->updateStudy($article, $study);
    }
    return false;
  }
  
  /**
   * Add a custom form validator to verify data files included with submission
   * @param string $hookName
   * @param array $args
   */
  function addAuthorSubmitFormValidator($hookName, $args) {
    $form =& $args[0];
    $form->addCheck(new FormValidatorCustom($form, '', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.generic.dataverse.settings.requireDataError', array(&$this, 'validateRequiredData'), array($form)));
  }
  
  /**
   * Verify data files have been provided, if required 
   * @return boolean
   */
  function validateRequiredData($fieldValue, $form) {
    $journal =& Request::getJournal();
    if (!$this->getSetting($journal->getId(), 'requireData')) return true;
    // Data files must be provided.
    $dvFileDao =& DAORegistry::getDAO('DataverseFileDAO');
    $dvFiles =& $dvFileDao->getDataverseFilesBySubmissionId($form->articleId);
    return count($dvFiles);
  }
  
  /**
   * Hook callback: if author has included data files with submission, create draft study
   */
  function handleAuthorSubmission($hookName, $args) {
    $step =& $args[0];
    $article =& $args[1];
    if ($step == 5) {
      // Author has completed submission. Check if submission has suppfiles to deposit.
      $dvFileDao =& DAORegistry::getDAO('DataverseFileDAO');
      $dvFiles =& $dvFileDao->getDataverseFilesBySubmissionId($article->getId());
      if ($dvFiles) {
        // Create a study for the new submission. Notify user on success or failure.
        $study =& $this->createStudy($article, $dvFiles);
        $user =& Request::getUser();
        
        import('classes.notification.NotificationManager');
        $notificationManager = new NotificationManager();
        /** @fixme clean up study create, update & move notifications. */
        $notificationManager->createTrivialNotification($user->getId(), isset($study) ? NOTIFICATION_TYPE_DATAVERSE_STUDY_CREATED : NOTIFICATION_TYPE_ERROR);
      }
    }
    return false;
  }
  
  /**
   * Callback is invoked when an editor records a decision on a submission. 
   * @param String $hookName
   * @param array $args
   */
  function handleEditorDecision($hookName, $args) {
    $submission =& $args[0];
    $decision =& $args[1];
     
    // Plugin may be configured to release on publication: defer decision 
    if ($this->getSetting($submission->getJournalId(), 'studyRelease') == DATAVERSE_PLUGIN_RELEASE_ARTICLE_PUBLISHED) {
      return false;
    }

    // Find study associated with submission
    $dataverseStudyDao =& DAORegistry::getDAO('DataverseStudyDAO');
    $study =& $dataverseStudyDao->getStudyBySubmissionId($submission->getId());
    
    if (isset($study)) {
      // Editor decision on a submission with a draft study in Dataverse
      if ($decision['decision'] == SUBMISSION_EDITOR_DECISION_ACCEPT) {
        $this->releaseStudy($study);
      }
      if ($decision['decision'] == SUBMISSION_EDITOR_DECISION_DECLINE) {
        // Draft studies will be deleted; released studies will be deaccesioned
        $this->deleteStudy($study);
      }
    }
    return false;
  }
  
  /**
   * Release study on article publication
   * @param string $hookName
   * @param array $args
   */
  function handleArticleUpdate($hookName, $args) {
    $journal =& Request::getJournal();
    $params =& $args[1];
    $articleId = $params[count($params)-1];
    $status = $params[6];
    
    if ($this->getSetting($journal->getId(), 'studyRelease') == DATAVERSE_PLUGIN_RELEASE_ARTICLE_PUBLISHED) {
      // See if study exists for submission
      $dvStudyDao =& DAORegistry::getDAO('DataverseStudyDAO');
      $study =& $dvStudyDao->getStudyBySubmissionId($articleId);
      if (isset($study) && $status == STATUS_PUBLISHED) { 
        $this->releaseStudy($study); 
      }
    }
    return false;
  }
  
  /**
   * Callback invoked when editor rejects unsuitable submissions
   * @param string $hookName
   * @param array $args
   */
  function handleUnsuitableSubmission($hookName, $args) {
    $submission =& $args[0];    
    $dataverseStudyDao =& DAORegistry::getDAO('DataverseStudyDAO');
    $study =& $dataverseStudyDao->getStudyBySubmissionId($submission->getId());
    if (isset($study)) {
      $this->deleteStudy($study);
    }
    return false;    
  }
  
  /**
   * Request service document at specified URL
   * 
   * @param string $sdUrl service document URL
   * @param type $user username 
   * @param type $password password
   * @param type $onBehalfOf issue request on behalf of user $onBehalfOf
   */
  function getServiceDocument($sdUrl, $user, $password, $onBehalfOf = NULL) {
    // allow insecure SSL connections
    $client = $this->_initSwordClient();
    return $client->servicedocument($sdUrl, $user, $password, $onBehalfOf);
  } 
  
  /**
   * Request terms of use of Dataverse configured for plugin
   * @return string
   */
  function getTermsOfUse() {
    $journal =& Request::getJournal();
    $sd = $this->getServiceDocument(
            $this->getSetting($journal->getId(), 'sdUri'), 
            $this->getSetting($journal->getId(), 'username'), 
            $this->getSetting($journal->getId(), 'password')
          );
    
    $dvTermsOfUse = '';
    if ($sd->sac_status == DATAVERSE_PLUGIN_HTTP_STATUS_OK) {
      $dvUri = $this->getSetting($journal->getId(), 'dvUri');
        
      // Find workspaces defined in service document
      foreach ($sd->sac_workspaces as $workspace) {
        foreach ($workspace->sac_collections as $collection) {
          if ($collection->sac_href[0] == $dvUri) {
            // TOU constructed from policies at dataverse, collection, study
            //  levels and separated by hyphens. Kludge in some line breaks.
            $dvTermsOfUse = str_replace(
                    DATAVERSE_PLUGIN_TOU_POLICY_SEPARATOR, 
                    '<br/>'. DATAVERSE_PLUGIN_TOU_POLICY_SEPARATOR .'<br/>', 
                    $collection->sac_collpolicy
                    );
            // Store DV terms of use as a fallback 
            $this->updateSetting($journal->getId(), 'dvTermsOfUse', $dvTermsOfUse, 'string');
            break;
          }
        }
      }
    }
    return $dvTermsOfUse;
  }

  /**
   * Create a Dataverse study
   * @param Article $article
   * @return DataverseStudy
   */
  function &createStudy(&$article, $dvFiles = array()) {
    $journal =& Request::getJournal();
    
    // Go no further if plugin is not configured.
    if (!$this->getSetting($journal->getId(), 'dvUri')) return false;

    $packager = new DataversePackager();
    $suppFileDao =& DAORegistry::getDAO('SuppFileDAO');     
    
    // Add article metadata
    $packager->addMetadata('title', $article->getLocalizedTitle());
    $packager->addMetadata('description', $article->getLocalizedAbstract());
    foreach ($article->getAuthors() as $author) {
      $packager->addMetadata('creator', $author->getFullName(true));
    }
    // subject: academic disciplines
    $split = '/\s*'. DATAVERSE_PLUGIN_SUBJECT_SEPARATOR .'\s*/';
    foreach(preg_split($split, $article->getLocalizedDiscipline(), NULL, PREG_SPLIT_NO_EMPTY) as $subject) {
      $packager->addMetadata('subject', $subject);
    }
    // subject: subject classifications
    foreach(preg_split($split, $article->getLocalizedSubjectClass(), NULL, PREG_SPLIT_NO_EMPTY) as $subject) {
      $packager->addMetadata('subject', $subject);
    }
    // subject:  keywords    
    foreach(preg_split($split, $article->getLocalizedSubject(), NULL, PREG_SPLIT_NO_EMPTY) as $subject) {
      $packager->addMetadata('subject', $subject);
    }
    // geographic coverage
    foreach(preg_split($split, $article->getLocalizedCoverageGeo(), NULL, PREG_SPLIT_NO_EMPTY) as $coverage) {
      $packager->addMetadata('coverage', $coverage);
    }
    // publisher
    $packager->addMetadata('publisher', $journal->getSetting('publisherInstitution'));
    // rights
    $packager->addMetadata('rights', $journal->getLocalizedSetting('copyrightNotice'));
    // isReferencedBy
    $packager->addMetadata('isReferencedBy', $this->getCitation($article));
    // Include (some) suppfile metadata in study
    foreach ($dvFiles as $dvFile) {
      $suppFile =& $suppFileDao->getSuppFile($dvFile->getSuppFileId(), $article->getId());
      if (isset($suppFile)) {
        // subject
        foreach(preg_split($split, $suppFile->getSuppFileSubject(), NULL, PREG_SPLIT_NO_EMPTY) as $subject) {
          $packager->addMetadata('subject', $subject);
        }
        // Type of file
        if ($suppFile->getType()) $packager->addMetadata('type', $suppFile->getType());
        // Type of file, user-defined:
        if ($suppFile->getSuppFileTypeOther()) $packager->addMetadata('type', $suppFile->getSuppFileTypeOther());
      }
    }
    // Write Atom entry file
    $packager->createAtomEntry();
    
    // Create the study in Dataverse
    $client = $this->_initSwordClient();
    $depositReceipt = $client->depositAtomEntry(
            $this->getSetting($article->getJournalId(), 'dvUri'), 
            $this->getSetting($article->getJournalId(), 'username'), 
            $this->getSetting($article->getJournalId(), 'password'),
            '',  // on behalf of: no one
            $packager->getAtomEntryFilePath());
    
    // Exit & notify if study failed to be created
    if ($depositReceipt->sac_status != DATAVERSE_PLUGIN_HTTP_STATUS_CREATED) return false;
    
    // Insert new Dataverse study for this submission
    $dataverseStudyDao =& DAORegistry::getDAO('DataverseStudyDAO');      
        
    $this->import('classes.DataverseStudy');
    $study = new DataverseStudy();
    $study->setSubmissionId($article->getId());
    $study->setEditUri($depositReceipt->sac_edit_iri);
    $study->setEditMediaUri($depositReceipt->sac_edit_media_iri);
    $study->setStatementUri($depositReceipt->sac_state_iri_atom);
    $study->setDataCitation($depositReceipt->sac_dcterms['bibliographicCitation'][0]);
        
    // Persistent URI may be present, as an altenate 
    foreach ($depositReceipt->sac_links as $link) {
      if ($link->sac_linkrel == 'alternate') {
        $study->setPersistentUri($link->sac_linkhref);
        break;
      }
    }
    $dataverseStudyDao->insertStudy($study);
    
    // Fine. Now add the files, if any are present.
    for ($i=0; $i<sizeof($dvFiles); $i++) {
      $dvFile =& $dvFiles[$i];
      $suppFile =& $suppFileDao->getSuppFile($dvFile->getSuppFileId(), $article->getId());
      /** @fixme add path & original filename, not the object */
      $dvFileIndex[str_replace(' ', '_', $suppFile->getOriginalFileName())] =& $dvFile;      
      $packager->addFile($suppFile);
    }
    
    // Create the deposit package & add package to Dataverse
    $packager->createPackage();
    $depositReceipt = $client->deposit(
            $study->getEditMediaUri(),
            $this->getSetting($journal->getId(), 'username'),
            $this->getSetting($journal->getId(), 'password'),
            '', // on behalf of: no one
            $packager->getPackageFilePath(),
            $packager->getPackaging(),
            $packager->getContentType(),
            false); // in progress? false 
    
    if ($depositReceipt->sac_status != DATAVERSE_PLUGIN_HTTP_STATUS_CREATED) return false;
    
    // Get the study statement & update the local file list
    $studyStatement = $client->retrieveAtomStatement(
            $study->getStatementUri(),
            $this->getSetting($journal->getId(), 'username'),
            $this->getSetting($journal->getId(), 'password'),
            '' // on behalf of
          );
    
    if (!isset($studyStatement)) return false;

    // Update each Dataverse file with study id & content source URI
    $dvFileDao =& DAORegistry::getDAO('DataverseFileDAO');
    foreach ($studyStatement->sac_entries as $entry) {
      $dvUriFileName = substr($entry->sac_content_source, strrpos($entry->sac_content_source, '/')+1);
      if (array_key_exists($dvUriFileName, $dvFileIndex)) {
        $dvFile =& $dvFileIndex[$dvUriFileName];
        $dvFile->setContentSourceUri($entry->sac_content_source);
        $dvFile->setStudyId($study->getId());
        $dvFileDao->updateDataverseFile($dvFile);
      }
    }

    // Done.
    return $study;
  }
  
  /**
   * Update cataloguing information for an existing study
   * @param Article $article
   * @param DataverseStudy $study
   */
  function &updateStudy(&$article, &$study) {
    $journal =& Request::getJournal();    
    $packager = new DataversePackager();
    // Add article metadata
    $packager->addMetadata('title', $article->getLocalizedTitle());
    $packager->addMetadata('description', $article->getLocalizedAbstract());
    foreach ($article->getAuthors() as $author) {
      $packager->addMetadata('creator', $author->getFullName(true));
    }
    // subject: academic disciplines
    $split = '/\s*'. DATAVERSE_PLUGIN_SUBJECT_SEPARATOR .'\s*/';
    foreach(preg_split($split, $article->getLocalizedDiscipline(), NULL, PREG_SPLIT_NO_EMPTY) as $subject) {
      $packager->addMetadata('subject', $subject);
    }
    // subject: subject classifications
    foreach(preg_split($split, $article->getLocalizedSubjectClass(), NULL, PREG_SPLIT_NO_EMPTY) as $subject) {
      $packager->addMetadata('subject', $subject);
    }
    // subject:  keywords    
    foreach(preg_split($split, $article->getLocalizedSubject(), NULL, PREG_SPLIT_NO_EMPTY) as $subject) {
      $packager->addMetadata('subject', $subject);
    }    
    // geographic coverage
    foreach(preg_split($split, $article->getLocalizedCoverageGeo(), NULL, PREG_SPLIT_NO_EMPTY) as $coverage) {
      $packager->addMetadata('coverage', $coverage);
    }
    // rights
    $packager->addMetadata('rights', $journal->getLocalizedSetting('copyrightNotice'));
    // publisher
    $packager->addMetadata('publisher', $journal->getSetting('publisherInstitution'));
    // metadata for published articles: public IDs, publication dates
    $pubIdAttributes = array();    
    if ($article->getStatus()==STATUS_PUBLISHED) {
      // publication date
      $publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
      $publishedArticle =& $publishedArticleDao->getPublishedArticleByArticleId($article->getId(), $article->getJournalId(), TRUE);
      $packager->addMetadata('date', strftime('%Y-%m-%d', strtotime($publishedArticle->getDatePublished())));
      // isReferencedBy: If article is published, add a persistent URL to citation using specified pubid plugin
      $pubIdPlugin =& PluginRegistry::getPlugin('pubIds', $this->getSetting($article->getJournalId(), 'pubIdPlugin'));
      if ($pubIdPlugin && $pubIdPlugin->getEnabled()) {
        $pubIdAttributes['agency'] = $pubIdPlugin->getDisplayName();
        $pubIdAttributes['IDNo'] = $article->getPubId($pubIdPlugin->getPubIdType());
        $pubIdAttributes['holdingsURI'] = $pubIdPlugin->getResolvingUrl($article->getJournalId(), $pubIdAttributes['IDNo']);
      }
      else {
        // If no pub id plugin selected, use OJS URL
        $pubIdAttributes['holdingsURI'] = Request::url($journal->getPath(), 'article', 'view', array($article->getId()));
      }
    }
    // isReferencedBy
    $packager->addMetadata('isReferencedBy', $this->getCitation($article), $pubIdAttributes);
    // Include (some) suppfile metadata in study
    $suppFileDao =& DAORegistry::getDAO('SuppFileDAO');    
    $dataverseFileDao =& DAORegistry::getDAO('DataverseFileDAO');
    $dvFiles =& $dataverseFileDao->getDataverseFilesByStudyId($study->getId());
    foreach ($dvFiles as $dvFile) {
      $suppFile =& $suppFileDao->getSuppFile($dvFile->getSuppFileId(), $article->getId());
      if (isset($suppFile)) {
        // subject
        foreach(preg_split($split, $suppFile->getSuppFileSubject(), NULL, PREG_SPLIT_NO_EMPTY) as $subject) {
          $packager->addMetadata('subject', $subject);
        }
        // Type of file
        if ($suppFile->getType()) $packager->addMetadata('type', $suppFile->getType());
        // Type of file, user-defined:
        if ($suppFile->getSuppFileTypeOther()) $packager->addMetadata('type', $suppFile->getSuppFileTypeOther());
      }
    }    
    // Write atom entry to file
    $packager->createAtomEntry();
    
    // Update the study in Dataverse
    $client = $this->_initSwordClient();
    $depositReceipt = $client->replaceMetadata(
            $study->getEditUri(),
            $this->getSetting($article->getJournalId(), 'username'), 
            $this->getSetting($article->getJournalId(), 'password'),            
            '', // on behalf of
            $packager->getAtomEntryFilePath());
    
    if ($depositReceipt->sac_status != DATAVERSE_PLUGIN_HTTP_STATUS_OK) return false;

    // Updating the metadata may have updated the data citation
    $study->setDataCitation($depositReceipt->sac_dcterms['bibliographicCitation'][0]);
    $dataverseStudyDao =& DAORegistry::getDAO('DataverseStudyDAO');
    $dataverseStudyDao->updateStudy($study);

    return $study;
  }

  /**
   * Add a file to an existing study
   * @param DataverseStudy $study
   * @param DataverseFile $dvFile
   */
  function &addFileToStudy(&$study, &$suppFile) {
    $packager = new DataversePackager();
    $packager->addFile($suppFile);
    $packager->createPackage();
    
    // Deposit the package
    $journal =& Request::getJournal();
    $client = $this->_initSwordClient();    
    $depositReceipt = $client->deposit(
            $study->getEditMediaUri(),
            $this->getSetting($journal->getId(), 'username'),
            $this->getSetting($journal->getId(), 'password'),
            '', // on behalf of: no one
            $packager->getPackageFilePath(),
            $packager->getPackaging(),
            $packager->getContentType(),
            false); // in progress? false 
    
    if ($depositReceipt->sac_status != DATAVERSE_PLUGIN_HTTP_STATUS_CREATED) return false;
    
    // Get the study statement & update the Dataverse file with content source URI
    $studyStatement = $client->retrieveAtomStatement(
            $study->getStatementUri(),
            $this->getSetting($journal->getId(), 'username'),
            $this->getSetting($journal->getId(), 'password'),
            '' // on behalf of
          );

    // Need the study statement to update Dataverse files
    if (!isset($studyStatement)) return false;

    // Create a new Dataverse file for inserted suppfile
    $this->import('classes.DataverseFile');
    $dvFile = new DataverseFile();
    $dvFile->setSuppFileId($suppFile->getId());
    $dvFile->setStudyId($study->getId());
    $dvFile->setSubmissionId($study->getSubmissionId());

    foreach ($studyStatement->sac_entries as $entry) {
      $dvUriFileName = substr($entry->sac_content_source, strrpos($entry->sac_content_source, '/')+1);
      if ($dvUriFileName == str_replace(' ', '_', $suppFile->getOriginalFileName())) {
        $dvFile->setContentSourceUri($entry->sac_content_source);
        break;
      }
    }
    /** @fixme what if we can't relate the file to a statement entry? */     
    if (!$dvFile->getContentSourceUri()) return false;
    
    $dvFileDao =& DAORegistry::getDAO('DataverseFileDAO');
    $dvFileDao->insertDataverseFile($dvFile);
    
    // Finally, file may have metadata that needs to be in study cataloguing information
    $articleDao =& DAORegistry::getDAO('ArticleDAO');
    $article =& $articleDao->getArticle($study->getSubmissionId(), $journal->getId(), true);
    $this->updateStudy($article, $study);

    return $dvFile;
  }
  
  /**
   * Notify journal manangers if Dataverse has not been released
   * @return boolean 
   */
  function dataverseIsReleased() {
    $journal =& Request::getJournal();
    $client = $this->_initSwordClient();    

    $depositReciept = $client->retrieveDepositReceipt(
        $this->getSetting($journal->getId(), 'dvUri'), 
        $this->getSetting($journal->getId(), 'username'),
        $this->getSetting($journal->getId(), 'password'), 
        ''); // on behalf of

    /** @fixme warn when we expect but don't receive a 200 response */
    if ($depositReciept->sac_status != DATAVERSE_PLUGIN_HTTP_STATUS_OK) return false;
          
    $depositReceiptXml = @new SimpleXMLElement($depositReciept->sac_xml);
    $releasedNodes = $depositReceiptXml->children('http://purl.org/net/sword/terms/state')->dataverseHasBeenReleased;

    if (!empty($releasedNodes)) {
      $released = $releasedNodes[0];
    }
    return ($released == 'true');
  }
  
  /**
   * Release draft study
   * @param DataverseStudy $study
   */
  function releaseStudy(&$study) {
    $journal =& Request::getJournal();              
    $user =& Request::getUser();
    
    $client = $this->_initSwordClient();    
    $response = $client->completeIncompleteDeposit(
            $study->getEditUri(),
            $this->getSetting($journal->getId(), 'username'),
            $this->getSetting($journal->getId(), 'password'),   
            ''); // on behalf of
    
    $studyReleased = ($response->sac_status == DATAVERSE_PLUGIN_HTTP_STATUS_OK); 

    // Notify on success or failure. Provide citation & link to study.
    import('classes.notification.NotificationManager');
    $notificationManager = new NotificationManager();

    if ($studyReleased) {
      $params = array('dataCitation' => $study->getDataCitation());
      $notificationManager->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_DATAVERSE_STUDY_RELEASED, $params);
    }
    else {
      $notificationManager->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_ERROR);
    }
    
    // Whether the study was released or not, notify JMs by email if Dataverse
    // has not yet been released. Released studies are not accessible until
    // the Dataverse has been released.
    if (!$this->dataverseIsReleased()) {
      $request =& Application::getRequest();                    
      $roleDao =& DAORegistry::getDAO('RoleDAO');
      $journalManagers =& $roleDao->getUsersByRoleId(ROLE_ID_JOURNAL_MANAGER, $journal->getId());
      while ($journalManagers && !$journalManagers->eof()) {
        $journalManager =& $journalManagers->next();
        $notification = $notificationManager->createNotification($request, $journalManager->getId(), NOTIFICATION_TYPE_DATAVERSE_UNRELEASED, $journal->getId(), ASSOC_TYPE_JOURNAL, $journal->getId(), NOTIFICATION_LEVEL_NORMAL);
        $notificationManager->sendNotificationEmail($request, $notification);
        unset($journalManager);
      } // end notifying JMs      
    } // end if (study not released)
    
    return $studyReleased;
  }
  
  /**
   * Delete draft study or deaccession released study
   * @fixme iff deleting a draft of a previously-released study, update citation.
   * @param DataverseStudy $study
   */
  function deleteStudy(&$study) {
    $journal =& Request::getJournal();              
    $client = $this->_initSwordClient();
    $response = $client->deleteContainer(
                $study->getEditUri(), 
                $this->getSetting($journal->getId(), 'username'),
                $this->getSetting($journal->getId(), 'password'),                
                ''); // on behalf of 
    
    $studyDeleted = ($response->sac_status == DATAVERSE_PLUGIN_HTTP_STATUS_NO_CONTENT);
    
    // Notify on success or failure
    import('classes.notification.NotificationManager');
    $notificationManager = new NotificationManager();
    $user =& Request::getUser();
    
    if ($studyDeleted) {
      $dvFileDao =& DAORegistry::getDAO('DataverseFileDAO');
      $dvFileDao->deleteDataverseFilesByStudyId($study->getId());
      $dataverseStudyDao = DAORegistry::getDAO('DataverseStudyDAO');
      $dataverseStudyDao->deleteStudy($study);
      $notificationManager->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_DATAVERSE_STUDY_DELETED);
    }
    else {
      $notificationManager->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_ERROR);      
    }
    return $studyDeleted;
  }
  
  /**
   * Delete a file from a study
   * @param string $contentSourceUri
   */
  function deleteFile(&$dvFile) {
    $journal =& Request::getJournal();
    $user =& Request::getUser();
    
    if (!$dvFile->getContentSourceUri()) {
      // File hasn't been deposited in Dataverse yet
      $dvFileDao =& DAORegistry::getDAO('DataverseFileDAO');      
      return $dvFileDao->deleteDataverseFile($dvFile);
    }
    
    // File is in Dataverse. Remove from study & db.
    $client = $this->_initSwordClient();
    $response = $client->deleteResourceContent(
            $dvFile->getContentSourceUri(),
            $this->getSetting($journal->getId(), 'username'),
            $this->getSetting($journal->getId(), 'password'),
            '' // on behalf of
            );
    $fileDeleted = ($response->sac_status == DATAVERSE_PLUGIN_HTTP_STATUS_NO_CONTENT);

    import('classes.notification.NotificationManager');
    $notificationManager = new NotificationManager();
    
    if ($fileDeleted) {
      $dvFileDao =& DAORegistry::getDAO('DataverseFileDAO');      
      $dvFileDao->deleteDataverseFile($dvFile);
      $notificationManager->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_DATAVERSE_FILE_DELETED);      
    }
    else {
      $notificationManager->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_ERROR);            
    }
    return $fileDeleted;
  }
  
  /**
   * Hook callback: add content to custom notifications
   */
	function getNotificationContents($hookName, $args) {
		$notification =& $args[0];
		$message =& $args[1];
    
		$type = $notification->getType();
		assert(isset($type));

		import('classes.notification.NotificationManager');
		$notificationManager = new NotificationManager();

		switch ($type) {
      case NOTIFICATION_TYPE_ERROR:
        $message = __('plugins.generic.dataverse.notification.error');
        break;
      
      case NOTIFICATION_TYPE_DATAVERSE_FILE_ADDED:
        $message = __('plugins.generic.dataverse.notification.fileAdded');
        break;
      
      case NOTIFICATION_TYPE_DATAVERSE_FILE_DELETED:
        $message = __('plugins.generic.dataverse.notification.fileDeleted');
        break;

      case NOTIFICATION_TYPE_DATAVERSE_STUDY_CREATED:
        $message = __('plugins.generic.dataverse.notification.studyCreated');
        break;
      
      case NOTIFICATION_TYPE_DATAVERSE_STUDY_UPDATED:
        $message = __('plugins.generic.dataverse.notification.studyUpdated');
        break;
      
      case NOTIFICATION_TYPE_DATAVERSE_STUDY_DELETED:
        $message = __('plugins.generic.dataverse.notification.studyDeleted');
        break;
      
			case NOTIFICATION_TYPE_DATAVERSE_STUDY_RELEASED:
				$notificationSettingsDao =& DAORegistry::getDAO('NotificationSettingsDAO');
				$params = $notificationSettingsDao->getNotificationSettings($notification->getId());
				$message = __('plugins.generic.dataverse.notification.studyReleased', $notificationManager->getParamsForCurrentLocale($params));
				break;
      
			case NOTIFICATION_TYPE_DATAVERSE_UNRELEASED:
				$notificationSettingsDao =& DAORegistry::getDAO('NotificationSettingsDAO');
				$params = $notificationSettingsDao->getNotificationSettings($notification->getId());
				$message = __('plugins.generic.dataverse.notification.releaseDataverse', $notificationManager->getParamsForCurrentLocale($params));
				break;
		}
	}  
  
  /**
   * Wrapper function for initializing SWORDv2 client with default cURL options
   * 
   * @param array $options 
   */
  function _initSwordClient($options = array(CURLOPT_SSL_VERIFYPEER => FALSE)) {
    return new SWORDAPPClient($options);
  }
  
  /**
   * Indicates whether study is locked for processing
   * @return boolean
   */
  function studyIsLocked($study) {
    $journal =& Request::getJournal();
    $client = $this->_initSwordClient();    
    $locked = false;    
    $statement = $client->retrieveAtomStatement($study->getStatementUri(), $this->getSetting($journal->getId(), 'username'), $this->getSetting($journal->getId(), 'password'), '');
    try {
      $statementXml = new SimpleXMLElement($statement->sac_xml); 
      foreach ($statementXml->category as $category) {
        if ($category->attributes()->{'term'} == 'locked') {
          $locked = $category->attributes()->{'term'} == 'true' ? true : false;
          break;
        }
      }        
    }
    catch (Exception $e) {
      $application =& PKPApplication::getApplication();
      error_log($application->getName() .'\n '. $e->getMessage() .'\n In file: '. $e->getFile() . '\n At line: '. $e->getLine());
    }      
    return $locked;
  }

  /**
   * Workaround to avoid using citation formation plugins. Returns formatted 
   * citation for $article
   * @param type $article
   * @return string
   */
  function getCitation($article) {
    $citationFormat = $this->getSetting($article->getJournalId(), 'citationFormat');
    $journal =& Request::getJournal();
    $issueDao = DAORegistry::getDAO('IssueDAO');
    $issue =& $issueDao->getIssueByArticleId($article->getId(), $article->getJournalId());

    $templateMgr =& TemplateManager::getManager();
    $templateMgr->assign_by_ref('article', $article);
    if ($article->getStatus() == STATUS_PUBLISHED) {
      $publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
      $publishedArticle =& $publishedArticleDao->getPublishedArticleByArticleId($article->getId(), $article->getJournalId(), TRUE);
      $templateMgr->assign_by_ref('publishedArticle', $publishedArticle);
    }
    $templateMgr->assign_by_ref('issue', $issue);
    $templateMgr->assign_by_ref('journal', $journal); 
    
    return $templateMgr->fetch($this->getTemplatePath() .'citation'. $citationFormat .'.tpl');
  }
}

?>
