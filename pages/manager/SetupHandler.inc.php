<?php

/**
 * SetupHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.manager
 *
 * Handle requests for journal setup functions. 
 *
 * $Id$
 */

class SetupHandler extends ManagerHandler {
	
	/**
	 * Display journal setup form for the selected step.
	 * Displays setup index page if a valid step is not specified.
	 * @param $args array optional, if set the first parameter is the step to display
	 */
	function setup($args) {
		parent::validate();
		parent::setupTemplate(true);
		
		$step = isset($args[0]) ? (int) $args[0] : 0;
		
		if ($step >= 1 && $step <= 5) {
			
			$formClass = "JournalSetupStep{$step}Form";
			import("manager.form.setup.$formClass");
			
			$setupForm = &new $formClass();
			$setupForm->initData();
			$setupForm->display();
		
		} else {
			$templateMgr = &TemplateManager::getManager();
			$templateMgr->display('manager/setup/index.tpl');
		}
	}
	
	/**
	 * Save changes to journal settings.
	 * @param $args array first parameter is the step being saved
	 */
	function saveSetup($args) {
		parent::validate();
		
		$step = isset($args[0]) ? (int) $args[0] : 0;
		
		if ($step >= 1 && $step <= 5) {

			parent::setupTemplate(true);
			
			$formClass = "JournalSetupStep{$step}Form";
			import("manager.form.setup.$formClass");
			
			$setupForm = &new $formClass();
			$setupForm->readInputData();
			
			// Check for any special cases before trying to save
			switch ($step) {
				case 1:
					if (Request::getUserVar('addSponsor')) {
						// Add a sponsor
						$editData = true;
						$sponsors = $setupForm->getData('sponsors');
						array_push($sponsors, array());
						$setupForm->setData('sponsors', $sponsors);
						
					} else if (($delSponsor = Request::getUserVar('delSponsor')) && count($delSponsor) == 1) {
						// Delete a sponsor
						$editData = true;
						list($delSponsor) = array_keys($delSponsor);
						$delSponsor = (int) $delSponsor;
						$sponsors = $setupForm->getData('sponsors');
						array_splice($sponsors, $delSponsor, 1);
						$setupForm->setData('sponsors', $sponsors);
						
					} else if (Request::getUserVar('addContributor')) {
						// Add a contributor
						$editData = true;
						$contributors = $setupForm->getData('contributors');
						array_push($contributors, array());
						$setupForm->setData('contributors', $contributors);
						
					} else if (($delContributor = Request::getUserVar('delContributor')) && count($delContributor) == 1) {
						// Delete a contributor
						$editData = true;
						list($delContributor) = array_keys($delContributor);
						$delContributor = (int) $delContributor;
						$contributors = $setupForm->getData('contributors');
						array_splice($contributors, $delContributor, 1);
						$setupForm->setData('contributors', $contributors);
					}
					break;
					
				case 2:
					if (Request::getUserVar('addCustomAboutItem')) {
						// Add a custom about item
						$editData = true;
						$customAboutItems = $setupForm->getData('customAboutItems');
						array_push($customAboutItems, array());
						$setupForm->setData('customAboutItems', $customAboutItems);
						
					} else if (($delCustomAboutItem = Request::getUserVar('delCustomAboutItem')) && count($delCustomAboutItem) == 1) {
						// Delete a custom about item
						$editData = true;
						list($delCustomAboutItem) = array_keys($delCustomAboutItem);
						$delCustomAboutItem = (int) $delCustomAboutItem;
						$customAboutItems = $setupForm->getData('customAboutItems');
						array_splice($customAboutItems, $delCustomAboutItem, 1);
						$setupForm->setData('customAboutItems', $customAboutItems);
					}
					break;
					
				case 3:
					if (Request::getUserVar('addChecklist')) {
						// Add a checklist item
						$editData = true;
						$checklist = $setupForm->getData('submissionChecklist');
						array_push($checklist, array('order' => $checklist[count($checklist)-1]['order']+1));
						$setupForm->setData('submissionChecklist', $checklist);
						
					} else if (($delChecklist = Request::getUserVar('delChecklist')) && count($delChecklist) == 1) {
						// Delete a checklist item
						$editData = true;
						list($delChecklist) = array_keys($delChecklist);
						$delChecklist = (int) $delChecklist;
						$checklist = $setupForm->getData('submissionChecklist');
						array_splice($checklist, $delChecklist, 1);
						$setupForm->setData('submissionChecklist', $checklist);
					}
					
					if (!isset($editData)) {
						// Reorder checklist items
						$checklist = $setupForm->getData('submissionChecklist');
						usort($checklist, create_function('$a,$b','return $a[\'order\'] == $b[\'order\'] ? 0 : ($a[\'order\'] < $b[\'order\'] ? -1 : 1);'));
						$setupForm->setData('submissionChecklist', $checklist);
					}
					break;
					
				case 5:	
					if (Request::getUserVar('uploadJournalHeaderTitleImage')) {
						$editData = true;
						$setupForm->uploadImage('journalHeaderTitleImage');					
					}			
					else if (Request::getUserVar('deleteJournalHeaderTitleImage')) {
						$editData = true;
						$setupForm->deleteImage('journalHeaderTitleImage');
					}
					else if (Request::getUserVar('uploadJournalHeaderLogoImage')) {
						$editData = true;
						$setupForm->uploadImage('journalHeaderLogoImage');					
					}			
					else if (Request::getUserVar('deleteJournalHeaderLogoImage')) {
						$editData = true;
						$setupForm->deleteImage('journalHeaderLogoImage');
					}
					else if (Request::getUserVar('uploadPageHeaderTitleImage')) {
						$editData = true;
						$setupForm->uploadImage('pageHeaderTitleImage');					
					}			
					else if (Request::getUserVar('deletePageHeaderTitleImage')) {
						$editData = true;
						$setupForm->deleteImage('pageHeaderTitleImage');
					}
					else if (Request::getUserVar('uploadPageHeaderLogoImage')) {
						$editData = true;
						$setupForm->uploadImage('pageHeaderLogoImage');					
					}
					else if (Request::getUserVar('deletePageHeaderLogoImage')) {
						$editData = true;
						$setupForm->deleteImage('pageHeaderLogoImage');
					}			
					else if (Request::getUserVar('uploadHomepageImage')) {
						$editData = true;
						$setupForm->uploadImage('homepageImage');
					}
					else if (Request::getUserVar('deleteHomepageImage')) {
						$editData = true;
						$setupForm->deleteImage('homepageImage');
					}
					else if (Request::getUserVar('uploadJournalStyleSheet')) {
						$editData =true;
						$setupForm->uploadStyleSheet('journalStyleSheet');
					}
					else if (Request::getUserVar('deleteJournalStyleSheet')) {
						$editData = true;
						$setupForm->deleteImage('journalStyleSheet');
					}							
					else if (Request::getUserVar('addNavItem')) {
						// Add a navigation bar item
						$editData = true;
						$navItems = $setupForm->getData('navItems');
						array_push($navItems,array());
						$setupForm->setData('navItems', $navItems);
						
					} else if (($delNavItem = Request::getUserVar('delNavItem')) && count($delNavItem) == 1) {
						// Delete a  navigation bar item
						$editData = true;
						list($delNavItem) = array_keys($delNavItem);
						$delNavItem = (int) $delNavItem;
						$navItems = $setupForm->getData('navItems');
						array_splice($navItems, $delNavItem, 1);		
						$setupForm->setData('navItems', $navItems);
					}				
			}
			
			if (!isset($editData) && $setupForm->validate()) {
			$journal = &Request::getJournal();
				$setupForm->execute();
				
				$templateMgr = &TemplateManager::getManager();
				$templateMgr->assign('setupStep', $step);
				$templateMgr->assign('leftSidebarTemplate', 'manager/setup/setupSidebar.tpl');
				$templateMgr->display('manager/setup/settingsSaved.tpl');
			
			} else {
				$setupForm->display();
			}
		
		} else {
			Request::redirect('manager/setup');
		}
	}
	
}
?>
