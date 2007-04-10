<?php

/**
 * GoogleScholarSettingsForm.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * Form for journal managers to modify Google Scholar gateway settings
 *
 * $Id$
 */

import('form.Form');

class LayoutManagerSettingsForm extends Form {
	/** @var $journalId int */
	var $journalId;

	/** @var $plugin object */
	var $plugin;
	
	/**
	 * Constructor
	 * @param $journalId int
	 */
	function LayoutManagerSettingsForm(&$plugin, $journalId) {
        $templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pluginFileUrl', $templateMgr->get_template_vars('baseUrl').'/plugins/generic/layoutManager');		
		$templateMgr->assign('layoutManagerPluginEdit', true);
				
		if ( $plugin->getSetting($journalId, 'layout') == 'one' ) {
			$template = 'twoColumnSettingsForm.tpl';
		} else { 
			$template = 'threeColumnSettingsForm.tpl';
		} 		
		parent::Form($plugin->getTemplatePath() . $template);
	
		$this->journalId = $journalId;
		$this->plugin =& $plugin;

	}
	
	/**
	 * Initialize form data from current group.
	 */
	function initData( $preview = false) {
		$journalId = $this->journalId;
		$plugin =& $this->plugin;

        $templateMgr = &TemplateManager::getManager();
		
		if ( $preview ) {
			error_log('getData');
			$blocks = $templateMgr->get_template_vars('blocks');
			$left = $templateMgr->get_template_vars('leftBlockOrder');
			$right = $templateMgr->get_template_vars('rightBlockOrder');			
		} else {
			$blocks = $plugin->getSetting($journalId, 'blocks');
			$left = $plugin->getSetting($journalId, 'leftBlockOrder');
			$right = $plugin->getSetting($journalId, 'rightBlockOrder');
		} 

		/* blocks array
		 * 0 - name
		 * 1 - template
		 * 2 - enabled
		 * 3 - order
		 */
		
		$leftBlockEnabled = array();
		$rightBlockEnabled = array();
		$blockDisabled = array();

		// Disabled blocks
		foreach ( $blocks as $block ) {
			$blockNames[] = $block[0];
			$blockTemplates[] = $block[1];
			if ( $block[2] == false ) {
				$blockDisabled[] = $block[0];
			}			
		}        
		
		// set the enabled for the left (to preserve ordering)
		if ( $left ) {
			foreach ( $left as $blockName ) {
				$leftBlockEnabled[] = $blockName;
			}
		}
		
		// set the enabled for the right (to preserve ordering)
		if ( $right ) {
			foreach ( $right as $blockName ) {
				$rightBlockEnabled[] = $blockName;
			}
		}

		// arrays to populate selects		
		$templateMgr->assign('blockDisabled', $blockDisabled );
		$templateMgr->assign('leftBlockEnabled', $leftBlockEnabled );
		$templateMgr->assign('rightBlockEnabled', $rightBlockEnabled );

		// comma separated values for hidden input
		$templateMgr->assign('blockSelectLeft_save', implode(',' , $leftBlockEnabled));
		$templateMgr->assign('blockSelectRight_save', implode(',' , $rightBlockEnabled));
		
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('blockSelectLeft_save', 'blockSelectRight_save', 'preview'));
	}
	
	/**
	 * Save group. 
	 */
	function execute( $preview = false ) {
		$plugin =& $this->plugin;
		$journalId = $this->journalId;
		
		// make sure $saveLeft and $saveRight are not made from an empty string
		if ( $this->getData('blockSelectLeft_save') != '' )
			$saveLeft = explode( ',' , $this->getData('blockSelectLeft_save') );
		else
			$saveLeft = array();

		if ( $this->getData('blockSelectRight_save') != '' )
			$saveRight = explode( ',' , $this->getData('blockSelectRight_save') );
		else
			$saveRight = array();

		$blocks = $plugin->getSetting($journalId, 'blocks');
		
		// set them all to DISABLED
		$iterBlocks = $blocks;
		foreach ( $iterBlocks as $block) {
			// this weird syntax is because PHP 4 does foreachs on a copy of the array
			// and so to change the original we need this weird stuff
			$blocks[$block[0]][2] = 0;
		}

		// Now enable all the ones that from the form
		// this is the easiest way to make sure the $blocks array is always up-to-date
		foreach ( $saveLeft as $blockName ) {	
			$blocks[$blockName][2] = 1;
		}
		foreach ( $saveRight as $blockName ) {	
			$blocks[$blockName][2] = 1;
		}
		
		if ( $preview ) {
        		$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign('blocks', $blocks);
			$templateMgr->assign('leftBlockOrder', $saveLeft);
			$templateMgr->assign('rightBlockOrder', $saveRight);			
		} else {
			$plugin->updateSetting($journalId, 'blocks', $blocks );
			$plugin->updateSetting($journalId, 'leftBlockOrder', $saveLeft );
			$plugin->updateSetting($journalId, 'rightBlockOrder', $saveRight );
		}
		
	}
		
}
?>
