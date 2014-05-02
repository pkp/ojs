<?php

/**
 * @file plugins/generic/externalFeed/ExternalFeedPlugin.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ExternalFeedPlugin
 * @ingroup plugins_generic_externalFeed
 *
 * @brief ExternalFeed plugin class
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class ExternalFeedPlugin extends GenericPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);

		if ($success && $this->getEnabled()) {
			$this->import('ExternalFeedDAO');

			$externalFeedDao = new ExternalFeedDAO($this->getName());
			$returner =& DAORegistry::registerDAO('ExternalFeedDAO', $externalFeedDao);

			$templateMgr =& TemplateManager::getManager();
			$templateMgr->addStyleSheet(Request::getBaseUrl() . '/' . $this->getStyleSheetFile());

			// Journal home page display
			HookRegistry::register('TemplateManager::display', array(&$this, 'displayHomepage'));

			// Register also as a block plugin
			HookRegistry::register('PluginRegistry::loadCategory', array(&$this, 'callbackLoadCategory'));

			// Journal Manager link to externalFeed management pages
			HookRegistry::register('Templates::Manager::Index::ManagementPages', array($this, 'displayManagerLink'));
		}
		return $success;
	}

	function getDisplayName() {
		return __('plugins.generic.externalFeed.displayName');
	}

	function getDescription() {
		return __('plugins.generic.externalFeed.description');
	}

	/**
	 * Get the filename of the ADODB schema for this plugin.
	 */
	function getInstallSchemaFile() {
		return $this->getPluginPath() . '/' . 'schema.xml';
	}

	/**
	 * Get the filename of the default CSS stylesheet for this plugin.
	 */
	function getDefaultStyleSheetFile() {
		return $this->getPluginPath() . '/' . 'externalFeed.css';
	}

	/**
	 * Get the filename of the CSS stylesheet for this plugin.
	 */
	function getStyleSheetFile() {
		$journal =& Request::getJournal();
		$journalId = $journal?$journal->getId():0;
		$styleSheet = $this->getSetting($journalId, 'externalFeedStyleSheet');

		if (empty($styleSheet)) {
			return $this->getDefaultStyleSheetFile();
		} else {
			import('classes.file.PublicFileManager');
			$fileManager = new PublicFileManager();
			return $fileManager->getJournalFilesPath($journalId) . '/' . $styleSheet['uploadName'];
		}
	}

	/**
	 * Extend the {url ...} smarty to support externalFeed plugin.
	 */
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
	 * Set the page's breadcrumbs, given the plugin's tree of items
	 * to append.
	 * @param $subclass boolean
	 */
	function setBreadcrumbs($isSubclass = false) {
		$templateMgr =& TemplateManager::getManager();
		$pageCrumbs = array(
			array(
				Request::url(null, 'user'),
				'navigation.user'
			),
			array(
				Request::url(null, 'manager'),
				'user.role.manager'
			)
		);
		if ($isSubclass) $pageCrumbs[] = array(
			Request::url(null, 'manager', 'plugin', array('generic', $this->getName(), 'feeds')),
			$this->getDisplayName(),
			true
		);

		$templateMgr->assign('pageHierarchy', $pageCrumbs);
	}

	/**
	 * Register as a block plugin, even though this is a generic plugin.
	 * This will allow the plugin to behave as a block plugin, i.e. to
	 * have layout tasks performed on it.
	 * @param $hookName string
	 * @param $args array
	 */
	function callbackLoadCategory($hookName, $args) {
		$category =& $args[0];
		$plugins =& $args[1];
		switch ($category) {
			case 'blocks':
				$this->import('ExternalFeedBlockPlugin');
				$blockPlugin = new ExternalFeedBlockPlugin($this->getName());
				$plugins[$blockPlugin->getSeq()][$blockPlugin->getPluginPath()] =& $blockPlugin;
				break;
		}
		return false;
	}

	/**
	 * Display verbs for the management interface.
	 */
	function getManagementVerbs() {
		$verbs = array();
		if ($this->getEnabled()) {
			$verbs[] = array('feeds', __('plugins.generic.externalFeed.manager.feeds'));
			$verbs[] = array('settings', __('plugins.generic.externalFeed.manager.settings'));
		}
		return parent::getManagementVerbs($verbs);
	}

	/**
	 * Display external feed content on journal homepage.
	 * @param $hookName string
	 * @param $args array
	 */
	function displayHomepage($hookName, $args) {
		$journal =& Request::getJournal();
		$journalId = $journal?$journal->getId():0;

		if ($this->getEnabled()) {
			$requestedPage = Request::getRequestedPage();

			if (empty($requestedPage) || $requestedPage == 'index') {
				$externalFeedDao =& DAORegistry::getDAO('ExternalFeedDAO');
				$this->import('simplepie.SimplePie');

				$feeds =& $externalFeedDao->getExternalFeedsByJournalId($journal->getId());
				$output = '<div id="externalFeedsHome">';

				while ($currentFeed =& $feeds->next()) {
					if (!$currentFeed->getDisplayHomepage()) continue;
					$feed = new SimplePie();
					$feed->set_feed_url($currentFeed->getUrl());
					$feed->enable_order_by_date(false);
					$feed->set_cache_location(CacheManager::getFileCachePath());
					$feed->init();

					if ($currentFeed->getLimitItems()) {
						$recentItems = $currentFeed->getRecentItems();
					} else {
						$recentItems = 0;
					}

					$output .= '<h3>' . $currentFeed->getLocalizedTitle() . '</h3>';
					$output .= '<table class="externalFeeds">';
					$output .= '<tr>';
					$output .= '<td colspan="2" class="headseparator">&nbsp;</td>';
					$output .= '</tr>';

					$separator = '';

					foreach ($feed->get_items(0, $recentItems) as $item):
						$output .= $separator;
						$output .= '<tr class="title">';
						$output .= '<td colspan="2" class="title">';
						$output .= '<h4>' . $item->get_title() . '</h4>';
						$output .= '</td>';
						$output .= '</tr>';
						$output .= '<tr class="description">';
						$output .= '<td colspan="2" class="description">';
						$output .= $item->get_description();
						$output .= '</td>';
						$output .= '</tr>';
						$output .= '<tr class="details">';
						$output .= '<td class="posted">';
						$output .= AppLocale::Translate('plugins.generic.externalFeed.posted') . ': ' . date('Y-m-d', strtotime($item->get_date()));
						$output .= '</td>';
						$output .= '<td class="more">';
						$output .= '<a href="' . $item->get_permalink() . '" target="_blank">' . AppLocale::Translate('plugins.generic.externalFeed.more') . '</a>';
						$output .= '</td>';
						$output .= '</tr>';

						$separator = '<tr><td colspan="2" class="separator">&nbsp;</td></tr>';
					endforeach;

					$output .= '<tr><td colspan="2" class="endseparator">&nbsp;</td></tr>';
					$output .= '</table>';
				}

				$output .= '</div>';

				$templateManager =& $args[0];
				$additionalHomeContent = $templateManager->get_template_vars('additionalHomeContent');
				$templateManager->assign('additionalHomeContent', $additionalHomeContent . "\n\n" . $output);
			}
		}
	}

	/**
	 * Display management link for JM.
	 * @param $hookName string
	 * @param $params array
	 */
	function displayManagerLink($hookName, $params) {
		if ($this->getEnabled()) {
			$smarty =& $params[1];
			$output =& $params[2];
			$output .= '<li>&#187; <a href="' . $this->smartyPluginUrl(array('op'=>'plugin', 'path'=>'feeds'), $smarty) . '">' . TemplateManager::smartyTranslate(array('key'=>'plugins.generic.externalFeed.manager.feeds'), $smarty) . '</a></li>';
		}
		return false;
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

		AppLocale::requireComponents(
			LOCALE_COMPONENT_APPLICATION_COMMON,
			LOCALE_COMPONENT_PKP_MANAGER,
			LOCALE_COMPONENT_PKP_USER
		);
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));
		$journal =& Request::getJournal();
		$journalId = $journal->getId();

		switch ($verb) {
			case 'delete':
				if (!empty($args)) {
					$externalFeedId = !isset($args) || empty($args) ? null : (int) $args[0];
					$externalFeedDao =& DAORegistry::getDAO('ExternalFeedDAO');

					// Ensure externalFeed is for this journal
					if ($externalFeedDao->getExternalFeedJournalId($externalFeedId) == $journalId) {
						$externalFeedDao->deleteExternalFeedById($externalFeedId);
					}
				}
				Request::redirect(null, 'manager', 'plugin', array('generic', $this->getName(), 'feeds'));
				return true;
			case 'move':
				$externalFeedId = !isset($args) || empty($args) ? null : (int) $args[0];
				$externalFeedDao =& DAORegistry::getDAO('ExternalFeedDAO');

				// Ensure externalFeed is valid and for this journal
				if (($externalFeedId != null && $externalFeedDao->getExternalFeedJournalId($externalFeedId) == $journalId)) {
					$feed =& $externalFeedDao->getExternalFeed($externalFeedId);

					$direction = Request::getUserVar('dir');

					if ($direction != null) {
						// moving with up or down arrow
						$isDown = ($direction=='d');
						$feed->setSeq($feed->getSeq()+($isDown?1.5:-1.5));
						$externalFeedDao->updateExternalFeed($feed);
						$externalFeedDao->resequenceExternalFeeds($feed->getJournalId());
					}
				}
				Request::redirect(null, 'manager', 'plugin', array('generic', $this->getName(), 'feeds'));
				return true;
			case 'create':
			case 'edit':
				$externalFeedId = !isset($args) || empty($args) ? null : (int) $args[0];
				$externalFeedDao =& DAORegistry::getDAO('ExternalFeedDAO');

				// Ensure externalFeed is valid and for this journal
				if (($externalFeedId != null && $externalFeedDao->getExternalFeedJournalId($externalFeedId) == $journalId) || ($externalFeedId == null)) {
					$this->import('ExternalFeedForm');

					if ($externalFeedId == null) {
						$templateMgr->assign('externalFeedTitle', 'plugins.generic.externalFeed.manager.createTitle');
					} else {
						$templateMgr->assign('externalFeedTitle', 'plugins.generic.externalFeed.manager.editTitle');
					}

					$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
					$journalSettings =& $journalSettingsDao->getJournalSettings($journalId);

					$externalFeedForm = new ExternalFeedForm($this, $externalFeedId);
					if ($externalFeedForm->isLocaleResubmit()) {
						$externalFeedForm->readInputData();
					} else {
						$externalFeedForm->initData();
					}
					$this->setBreadCrumbs(true);
					$templateMgr->assign('journalSettings', $journalSettings);
					$externalFeedForm->display();
				} else {
					Request::redirect(null, 'manager', 'plugin', array('generic', $this->getName(), 'feeds'));
				}
				return true;
			case 'update':
				$externalFeedId = Request::getUserVar('feedId') == null ? null : (int) Request::getUserVar('feedId');
				$externalFeedDao =& DAORegistry::getDAO('ExternalFeedDAO');

				if (($externalFeedId != null && $externalFeedDao->getExternalFeedJournalId($externalFeedId) == $journalId) || $externalFeedId == null) {

					$this->import('ExternalFeedForm');
					$externalFeedForm = new ExternalFeedForm($this, $externalFeedId);
					$externalFeedForm->readInputData();

					if ($externalFeedForm->validate()) {
						$externalFeedForm->execute();

						if (Request::getUserVar('createAnother')) {
							Request::redirect(null, 'manager', 'plugin', array('generic', $this->getName(), 'create'));
						} else {
							Request::redirect(null, 'manager', 'plugin', array('generic', $this->getName(), 'feeds'));
						}
					} else {
						if ($externalFeedId == null) {
							$templateMgr->assign('externalFeedTitle', 'plugins.generic.externalFeed.manager.createTitle');
						} else {
							$templateMgr->assign('externalFeedTitle', 'plugins.generic.externalFeed.manager.editTitle');
						}

						$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
						$journalSettings =& $journalSettingsDao->getJournalSettings($journalId);

						$this->setBreadCrumbs(true);
						$templateMgr->assign('journalSettings', $journalSettings);
						$externalFeedForm->display();
					}
				} else {
					Request::redirect(null, 'manager', 'plugin', array('generic', $this->getName(), 'feeds'));
				}
				return true;
			case 'settings':
				$this->import('ExternalFeedSettingsForm');
				$form = new ExternalFeedSettingsForm($this, $journal->getId());
				if (Request::getUserVar('save')) {
					Request::redirect(null, 'manager', 'plugin', array('generic', $this->getName(), 'feeds'));
				} elseif (Request::getUserVar('uploadStyleSheet')) {
					$form->uploadStyleSheet();
				} elseif (Request::getUserVar('deleteStyleSheet')) {
					$form->deleteStyleSheet();
				}
				$this->setBreadCrumbs(true);
				$form->initData();
				$form->display();
				return true;
			case 'feeds':
			default:
				$this->import('ExternalFeed');
				$rangeInfo =& Handler::getRangeInfo('feeds');
				$externalFeedDao =& DAORegistry::getDAO('ExternalFeedDAO');
				$feeds =& $externalFeedDao->getExternalFeedsByJournalId($journalId, $rangeInfo);
				$templateMgr->assign('feeds', $feeds);
				$this->setBreadCrumbs();

				$templateMgr->display($this->getTemplatePath() . 'externalFeeds.tpl');
				return true;
		}
	}
}

?>
