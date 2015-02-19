<?php

/**
 * @file classes/template/TemplateManager.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
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
	function TemplateManager($request) {
		parent::PKPTemplateManager($request);

		if (!defined('SESSION_DISABLE_INIT')) {
			/**
			 * Kludge to make sure no code that tries to connect to
			 * the database is executed (e.g., when loading
			 * installer pages).
			 */

			$context = $request->getContext();
			$site = $request->getSite();

			$publicFileManager = new PublicFileManager();
			$siteFilesDir = $request->getBaseUrl() . '/' . $publicFileManager->getSiteFilesPath();
			$this->assign('sitePublicFilesDir', $siteFilesDir);
			$this->assign('publicFilesDir', $siteFilesDir); // May be overridden by journal

			$siteStyleFilename = $publicFileManager->getSiteFilesPath() . '/' . $site->getSiteStyleFilename();
			if (file_exists($siteStyleFilename)) $this->addStyleSheet($request->getBaseUrl() . '/' . $siteStyleFilename, STYLE_SEQUENCE_LAST);

			$this->assign('siteCategoriesEnabled', $site->getSetting('categoriesEnabled'));

			if (isset($context)) {

				$this->assign('currentJournal', $context);
				$this->assign('siteTitle', $context->getLocalizedName());
				$this->assign('publicFilesDir', $request->getBaseUrl() . '/' . $publicFileManager->getJournalFilesPath($context->getId()));

				$this->assign('primaryLocale', $context->getPrimaryLocale());
				$this->assign('alternateLocales', $context->getSetting('alternateLocales'));

				// Assign page header
				$this->assign('displayPageHeaderTitle', $context->getLocalizedPageHeaderTitle());
				$this->assign('displayPageHeaderLogo', $context->getLocalizedPageHeaderLogo());
				$this->assign('displayPageHeaderTitleAltText', $context->getLocalizedSetting('pageHeaderTitleImageAltText'));
				$this->assign('displayPageHeaderLogoAltText', $context->getLocalizedSetting('pageHeaderLogoImageAltText'));
				$this->assign('displayFavicon', $context->getLocalizedFavicon());
				$this->assign('faviconDir', $request->getBaseUrl() . '/' . $publicFileManager->getJournalFilesPath($context->getId()));
				$this->assign('alternatePageHeader', $context->getLocalizedSetting('journalPageHeader'));
				$this->assign('metaSearchDescription', $context->getLocalizedSetting('searchDescription'));
				$this->assign('metaSearchKeywords', $context->getLocalizedSetting('searchKeywords'));
				$this->assign('metaCustomHeaders', $context->getLocalizedSetting('customHeaders'));
				$this->assign('numPageLinks', $context->getSetting('numPageLinks'));
				$this->assign('itemsPerPage', $context->getSetting('itemsPerPage'));
				$this->assign('enableAnnouncements', $context->getSetting('enableAnnouncements'));

				// Assign stylesheets and footer
				$contextStyleSheet = $context->getSetting('journalStyleSheet');
				if ($contextStyleSheet) {
					$this->addStyleSheet($request->getBaseUrl() . '/' . $publicFileManager->getJournalFilesPath($context->getId()) . '/' . $contextStyleSheet['uploadName'], STYLE_SEQUENCE_LAST);
				}

				import('classes.payment.ojs.OJSPaymentManager');
				$paymentManager = new OJSPaymentManager($request);
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
		}
	}
}

?>
