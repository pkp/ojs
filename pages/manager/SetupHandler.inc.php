<?php

/**
 * @file pages/manager/SetupHandler.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SetupHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for journal setup functions.
 */

import('pages.manager.ManagerHandler');

class SetupHandler extends ManagerHandler {
	/**
	 * Constructor
	 **/
	function SetupHandler() {
		parent::ManagerHandler();
	}

	/**
	 * Display journal setup form for the selected step.
	 * Displays setup index page if a valid step is not specified.
	 * @param $args array optional, if set the first parameter is the step to display
	 * @param $request Request
	 */
	function setup($args, &$request) {
		$this->validate();
		$this->setupTemplate(true);

		$step = isset($args[0]) ? (int) $args[0] : 0;

		if ($step >= 1 && $step <= 5) {

			$formClass = "JournalSetupStep{$step}Form";
			import("classes.manager.form.setup.$formClass");

			$setupForm = new $formClass();
			if ($setupForm->isLocaleResubmit()) {
				$setupForm->readInputData();
			} else {
				$setupForm->initData();
			}
			$setupForm->display($request, $this->getDispatcher());

		} else {
			$templateMgr =& TemplateManager::getManager($request);
			$templateMgr->assign('helpTopicId','journal.managementPages.setup');
			$templateMgr->display('manager/setup/index.tpl');
		}
	}

	/**
	 * Save changes to journal settings.
	 * @param $args array first parameter is the step being saved
	 * @param $request Request
	 */
	function saveSetup($args, &$request) {
		$this->validate();

		$step = isset($args[0]) ? (int) $args[0] : 0;

		if ($step >= 1 && $step <= 5) {

			$this->setupTemplate(true);

			$formClass = "JournalSetupStep{$step}Form";
			import("classes.manager.form.setup.$formClass");

			$setupForm = new $formClass();
			$setupForm->readInputData();
			$formLocale = $setupForm->getFormLocale();

			// Check for any special cases before trying to save
			switch ($step) {
				case 1:
					if ($request->getUserVar('addSponsor')) {
						// Add a sponsor
						$editData = true;
						$sponsors = $setupForm->getData('sponsors');
						array_push($sponsors, array());
						$setupForm->setData('sponsors', $sponsors);

					} else if (($delSponsor = $request->getUserVar('delSponsor')) && count($delSponsor) == 1) {
						// Delete a sponsor
						$editData = true;
						list($delSponsor) = array_keys($delSponsor);
						$delSponsor = (int) $delSponsor;
						$sponsors = $setupForm->getData('sponsors');
						array_splice($sponsors, $delSponsor, 1);
						$setupForm->setData('sponsors', $sponsors);

					} else if ($request->getUserVar('addContributor')) {
						// Add a contributor
						$editData = true;
						$contributors = $setupForm->getData('contributors');
						array_push($contributors, array());
						$setupForm->setData('contributors', $contributors);

					} else if (($delContributor = $request->getUserVar('delContributor')) && count($delContributor) == 1) {
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
					if ($request->getUserVar('addCustomAboutItem')) {
						// Add a custom about item
						$editData = true;
						$customAboutItems = $setupForm->getData('customAboutItems');
						$customAboutItems[$formLocale][] = array();
						$setupForm->setData('customAboutItems', $customAboutItems);

					} else if (($delCustomAboutItem = $request->getUserVar('delCustomAboutItem')) && count($delCustomAboutItem) == 1) {
						// Delete a custom about item
						$editData = true;
						list($delCustomAboutItem) = array_keys($delCustomAboutItem);
						$delCustomAboutItem = (int) $delCustomAboutItem;
						$customAboutItems = $setupForm->getData('customAboutItems');
						if (!isset($customAboutItems[$formLocale])) $customAboutItems[$formLocale][] = array();
						array_splice($customAboutItems[$formLocale], $delCustomAboutItem, 1);
						$setupForm->setData('customAboutItems', $customAboutItems);
					}
					if ($request->getUserVar('addReviewerDatabaseLink')) {
						// Add a reviewer database link
						$editData = true;
						$reviewerDatabaseLinks = $setupForm->getData('reviewerDatabaseLinks');
						array_push($reviewerDatabaseLinks, array());
						$setupForm->setData('reviewerDatabaseLinks', $reviewerDatabaseLinks);

					} else if (($delReviewerDatabaseLink = $request->getUserVar('delReviewerDatabaseLink')) && count($delReviewerDatabaseLink) == 1) {
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
					if ($request->getUserVar('addChecklist')) {
						// Add a checklist item
						$editData = true;
						$checklist = $setupForm->getData('submissionChecklist');
						if (!isset($checklist[$formLocale]) || !is_array($checklist[$formLocale])) {
							$checklist[$formLocale] = array();
							$lastOrder = 0;
						} else {
							$lastOrder = $checklist[$formLocale][count($checklist[$formLocale])-1]['order'];
						}
						array_push($checklist[$formLocale], array('order' => $lastOrder+1));
						$setupForm->setData('submissionChecklist', $checklist);

					} else if (($delChecklist = $request->getUserVar('delChecklist')) && count($delChecklist) == 1) {
						// Delete a checklist item
						$editData = true;
						list($delChecklist) = array_keys($delChecklist);
						$delChecklist = (int) $delChecklist;
						$checklist = $setupForm->getData('submissionChecklist');
						if (!isset($checklist[$formLocale])) $checklist[$formLocale] = array();
						array_splice($checklist[$formLocale], $delChecklist, 1);
						$setupForm->setData('submissionChecklist', $checklist);
					}

					if (!isset($editData)) {
						// Reorder checklist items
						$checklist = $setupForm->getData('submissionChecklist');
						if (isset($checklist[$formLocale]) && is_array($checklist[$formLocale])) {
							usort($checklist[$formLocale], create_function('$a,$b','return $a[\'order\'] == $b[\'order\'] ? 0 : ($a[\'order\'] < $b[\'order\'] ? -1 : 1);'));
						} else if (!isset($checklist[$formLocale])) $checklist[$formLocale] = array();
						$setupForm->setData('submissionChecklist', $checklist);
					}
					break;

				case 4:
					$router =& $request->getRouter();
					$journal =& $router->getContext($request);
					$templates = $journal->getSetting('templates');
					import('classes.file.JournalFileManager');
					$journalFileManager = new JournalFileManager($journal);
					if ($request->getUserVar('addTemplate')) {
						// Add a layout template
						$editData = true;
						if (!is_array($templates)) $templates = array();
						$templateId = count($templates);
						$originalFilename = $_FILES['template-file']['name'];
						$fileType = $journalFileManager->getUploadedFileType('template-file');
						$filename = "template-$templateId." . $journalFileManager->parseFileExtension($originalFilename);
						$journalFileManager->uploadFile('template-file', $filename);
						$templates[$templateId] = array(
							'originalFilename' => $originalFilename,
							'fileType' => $fileType,
							'filename' => $filename,
							'title' => $request->getUserVar('template-title')
						);
						$journal->updateSetting('templates', $templates);
					} else if (($delTemplate = $request->getUserVar('delTemplate')) && count($delTemplate) == 1) {
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
					if ($request->getUserVar('uploadHomeHeaderTitleImage')) {
						if ($setupForm->uploadImage('homeHeaderTitleImage', $formLocale)) {
							$editData = true;
						} else {
							$setupForm->addError('homeHeaderTitleImage', __('manager.setup.homeTitleImageInvalid'));
						}

					} else if ($request->getUserVar('deleteHomeHeaderTitleImage')) {
						$editData = true;
						$setupForm->deleteImage('homeHeaderTitleImage', $formLocale);

					} else if ($request->getUserVar('uploadHomeHeaderLogoImage')) {
						if ($setupForm->uploadImage('homeHeaderLogoImage', $formLocale)) {
							$editData = true;
						} else {
							$setupForm->addError('homeHeaderLogoImage', __('manager.setup.homeHeaderImageInvalid'));
						}

					} else if ($request->getUserVar('deleteHomeHeaderLogoImage')) {
						$editData = true;
						$setupForm->deleteImage('homeHeaderLogoImage', $formLocale);

					} else if ($request->getUserVar('uploadJournalThumbnail')) {
						if ($setupForm->uploadImage('journalThumbnail', $formLocale)) {
							$editData = true;
						} else {
							$setupForm->addError('journalThumbnail', __('manager.setup.journalThumbnailInvalid'));
						}

					} else if ($request->getUserVar('deleteJournalThumbnail')) {
						$editData = true;
						$setupForm->deleteImage('journalThumbnail', $formLocale);

					} else if ($request->getUserVar('uploadJournalFavicon')) {
						if ($setupForm->uploadImage('journalFavicon', $formLocale)) {
							$editData = true;
						} else {
							$setupForm->addError('journalFavicon', __('manager.setup.journalFaviconInvalid'));
						}

					} else if ($request->getUserVar('deleteJournalFavicon')) {
						$editData = true;
						$setupForm->deleteImage('journalFavicon', $formLocale);

					} else if ($request->getUserVar('uploadPageHeaderTitleImage')) {
						if ($setupForm->uploadImage('pageHeaderTitleImage', $formLocale)) {
							$editData = true;
						} else {
							$setupForm->addError('pageHeaderTitleImage', __('manager.setup.pageHeaderTitleImageInvalid'));
						}

					} else if ($request->getUserVar('deletePageHeaderTitleImage')) {
						$editData = true;
						$setupForm->deleteImage('pageHeaderTitleImage', $formLocale);

					} else if ($request->getUserVar('uploadPageHeaderLogoImage')) {
						if ($setupForm->uploadImage('pageHeaderLogoImage', $formLocale)) {
							$editData = true;
						} else {
							$setupForm->addError('pageHeaderLogoImage', __('manager.setup.pageHeaderLogoImageInvalid'));
						}

					} else if ($request->getUserVar('deletePageHeaderLogoImage')) {
						$editData = true;
						$setupForm->deleteImage('pageHeaderLogoImage', $formLocale);

					} else if ($request->getUserVar('uploadHomepageImage')) {
						if ($setupForm->uploadImage('homepageImage', $formLocale)) {
							$editData = true;
						} else {
							$setupForm->addError('homepageImage', __('manager.setup.homepageImageInvalid'));
						}

					} else if ($request->getUserVar('deleteHomepageImage')) {
						$editData = true;
						$setupForm->deleteImage('homepageImage', $formLocale);
					} else if ($request->getUserVar('uploadJournalStyleSheet')) {
						if ($setupForm->uploadStyleSheet('journalStyleSheet')) {
							$editData = true;
						} else {
							$setupForm->addError('journalStyleSheet', __('manager.setup.journalStyleSheetInvalid'));
						}

					} else if ($request->getUserVar('deleteJournalStyleSheet')) {
						$editData = true;
						$setupForm->deleteImage('journalStyleSheet');

					} else if ($request->getUserVar('addNavItem')) {
						// Add a navigation bar item
						$editData = true;
						$navItems = $setupForm->getData('navItems');
						$navItems[$formLocale][] = array();
						$setupForm->setData('navItems', $navItems);

					} else if (($delNavItem = $request->getUserVar('delNavItem')) && count($delNavItem) == 1) {
						// Delete a  navigation bar item
						$editData = true;
						list($delNavItem) = array_keys($delNavItem);
						$delNavItem = (int) $delNavItem;
						$navItems = $setupForm->getData('navItems');
						if (is_array($navItems) && is_array($navItems[$formLocale])) {
							array_splice($navItems[$formLocale], $delNavItem, 1);
							$setupForm->setData('navItems', $navItems);
						}
					}
					break;
			}

			if (!isset($editData) && $setupForm->validate()) {
				$setupForm->execute();

				$request->redirect(null, null, 'setupSaved', $step);
			} else {
				$setupForm->display($request, $this->getDispatcher());
			}

		} else {
			$request->redirect();
		}
	}

	/**
	 * Display a "Settings Saved" message
	 * @param $args array
	 * @param $request Request
	 */
	function setupSaved($args, &$request) {
		$this->validate();

		$step = isset($args[0]) ? (int) $args[0] : 0;

		if ($step >= 1 && $step <= 5) {
			$this->setupTemplate(true);

			$templateMgr =& TemplateManager::getManager($request);
			$templateMgr->assign('setupStep', $step);
			$templateMgr->assign('helpTopicId', 'journal.managementPages.setup');
			$templateMgr->display('manager/setup/settingsSaved.tpl');
		} else {
			$request->redirect(null, 'index');
		}
	}

	/**
	 * Download a layout template.
	 * @param $args array
	 * @param $request Request
	 */
	function downloadLayoutTemplate($args, &$request) {
		$this->validate();
		$router =& $request->getRouter();
		$journal =& $router->getContext($request);
		$templates = $journal->getSetting('templates');
		import('classes.file.JournalFileManager');
		$journalFileManager = new JournalFileManager($journal);
		$templateId = (int) array_shift($args);
		if ($templateId >= count($templates) || $templateId < 0) $request->redirect(null, null, 'setup');
		$template =& $templates[$templateId];

		$filename = "template-$templateId." . $journalFileManager->parseFileExtension($template['originalFilename']);
		$journalFileManager->downloadFile($filename, $template['fileType']);
	}

	/**
	 * Reset the license attached to article content.
	 * @param $args array
	 * @param $request Request
	 */
	function resetPermissions($args, &$request) {
		$this->validate();
		$router =& $request->getRouter();
		$journal =& $router->getContext($request);

		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$articleDao->resetPermissions($journal->getId());

		$request->redirect(null, null, 'setup', array('3'));
	}
}
?>
