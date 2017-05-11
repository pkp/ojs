{**
 * templates/controllers/grid/user/reviewer/form/noFilesWarning.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Warning indicating that no files were selected.
 *
 *}
<div class="pkp_notification" id="noFilesWarning" style="display: none;">
	{include file="controllers/notification/inPlaceNotificationContent.tpl" notificationId=noFilesWarningContent notificationStyleClass=notifyWarning notificationTitle="editor.submission.noReviewerFilesSelected"|translate notificationContents="editor.submission.noReviewerFilesSelected.details"|translate}
</div>
