<?php

/**
 * @file pages/rtadmin/RTSharingHandler.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RTSharingHandler
 * @ingroup pages_rtadmin
 *
 * @brief Handle Reading Tools sharing requests -- setup section.
 */

import('pages.rtadmin.RTAdminHandler');
import('classes.rt.ojs.SharingRT');

class RTSharingHandler extends RTAdminHandler {
	/**
	 * Constructor
	 **/
	function RTSharingHandler() {
		parent::RTAdminHandler();
	}

	function sharingSettings() {
		$this->validate();
		$journal = Request::getJournal();
		if ($journal) {
			$this->setupTemplate(true);
			$templateMgr =& TemplateManager::getManager();

			$rtDao =& DAORegistry::getDAO('RTDAO');
			$rt = $rtDao->getJournalRTByJournal($journal);

			$templateMgr->assign('sharingEnabled', $rt->getSharingEnabled());
			$templateMgr->assign('sharingUserName', $rt->getSharingUserName());
			$templateMgr->assign('sharingButtonStyle', $rt->getSharingButtonStyle());
			$templateMgr->assign('sharingButtonStyleOptions', array_keys(SharingRT::getBtnStyles()));
			$templateMgr->assign('sharingDropDownMenu', $rt->getSharingDropDownMenu());
			$templateMgr->assign('sharingBrand', $rt->getSharingBrand());
			$templateMgr->assign('sharingDropDown', $rt->getSharingDropDown());
			$templateMgr->assign('sharingLanguage', $rt->getSharingLanguage());
			$templateMgr->assign('sharingLanguageOptions', SharingRT::getLanguages());
			$templateMgr->assign('sharingLogo', $rt->getSharingLogo());
			$templateMgr->assign('sharingLogoBackground', $rt->getSharingLogoBackground());
			$templateMgr->assign('sharingLogoColor', $rt->getSharingLogoColor());

			$templateMgr->assign('helpTopicId', 'journal.managementPages.readingTools.addthisSettings');
			$templateMgr->display('rtadmin/addthis.tpl');
		} else {
			Request::redirect(null, Request::getRequestedPage());
		}
	}

	function saveSharingSettings() {
		$this->validate();

		$journal = Request::getJournal();

		if ($journal) {
			$rtDao =& DAORegistry::getDAO('RTDAO');
			$rt = $rtDao->getJournalRTByJournal($journal);

			$rt->setSharingEnabled(Request::getUserVar('sharingEnabled'));
			$rt->setSharingUserName(Request::getUserVar('sharingUserName'));
			$rt->setSharingButtonStyle(Request::getUserVar('sharingButtonStyle'));
			$rt->setSharingDropDownMenu(Request::getUserVar('sharingDropDownMenu'));
			$rt->setSharingBrand(Request::getUserVar('sharingBrand'));
			$rt->setSharingDropDown(Request::getUserVar('sharingDropDown'));
			$rt->setSharingLanguage(Request::getUserVar('sharingLanguage'));
			$rt->setSharingLogo(Request::getUserVar('sharingLogo'));
			$rt->setSharingLogoBackground(Request::getUserVar('sharingLogoBackground'));
			$rt->setSharingLogoColor(Request::getUserVar('sharingLogoColor'));

			$rtDao->updateJournalRT($rt);
		}
		Request::redirect(null, Request::getRequestedPage());
	}
}

?>
