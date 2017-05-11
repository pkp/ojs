{**
 * controllers/tab/settings/permissionSettings/form/permissionSettingsForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Indexing management form.
 *
 *}

{* Help Link *}
{help file="settings.md" section="distribution" class="pkp_help_tab"}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#permissionSettingsForm').pkpHandler(
			'$.pkp.controllers.tab.settings.permissions.form.PermissionSettingsFormHandler',
			{ldelim}
				resetPermissionsUrl: {url|json_encode op="resetPermissions" escape=false},
				resetPermissionsConfirmText: {translate|json_encode key="manager.setup.resetPermissions.confirm"},
			{rdelim}
		);
	{rdelim});
</script>

<form class="pkp_form" id="permissionSettingsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.DistributionSettingsTabHandler" op="saveFormData" tab="permissions"}">
	{csrf}
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="permissionSettingsFormNotification"}
	{include file="controllers/tab/settings/wizardMode.tpl" wizardMode=$wizardMode}

	{fbvFormArea id="permissionSettings"}
		{fbvFormSection label="manager.setup.authorCopyrightNotice" description=$authorCopyrightNoticeDescription}
			{fbvElement type="textarea" multilingual=true name="copyrightNotice" id="copyrightNotice" value=$copyrightNotice rich=true}
		{/fbvFormSection}

		{fbvFormSection list=true}
			{fbvElement type="checkbox" id="copyrightNoticeAgree" value="1" checked=$copyrightNoticeAgree label="manager.setup.authorCopyrightNoticeAgree"}
		{/fbvFormSection}


		{$additionalFormContent}
	{/fbvFormArea}

	{fbvFormArea id="copyrightHolderSettings" title="submission.copyrightHolder"}
		{fbvFormSection list=true}
			{fbvElement type="radio" id="copyrightHolderType-author" name="copyrightHolderType" value="author" checked=$copyrightHolderType|compare:"author" label="user.role.author"}
			{fbvElement type="radio" id="copyrightHolderType-context" name="copyrightHolderType" value="context" checked=$copyrightHolderType|compare:"context" label="context.context"}
			{fbvElement type="radio" id="copyrightHolderType-author" name="copyrightHolderType" value="other" checked=$copyrightHolderType|compare:"other" label="common.other"}
		{/fbvFormSection}
		{fbvFormSection size=$fbvStyles.size.MEDIUM inline=true}
			{fbvElement type="text" id="copyrightHolderOther" name="copyrightHolderOther" value=$copyrightHolderOther multilingual=true label="common.other" disabled=$copyrightHolderType|compare:"other":false:true}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormArea id="licenseSettings"}
		{fbvFormSection title="submission.license"}
			{fbvElement type="select" id="licenseURLSelect" from=$ccLicenseOptions selected=$licenseURL size=$fbvStyles.size.MEDIUM inline=true}
			{fbvElement type="text" id="licenseURL" name="licenseURL" value=$licenseURL label="manager.setup.licenseURLDescription" size=$fbvStyles.size.MEDIUM inline=true}
		{/fbvFormSection}
	{/fbvFormArea}

	{if !$wizardMode}
		{fbvFormSection title="manager.setup.resetPermissions"}
			<p>{translate key="manager.setup.resetPermissions.description"}</p>
			{fbvElement type="button" id="resetPermissionsButton" label="manager.setup.resetPermissions"}
		{/fbvFormSection}
		{fbvFormSection class="formButtons"}
			{assign var=buttonId value="submitFormButton"|concat:"-"|uniqid}
			{fbvElement type="submit" class="submitFormButton" id=$buttonId label="common.save"}
		{/fbvFormSection}
	{/if}
</form>
