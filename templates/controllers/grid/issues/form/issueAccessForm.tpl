{**
 * templates/controllers/grid/issues/form/issueData.tpl
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Form for creation and modification of an issue
 *}

<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#issueAccessForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="issueAccessForm" method="post" action="{url op="updateAccess" issueId=$issueId}">
	{csrf}
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="issueAccessNotification"}

	{fbvFormArea id="issueAccessArea"}
		{fbvFormSection title="editor.issues.accessStatus"}
			{fbvElement required="true" type="select" id="accessStatus" from=$accessOptions selected=$accessStatus}
		{/fbvFormSection}
		{fbvFormSection title="editor.issues.accessDate"}
			{fbvElement type="text" id="openAccessDate" value=$openAccessDate size=$fbvStyles.size.SMALL class="datepicker"}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormButtons submitText="common.save"}
</form>
