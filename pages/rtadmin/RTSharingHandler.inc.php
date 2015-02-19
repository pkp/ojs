<?php

/**
 * @file pages/rtadmin/RTSharingHandler.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
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

	function sharingSettings($args, $request) {
		$this->validate();
		$journal = $request->getJournal();
		if ($journal) {
			$this->setupTemplate($request);
			$templateMgr = TemplateManager::getManager($request);

			$rtDao = DAORegistry::getDAO('RTDAO');
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

			$templateMgr->display('rtadmin/addthis.tpl');
		} else {
			$request->redirect(null, $request->getRequestedPage());
		}
	}

	function saveSharingSettings($args, $request) {
		$this->validate();

		$journal = $request->getJournal();

		if ($journal) {
			$rtDao = DAORegistry::getDAO('RTDAO');
			$rt = $rtDao->getJournalRTByJournal($journal);

			$rt->setSharingEnabled($request->getUserVar('sharingEnabled'));
			$rt->setSharingUserName($request->getUserVar('sharingUserName'));
			$rt->setSharingButtonStyle($request->getUserVar('sharingButtonStyle'));
			$rt->setSharingDropDownMenu($request->getUserVar('sharingDropDownMenu'));
			$rt->setSharingBrand($request->getUserVar('sharingBrand'));
			$rt->setSharingDropDown($request->getUserVar('sharingDropDown'));
			$rt->setSharingLanguage($request->getUserVar('sharingLanguage'));
			$rt->setSharingLogo($request->getUserVar('sharingLogo'));
			$rt->setSharingLogoBackground($request->getUserVar('sharingLogoBackground'));
			$rt->setSharingLogoColor($request->getUserVar('sharingLogoColor'));

			$rtDao->updateJournalRT($rt);
		}
		$request->redirect(null, $request->getRequestedPage());
	}
}

?>
