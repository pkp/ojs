<?php

	/**
	 * @file plugins/generic/markup/MarkupPlugin.inc.php
	 *
	 * Copyright (c) 2003-2013 John Willinsky
	 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
	 *
	 * @class MarkupPlugin
	 * @ingroup plugins_generic_markup
	 *
	 * @brief NLM XML and HTML transform plugin class
	 *
	 * Specification:
	 *
	 * When an author, copyeditor or editor uploads a new version (odt, docx, doc, or pdf format) of an article, this module submits it to the pdfx server specified in the configuration file.  The following files are returned in gzip'ed archive file (X-Y-Z-AG.tar.gz) file.  An article supplementary file is created/updated to hold the archive.
	 *
	 * manifest.xml
	 * document-new.pdf (layout version of PDF)
	 * document-review.pdf (review version of PDF, header author info stripped)
	 * document.xml (NLM-XML3/JATS-compliant)
	 * document.html (web-viewable article version)
	 * 
	 * If the article is being uploaded as a galley publish, this plugin extracts the html, xml and pdf versions and places them in the galley; and image or other media files go into a special supplementary file subfolder called "markup".
	 *
	 * @see technicalNotes.md file for details on the interface between this plugin and the Document Markup Server.
	 */
	 
define("MARKUP_GATEWAY_FOLDER",'markup'); //plugin gateway path folder.

import('lib.pkp.classes.plugins.GenericPlugin');
class MarkupPlugin extends GenericPlugin {
	
	/**
	 * Get the system name of this plugin. 
	 * The name must be unique within its category. It enables a simple URL to an articles markup files, e.g. ... /index.php/chaos/gateway/plugin/markup/1/0/document.html
	 * 
	 * @return string name of plugin
	 */
	function getName() {
		return 'markup'; // DEFAULT is markupplugin
	}
		
	function getDisplayName() {
		return __('plugins.generic.markup.displayName');
	}

	function getDescription() {
		return __('plugins.generic.markup.description');
	}
	

	/**
	 * Display verbs for the management interface.
	 */
	function getManagementVerbs() {
		$verbs = array();
		if ($this->getEnabled()) {
			$verbs[] = array('settings',  __('plugins.generic.markup.settings'));
		}
		return parent::getManagementVerbs($verbs);
	}
	
	/**
	 * Provides enable / disable / settings form options
	 *
	 * @param $verb string.
	 * @param $args ? (unused)
	 */
	function manage($verb, $args, &$message, &$messageParams) {
		if (!parent::manage($verb, $args, $message, $messageParams)) return false;
		$messageParams = array('pluginName'=> $this->getDisplayName());

		switch ($verb) {

			case 'enable':
				$this->setEnabled(true);
				$message=NOTIFICATION_TYPE_PLUGIN_ENABLED;
				return false;
				
			case 'disable':
				$this->setEnabled(false);
				$message=NOTIFICATION_TYPE_PLUGIN_DISABLED;
				return false;
			
			case 'settings':
				$journal =& Request::getJournal();
				
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));
				
				$this->import('SettingsForm');
				$form = new SettingsForm($this, $journal->getId());

				if (Request::getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						$this->import('MarkupPluginUtilities');
						MarkupPluginUtilities::notificationService(__('plugins.generic.markup.settings.saved'));
						return false;
					} else {
						$form->display();
					}
				} else {
					
					$form->initData();
					$form->display();
				}
				break;
			default:
				assert(false);
				return false;
		}
		return true;
	}

	
	/**
	 * Install triggers for various file upload points.
	 * We avoid reviewer upload hooks since user may be uploading commentary. Also, because EDITOR doesn't have a hook like LayoutEditorAction::deleteSuppFile we don't use it for tidying up. Also, ignoring AuthorAction::uploadRevisedVersion
	 *
	 * @param $category String Name of category plugin was registered to
	 *
	 * @return boolean True iff plugin initialized successfully; if false, the plugin will not be registered.
	 */
	function register($category, $path) { 
		$success = parent::register($category, $path);
		$this->addLocaleData();

		if ($success && $this->getEnabled()) {

			//Add gateway plugin class to handle markup content requests;
			HookRegistry::register('PluginRegistry::loadCategory', array(&$this, 'callbackLoadCategory'));
			
			// For User > Author > Submissions > New Submission: Any step in form.  Triggers at step 5, after entry of title and authors.	SEE line 155 of /current/html/SubmitHandler.inc.php: 
			HookRegistry::register('Author::SubmitHandler::saveSubmit', array(&$this, '_authorNewSubmissionConfirmation'));

			// The following hooks fire AFTER Apache upload of file, but before it is brought into OJS and assigned an id 
			
			// SEE classes/submission/author/authorAction.inc.php
			// For User > Author > Submissions > X > Review: Upload Author 
			HookRegistry::register('AuthorAction::uploadRevisedVersion', array(&$this, '_fileToMarkup'));	// Receives &$authorSubmission
			
			// For User > Author > Submissions > X > Editing: Author Copyedit: 
			HookRegistry::register('AuthorAction::uploadCopyeditVersion', array(&$this, '_fileToMarkup'));
	
			HookRegistry::register('CopyeditorAction::uploadCopyeditVersion', array(&$this, '_fileToMarkup')); 
			
			// For Submissions >x> Review: Submission: 
			HookRegistry::register('SectionEditorAction::uploadReviewVersion', array(&$this, '_fileToMarkup'));	
			// For Submissions >x> Review: Editor Decision: 
			HookRegistry::register('SectionEditorAction::uploadEditorVersion', array(&$this, '_fileToMarkup'));	
			
			// For Submissions >x> Editing: Copyediting: 
			HookRegistry::register('SectionEditorAction::uploadCopyeditVersion', array(&$this, '_fileToMarkup'));	
			
			// For User > Editor > Submissions > #4 > Review: Peer Review (reviewer) : Upload review (editor on behalf of reviewer):
			HookRegistry::register('SectionEditorAction::uploadReviewForReviewer', array(&$this, '_fileToMarkup'));	

			// For Submissions >x> Editing: Layout: 
			HookRegistry::register('SectionEditorAction::uploadLayoutVersion', array(&$this, '_fileToMarkup'));	
			HookRegistry::register('LayoutEditorAction::uploadLayoutVersion', array(&$this, '_fileToMarkup'));	

			HookRegistry::register('ArticleGalleyDAO::deleteGalleyById', array(&$this, 'deleteGalleyMedia'));
			
			HookRegistry::register('TemplateManager::display', array(&$this, 'displayGalleyHook'));
			
			HookRegistry::register('ArticleHandler::downloadFile', array(&$this, 'downloadArticleHook'));	

		}

		return $success;
	}

	
	/**
	 * Register as a gateway plugin too.
	 * This allows the fetch() function to respond to requests for article files.  
	 *
	 * @param $hookName string
	 * @param $args array [category string, plugins array]
	 */
	function callbackLoadCategory($hookName, $args) {
		$category =& $args[0];
		$plugins =& $args[1];
			
		switch ($category) {
			case 'gateways': // Piggyback gateway accesss to this plugin.

				$this->import('MarkupGatewayPlugin');
				$gatewayPlugin = new MarkupGatewayPlugin($this->getName());
				$plugins[$gatewayPlugin->getSeq()][$gatewayPlugin->getPluginPath()] =& $gatewayPlugin;
				break;
		}
		return false;
	}
	 
	
	/**
	 * Trigger document conversion when an author confirms a new submission (step 5).
	 * Triggered at User > Author > a > New Submission, any step in form.
	 *
	 * @param $hookName string 
	 * @param $params array [&$step, &$article, &$submitForm]
	 */
	function _authorNewSubmissionConfirmation($hookName, $params) {
		$step =& $params[0];
		if($step == 5) { // Only Interested in final confirmation step
				
			$article =& $params[1];
			$articleId	 = $article->getId();
			$journal =& Request::getJournal();
			$journalId	 = $journal->getId();
			
			// 1) Check if upload of user's doc succeeded
			$fileId	= $article->getSubmissionFileId(); 
			$articleFileDao =& DAORegistry::getDAO('ArticleFileDAO'); 
			$articleFile =& $articleFileDao->getArticleFile($fileId);
			if (!isset($articleFile)) {
				return false;
			}

			// 2) Ensure a supplementary file record titled "Document Markup Files" is in place.
			$suppFile = $this->_supplementaryFile($articleId);
			
			// 3) Set supplementary file record's file id and folder location of uploaded article file 
			import('classes.file.ArticleFileManager');
			$articleFileManager = new ArticleFileManager($articleId);
			$articleFileDir = $articleFileManager->filesDir;
			$articleFilePath = $articleFileDir. $articleFileManager->fileStageToPath( $articleFile->getFileStage() ) . '/' . $articleFile->getFileName();
			$this->_setSuppFileId($suppFile, $articleFilePath, $articleFileManager); 
			
			// Submit the article to the pdfx server
			$this->_submitURL($articleId);

		}
		
		return false; // Or true if for some reason we'd want to cancel the import of the uploaded file into OJS.
	}

	/**
	 * Trigger document conversion from various hooks for editor, section editor, layout editor etc. uploading of documents.
	 *
	 * @param $hookName string 
	 * @param $params array [article object , ...]
	 *
	 */
	function _fileToMarkup($hookName, $params) {
		
		$article =& $params[0]; 
		$articleId = $article->getId();
		$journal =& Request::getJournal(); /* @var $journal Journal */
		$journalId	 = $journal->getId();

		// Ensure a supplementary file record is in place. (not nec. file itself).
		$suppFile = $this->_supplementaryFile($articleId);

		// The form $fieldname of the uploaded file differs in one case of the calling hooks.  For the "Submissions >x> Editing: Layout:" call (SectionEditorAction::uploadLayoutVersionFForm) the file is called 'layoutFile', while in all other cases it is called 'upload'. 
		$fieldName = 'upload'; 
		if ($hookName == "SectionEditorAction::uploadLayoutVersion") {
			$fieldName = 'layoutFile'; 
		}

		// Trigger only if file uploaded.
		import('classes.file.ArticleFileManager');
		$articleFileManager = new ArticleFileManager($articleId);		
		if ($articleFileManager->uploadedFileExists($fieldName) ) {
			
			// Uploaded temp file must have an extension to continue 
			$this->import('MarkupPluginUtilities');
			$newPath = MarkupPluginUtilities::copyTempFilePlusExt($articleId, $fieldName);
			if ($newPath !== false) { 
				$this->_setSuppFileId($suppFile, $newPath, $articleFileManager); 
				@unlink($newPath);// Delete our temp copy of upload 
	
				// If we have Layout upload then trigger galley link creation.
				if (strpos($hookName, 'uploadLayoutVersion') >0) 
					$galleyFlag = true;
				else
					$galleyFlag = false;
					
				// Submit the article to the pdfx server
				$this->_submitURL($articleId, $galleyFlag);
			}
		}

		return false; // Or true if we want to cancel the file upload.
	}
	
	/** 
	 * Make a new supplementary file record or copy over an existing one.
	 * Depends on mime_content_type() to get suffix of uploaded file.
	 *
	 * @param $suppFile object
	 * @param $suppFilePath string file path		
	 * @param $articleFileManager object, already initialized with an article id.
	 */
	function _setSuppFileId(&$suppFile, $suppFilePath, &$articleFileManager) {

		//$articleFileManager= new ArticleFileManager($articleId);
		$mimeType = String::mime_content_type($suppFilePath);	
		$suppFileId = $suppFile->getFileId();

		if ($suppFileId == 0) { // There is no current supplementary file. 
			$suppFileId = $articleFileManager->copySuppFile($suppFilePath, $mimeType);
			$suppFile->setFileId($suppFileId);
			
			$suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
			$suppFileDao->updateSuppFile($suppFile);
		}
		else {
			$articleFileManager->copySuppFile($suppFilePath, $mimeType, $suppFileId, true);
		}
	}
	
	/**
	 * Web URL call triggers separate thread to do document conversion on  uploaded document.
	 * URL request goes out to this same plugin.  The url path is enabled by the  gateway fetch() part of this plugin.  Then php continues on in 1 second ...
	 * Note: A bad $curlError response is: 'Timeout was reached', which occurs when not enough time alloted to get request going. Seems like 1000 ms is minimum.  Otherwise URL fetch not even triggered. The right $curlError response which allows thread to continue is: 'Operation timed out after XYZ milliseconds with 0 bytes received'
	 *
	 * @param $articleId int 
	 * @param $galleyFlag boolean communicates whether or not galley links should be created (i.e. article is being published) 
	 */
	function _submitURL($articleId, $galleyFlag) {
		
		$args = array(
			'articleId' => $articleId,
			'action' => $galleyFlag ? 'refreshgalley' : 'refresh'
		);
		$this->import('MarkupPluginUtilities');
		$url = MarkupPluginUtilities::getMarkupURL($args);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT_MS, 1000);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		$contents = curl_exec ($ch);
		$curlError = curl_error($ch);
		curl_close ($ch);
		
		return false;
	}

	/**
	 * Ensures that a single "Document Markup Files" supplementary file record exists for given article.
	 * The title name of this file must be unique so it can be found again by this plugin.
	 * Skipping search indexing since this content is a repetition of article.
	 *
	 * @param: $articleId int
	 * @var $locale string controlled by current locale, eg. en_US
	 */
	function _supplementaryFile($articleId) {

		$suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
		$suppFiles =& $suppFileDao->getSuppFilesBySetting('title','Document Markup Files', $articleId);
		$locale = AppLocale::getLocale();
			
		if (count($suppFiles) == 0) {

			import('classes.article.SuppFile');
			$suppFile = new SuppFile(); 
			$suppFile->setArticleId($articleId);

			// DO NOT CHANGE THIS NAME - IT IS MATCHED LATER TO OVERWRITE INITIAL PDF/DOCX WIH ADJUSTED zip contents.
			$suppFile->setTitle('Document Markup Files', $locale );
			$suppFile->setType(''); //possibly 'common.other'
			$suppFile->setTypeOther("zip", $locale);
			$suppFile->setDescription(__('plugins.generic.markup.archive.description'), $locale);
			$suppFile->setDateCreated( Core::getCurrentDate() );
			$suppFile->setShowReviewers(0);
			$suppFile->setFileId(0); // Ensures new record created.
			// Unused: subject, source, language
			$suppId = $suppFileDao->insertSuppFile($suppFile);
			$suppFile->setId($suppId);
		}

		else {//Supplementary file exists, so just overwrite its file.
			$suppFile = $suppFiles[0];
		}
		$this->import('MarkupPluginUtilities');
		MarkupPluginUtilities::notificationService(__('plugins.generic.markup.archive.processing'));
		$suppFileDao->updateSuppFile($suppFile);
				
		return $suppFile;
	}
	
	

	/**
	 * HOOK Sees if there are any HTML or XML galleys left if galley item is deleted.  If not, delete all markup related file(s).
	 *
	 * @param $hookName string
	 * @param $params array [$galleyId]
	 *
	 * @see register()
	 **/
	function deleteGalleyMedia($hookName, $params) {
		$galleyId = $params[0];
		$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');
		$galley =& $galleyDao->getGalley($galleyId);
		$articleId = $galley->getSubmissionId();
		$type = $galley->getLabel();
		$this->import('MarkupPluginUtilities');
		MarkupPluginUtilities::checkGalleyMedia($articleId,$type); 
		return false;	
	}

				/*
					
					if ($article && $galley && !HookRegistry::call('ArticleHandler::downloadFile', array(&$article, &$galley))) {
					*/
					
	function downloadArticleHook($hookName, $params) {
		
		$article = $params[0];
		$galley = $params[1];
		$articleId = $article->getId();			
		return  $this->rewriteArticle($articleId, $galley, false);
		
		die("FIXME: what shall we do with this HTML?");
		return false;
		
	}
	
	/**
	 * This hook handles display of any HTML & XML ProofGalley links that were generated by this plugin.  PDFs are not handled here.
	 * It displays html file with relative urls modified to reference plugin location for article's media.
	 * Note: permissions for 5 user types (author, copyeditor, layouteditor,proofreader,sectioneditor) have already been decided in caller's proofGalley() methods.
	 * ALSO: Do NOT pass hook $params by reference, or this hook will mysteriously never fire!
	 *
	 * @param $hookName string
	 * @param $params array [$galleyId]
	 *
	 **/
	function displayGalleyHook($hookName, $params) {

		if ($params[1] != 'submission/layout/proofGalley.tpl') return false; 
		
		$templateMgr = $params[0];
		$galleyId = $templateMgr->get_template_vars('galleyId');
		$articleId = $templateMgr->get_template_vars('articleId');
		if (!$articleId) return false;
		
		$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');
		$galley =& $galleyDao->getGalley($galleyId, $articleId);
		if (!$galley) return false;

		return $this->rewriteArticle($articleId, $galley, true);
	}
	

	function rewriteArticle($articleId, &$galley, $backLinkFlag) {
		
		$label = strtoupper($galley->getLabel());

		// Final test of gallery types we allow this plugin to handle. 
		$keepers = array('HTML'); // Add XML when appropriate rewrite determined
		if (!in_array($label,$keepers)) return false;

		// Now we know we have a markup galley
		$filepath = $galley->getFilePath();
		$fileManager = new FileManager();
		$fileExt = $fileManager->parseFileExtension($filepath);

		$mimeType = ($fileExt == 'xml') ?  'application/xml' : 'text/html';
		header('Content-Type: ' . $mimeType . '; charset=UTF-8');
		header('Cache-Control: ' . $templateMgr->cacheability);
		header('Content-Length: ' . filesize($filepath));
		ob_clean();
		flush();

		// Get article's plugin file folder
		$args = array(
			'articleId' => $articleId,
			'fileName' => ''
		);
		$this->import('MarkupPluginUtilities');
		$articleURL = MarkupPluginUtilities::getMarkupURL($args);
		$markupURL = Request::url(null, 'gateway', 'plugin', array(MARKUP_GATEWAY_FOLDER,null), null);
		
		$html = file_get_contents($filepath);

		// 1) get rid of relative path to markup root:
		$html = preg_replace("#((\shref|src)\s*=\s*[\"'])(\.\./\.\./)([^\"'>]+)([\"'>]+)#", '$1'.$markupURL.'$4$5', $html);
		
		// 2) Insert document base url into all relative urls except anchorlinks.
		$html = preg_replace("#((\shref|src)\s*=\s*[\"'])(?!\#|http|mailto)([^\"'>]+)([\"'>]+)#", '$1'.$articleURL.'$3$4', $html);

		// Converts remaining file name to path[] param.  Will need 1 more call if media subfolders exist.
		if (Request::isPathInfoEnabled()==false) {
			$html = preg_replace("#((\shref|src)\s*=\s*[\"'])(?!\#)([^\"'>]+index\.php[^\"'>]+)/([^\"\?'>]+)#", '$1$3&path[]=$4', $html);
		}

		if ($backLinkFlag == true) {
			// Inject iframe at top of page that enables return to previous page.
			$backURL = Request::url(null, null, 'proofGalleyTop', $articleId, null);
			$iframe = '<iframe src="'.$backURL.'" noresize="noresize" frameborder="0" scrolling="no" height="40" width="100%"></iframe>';
			$insertPtr = strpos($html,'<body>')+6;
			$html = substr($html,0,$insertPtr)
				."\n\t"
				.$iframe
				.substr($html,$insertPtr);
		}
		
		echo($html);
		
		return true;

	}
}?>
