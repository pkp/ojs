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
 
 Specification:
 
 When an author, copyeditor or editor uploads a new version (odt, docx, doc, or pdf format) of an article, this module submits it to the pdfx server specified in the configuration file.  The following files are returned in gzip'ed archive file (X-Y-Z-AG.tar.gz) file which is added (or replaces a pre-existing version in) the Supplementary files section.
 
 	manifest.xml
 	document.pdf (used for parsing; generated if input is not PDF)
 	document-new.pdf (layout version of PDF)
	document.nlm.xml (NLM-XML3/JATS-compliant)
	document.html (web-viewable article version)
	document.bib (JSON-like format for structured citations)
	document.refs (a text file of the article's citations and their bibliographic references, formatted according to selected CSL style. Also indicates when references were unused in body of article.)
	
If the article is being uploaded as a galley publish, this plugin will extract the html, xml and pdf versions when they are ready, and will place them in the supplementary file folder.
 
 */
	
import('lib.pkp.classes.plugins.GenericPlugin');
class MarkupPlugin extends GenericPlugin {
	
	/**
	 * URL for Markup server
	 * @var string
	 */
	var $_host;

	/**
	 * Set the host
	 * @param $host string
	 */
	function setHost($host) {
	    $this->_host = $host;
	}

	/**
	 * Get the host
	 * @return string
	 */
	function getHost() {
	    return $this->_host;
	}
	
	/**
	 * Get the system name of this plugin. 
	 * The name must be unique within its category. This name is short since it enables a simple URL to an articles markup files, e.g. http://ubie/ojs/index.php/chaos/gateway/plugin/markup/1/0/document.html
	 * 
	 * @return string name of plugin
	 */
	function getName() {
		return 'markup'; 
	}
 
	function getDisplayName() {
		return __('plugins.generic.markup.displayName');
	}

	function getDescription() {
		return __('plugins.generic.markup.description');
	}
	
	/**
	 * Get the management verbs for this plugin
	 */
	function getManagementVerbs() {
		$verbs = parent::getManagementVerbs();
		if (!$this->getEnabled()) return $verbs; // enable/upgrade/delete
		$verbs[] = array(
			'settings', __('plugins.generic.markup.settings')
		);
		return $verbs;
	}
	
	/**
	 * Provides enable / disable / settings form options
	 *
	 * @param $verb string.
	 * @param $args ? (unused)
	*/
	function manage($verb, $args) {
		$message="";
		$messageParams = array();

		/* If manage() returns true, parent's manage() handler is skipped.  Enable/disable still seem to need to be handled in parent as well as here.  Settings is handled here entirely.
		*/
		switch ($verb) {

			case 'enable':
				$this->setEnabled(true);
				$message = NOTIFICATION_TYPE_PLUGIN_ENABLED;
				$s = array('pluginName' => $this->getDisplayName());
				return false;
				
			case 'disable':
				$this->setEnabled(false);
				$message = NOTIFICATION_TYPE_PLUGIN_DISABLED;
				$messageParams = array('pluginName' => $this->getDisplayName());
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
						Request::redirect(null, null, null, 'generic');
					} else {
						$form->display();
					}
				} else {
					
					$form->initData();
					$form->display();
				}
				break;
			default:
				return false;
		}
		return true;
	}
	
	
	/**
	 * Called as a plugin is registered to the registry.
	 *
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */

	function register($category, $path) { 
		 /* Call comes in as: "register('generic' , 'plugins/generic/markup');"
		  For info on HookRegistry - see lib/pkp/classes/plugins/
		  FUTURE: 	Could also create hooks to hide References section in User > Author > Submissions > New Submission; and to auto-populate it on upload.
		 */
		$success = parent::register($category, $path);
		$this->addLocaleData();

		if ($success && $this->getEnabled()) {

			/* For User > Author > Submissions > New Submission: Any step in form: SubmitHandler::saveSubmit
			 THIS IS A GENERAL HOOK WHICH WE WANT TO CATCH AFTER ENTRY OF TITLE AND AUTHORS.
			 SEE line 155 of /current/html/SubmitHandler.inc.php: 
			 HookRegistry::call('Author::SubmitHandler::saveSubmit', array(&$step, &$article, &$submitForm));
			*/
			HookRegistry::register('Author::SubmitHandler::saveSubmit', array(&$this, '_authorNewSubmissionConfirmation'));

			/* IGNORE THIS HOOK: SINCE HANDLED ABOVE, after title & authors info entered
			 For User > Author > Submissions > New Submission: Submission File: Replace submission file
			 HookRegistry::register('AuthorAction::​uploadRevisedVersion', array(&$this, 'fileCallback'));
			*/

			// The following hooks fire AFTER Apache upload of file, but before it is brought into OJS and assigned an id 
			
			// SEE classes/submission/author/authorAction.inc.php
			// For User > Author > Submissions > X > Review: Upload Author 
			HookRegistry::register('AuthorAction::uploadRevisedVersion', array(&$this, '_fileToMarkup'));	// Receives &$authorSubmission
			
			// For User > Author > Submissions > X > Editing: Author Copyedit: 
			HookRegistry::register('AuthorAction::uploadCopyeditVersion', array(&$this, '_fileToMarkup'));
	
			// Copyeditor user handled in future versions of OJS I believe.
			HookRegistry::register('CopyeditorAction::uploadCopyeditVersion', array(&$this, '_fileToMarkup')); // receives array(&$copyeditorSubmission));
			
			// SEE clases/submission/sectionEditor/SectionEditorAction.inc.php
			// For Submissions >x> Review: Submission: 
			HookRegistry::register('SectionEditorAction::uploadReviewVersion', array(&$this, '_fileToMarkup'));	
			// For Submissions >x> Review: Editor Decision: 
			HookRegistry::register('SectionEditorAction::uploadEditorVersion', array(&$this, '_fileToMarkup'));	
			
			// For Submissions >x> Editing: Copyediting: 
			HookRegistry::register('SectionEditorAction::uploadCopyeditVersion', array(&$this, '_fileToMarkup'));	
			
			// EDITOR ON BEHALF OF REVIEWER: PROBABLY AVOID THIS?
			// For User > Editor > Submissions > #4 > Review: Peer Review (reviewer) : Upload review: 
			// hook receives array(&$reviewAssignment, &$reviewer)
			HookRegistry::register('SectionEditorAction::uploadReviewForReviewer', array(&$this, '_fileToMarkup'));	

			// For Submissions >x> Editing: Layout: 
			HookRegistry::register('SectionEditorAction::uploadLayoutVersion', array(&$this, '_fileToMarkup'));	
			HookRegistry::register('LayoutEditorAction::uploadLayoutVersion', array(&$this, '_fileToMarkup'));	

			// REVIEWER:  AVOIDED.  USER MAY BE UPLOADING COMMENTARY
			// ArticleGalleyDAO::insertNewGalley: WE DON'T TAKE OVER THIS HOOK.  Editors are allowed to upload their own files to a galley.
			
			//Shoehorn part of this plugin into the gateway plugin class so we can call it with fetch();
			HookRegistry::register('PluginRegistry::loadCategory', array(&$this, '_callbackLoadCategory'));
				
			//This fires once for each individual supplementary file deleted.  TEST: Does it handle case wher
			// PROBLEM EDITOR doesn't have a hook for this.  Consequently, supplementary file subfolder deletion is handled in fetch() 
			//HookRegistry::register('LayoutEditorAction::deleteSuppFile', array(&$this, '_deleteSuppFile'));

			HookRegistry::register('ArticleGalleyDAO::deleteGalleyById', array(&$this, '_deleteGalley'));
			
		}

		return $success;
	}

	/**
	 * Register as a gateway plugin, even though this is a generic plugin.
	 * This allows the fetch() function to respond to requests for article files.  
	 *
	 * @param $hookName string
	 * @param $args array [category string, plugins array]
	*/
	function _callbackLoadCategory($hookName, $args) {
		$category =& $args[0];
		$plugins =& $args[1];
		switch ($category) {
			case 'gateways': //Add this plugin to gateways list.
				$gatewayPlugin =& $this;
				$plugins[$gatewayPlugin->getSeq()][$gatewayPlugin->getPluginPath()] =& $gatewayPlugin;
				break;
			/*	The alternate approach (below) is to load separate code for the gatewayPlugin (consisting mainly of fetch()) but that approach causes a settings link / form to appears under the plugin/gateways category too. "$this->import('MarkupPlugin'); $gatewayPlugin = new MarkupPlugin($this->getName());" */
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
			
			//Ensure a supplementary file record is in place. (not nec. file itself).
			$suppFile = $this->_supplementaryFile($articleId);
					
			$fileId	= $article->getSubmissionFileId(); //Id of doc just uploaded.
			
			$articleFileDao =& DAORegistry::getDAO('ArticleFileDAO'); 
			$articleFile =& $articleFileDao->getArticleFile($fileId);
	
			if (!isset($articleFile)) {
				return false;
			}
			
			//Need article file manager just to get the file path of article.
			import('classes.file.ArticleFileManager');
			$articleFileManager = new ArticleFileManager($articleId);
			$articleFileDir = $articleFileManager->filesDir;
			
			//fileStageToPath : see classes/file/ArticleFileManager.inc.php
			//REPLACE $articleFilePath WITH PATH TO UPLOADED FILE ABOVE
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
	 * @param $hookName string , eg. SectionEditorAction::uploadCopyeditVersion
	 * @param $params array [article object , ...]
	 */
	function _fileToMarkup($hookName, $params) {
		
		$article =& $params[0]; 
		$articleId	 = $article->getId();
		$journal =& Request::getJournal(); /* @var $journal Journal */
		$journalId	 = $journal->getId();

		// Ensure a supplementary file record is in place. (not nec. file itself).
		$suppFile = $this->_supplementaryFile($articleId);

		/* The name ($fieldname below) of the uploaded file differs in one case of the calling hooks.  For the "Submissions >x> Editing: Layout:" call (SectionEditorAction::uploadLayoutVersionFForm) the file is called 'layoutFile', while in all other cases it is called 'upload'. */
		$fieldName = 'upload'; // See $fieldName notes above.
		if ($hookName == "SectionEditorAction::uploadLayoutVersion") {
			$fieldName = 'layoutFile'; 
		}

		// Need article file manager just to get the file path of article.
		import('classes.file.ArticleFileManager');
		$articleFileManager = new ArticleFileManager($articleId);		
		// SEE ojs/lib/pkp/classes/file/FileManager.inc.php
		// Don't trigger this process if for some reason the user's file wasn't uploaded.
		if ($articleFileManager->uploadedFileExists($fieldName) ) {
			// Issue is that temp files don't have suffixes.  when a temp file is copied into a supplementary file record, it is given a .txt suffix by default.  So first we have to get a copy of the temp file with the right suffix added. This happens regardless of mime type smarts.
			$newPath = $this->_copyTempFile($articleFileManager, $fieldName);
			$this->_setSuppFileId($suppFile, $newPath, $articleFileManager); 
			@unlink($newPath);// delete our temp copy of uploaded file. 

			// If we have Layout upload then trigger galley link creation.
			if (strpos($hookName, 'uploadLayoutVersion') >0) 
				$galleyFlag = "galley";
			else
				$galleyFlag = "";
				
			// Submit the article to the pdfx server
			$this->_submitURL($articleId, $galleyFlag);

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
	function _setSuppFileId(&$suppFile, &$suppFilePath, &$articleFileManager) {
		$mimeType = String::mime_content_type($suppFilePath);	
		$suppFileId = $suppFile->getFileId();

		if ($suppFileId == 0) { // There is no current supplementary file
			$suppFileId = $articleFileManager->copySuppFile($suppFilePath, $mimeType);
			$suppFile->setFileId($suppFileId);
			
			$suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
			$suppFileDao->updateSuppFile($suppFile);
		}
		else {
			// See copySuppFile() in classes/file/ArticleFileManager.inc.php
			$articleFileManager->copySuppFile($suppFilePath, $mimeType, $suppFileId, true);
		}
	}
	
	/**
	* Web URL call triggers separate thread to do document conversion on  uploaded document.
	* URL request goes out to this same plugin.  The url path is enabled by the  gateway fetch() part of this plugin.  Then php continues on in 1 second ...
	*
	* @param $articleId int 
	* @param $galleyFlag boolean communicates whether or not galley links should be created (i.e. article is being published) 
	*/
	function _submitURL($articleId, $galleyFlag) {
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->_getMarkupURL($articleId)."/refresh".$galleyFlag);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //sends output to $contents
		curl_setopt($ch, CURLOPT_TIMEOUT_MS, 1000);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		$contents = curl_exec ($ch);

		/* The right $curlError response is:
		'Operation timed out after XYZ milliseconds with 0 bytes received'
		Bad response is: 'Timeout was reached'
		Occurs when not enough time alloted to get process going. Seems like 1000 ms is minimum.  Otherwise URL fetch not even triggered.
		*/
		$curlError = curl_error($ch);
		
		curl_close ($ch);
		return false;
	}

	
	/**
	 * Ensures a "Document Markup Files" supplementary file record exists for given article.
	 * The title name of this file must be unique.  IT SHOULD NOT BE LOCALIZED, i.e. All languages have one record. 
	 *
	 * @param: $articleId int
	 * @var $locale string controlled by current locale, eg. en_US
	 */
	function _supplementaryFile($articleId) {

		//SEE: classes/article/SuppFileDAO.inc.php
		$suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
		$suppFiles =& $suppFileDao->getSuppFilesBySetting('title','Document Markup Files', $articleId);
		$locale = AppLocale::getLocale();// eg. 'en_US'); 
			
		if (count($suppFiles) == 0) {

			// Insert article_supp_file
			// Adapted from classes/submission/form/supFileForm.inc.php
			//SubmissionFile->classes.article.ArticleFile
			import('classes.article.SuppFile');
			$suppFile = new SuppFile(); //See classes/article/SuppFile.inc.php
			$suppFile->setArticleId($articleId);

			// DO NOT CHANGE (LOCALIZE) THIS NAME - IT IS MATCHED LATER TO OVERWRITE INITIAL PDF/DOCX WIH ADJUSTED zip contents.
			$suppFile->setTitle('Document Markup Files', $locale );
			$suppFile->setType(''); //possibly 'common.other'
			$suppFile->setTypeOther("zip", $locale);
			$suppFile->setDescription(__('plugins.generic.markup.archive.description'), $locale);
			$suppFile->setDateCreated( Core::getCurrentDate() );
			$suppFile->setShowReviewers(0);
			$suppFile->setFileId(0); //has to be set (zero) for new create.
			// Unused: subject, source, language
			$suppId = $suppFileDao->insertSuppFile($suppFile);
			$suppFile->setId($suppId);
		}
				
		else {//Supplementary file exists, so just overwrite its file.
			$suppFile = $suppFiles[0];
		}
		// Skipping search index since this content is a repetition of article	
		$suppFile->setCreator(__('plugins.generic.markup.archive.processing'), $locale);
		$suppFileDao->updateSuppFile($suppFile);
				
		return $suppFile;
	}
	
	/**
	* Provide suffix for uploaded file.
	* The uploaded temp file doesn't have a file suffix.  We copy this file and add a suffix, in preperation for uploading it to document markup server.
	*
	* @param: $articleFileManager object primed with article	
	* @param: $fieldName string upload form field name	
	*/
	function _copyTempFile(&$articleFileManager, $fieldName) {

		$articleFilePath = $articleFileManager->getUploadedFilePath($fieldName);
		$fileName =  $articleFileManager->getUploadedFileName($fieldName);
		$fileNameArray = explode(".",$fileName);
		$suffix = $fileNameArray[count($fileNameArray)-1];
		$newFilePath = $articleFilePath.".".$suffix;
		$articleFileManager->copyFile($articleFilePath, $newFilePath);
		
		return $newFilePath;
	}

	/**
	* Handles URL request to trigger document processing for given article; also handles URL requests for xml/pdf/html versions of an article as well as the html's image and css files.   
	* This is the gateway plugin component of this plugin.  It sets up a fetch() receiver for requests of form "http://[domain]/ojs/index.php/[journal name]/gateway/plugin/markup/...".  THIS IS NOT A HOOK.  Access is view-only for now.
	*
	* URL is generally of the form:
	* 		http://ubie/ojs/index.php/chaos/gateway/plugin/markup/[article id]
	*		- retrieves document.xml file manifest
	*		... /markup/1/0/document.html // retrieves document.html page and related images/media files.
	*		... /markup/1/
	*		... /markup/1/0/document.xml
	*		... /markup/1/0/document.pdf 		
	*		... /markup/1/0/document-review.pdf 	
	*		... /css/[filename] 		//stylesheets for given journal		
	*		... /[articleid]/refresh 	//generate zip file 
	*		... /[articleid]/refreshgalley 	//generate zip file and make galley links 
	*		... /[articleid]/0/[filename] // return galley pdf/xml/html
	*		... /[articleid]/[versionid]/[action] //FUTURE: access version.
	*
	* @param $args Array of relative url folders down from plugin
	*/
	function fetch($args) {
		
		if (! $this->getEnabled() ) 
			return $this->_exitFetch( __('plugins.generic.markup.archive.enable'));
	
		// Make sure we're within a Journal context
		$journal =& Request::getJournal();
		if (!$journal) 
			return $this->_exitFetch( __('plugins.generic.markup.archive.no_journal'));
		
		$journalId = $journal->getId();
	
		/* See what kind of request this is: 
			...plugin/markup/$param_1/$param_2/$fileName
		*/
		$param_1 = strtolower(array_shift($args));
		$param_2 = strtolower(array_shift($args)); 
		
		$fileName = strtolower(array_shift($args)); 
		// Clean filename, if any:
		$fileName = preg_replace('/[^[:alnum:]\._-]/', '', $fileName );
			
		/* STYLESHEET HANDLING
		* Recognizes any relative urls like ".../css/styles.css"
		* Provide Journal specific stylesheet content.
		* No need to check user permissions here
		*/
		if ($param_1 == "css") {
			$folder =  Config::getVar('files', 'files_dir') . '/journals/' . $journalId . '/css/';
			return $this->_downloadFile($folder, $param_2);
		}
		
		/* DEALING WITH A PARTICULAR ARTICLE HERE */
	
		$articleId = intval($param_1);
		if (!$articleId) 
			return $this->_exitFetch( __('plugins.generic.markup.archive.no_articleID'));
	
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$article = &$articleDao->getArticle($articleId);
		if (!$article) 
			return $this->_exitFetch(  __('plugins.generic.markup.archive.no_article'));
	
		if ($param_2 == 'refresh' ) {
			$this->_refresh($article, false);
			return true; //Doesn't matter what is returned.  This is a separate curl() thread.
		};
		// As above, but galley links created too.
		if ($param_2 == 'refreshgalley') {
			$this->_refresh($article, true);
			return true; 
		};	
		
		if (trim($fileName) == '')
			return $this->_exitFetch( __('plugins.generic.markup.archive.bad_filename')); 
	
		/* Now we deliver any markup file request if its article's publish state allow it, or if user's credentials allow it. 
		$param_2 is /0/ for version/revision; a constant for now. 
		$fileName should be a file name.
		*/
		
		$markupFolder = $this->_getSuppFolder($articleId).'/markup/';
		
		if (!file_exists($markupFolder.$fileName))
			return $this->_exitFetch( __('plugins.generic.markup.archive.no_file')); 
		
		$status = $article->getStatus();
	
		// Most requests come in when an article is in its published state, so check that first.
		if ($status == STATUS_PUBLISHED ) { 
			if ($this->_publishedDownloadCheck($articleId, $journal, $fileName)) {
				$this->_downloadFile($markupFolder, $fileName);
				return true;
			}
		}
	
		// Article not published, so access can only be granted if user is logged in and of the right type / connection to article
		$user =& Request::getUser();
		$userId = $user?$user->getId():0;
	
		if (!$userId) 
			return $this->_exitFetch( __('plugins.generic.markup.archive.login')); 
		
		if ($this->_authorizedUser($userId, $articleId, $journalId, $fileName) ) {
			$this->_downloadFile($markupFolder, $fileName);
			return true;
		}

		return $this->_exitFetch( __('plugins.generic.markup.archive.no_access')); 
		//return true; // Ensures that fetch() gets to display its status message if no file downloaded; otherwise automatic redirect to OJS home page occurs.
	}

	/**
	* Request is for a "refresh" of an article's markup archive.  
	* If article's "Document Markup Files" supplementary file is not a .zip (in other words it is an uploaded .doc or .pdf), then send the supplementary file to the PKP Document Markup Server for conversion.
	* Status of the supplementary file (Creator field) is updated to indicate progress in fetching and processing it.
	*
	* @param $article object
	* @param $galleyLinkFlag boolean
	*
	* @see fetch()
	*/
	function _refresh(&$article, $galleyLinkFlag) {
		$journal =& Request::getJournal(); 
		$journalId = $journal->getId();
		$articleId = $article->getId();
		
		//SEE: classes/article/SuppFileDAO.inc.php
		$suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
		$suppFiles =& $suppFileDao->getSuppFilesBySetting("title","Document Markup Files", $articleId);
		
		if (count($suppFiles) == 0) 
			return $this->_exitFetch( __('plugins.generic.markup.archive.supp_missing'));

		$suppFile = $suppFiles[0];

		$fileId = $suppFile->getFileId();
		if ($fileId == 0) 
			return $this->_exitFetch( __('plugins.generic.markup.archive.supp_file_missing'));
						
		$suppFile->setCreator( __('plugins.generic.markup.archive.processing'), AppLocale::getLocale());//'en_US'); 
		//SEE: classes/article/SuppFileDAO.inc.php
		$suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
		$suppFileDao->updateSuppFile($suppFile);

		$suppFileName = $suppFile->getFileName();
		if (preg_match("/.*\.zip/", $suppFileName ) ) {
			return $this->_exitFetch( __('plugins.generic.markup.archive.is_zip'));
		}

		// Construct the argument list and call the plug-in settings DAO
		$settingsDao =& DAORegistry::getDAO('PluginSettingsDAO');
		$markupHostURL = $this->_pluginSetting($settingsDao, $journalId, 'markupHostURL');
		if (!strlen($markupHostURL)) {
			return $this->_exitFetch( __('plugins.generic.markup.archive.no_url'));
		}
		
		$hostUser = $this->_pluginSetting($settingsDao, $journalId, 'markupHostUser');
		$hostPass = $this->_pluginSetting($settingsDao, $journalId, 'markupHostPass');
		
		import('classes.file.ArticleFileManager');
		$articleFileManager = new ArticleFileManager($articleId);
		$articleFileDir = $articleFileManager->filesDir;
		// fileStageToPath() : see classes/file/ArticleFileManager.inc.php
		$suppFilePath = $articleFileDir. $articleFileManager->fileStageToPath( ARTICLE_FILE_SUPP ) . '/' . $suppFileName ;

		//In authors array we just want the _data object.
		$authors = $article->getAuthors(); 
		$authorsOut = array();
		foreach($authors as $author) {
			$author = ($author->_data);
			unset($author["sequence"], $author["biography"]);
			//Remaining fields: submissionId, firstName, middleName, lastName, country, email, url, primaryContact (boolean), affiliation:{"en_US":"..."}
			$authorsOut[] = $author;
		}

		$cssURL = Request::getJournal()->getUrl() . '/gateway/plugin/markup/css/';

		$cslStyle = $this->_pluginSetting($settingsDao, $journalId, 'cslStyle');
		
		//Prepare request for Document Markup Server
		$args = array(
			'type' => 'PDFX.fileUpload',
			'data' => array(
				'user' => $hostUser, //login with these params or use guest if blank.
				'pass' => $hostPass,
				'cslStyle' => $cslStyle,
				'cssURL' => $cssURL,
				'title'	  => $article->getLocalizedTitle(),
				'authors' => $authorsOut,
				'journalId' => $journalId,
				'articleId' => $article->getId(),

				'publicationName' => $journal->getLocalizedTitle(),
				'copyright' =>  strip_tags($journal->getLocalizedSetting('copyrightNotice')),
				'publisher' => strip_tags($journal->getLocalizedSetting('publisherNote')),
				'rights' => strip_tags($journal->getLocalizedSetting('openAccessPolicy')),
				'eISSN' => $journal->getLocalizedSetting('onlineIssn'),
				'ISSN' => $journal->getLocalizedSetting('printIssn'), // http://www.issn.org/

				'DOI' => $article->getPubId('doi') //http://dx.doi.org

			)
		);

		// This field has content only if header image actually exists in the right folder.
		$ImageFileGlob =  Config::getVar('files', 'files_dir') . '/journals/' . $journalId . '/css/article_header.{jpg,png}';
		$g = glob($ImageFileGlob,GLOB_BRACE);
		$cssHeaderImageName = basename($g[0]);
		if (strlen($cssHeaderImageName) > 0) 
			$args['data']['cssHeaderImageURL'] = $cssURL.$cssHeaderImageName;
					
		// Provide some publication info
		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$issue =& $issueDao->getIssueByArticleId($articleId, $journalId);
		if ($issue && $issue->getPublished()) { // At what point are articles shunted into issues?
			$args['data']['number'] = $issue->getNumber();
			$args['data']['volume'] = $issue->getVolume();
			$args['data']['year'] = $issue->getYear();
			$args['data']['publicationDate'] = $issue->getDatePublished();
		};

		$reviewVersion = $this->_pluginSetting($settingsDao, $journalId,  'reviewVersion');
		if ($reviewVersion == true) {
			$args['data']['reviewVersion'] = true;
		};
	
		$postFields = array(
			'jit_events' => json_encode(array($args)),
			'userfile' => "@". $suppFilePath //FULL FILE PATH TO ARTICLE FILE - includes file suffix (important since it has to be recognized by service).
		);
		// Uncomment to record an example of server to server call:
		//@file_put_contents('/var/www/ojs/plugins/generic/markup/curlExample.txt',  json_encode(array($args)) );
		
		//cURL sends article file to pdfx server for processing, and in 15-30+ seconds or so returns jobId which is folder where document.zip archive of converted documents sits.
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $markupHostURL."process.php");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //sends output to $contents
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		$contents = curl_exec ($ch);
		$errorMsg = curl_error($ch);
		curl_close ($ch);

		// Is there a possibility where user upploads file, but deletes supplementary file placeholder before pdfx server returns document.zip? Could just call _supplementaryFile() for the first time here.
		//$suppFile = $this->_supplementaryFile($articleId); 

		// Document markup server provides plain text error message details.
		if ($contents === false) 
			return $this->_exitFetchStatus($errorMsg, $suppFile, $suppFileDao);
	
		$events = json_decode($contents,true);
		
		//Absence of data would result in error here.
		$response = $events["jit_events"][0]; //First and only request
		if ($response['error'] > 0) 
			return $this->_exitFetchStatus($response['message'], $suppFile, $suppFileDao);

		// With a $jobId, we can fetch URL of zip file and enter into supplimentary file record.
		$jobId = $response['data']['jobId'];
		if (strlen($jobId) == 0) 
			return $this->_exitFetch( __('plugins.generic.markup.archive.no_job').$contents, $suppFile, $suppFileDao);
		
		$archiveURL = $markupHostURL . 'job/'.$jobId.'/document.zip';
		
		$suppFileId = $suppFile->getFileId();
		// In new scheme this case shouldn't happen, since supp file contains content sent out to document markup server. 
		// there is no current supplementary file
			
		if ($suppFileId == 0) { 
			$suppFileId = $articleFileManager->copySuppFile($archiveURL, 'application/zip');
			$suppFile->setFileId($suppFileId);
		}
		else {
			// copySuppFile($url, $mimeType, $fileId = null, $overwrite = true)
			// IN: classes/file/ArticleFileManager.inc.php
			// .tar.gz: application/x-gzip , .zip: application/zip
			//Null if unsuccessful.
			$articleFileManager->copySuppFile($archiveURL, 'application/zip', $suppFileId, true);
		}
		
		$suppFile->setCreator(__('plugins.generic.markup.archive.downloaded'), AppLocale::getLocale()); // 'en_US'
		$suppFileDao->updateSuppFile($suppFile);
		
		// LAUNCH THIS GALLEY / UNZIP ONLY during Layout publish
		if ($galleyLinkFlag) {
			if (! $this->_unzipSuppFile($articleId, $suppFile, $suppFileDao, $galleyLinkFlag) ) 
				return true; // Any errors are reported within call. 
		}
		else {
			// This makes sure no galleys are accidentally left around from earlier versions of this document.  It means the last upload of the document should be to the Layout files list.  If for some reason the article is re-uploaded for another reason, then an editor needs to re-upload it to the Layout section to get back article galley links. 
			$this->_deleteGalleys($articleId);	
		}
		
		return $this->_exitFetchStatus("Completed: Journal $journalId, Article $articleId, Job $jobId", $suppFile, $suppFileDao);

	}
	
	function _pluginSetting(&$pluginSettingsDao, $journalId, $settingName) {
		return call_user_func_array(
			array(&$pluginSettingsDao, 'getSetting'), array($journalId, 'markup', $settingName)
		);
	}
	
	/**
	* Do all necessary checks to see if user is allowed to download this file
	* Basically a variation on /ojs.pages/article/ArticleManager.inc.php validate() AND /ojs.pages/issue/IssueManager.inc.php validate() 
 	* FUTURE: provide access to a file only if galley exists for it?
	*
	* @param $articleId int
 	* @param $journal object
 	* @param $fileName string , included in case we have to filter about requested file type (pdf or other)
 	*
 	* @return boolean true iff user allowed to see given file
	* @see fetch()
	*/
	function _publishedDownloadCheck($articleId, &$journal, $fileName) {

		$journalId = $journal->getId();
		
		//Is Issue published?
		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$issue =& $issueDao->getIssueByArticleId($articleId, $journalId);
		if (!$issue->getPublished()) {
			return false;
		}
				
		// Flag indicating user's computer domain is ok for viewing. $articleId just used to lookup published article id, which is then used to say OK if an expired subscription was valid when article published.!!!!?
		import('classes.issue.IssueAction');
		$isSubscribedDomain = IssueAction::subscribedDomain($journal, $issue->getId(), $articleId);
		if ($isSubscribedDomain) 
			return true;// Let em see it.	
		
		// Login required. Presumably flag for 'restrictSiteAccess' => 'Users must be registered and log in to view the journal site.'
		$subscriptionRequired = IssueAction::subscriptionRequired($issue);
		if (!$subscriptionRequired) 
			return true;// Let em see it.	
		
		// Now subscriptionRequired
		// Some journals allow access at the individual article level without login?
		if (!$journal->getSetting('restrictArticleAccess')) 
			return true;
		
		// 'Users must be registered and log in to view restricted article related content'
		if (!Validation::isLoggedIn()) 
			return false;
		
		// Subscription Access.  Calls getPublishedArticleByArticleId with $articleId
		$subscribedUser = IssueAction::subscribedUser($journal, $issue->getId(), $articleId);
		if ($subscribedUser) 
			return true;
		
		// A chunk of work below is done in above IssueAction::subscribedUser, but we don't have access to it.
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		// Regular article doesn't have getAccessStatus()
		$publishedArticle =& $publishedArticleDao->getPublishedArticleByArticleId((int) $articleId, (int) $journalId, true);
		// Choice here: ARTICLE_ACCESS_OPEN or ARTICLE_ACCESS_ISSUE_DEFAULT
		if ($publishedArticle->getAccessStatus() == ARTICLE_ACCESS_OPEN) 
			return true;
	
		// At this point access requires fee-based service except possibly in non-pdf case.  The OJSPaymentManager($request) uses $request just to look up journal.
		import('classes.payment.ojs.OJSPaymentManager');
		$request =& Request::getRequest();
		$paymentManager = new OJSPaymentManager($request);
		
		// One of these fee methods must be active (with fees above $0), or else we quit.
		if ( !$paymentManager->purchaseArticleEnabled() &&
			!$paymentManager->purchaseIssueEnabled() &&
			!$paymentManager->membershipEnabled() ) {
			return false;
		}

		// If only pdf files are being restricted, then approve all non-pdf files; should this be moved above fee method?
		if ( $paymentManager->onlyPdfEnabled() && pathinfo($fileName, PATHINFO_EXTENSION) != 'pdf') {
			return true;
		}
		
		$completedPaymentDao =& DAORegistry::getDAO('OJSCompletedPaymentDAO');

		// If article has been paid for
		if ($completedPaymentDao->hasPaidPurchaseArticle($userId, $publishedArticle->getId()) )
			return true;
		
		// If issue has been paid for
		if ( $completedPaymentDao->hasPaidPurchaseIssue($userId, $issue->getId()) )
			return true;
		
		// If membership is still good; could move this up.
		$dateEndMembership = $user->getSetting('dateEndMembership', 0);
		if (!is_null($dateEndMembership) && $dateEndMembership > time() ) 
			return true;

		// In all other cases...
		return false;
	}
	
	/**
	* Set Supplementary file's creator field to show status of document conversion process.
	* Returns message; this may be displayed if appropriate.
	*
	* @param $msg string 
	* @param $suppFile object (supplementary file record)
	* @param $suppFileDao object primed with article
	*/
	function _exitFetchStatus($msg, &$suppFile, &$suppFileDao) {
		if ($suppFile) {
			$suppFile->setCreator(__('plugins.generic.markup.archive.status') . $msg ,  AppLocale::getLocale());//'en_US'); 
			$suppFileDao->updateSuppFile($suppFile);
		}
		
		return $this->_exitFetch($msg);
	}
	
	/**
	* Calculate current user's read permission with respect to given article.
	* Handles case where article isn't published yet.
	* FUTURE: Return editing permission too (only if STATUS_QUEUED)
	*
	*   - user is SITE_ADMIN or JOURNAL_MANAGER ?: return true
	*	- user is Editor / Section Editor of given journal ?: return true
	*	- user is author / reader / reviewer of given article ?: return true.
	*
	* USERS TO CONSIDER: See ojs/classes/security/Validation.inc.php
	*
	*  ROLE_ID_SITE_ADMIN		isSiteAdmin() RARE
	*
	*  All isXYZ() functions below can take a journalId.
	*  ROLE_ID_JOURNAL_MANAGER isJournalManager()
	*  ROLE_ID_EDITOR 			isEditor()
	*  ROLE_ID_SECTION_EDITOR	isSectionEditor()
	*
	*  ROLE_ID_COPYEDITOR		isCopyeditor()
	*  ROLE_ID_LAYOUT_EDITOR 	isLayoutEditor()
	*  ROLE_ID_PROOFREADER		isProofreader()
	*
	*  ROLE_ID_AUTHOR			isAuthor()
	*  ROLE_ID_READER			isReader()
	*  ROLE_ID_REVIEWER		isReviewer()
	*
	* @return userType that matches user to article.
	**/
	function _authorizedUser($userId, $articleId, $journalId, $fileName) {

		$roleDao = &DAORegistry::getDAO('RoleDAO'); 
		$roles =& $roleDao->getRolesByUserId($userId);
		foreach ($roles as $role) {
			$roleType = $role->getRoleId();
			if ($roleType == ROLE_ID_SITE_ADMIN) return ROLE_ID_SITE_ADMIN;
			if ($role->getJournalId() == $journalId) {

				switch ($roleType) {
					// These users get global access
					case ROLE_ID_JOURNAL_MANAGER :
					case ROLE_ID_EDITOR :	
						return $roleType; 
						break;
						
					case ROLE_ID_SECTION_EDITOR :		

						$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');

						$sectionEditorSubmission =& $sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
			
						if ($sectionEditorSubmission != null && $sectionEditorSubmission->getJournalId() == $journalId && $sectionEditorSubmission->getDateSubmitted() != null) {
							// If this user isn't the submission's editor, they don't have access.
							$editAssignments =& $sectionEditorSubmission->getEditAssignments();

							foreach ($editAssignments as $editAssignment) {
								if ($editAssignment->getEditorId() == $userId) {
									return $roleType; 
								}
							}
						};
		
						break;
						
					case ROLE_ID_LAYOUT_EDITOR : 
			
						$signoffDao =& DAORegistry::getDAO('SignoffDAO');
						if ($signoffDao->signoffExists('SIGNOFF_LAYOUT', ASSOC_TYPE_ARTICLE, $articleId, $userId)) {
							return $roleType;
						}
						break;
							
					/* UNTESTED: In OJS 2.4 no such users.
					case ROLE_ID_PROOFREADER :
						$signoffDao =& DAORegistry::getDAO('SignoffDAO');
						if ($signoffDao->signoffExists('SIGNOFF_PROOFING', ASSOC_TYPE_ARTICLE, $articleId, $userId)) {
								return $roleType; 
						}
						break;

					case ROLE_ID_COPYEDITOR : //'SIGNOFF_COPYEDITING'
						$SESDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');
						if ($SESDao->copyeditorExists($articleId, $userId) )
							return $roleType; 
						break;
					*/
					
					case ROLE_ID_AUTHOR : //Find out if article has this submitter.
						
						$articleDao =& DAORegistry::getDAO('ArticleDAO');
						$article =& $articleDao->getArticle($articleId, $journalId);
						if ($article && $article->getUserId() == $userId && ($article->getStatus() == STATUS_QUEUED || $article->getStatus() == STATUS_PUBLISHED)) {
							 return $roleType;
						}
						break;
						
					case ROLE_ID_REVIEWER :
						// Find out if article currently has this reviewer.
						$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
						$reviewAssignments = $reviewAssignmentDao->getBySubmissionId($articleId);
						foreach ($reviewAssignments as $assignment) {
							if ($assignment->getReviewerId() == $userId) {
								//	REVIEWER ACCESS: If reviewers are not supposed to see list of authors, REVIEWER ONLY GETS TO SEE document-review.pdf version, which has all author information stripped.
								$settingsDao =& DAORegistry::getDAO('PluginSettingsDAO');
								if ($this->_pluginSetting($settingsDao, $journalId, 'reviewVersion') != true || $fileName == 'document-review.pdf')
									return $roleType; 
								continue; // We've matched to user so no more tries.
							}
						}

						break;
				}
			}
		}

		return false;
		
	}
	

	/**
	* Atom XML template displayed in response to a direct plugin URL document fetch() or refresh() call .
	* Never seen by OJS end users unless accessing a document directly by URL and there is a problem.  Useful for programmers to debug fetch issue.
	*
	* @param $msg string status indicating job success or error
	*/
	function _exitFetch($msg) {
		
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('journal', $journal);
		$templateMgr->assign('selfUrl', Request::getCompleteUrl() ); 
		$templateMgr->assign('dateUpdated', Core::getCurrentDate() );
		$templateMgr->assign('description', $msg);

		$templateMgr->display(dirname(__FILE__) . "/templates/fetch.tpl", "application/atom+xml");

		return true;

	}
	
	/**
	 * Uncompresses document.zip into an article's supplementary file markup folder.
	 * Unzip is triggered by URL call to /refreshGalley ; in OJS this is triggered by editor's file upload to Layout files area.  Article has a freshly generated supplementary documents.zip file.  Now into the /markup folder, extract all graphics, and the following:
	 *		manifest.xml
	 *		document.xml
	 *		document-new.pdf
	 *		document.html
	 *		document-review.pdf // doesn't have author list 
	 * and make galley links for the xml, pdf, and html files.
	 * WARNING: zip extractTo() function will fail completely if we include an entry in $extractFiles() that doesn't exist in zip manifest.
	 *
	 * @param $articleId int
	 * @param $suppFile object
	 * @param $suppFileDao object primed with article
	 * @param $galleyLinkFlag boolean signals creation of galley links
	 *
	 * @see _refresh()
	*/
	function _unzipSuppFile($articleId, &$suppFile, &$suppFileDao, $galleyLinkFlag) {
				
		// We need updated name. It was x.pdf or docx, now its y.zip:
		$suppFile =& $suppFileDao->getSuppFile($suppFile->getId() );
		$suppFileName = $suppFile->getFileName();
		
		$suppFolder = $this->_getSuppFolder($articleId);		
		$markupPath = $suppFolder.'/markup';

		//@unlink($suppFilePath."/markup"); //clear old file out.

		$zip = new ZipArchive;
		$res = $zip->open($suppFolder.'/'.$suppFileName);
		
		if ($res !== TRUE) {
			$this->_exitFetchStatus("Unzip can't open ".$suppFileName, $suppFile, $suppFileDao);
			return false;
		}

		// Ensure that we only extract "good" files.
		$candidates = array("manifest.xml","document.xml", "document-new.pdf", "document.html","document-review.pdf");
		$extractFiles = array();
		for ($ptr = 0; $ptr < count($candidates); $ptr++) {
			$candidate = $candidates[$ptr];
			if ( ($zip->locateName($candidate)) !== false)
				$extractFiles[] = $candidate;
		};
		
		// Get all graphics
		$extractSuffixes = array("png","jpg");
		for ($i = 0; $i < $zip->numFiles; $i++) {
			 $fileName = $zip->getNameIndex($i);
			 
			 if (in_array(strtolower(pathinfo($fileName, PATHINFO_EXTENSION)), $extractSuffixes)) 
			 	 $extractFiles[] = $fileName;
 
		 }
		// PHP docs say extractTo() returns false on failure, but its triggering this, and yet returning "No error" for $errorMsg below.
		if ($zip->extractTo($markupPath, $extractFiles) === FALSE) {
			$errorMsg = $zip->getStatusString();
			if ($errorMsg != 'No error') {
				$zip->close();
				$this->_exitFetchStatus( __('plugins.generic.markup.archive.bad_zip').$errorMsg, $suppFile, $suppFileDao);
				return false;
			}
		}
	
		$zip->close();
		
		if ($galleyLinkFlag) {
			//Now write contents of $suppFileId document.zip to [/var/www_uploads]/journals/[x]/articles/[y]/supp/[z]/markup/
			$this->_setupGalleyForMarkup($articleId, "document.html");
			$this->_setupGalleyForMarkup($articleId, "document-new.pdf");
			$this->_setupGalleyForMarkup($articleId, "document.xml");
		}
		
		return true;
	}
			
	// At what point are articles shunted into issues?
	/**
	* Return requested markup file to user's browser	
	* Eg. /var/www_uploads/journals/1/articles/2/supp/markup/document.html : text/html
	*
	* @param $folder 
	* @param $fileName 
	*/
	function _downloadFile($folder, $fileName) {

		// If supplementary file is a zip then it has been successfully downloaded, then the related documents have been unzipped to the supp / markup subfolder.
		$fileName = preg_replace('/[^[:alnum:]\._-]/', '', $fileName );
		if (!file_exists($folder.'/'.$fileName)) {
			return $this->_exitFetch('The requested file does not exist: '.$fileName);
		}
		$suffix = pathinfo($fileName, PATHINFO_EXTENSION);
			
		switch ($suffix) {

			case 'xml'  : $mimeType = 'application/xml'; break;
			case 'txt'  : $mimeType = 'text/plain'; break;
			case 'pdf'	: $mimeType = 'application/pdf'; break;
			case 'html' : $mimeType = 'text/html'; break;				
			case 'png' : $mimeType = 'image/png'; break;
			case 'jpg' : $mimeType = 'image/jpeg'; break;			
			case 'css' : $mimeType = 'text/css'; break;
			//case 'zip'	:  $mimeType = 'application/zip';break;
			default: 
				return false; //WARNING: File type not matched.
		}

		// use FileManager to send file to browser		
		$fileManager = new FileManager();
		$fileManager->downloadFile($folder. $fileName, $mimeType, true);
		
		return true;
		
	}
	
	
	/**
	* Populates an article's galley links with remote_urls.
	* CURRENTLY: no sensitivity to an article's revision/version. 
	* "/0/" is used as placeholder for future revision #
	*
	* @param $articleId int
	* @param $fileName string document.[xml | pdf | html] to link
	*
	* @return $galleyId int Id of new galley link created.
	*/
	function _setupGalleyForMarkup(&$articleId, $fileName) {
		
		$journal =& Request::getJournal();
		$journalId = $journal->getId();
		
		$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');
		$remoteURL = $this->_getMarkupURL($articleId). "/0/" . $fileName;

		$gals =& $galleyDao->getGalleysByArticle($articleId);
		foreach ($gals as $gal) {
			//Currently there is no method for querying a galley entry's remote_url field.  It isn't a "setting" in article_galley_settings.  So doing a loop here.
			if ($gal->getRemoteURL() == $remoteURL) {
				return true; //no need to overwrite	
    		}
    	}
		
		$galley = new ArticleGalley();
		$galley->setArticleId($articleId);
		$galley->setFileId(null);
		$suffix = pathinfo($fileName, PATHINFO_EXTENSION);
		$galley->setLabel(strtoupper($suffix));
		$galley->setLocale(AppLocale::getLocale()); //	'en_US'
		$galley->setRemoteURL( $remoteURL  );
	
		// Insert new galley link
		$galleyDao->insertGalley($galley);
		return $galley->getId();
		
	}

	/**
	* Delete specific file referenced in galley when galley item is deleted.
	* Hook call (ArticleGalleyDAO::deleteGalleyById) checks for remote url type of galley, if it matches this plugin's type of url, then we want to delete corresponding file(s).
	*
	* @param $hookName string
	* @param $params array [$galleyId]
	*
	* @see register()
	**/
	function _deleteGalley($hookName, $params) {
		$galleyId=$params[0];
		$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');
		$galley =& $galleyDao->getGalley($galleyId);
		$data = $galley->_data;
		$label = $data['label']; // HTML / PDF / XML
		$articleId = $data['submissionId'];
		$remoteURL = $data['remoteURL'];
		//if trailing url is clearly made by this Plugin ...
		if (preg_match("#plugin/markup/$articleId/[0-9]+/document(.html|-new.pdf|.xml)#", $remoteURL, $matches)) {
			switch ($matches[1]) {
				case ".xml": $suffix = '.xml'; break;
				case "-new.pdf": $suffix = '.pdf'; break;
				case ".html": $suffix = '.html,.jpg,.png'; break;
				default: return false; //shouldn't occur
			}
			$suppFolder = $this->_getSuppFolder($articleId).'/markup/document*{'.$suffix.'}';
			$glob = glob($suppFolder,GLOB_BRACE);
			foreach ($glob as $g) {unlink($g);}
		
		}
		return false;	
	}

	/**
	 * Delete all galleys for an article 
	 * Triggered when refresh() called and galley generation flag= false
	 *
	 * @param $articleId int
	 *
	 * @see _refresh()
	 */
	function _deleteGalleys($articleId) {
		// Delete all markup files
		$suppFolder = $this->_getSuppFolder($articleId).'/markup/*';
		$glob = glob($suppFolder);
		foreach ($glob as $g) {unlink($g);}
		
		// Delete galley links
		$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');
		$gals =& $galleyDao->getGalleysByArticle($articleId);
		foreach ($gals as $galley) {
			$data = $galley->_data;
			//if trailing url is clearly made by this Plugin ...
			if (preg_match("#plugin/markup/$articleId/[0-9]+/#", $data['remoteURL'])) {
				$galleyDao -> deleteGalley($galley);
			}
		}
	}
	
	/**
	 * Return server's folder path that points to an article's supplementary file folder.
	 *
	 * @param $articleId int
	 *
	 * @return string supplementary file folder path.
	*/
	function _getSuppFolder(&$articleId) {
		import('classes.file.ArticleFileManager');	
		$articleFileManager = new ArticleFileManager($articleId);
		//fileStageToPath : see classes/file/ArticleFileManager.inc.php
		return ($articleFileManager->filesDir) . ($articleFileManager->fileStageToPath( ARTICLE_FILE_SUPP )) ;
	}

	/**
	 * Return plugin root url that provides file access for a given article within context of current journal.
	 *
	 * @param $articleId int
	 *
	 * @return string URL
	*/	
	function _getMarkupURL($articleId) {
		$journal =& Request::getJournal();
		return $journal->getUrl() . '/gateway/plugin/markup/'.$articleId; 
	}
	
}
?>
