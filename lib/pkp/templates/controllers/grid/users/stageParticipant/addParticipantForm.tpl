{**
 * templates/controllers/grid/users/stageParticipant/addParticipantForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form that holds the stage participants list
 *
 *}

{* Help link *}
{help file="editorial-workflow.md" section="participants" class="pkp_help_modal"}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#addParticipantForm').pkpHandler('$.pkp.controllers.grid.users.stageParticipant.form.StageParticipantNotifyHandler',
			{ldelim}
				templateUrl: {url|json_encode router=$smarty.const.ROUTE_COMPONENT component='grid.users.stageParticipant.StageParticipantGridHandler' op='fetchTemplateBody' stageId=$stageId submissionId=$submissionId escape=false}
			{rdelim}
		);
	{rdelim});
</script>

<form class="pkp_form" id="addParticipantForm" action="{url op="saveParticipant"}" method="post">
	{csrf}
	<div class="pkp_helpers_clear"></div>

	{fbvFormArea id="addParticipant"}
		<input type="hidden" name="submissionId" value="{$submissionId|escape}" />
		<input type="hidden" name="stageId" value="{$stageId|escape}" />
		<input type="hidden" name="userGroupId" value="" />

		{url|assign:userSelectGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.users.userSelect.UserSelectGridHandler" op="fetchGrid" submissionId=$submissionId stageId=$stageId escape=false}
		{load_url_in_div id='userSelectGridContainer' url=$userSelectGridUrl}

	{/fbvFormArea}

	{fbvFormArea id="notifyFormArea"}
		{fbvFormSection title="stageParticipants.notify.chooseMessage" for="template" size=$fbvStyles.size.medium}
			{fbvElement type="select" from=$templates translate=false id="template" defaultValue="" defaultLabel=""}
		{/fbvFormSection}

		{fbvFormSection title="stageParticipants.notify.message" for="message"}
			{fbvElement type="textarea" id="message" rich=true}
		{/fbvFormSection}
		<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
		{fbvFormButtons}
	{/fbvFormArea}
</form>
