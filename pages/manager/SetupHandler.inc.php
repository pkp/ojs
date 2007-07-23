<?php

/**
 * @file SetupHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.manager
 * @class SetupHandler
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
			$templateMgr->assign('helpTopicId','journal.managementPages.setup');
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
					if (Request::getUserVar('addReviewerDatabaseLink')) {
						// Add a reviewer database link
						$editData = true;
						$reviewerDatabaseLinks = $setupForm->getData('reviewerDatabaseLinks');
						array_push($reviewerDatabaseLinks, array());
						$setupForm->setData('reviewerDatabaseLinks', $reviewerDatabaseLinks);
						
					} else if (($delReviewerDatabaseLink = Request::getUserVar('delReviewerDatabaseLink')) && count($delReviewerDatabaseLink) == 1) {
						// Delete a custom about item
						$editData = true;
						list($delReviewerDatabaseLink) = array_keys($delReviewerDatabaseLink);
						$delReviewerDatabaseLink = (int) $delReviewerDatabaseLink;
						$reviewerDatabaseLinks = $setupForm->getData('reviewerDatabaseLinks');
						array_splice($reviewerDatabaseLinks, $delReviewerDatabaseLink, 1);
						$setupForm->setData('reviewerDatabaseLinks', $reviewerDatabaseLinks);
					}
					break;
					
				case 3:
					if (Request::getUserVar('addChecklist')) {
						// Add a checklist item
						$editData = true;
						$checklist = $setupForm->getData('submissionChecklist');
						if (!is_array($checklist)) {
							$checklist = array();
							$lastOrder = 0;
						} else {
							$lastOrder = $checklist[count($checklist)-1]['order'];
						}
						array_push($checklist, array('order' => $lastOrder+1));
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
						if (is_array($checklist)) {
							usort($checklist, create_function('$a,$b','return $a[\'order\'] == $b[\'order\'] ? 0 : ($a[\'order\'] < $b[\'order\'] ? -1 : 1);'));
						}
						$setupForm->setData('submissionChecklist', $checklist);
					}
					break;

				case 4:
					$journal =& Request::getJournal();
					$templates = $journal->getSetting('templates');
					import('file.JournalFileManager');
					$journalFileManager =& new JournalFileManager($journal);
					if (Request::getUserVar('addTemplate')) {
						// Add a layout template
						$editData = true;
						if (!is_array($templates)) $templates = array();
						$templateId = count($templates);
						$originalFilename = $_FILES['template-file']['name'];
						$fileType = $_FILES['template-file']['type'];
						$filename = "template-$templateId." . $journalFileManager->parseFileExtension($originalFilename);
						$journalFileManager->uploadFile('template-file', $filename);
						$templates[$templateId] = array(
							'originalFilename' => $originalFilename,
							'fileType' => $fileType,
							'filename' => $filename,
							'title' => Request::getUserVar('template-title')
						);
						$journal->updateSetting('templates', $templates);
					} else if (($delTemplate = Request::getUserVar('delTemplate')) && count($delTemplate) == 1) {
						// Delete a template
						$editData = true;
						list($delTemplate) = array_keys($delTemplate);
						$delTemplate = (int) $delTemplate;
						$template = $templates[$delTemplate];
						$filename = "template-$delTemplate." . $journalFileManager->parseFileExtension($template['originalFilename']);
						$journalFileManager->deleteFile($filename);
						array_splice($templates, $delTemplate, 1);
						$journal->updateSetting('templates', $templates);
					}
					$setupForm->setData('templates', $templates);
					break;
				case 5:	
					if (Request::getUserVar('uploadHomeHeaderTitleImage')) {
						if ($setupForm->uploadImage('homeHeaderTitleImage')) {
							$editData = true;
						} else {
							$setupForm->addError('homeHeaderTitleImage', 'manager.setup.homeTitleImageInvalid');
						}

					} else if (Request::getUserVar('deleteHomeHeaderTitleImage')) {
						$editData = true;
						$setupForm->deleteImage('homeHeaderTitleImage');
						
					} else if (Request::getUserVar('uploadHomeHeaderLogoImage')) {
						if ($setupForm->uploadImage('homeHeaderLogoImage')) {
							$editData = true;
						} else {
							$setupForm->addError('homeHeaderLogoImage', 'manager.setup.homeHeaderImageInvalid');
						}

					} else if (Request::getUserVar('deleteHomeHeaderLogoImage')) {
						$editData = true;
						$setupForm->deleteImage('homeHeaderLogoImage');
						
					} else if (Request::getUserVar('uploadPageHeaderTitleImage')) {
						if ($setupForm->uploadImage('pageHeaderTitleImage')) {
							$editData = true;
						} else {
							$setupForm->addError('pageHeaderTitleImage', 'manager.setup.pageHeaderTitleImageInvalid');
						}

					} else if (Request::getUserVar('deletePageHeaderTitleImage')) {
						$editData = true;
						$setupForm->deleteImage('pageHeaderTitleImage');
						
					} else if (Request::getUserVar('uploadPageHeaderLogoImage')) {
						if ($setupForm->uploadImage('pageHeaderLogoImage')) {
							$editData = true;
						} else {
							$setupForm->addError('pageHeaderLogoImage', 'manager.setup.pageHeaderLogoImageInvalid');
						}

					} else if (Request::getUserVar('deletePageHeaderLogoImage')) {
						$editData = true;
						$setupForm->deleteImage('pageHeaderLogoImage');
						
					} else if (Request::getUserVar('uploadHomeHeaderTitleImageAlt1')) {
						if ($setupForm->uploadImage('homeHeaderTitleImageAlt1')) {
							$editData = true;
						} else {
							$setupForm->addError('homeHeaderTitleImageAlt1', 'manager.setup.homeHeaderTitleImageAlt1Invalid');
						}

					} else if (Request::getUserVar('deleteHomeHeaderTitleImageAlt1')) {
						$editData = true;
						$setupForm->deleteImage('homeHeaderTitleImageAlt1');
						
					} else if (Request::getUserVar('uploadHomeHeaderLogoImageAlt1')) {
						if ($setupForm->uploadImage('homeHeaderLogoImageAlt1')) {
							$editData = true;
						} else {
							$setupForm->addError('homeHeaderLogoImageAlt1', 'manager.setup.homeHeaderLogoImageAlt1Invalid');
						}

					} else if (Request::getUserVar('deleteHomeHeaderLogoImageAlt1')) {
						$editData = true;
						$setupForm->deleteImage('homeHeaderLogoImageAlt1');
						
					} else if (Request::getUserVar('uploadPageHeaderTitleImageAlt1')) {
						if ($setupForm->uploadImage('pageHeaderTitleImageAlt1')) {
							$editData = true;
						} else {
							$setupForm->addError('pageHeaderTitleImageAlt1', 'manager.setup.pageHeaderTitleImageAlt1Invalid');
						}

					} else if (Request::getUserVar('deletePageHeaderTitleImageAlt1')) {
						$editData = true;
						$setupForm->deleteImage('pageHeaderTitleImageAlt1');
						
					} else if (Request::getUserVar('uploadPageHeaderLogoImageAlt1')) {
						if ($setupForm->uploadImage('pageHeaderLogoImageAlt1')) {
							$editData = true;
						} else {
							$setupForm->addError('pageHeaderLogoImageAlt1', 'manager.setup.pageHeaderLogoImageAlt1Invalid');
						}

					} else if (Request::getUserVar('deletePageHeaderLogoImageAlt1')) {
						$editData = true;
						$setupForm->deleteImage('pageHeaderLogoImageAlt1');
						
					} else if (Request::getUserVar('uploadHomeHeaderTitleImageAlt2')) {
						if ($setupForm->uploadImage('homeHeaderTitleImageAlt2')) {
							$editData = true;
						} else {
							$setupForm->addError('homeHeaderTitleImageAlt2', 'manager.setup.homeHeaderTitleImageAlt2Invalid');
						}

					} else if (Request::getUserVar('deleteHomeHeaderTitleImageAlt2')) {
						$editData = true;
						$setupForm->deleteImage('homeHeaderTitleImageAlt2');
						
					} else if (Request::getUserVar('uploadHomeHeaderLogoImageAlt2')) {
						if ($setupForm->uploadImage('homeHeaderLogoImageAlt2')) {
							$editData = true;
						} else {
							$setupForm->addError('homeHeaderLogoImageAlt2', 'manager.setup.homeHeaderLogoImageAlt2Invalid');
						}

					} else if (Request::getUserVar('deleteHomeHeaderLogoImageAlt2')) {
						$editData = true;
						$setupForm->deleteImage('homeHeaderLogoImageAlt2');
						
					} else if (Request::getUserVar('uploadPageHeaderTitleImageAlt2')) {
						if ($setupForm->uploadImage('pageHeaderTitleImageAlt2')) {
							$editData = true;
						} else {
							$setupForm->addError('pageHeaderTitleImageAlt2', 'manager.setup.pageHeaderTitleImageAlt2Invalid');
						}

					} else if (Request::getUserVar('deletePageHeaderTitleImageAlt2')) {
						$editData = true;
						$setupForm->deleteImage('pageHeaderTitleImageAlt2');
						
					} else if (Request::getUserVar('uploadPageHeaderLogoImageAlt2')) {
						if ($setupForm->uploadImage('pageHeaderLogoImageAlt2')) {
							$editData = true;
						} else {
							$setupForm->addError('pageHeaderLogoImageAlt2', 'manager.setup.pageHeaderLogoImageAlt2Invalid');
						}

					} else if (Request::getUserVar('deletePageHeaderLogoImageAlt2')) {
						$editData = true;
						$setupForm->deleteImage('pageHeaderLogoImageAlt2');
						
					} else if (Request::getUserVar('uploadHomepageImage')) {
						if ($setupForm->uploadImage('homepageImage')) {
							$editData = true;
						} else {
							$setupForm->addError('homepageImage', 'manager.setup.homepageImageInvalid');
						}

					} else if (Request::getUserVar('deleteHomepageImage')) {
						$editData = true;
						$setupForm->deleteImage('homepageImage');
					} else if (Request::getUserVar('uploadJournalStyleSheet')) {
						if ($setupForm->uploadStyleSheet('journalStyleSheet')) {
							$editData = true;
						} else {
							$setupForm->addError('journalStyleSheet', 'manager.setup.journalStyleSheetInvalid');
						}

					} else if (Request::getUserVar('deleteJournalStyleSheet')) {
						$editData = true;
						$setupForm->deleteImage('journalStyleSheet');
						
					} else if (Request::getUserVar('addNavItem')) {
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
					break;
			}
			
			if (!isset($editData) && $setupForm->validate()) {
				$setupForm->execute();

				Request::redirect(null, null, 'setupSaved', $step);
			} else {
				$setupForm->display();
			}
		
		} else {
			Request::redirect();
		}
	}

	/**
	 * Display a "Settings Saved" message
	 */
	function setupSaved($args) {
		parent::validate();
		
		$step = isset($args[0]) ? (int) $args[0] : 0;
		
		if ($step >= 1 && $step <= 5) {
			parent::setupTemplate(true);

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign('setupStep', $step);
			$templateMgr->assign('helpTopicId', 'journal.managementPages.setup');
			$templateMgr->display('manager/setup/settingsSaved.tpl');
		} else {
			Request::redirect(null, 'index');
		}
	}

	function downloadLayoutTemplate($args) {
		parent::validate();
		$journal =& Request::getJournal();
		$templates = $journal->getSetting('templates');
		import('file.JournalFileManager');
		$journalFileManager =& new JournalFileManager($journal);
		$templateId = (int) array_shift($args);
		if ($templateId >= count($templates) || $templateId < 0) Request::redirect(null, null, 'setup');
		$template =& $templates[$templateId];

		$filename = "template-$templateId." . $journalFileManager->parseFileExtension($template['originalFilename']);
		$journalFileManager->downloadFile($filename, $template['fileType']);
	}
}
?>
