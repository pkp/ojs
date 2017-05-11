{**
 * controllers/tab/settings/policies/form/policiesForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Policies management form.
 *
 *}

{* Help Link *}
{help file="settings.md" section="context" class="pkp_help_tab"}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#policiesForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="policiesForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="saveFormData" tab="policies"}">
	{csrf}

	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="policiesFormNotification"}
	{include file="controllers/tab/settings/wizardMode.tpl" wizardMode=$wizardMode}

	{fbvFormArea id="policiesFormArea"}
		{fbvFormSection label="manager.setup.privacyStatement" description="manager.setup.privacyStatement.description"}
			{fbvElement type="textarea" multilingual=true rich=true name="privacyStatement" id="privacyStatement" value=$privacyStatement}
		{/fbvFormSection}

		{* In wizard mode, these fields should be hidden *}
		{if $wizardMode}
			{assign var="wizardClasses" value="is_wizard_mode"}
		{else}
			{assign var="wizardClasses" value=""}
		{/if}
		{fbvFormSection label="manager.setup.focusAndScope" description="manager.setup.focusAndScope.description" class=$wizardClasses}
			{fbvElement type="textarea" multilingual=true name="focusScopeDesc" id="focusScopeDesc" value=$focusScopeDesc rich=true}
		{/fbvFormSection}
		{fbvFormSection label="manager.setup.openAccessPolicy" description="manager.setup.openAccessPolicy.description" class=$wizardClasses}
			{url|assign:"accessAndSecurityUrl" page="settings" op="access"}
			{translate|assign:"securitySettingsNote" key="manager.setup.securitySettings.note" accessAndSecurityUrl=$accessAndSecurityUrl}
			{fbvElement type="textarea" multilingual="true" name="openAccessPolicy" id="openAccessPolicy" value=$openAccessPolicy rich=true}
		{/fbvFormSection}
		{fbvFormSection label="manager.setup.reviewPolicy" description="manager.setup.peerReview.description" class=$wizardClasses}
			{fbvElement type="textarea" multilingual=true name="reviewPolicy" id="reviewPolicy" value=$reviewPolicy rich=true}
		{/fbvFormSection}
		{fbvFormSection label="manager.setup.competingInterests" description="manager.setup.competingInterestsDescription" class=$wizardClasses}
			{fbvElement type="textarea" multilingual="true" id="competingInterestsPolicy" rich=true value=$competingInterestsPolicy}
		{/fbvFormSection}

		{$additionalFormContent}
	{/fbvFormArea}

	{if !$wizardMode}
		{fbvFormButtons id="policiesFormSubmit" submitText="common.save" hideCancel=true}
	{/if}
</form>
