<?php

/**
 * @file classes/template/TemplateManager.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TemplateManager
 * @ingroup template
 *
 * @brief Class for accessing the underlying template engine.
 * Currently integrated with Smarty (from http://smarty.php.net/).
 *
 */


import('classes.search.ArticleSearch');
import('classes.file.PublicFileManager');
import('lib.pkp.classes.template.PKPTemplateManager');

class TemplateManager extends PKPTemplateManager {
	/**
	 * Constructor.
	 * Initialize template engine and assign basic template variables.
	 * @param $request PKPRequest
	 */
	function TemplateManager($request = null) {
		parent::PKPTemplateManager($request);

		// Retrieve the router
		$router = $this->request->getRouter();
		assert(is_a($router, 'PKPRouter'));

		// Are we using implicit authentication?
		$this->assign('implicitAuth', Config::getVar('security', 'implicit_auth'));

		if (!defined('SESSION_DISABLE_INIT')) {
			/**
			 * Kludge to make sure no code that tries to connect to
			 * the database is executed (e.g., when loading
			 * installer pages).
			 */

			$context = $router->getContext($this->request);
			$site = $this->request->getSite();

			$publicFileManager = new PublicFileManager();
			$siteFilesDir = $this->request->getBaseUrl() . '/' . $publicFileManager->getSiteFilesPath();
			$this->assign('sitePublicFilesDir', $siteFilesDir);
			$this->assign('publicFilesDir', $siteFilesDir); // May be overridden by journal

			$siteStyleFilename = $publicFileManager->getSiteFilesPath() . '/' . $site->getSiteStyleFilename();
			if (file_exists($siteStyleFilename)) $this->addStyleSheet($this->request->getBaseUrl() . '/' . $siteStyleFilename);

			$this->assign('siteCategoriesEnabled', $site->getSetting('categoriesEnabled'));

			if (isset($context)) {

				$this->assign('currentJournal', $context);
				$this->assign('siteTitle', $context->getLocalizedName());
				$this->assign('publicFilesDir', $this->request->getBaseUrl() . '/' . $publicFileManager->getJournalFilesPath($context->getId()));

				$this->assign('primaryLocale', $context->getPrimaryLocale());
				$this->assign('alternateLocales', $context->getSetting('alternateLocales'));

				// Assign page header
				$this->assign('displayPageHeaderTitle', $context->getLocalizedPageHeaderTitle());
				$this->assign('displayPageHeaderLogo', $context->getLocalizedPageHeaderLogo());
				$this->assign('displayPageHeaderTitleAltText', $context->getLocalizedSetting('pageHeaderTitleImageAltText'));
				$this->assign('displayPageHeaderLogoAltText', $context->getLocalizedSetting('pageHeaderLogoImageAltText'));
				$this->assign('displayFavicon', $context->getLocalizedFavicon());
				$this->assign('faviconDir', $this->request->getBaseUrl() . '/' . $publicFileManager->getJournalFilesPath($context->getId()));
				$this->assign('alternatePageHeader', $context->getLocalizedSetting('journalPageHeader'));
				$this->assign('metaSearchDescription', $context->getLocalizedSetting('searchDescription'));
				$this->assign('metaSearchKeywords', $context->getLocalizedSetting('searchKeywords'));
				$this->assign('metaCustomHeaders', $context->getLocalizedSetting('customHeaders'));
				$this->assign('numPageLinks', $context->getSetting('numPageLinks'));
				$this->assign('itemsPerPage', $context->getSetting('itemsPerPage'));
				$this->assign('enableAnnouncements', $context->getSetting('enableAnnouncements'));
				$this->assign(
					'hideRegisterLink',
					!$context->getSetting('allowRegReviewer') &&
					!$context->getSetting('allowRegReader') &&
					!$context->getSetting('allowRegAuthor')
				);

				// Assign stylesheets and footer
				$contextStyleSheet = $context->getSetting('journalStyleSheet');
				if ($contextStyleSheet) {
					$this->addStyleSheet($this->request->getBaseUrl() . '/' . $publicFileManager->getJournalFilesPath($context->getId()) . '/' . $contextStyleSheet['uploadName']);
				}

				import('classes.payment.ojs.OJSPaymentManager');
				$paymentManager = new OJSPaymentManager($this->request);
				$this->assign('journalPaymentsEnabled', $paymentManager->isConfigured());

				// Include footer links if they have been defined.
				$footerCategoryDao = DAORegistry::getDAO('FooterCategoryDAO');
				$footerCategories = $footerCategoryDao->getNotEmptyByContextId($context->getId());
				$this->assign('footerCategories', $footerCategories->toArray());

				$footerLinkDao = DAORegistry::getDAO('FooterLinkDAO');
				$this->assign('maxLinks', $footerLinkDao->getLargestCategoryTotalbyContextId($context->getId()));
				$this->assign('pageFooter', $context->getLocalizedSetting('journalPageFooter'));
			} else {
				// Add the site-wide logo, if set for this locale or the primary locale
				$displayPageHeaderTitle = $site->getLocalizedPageHeaderTitle();
				$this->assign('displayPageHeaderTitle', $displayPageHeaderTitle);
				if (isset($displayPageHeaderTitle['altText'])) $this->assign('displayPageHeaderTitleAltText', $displayPageHeaderTitle['altText']);

				$this->assign('siteTitle', $site->getLocalizedTitle());
			}

			if (!$site->getRedirect()) {
				$this->assign('hasOtherJournals', true);
			}
		}
	}

	/**
	 * Display page links for a listing of items that has been
	 * divided onto multiple pages.
	 * Usage:
	 * {page_links
	 * 	name="nameMustMatchGetRangeInfoCall"
	 * 	iterator=$myIterator
	 *	additional_param=myAdditionalParameterValue
	 * }
	 */
	function smartyPageLinks($params, &$smarty) {
		$iterator = $params['iterator'];
		$name = $params['name'];
		if (isset($params['params']) && is_array($params['params'])) {
			$extraParams = $params['params'];
			unset($params['params']);
			$params = array_merge($params, $extraParams);
		}
		if (isset($params['anchor'])) {
			$anchor = $params['anchor'];
			unset($params['anchor']);
		} else {
			$anchor = null;
		}
		if (isset($params['all_extra'])) {
			$allExtra = ' ' . $params['all_extra'];
			unset($params['all_extra']);
		} else {
			$allExtra = '';
		}

		unset($params['iterator']);
		unset($params['name']);

		$numPageLinks = $smarty->get_template_vars('numPageLinks');
		if (!is_numeric($numPageLinks)) $numPageLinks=10;

		$page = $iterator->getPage();
		$pageCount = $iterator->getPageCount();

		$pageBase = max($page - floor($numPageLinks / 2), 1);
		$paramName = $name . 'Page';

		if ($pageCount<=1) return '';

		$value = '';

		if ($page>1) {
			$params[$paramName] = 1;
			$value .= '<a href="' . $this->request->url(null, null, null, $this->request->getRequestedArgs(), $params, $anchor, true) . '"' . $allExtra . '>&lt;&lt;</a>&nbsp;';
			$params[$paramName] = $page - 1;
			$value .= '<a href="' . $this->request->url(null, null, null, $this->request->getRequestedArgs(), $params, $anchor, true) . '"' . $allExtra . '>&lt;</a>&nbsp;';
		}

		for ($i=$pageBase; $i<min($pageBase+$numPageLinks, $pageCount+1); $i++) {
			if ($i == $page) {
				$value .= "<strong>$i</strong>&nbsp;";
			} else {
				$params[$paramName] = $i;
				$value .= '<a href="' . $this->request->url(null, null, null, $this->request->getRequestedArgs(), $params, $anchor, true) . '"' . $allExtra . '>' . $i . '</a>&nbsp;';
			}
		}
		if ($page < $pageCount) {
			$params[$paramName] = $page + 1;
			$value .= '<a href="' . $this->request->url(null, null, null, $this->request->getRequestedArgs(), $params, $anchor, true) . '"' . $allExtra . '>&gt;</a>&nbsp;';
			$params[$paramName] = $pageCount;
			$value .= '<a href="' . $this->request->url(null, null, null, $this->request->getRequestedArgs(), $params, $anchor, true) . '"' . $allExtra . '>&gt;&gt;</a>&nbsp;';
		}

		return $value;
	}
}

?>
