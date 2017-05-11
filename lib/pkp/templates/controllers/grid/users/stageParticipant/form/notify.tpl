{**
 * templates/controllers/grid/users/stageParticipant/form/notify.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display a form to notify other users about this file.
 *}
<script type="text/javascript">
	// Attach the notification handler.
	$(function() {ldelim}
		$('#notifyForm').pkpHandler(
			'$.pkp.controllers.grid.users.stageParticipant.form.StageParticipantNotifyHandler',
			{ldelim}
				templateUrl: {url|json_encode router=$smarty.const.ROUTE_COMPONENT component='grid.users.stageParticipant.StageParticipantGridHandler' op='fetchTemplateBody' stageId=$stageId submissionId=$submissionId escape=false}
			{rdelim}
		);
	{rdelim});
</script>
<div id="informationCenterNotifyTab">
	<form class="pkp_form" id="notifyForm" action="{url op="sendNotification" stageId=$stageId submissionId=$submissionId escape=false}" method="post">
		{csrf}
		{include file="controllers/notification/inPlaceNotification.tpl" notificationId="notifyFormNotification"}
		{fbvFormArea id="notifyFormArea"}
			{* Since the listbuilder only reports changes, we need to pass along the initial list *}
			<input type="hidden" name="userId" value="{$userId|escape}"/>

			{if $includeNotifyUsersListbuilder}
				{url|assign:notifyUsersUrl router=$smarty.const.ROUTE_COMPONENT component="listbuilder.users.StageUsersListbuilderHandler" op="fetch" params=$linkParams submissionId=$submissionId userIds=$userId|to_array escape=false}
				{load_url_in_div id="notifyUsersContainer" url=$notifyUsersUrl}
			{/if}

			{fbvFormSection title="stageParticipants.notify.chooseMessage" for="template" size=$fbvStyles.size.medium}
				{fbvElement type="select" from=$templates translate=false id="template" defaultValue="" defaultLabel=""}
			{/fbvFormSection}

			{fbvFormSection title="stageParticipants.notify.message" for="message" required="true"}
				{fbvElement type="textarea" id="message" rich=true required="true"}
			{/fbvFormSection}
			{fbvFormButtons id="notifyButton" hideCancel=true submitText="submission.stageParticipants.notify"}
		{/fbvFormArea}
	</form>
	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</div>
