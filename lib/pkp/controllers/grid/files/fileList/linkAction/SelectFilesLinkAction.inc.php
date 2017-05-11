<?php
/**
 * @file controllers/grid/files/fileList/linkAction/SelectFilesLinkAction.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SelectFilesLinkAction
 * @ingroup controllers_grid_files_fileList_linkAction
 *
 * @brief An abstract base action for actions to open up a modal that allows users to
 *  select files from a file list grid.
 */

import('lib.pkp.classes.linkAction.LinkAction');

class SelectFilesLinkAction extends LinkAction {

	/**
	 * Constructor
	 * @param $request Request
	 * @param $actionArgs array The parameters required by the
	 *  link action target to identify a list of files.
	 * @param $actionLabel string The localized label of the link action.
	 * @param $modalTitle string the (optional) title to be used for the modal.
	 */
	function __construct($request, $actionArgs, $actionLabel, $modalTitle = null) {
		// Create an ajax action request that'll contain
		// the file selection grid.
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		$modalTitle = isset($modalTitle) ? $modalTitle : $actionLabel;
		$router = $request->getRouter();
		$ajaxModal = new AjaxModal(
				$router->url($request, null, null, 'selectFiles', null, $actionArgs),
				$modalTitle, 'modal_add_file');

		// Configure the link action.
		parent::__construct('selectFiles', $ajaxModal, $actionLabel, 'add');
	}
}

?>
