{**
 * templates/submission/form/step1.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 1 of author submission process.
 *}
<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#submitStep1Form').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="submitStep1Form" method="post" action="{url op="saveStep" path=$submitStep}">
{csrf}
{if $submissionId}<input type="hidden" name="submissionId" value="{$submissionId|escape}"/>{/if}
	<input type="hidden" name="submissionChecklist" value="1"/>

{include file="controllers/notification/inPlaceNotification.tpl" notificationId="submitStep1FormNotification"}

{fbvFormArea id="submissionStep1"}
	<!-- Author user group selection (only appears if user has > 1 author user groups) -->
	{if count($authorUserGroupOptions) > 1}
		{fbvFormSection label="submission.submit.userGroup" description="submission.submit.userGroupDescription"}
			{fbvElement type="select" id="authorUserGroupId" from=$authorUserGroupOptions translate=false size=$fbvStyles.size.SMALL}
		{/fbvFormSection}
	{elseif count($authorUserGroupOptions) == 1}
		{foreach from=$authorUserGroupOptions key="key" item="authorUserGroupName"}{assign var=authorUserGroupId value=$key}{/foreach}
		{fbvElement type="hidden" id="authorUserGroupId" value=$authorUserGroupId}
	{else}
		<div class="pkp_notification">
			{include file="controllers/notification/inPlaceNotificationContent.tpl" notificationId=submit notificationStyleClass=notifyError notificationTitle="common.warning"|translate notificationContents="author.submit.userGroupRequired"|translate}
		</div>
	{/if}

	{if $copyrightNoticeAgree}
		{fbvFormSection title="submission.submit.copyrightNoticeAgreementLabel"}
			{$copyrightNotice}
			{fbvFormSection list="true"}
				{fbvElement type="checkbox" id="copyrightNoticeAgree" required=true value=1 label="submission.submit.copyrightNoticeAgree" checked=$submissionId}
			{/fbvFormSection}
		{/fbvFormSection}
	{/if}

	{$additionalFormContent1}

	{include file="core:submission/submissionLocale.tpl"}

	{$additionalFormContent2}

	{* Submission checklist *}
	{if $currentContext->getLocalizedSetting('submissionChecklist')}
		{fbvFormSection list="true" label="submission.submit.submissionChecklist" description="submission.submit.submissionChecklistDescription" id="pkp_submissionChecklist"}
			{foreach name=checklist from=$currentContext->getLocalizedSetting('submissionChecklist') key=checklistId item=checklistItem}
				{fbvElement type="checkbox" id="checklist-$checklistId" required=true value=1 label=$checklistItem.content translate=false checked=false}
			{/foreach}
		{/fbvFormSection}
	{/if}

	{* Cover Note To Editor*}
	{fbvFormSection for="commentsToEditor" title="submission.submit.coverNote"}
		{fbvElement type="textarea" name="commentsToEditor" id="commentsToEditor" value=$commentsToEditor rich=true}
	{/fbvFormSection}

	{* Privacy Statement *}
	{fbvFormSection for="privacyStatement" title="submission.submit.privacyStatement"}
		{fbvElement type="textarea" name="privacyStatement" id="privacyStatement" disabled=true value=$currentContext->getLocalizedSetting('privacyStatement') rich=true}
	{/fbvFormSection}

	{* Buttons *}
	{fbvFormButtons id="step1Buttons" submitText="common.saveAndContinue"}

	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
{/fbvFormArea}

</form>
