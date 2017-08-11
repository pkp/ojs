{**
 * templates/controllers/grid/issues/form/issueData.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for creation and modification of an issue
 *}

{help file="issue-management.md#edit-issue-data" class="pkp_help_tab"}
<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#issueAccessForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="issueAccessForm" method="post" action="{url op="updateAccess" issueId=$issueId}">
	{csrf}
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="issueAccessNotification"}

	{fbvFormArea id="datePublishedArea" title="editor.issues.accessDate"}
		{fbvFormSection}
			{fbvElement type="text" id="openAccessDate" value=$openAccessDate|date_format:$dateFormatShort size=$fbvStyles.size.SMALL class="datepicker"}

		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormArea id="issueAccessArea" title="editor.issues.accessStatus"}
		{fbvFormSection}
			{fbvElement required="true" type="select" id="accessStatus" from=$accessOptions selected=$accessStatus}
		{/fbvFormSection}

	{fbvFormButtons submitText="common.save"}
	{/fbvFormArea}
</form>
