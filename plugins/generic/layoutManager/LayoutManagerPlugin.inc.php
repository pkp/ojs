<?php

/**
 * LayoutManager.inc.php
 *
 * Copyright (c) 2003-2006 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * Web Feeds plugin class
 *
 * $Id$
 */
 
import('classes.plugins.GenericPlugin');

class LayoutManager extends GenericPlugin {

	function getName() {
		return 'LayoutManager';
	}
	
	function getDisplayName() {
		return Locale::translate('plugins.generic.layoutmanager.displayName');
	}
    
	function getDescription() {
		return Locale::translate('plugins.generic.layoutmanager.description');
	}   

	function register($category, $path) {
		if (!Config::getVar('general', 'installed')) return false;
		if (parent::register($category, $path)) {
			HookRegistry::register('TemplateManager::display',array(&$this, 'callbackAddLeftSidebarCss'));
			HookRegistry::register('Templates::Common::Header::sidebar', array(&$this, 'callbackChangeSidebar'));

			// Add the necessary Smarty functions. FIXME?
			$functions = array(
				'checkall', 'copy', 'count_chars', 'img_move', 'init', 'moveall', 'movedown', 'move',
				'moveup', 'remove', 'rename', 'save', 'selectall'
			);
			$smartyFunctionPrefix = 'formtool_';
			$templateMgr =& TemplateManager::getManager();

			foreach ($functions as $function) {
				require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'smarty' . DIRECTORY_SEPARATOR . 'function.' . $smartyFunctionPrefix . $function . '.php');
				$templateMgr->register_function($smartyFunctionPrefix . $function, 'smarty_function_' . $smartyFunctionPrefix . $function);
			}

			$this->addLocaleData();
			return true;
		}
		return false;
	}

	/* add a stylesheet that shrinks the main frame
	 * and moves it to the right when using 2 sidebars
	 */
	function callbackAddLeftSidebarCss ($hookName, $args) {
		if ( $this->getEnabled() ) {
			$templateMgr =& $args[0];
			$journal =& Request::getJournal();
			$journalId = $journal->getJournalId();

			/* 
			 * load if in 3 column layout mode
			 * but not on article views since they have no sidebar
			 */
			if ( $this->getSetting( $journalId, 'layout') == 'two' && Request::getRequestedPage() != 'article' )   {
				/* add some CSS to shuffle around columns and allow 3 columns */
				$cssUrl = $templateMgr->get_template_vars('baseUrl').'/plugins/generic/layoutManager/threeColumnLayout.css';
				$templateMgr->addStyleSheet($cssUrl);
			}

			// TODO: if we are displaying an article, do so inline
			// this should be an option in the settings

		}
		return false;
	}

	/* intercept the sidebar display and create
	 * a new sidebar template that has the blocks in the 
	 * order specified by the user 
	 */
	function callbackChangeSidebar($hookName, $args) {
		if ( $this->getEnabled() ) {
	 		$templateMgr =& $args[0];
	 		$template = & $args[1];
	 		$sendContentType = &$args[2];
	 		$charset = &$args[3]; 
	 		$output = &$args[4];
		
			$journal =& Request::getJournal();
			$journalId = $journal->getJournalId();			

			// $blocks has all the information about 
			// all the block sections (whether enabled or not)								
			// including the template file paths.  They are not ordered here.
		 	$blocks = $this->getSetting($journalId, 'blocks');				
		 	
		 	// left and right block order has an array of the enabled blocks
		 	// the array has the elements in the order they are to be displayed
			if ( $templateMgr->get_template_vars('leftBlockOrder') != '' && $templateMgr->get_template_vars('rightBlockOrder') != '' ) {
				$leftBlockOrder = $templateMgr->get_template_vars('leftBlockOrder');
				$rightBlockOrder = $templateMgr->get_template_vars('rightBlockOrder');
			} else {
			 	$leftBlockOrder = $this->getSetting( $journalId, 'leftBlockOrder' );
			 	$rightBlockOrder = $this->getSetting( $journalId, 'rightBlockOrder' );
			}
		 			 	
			if ( $blocks == null || count($blocks) < 6 ) {
				$this->setDefaultBlocks($journalId, $blocks, $rightBlockOrder);
			}
							
			if ($leftBlockOrder) {
				foreach ( $leftBlockOrder as $blockName ) {
					$leftSidebarTemplates[$blockName] = $blocks[$blockName][1];
				}
			}

			if ($rightBlockOrder) {
				foreach ( $rightBlockOrder as $blockName ) {
					$rightSidebarTemplates[$blockName] = $blocks[$blockName][1];
				}
			}	
			
			$templateMgr->assign('leftSidebarTemplates', $leftSidebarTemplates);
			$templateMgr->assign('rightSidebarTemplates', $rightSidebarTemplates);

			if ( $this->getSetting( $journalId, 'layout') == 'two' ) {
				$templateMgr->assign('threeColumns', true);
			}

			$templateMgr->display($this->getTemplatePath().'templates/layoutmanagertemplate.tpl', '', '');
					
			return true;
		}

		return false;
	}

	function setDefaultBlocks( $journalId, &$blocks, &$rightBlockOrder ) {
		
		// have to strip the file: from the beginning for is_dir to work correctly
		$templateDir = str_replace('file:', '', $this->getTemplatePath().'templates/');
 	
		// Open a known directory, and proceed to read its contents
		// Until there is a template plugin class, we have to grab the templates
		// from the directory.  Ideally this will eventually be replaced by having a lot of 
		// template plugins, each which registers itself using the registerBlock function
		if (is_dir($templateDir)) {
		   if ($dh = opendir($templateDir)) {
		       while (($file = readdir($dh)) !== false) {
			  			       	
		       	  if ( is_dir($templateDir.$file) &&  substr($file, 0, 1) != '.' ) {
		       	  	if ( is_file($templateDir.$file.'/weight.txt') ) {
		       	  		$weight = (int) preg_replace('/[^0-9]/', '', file_get_contents($templateDir.$file.'/weight.txt'));
		       	  	} else {
		       	  		$weight = $weight + 1;
		       	  	}  
		           	$blocks[$file] = array($file, $this->getTemplatePath().'templates/'.$file.'/sidebar.tpl', true, $weight ) ;
		           	
		       	  }
		       }
		       closedir($dh);
		   }
		}

		uasort( $blocks, array(&$this, "blockOrderCompare") );
		
		foreach ($blocks as $block) {
			$rightBlockOrder[] = $block[0];			
		}
				
		$this->updateSetting($journalId, 'blocks', $blocks);
		$this->updateSetting($journalId, 'rightBlockOrder', $rightBlockOrder);

		// by default everything appears on the right
		$this->updateSetting($journalId, 'leftBlockOrder', array());

		return true;
	}
	
	function blockOrderCompare($a, $b) {
	
		$a = $a[3];
		$b = $b[3];
		
		if ($a == $b) {
       		return 0;
   		}
   		return ($a < $b) ? -1 : 1;

	}	
	
	/**
	 * Determine whether or not this plugin is enabled.
	 */
	function getEnabled() {
		$journal = &Request::getJournal();
		if (!$journal) return false;
		return $this->getSetting($journal->getJournalId(), 'enabled');
	}

	/**
	 * Set the enabled/disabled state of this plugin
	 */
	function setEnabled($enabled) {
		$journal = &Request::getJournal();
		if ($journal) {
			$journalId = $journal->getJournalId();
			$this->updateSetting($journalId, 'enabled', $enabled ? true : false);
			if ( !$this->getSetting($journalId, 'layout') ) {
				$this->updateSetting($journalId, 'layout','one' );
			}			
			return true;
		}
		return false;
	}
	
	/**
	 * Display verbs for the management interface.
	 */
	function getManagementVerbs() {
		$verbs = array();
		if ($this->getEnabled()) {
			$verbs[] = array(
				'disable',
				Locale::translate('manager.plugins.disable')
			);
			$verbs[] = array(
				'settings', 
				Locale::translate('plugins.generic.layoutmanager.settings')
			);
		} else {
			$verbs[] = array(
				'enable',
				Locale::translate('manager.plugins.enable')
			);
		} 
		return $verbs;
	}
	
	/* Other plugins can call this function in order to specify a template they want displayed
	 * 
	 * PluginName must be unique - Does not need to necessarily be the Name of the plugin, just something to be refered by
	 * $templatePath - the template that will be displayed
	 * $order - order is used to decide where the default location will be for a plugin when it registers
	 */
	function registerBlock( $pluginName, $templatePath, $order ) {
			$journal =& Request::getJournal();
			$journalId = $journal->getJournalId();			
		
		 	$blocks = $this->getSetting($journalId, 'blocks');
		 	$rightBlockOrderTemp = $this->getSetting( $journalId, 'rightBlockOrder' );		 	
		 	
		 	// add to the array of blocks	
			$blocks[$pluginName] = array ( $pluginName, $templatePath, true, $order );

			// make a copy of the array (one by one, to preserve order) 
			// and inject the pluginName when necessary
			$added = false;

			// figure out the relative weight 
			// (e.g. a weight of 4 will go 40% of the way down the list
			if ( count($rightBlockOrderTemp) > 0 ) {
				$inc = 1 / count($rightBlockOrderTemp);
				$count = $inc;
				$order = $order * $inc; 
				foreach ( $rightBlockOrderTemp as $blockName ) {
					if ( !$added &&  $count > $order) {
						$rightBlockOrder[] = $pluginName;
						$added = true;
					} 
					$rightBlockOrder[] = $blockName;					
					$count += $inc;
				}
			} else {
				$rightBlockOrder[] = $pluginName;
			}
			
			// if it hasn't been added, its because it goes at the end
			if ( !$added )
				$rightBlockOrder[] = $pluginName;				
			
			$this->updateSetting($journalId, 'blocks', $blocks);		
			$this->updateSetting($journalId, 'rightBlockOrder', $rightBlockOrder);			
	}

	/* Plugins can disable themselves so that the template manager does not attempt to 
	 * display their templates once they have been disabled
	 * 
	 * $pluginName - The exact name that was passed in when registerBlock was called
	 */
	function deRegisterBlock( $pluginName ) {
			$journal =& Request::getJournal();
			$journalId = $journal->getJournalId();			
		
		 	$blocks = $this->getSetting($journalId, 'blocks');
		 	// delete it from the $blocks array
			unset($blocks[$pluginName]);
			
		 	$enabledBlockOrderTemp = $this->getSetting( $journalId, 'leftBlockOrder' );
		 	$leftBlockOrder = array();
			// make a copy of the enabled array without this one
			// this ensures the order is preserved for others without
			// the need for weird array manipulation
			foreach ( $enabledBlockOrderTemp as $blockName ) {
				if ( $blockName != $pluginName )
					$leftBlockOrder[] = $blockName;
			}

		 	$enabledBlockOrderTemp = $this->getSetting( $journalId, 'rightBlockOrder' );
		 	$rightBlockOrder = array();
			// and make a copy of the enabled array without this one
			// this ensures the order is preserved for others without
			// the need for weird array manipulation
			foreach ( $enabledBlockOrderTemp as $blockName ) {
				if ( $blockName != $pluginName )
					$rightBlockOrder[] = $blockName;
			}

		 			
			$this->updateSetting($journalId, 'blocks', $blocks);		
			$this->updateSetting($journalId, 'leftBlockOrder', $leftBlockOrder);
			$this->updateSetting($journalId, 'rightBlockOrder', $rightBlockOrder);
	}
		
	/**
	 * Perform management functions
	 */
	function manage($verb, $args) {
		$returner = true;
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));

		// add a CSS to highlight the blocks for ease of display
		$cssUrl = $templateMgr->get_template_vars('baseUrl').'/plugins/generic/layoutManager/LayoutSettings.css';
		$templateMgr->addStyleSheet($cssUrl);

		$journal =& Request::getJournal();
		$journalId = $journal->getJournalId();	
		
		switch ($verb) {
			// process the settings form ( for moving blocks around )
			case 'settings':
//				$journal =& Request::getJournal();
				$this->import('LayoutManagerSettingsForm');
				$form =& new LayoutManagerSettingsForm($this, $journal->getJournalId());
				if ( Request::getUserVar('blockSelectRight_save') || Request::getUserVar('blockSelectLeft_save')) {
					$form->readInputData();
					if ($form->validate()) {
						// preview == 1 means we should stay on the same page
						// and initData and execute should not use plugin settings 
						// so changes are not permanent
						if ( Request::getUserVar('preview') == 1 ) {
							$form->execute( true );
							$form->initData( true );
							$form->display();
						} else {
							$form->execute( false );							
							Request::redirect(null, null, 'plugins');
						}
						
					} else {
						$form->display();
					}
				} else {
					$form->initData();
					$form->display();
				}
				$returner = true;
				break;
			// switch between the two and three column layouts
			case 'changeLayout':
				$journal =& Request::getJournal();
			    $layout = $this->getSetting($journalId, 'layout');
			    if ( $layout == 'two' ) {
			    		// when moving to a 2 column layout all the items in the left
			    		// have to be moved to the right
			    		$this->updateSetting($journalId, 'layout', 'one' );
			    		$this->updateSetting($journalId, 'rightBlockOrder', array_merge($this->getSetting( $journalId, 'rightBlockOrder' ) , $this->getSetting( $journalId, 'leftBlockOrder' ) ) );
			    		$this->updateSetting($journalId, 'leftBlockOrder', null);
			    		
			    } else {
			    		$this->updateSetting($journalId, 'layout', 'two' );			    			
			    }
			    // stay on the settings form
				Request::redirect(null, 'manager', 'plugin', array('generic', $this->getName(), 'settings'));			
				break;
			case 'enable':
				$this->setEnabled(true);
				$returner = false;
				break;
			case 'disable':
				$this->setEnabled(false);
				$returner = false;
				break;	
		}
		
		return $returner;		
	}

}
?>
