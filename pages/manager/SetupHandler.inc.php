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
	 * @param $args array optional, if set the first paremeter is the step to display
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
			switch($step) {
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
				
			}
			
			if (!isset($editData) && $setupForm->validate()) {
				$setupForm->execute();
				
				$templateMgr = &TemplateManager::getManager();
				$templateMgr->assign('step', $step);
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
