{**
 * plugins/generic/datacite/templates/settingsForm.tpl
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Datacite plugin settings
 *
 *}
<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#dataciteSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<div class="pkp_notification" id="dataciteConfigurationErrors">
	{foreach from=$configurationErrors item=configurationError}
		{if $configurationError == $smarty.const.DOI_EXPORT_CONFIG_ERROR_DOIPREFIX}
			{include file="controllers/notification/inPlaceNotificationContent.tpl" notificationId=dataciteConfigurationErrors notificationStyleClass="notifyWarning" notificationTitle="plugins.importexport.common.missingRequirements"|translate notificationContents="plugins.importexport.common.error.DOIsNotAvailable"|translate}
		{elseif $configurationError == $smarty.const.EXPORT_CONFIG_ERROR_SETTINGS}
			{include file="controllers/notification/inPlaceNotificationContent.tpl" notificationId=dataciteConfigurationErrors notificationStyleClass="notifyWarning" notificationTitle="plugins.importexport.common.missingRequirements"|translate notificationContents="plugins.importexport.common.error.pluginNotConfigured"|translate}
		{/if}
	{/foreach}
	{if !$exportArticles && !$exportIssues && !$exportRepresentations}
		{include file="controllers/notification/inPlaceNotificationContent.tpl" notificationId=dataciteConfigurationErrors notificationStyleClass="notifyWarning" notificationTitle="plugins.importexport.common.missingRequirements"|translate notificationContents="plugins.importexport.common.error.noDOIContentObjects"|translate}
	{/if}
</div>

<form class="pkp_form" id="dataciteSettingsForm" method="post" action="{url router=\PKP\core\PKPApplication::ROUTE_COMPONENT op="manage" plugin="DataciteExportPlugin" category="importexport" verb="save"}">
	{csrf}
	{if $doiPluginSettingsLinkAction}
		{fbvFormArea id="doiPluginSettingsLink"}
			{fbvFormSection}
				{include file="linkAction/linkAction.tpl" action=$doiPluginSettingsLinkAction}
			{/fbvFormSection}
		{/fbvFormArea}
	{/if}
	{fbvFormArea id="dataciteSettingsFormArea"}
		<p class="pkp_help">{translate key="plugins.importexport.datacite.settings.description"}</p>
		<p class="pkp_help">{translate key="plugins.importexport.datacite.intro"}</p>
		{fbvFormSection}
			{fbvElement type="text" id="username" value=$username label="plugins.importexport.datacite.settings.form.username" maxlength="50" size=$fbvStyles.size.MEDIUM}
			{fbvElement type="text" password="true" id="password" value=$password label="plugins.importexport.common.settings.form.password" maxLength="50" size=$fbvStyles.size.MEDIUM}
			<span class="instruct">{translate key="plugins.importexport.common.settings.form.password.description"}</span><br/>
		{/fbvFormSection}
		{fbvFormSection list="true"}
			{fbvElement type="checkbox" id="automaticRegistration" label="plugins.importexport.datacite.settings.form.automaticRegistration.description" checked=$automaticRegistration|compare:true}
		{/fbvFormSection}
		{fbvFormSection list="true"}
			{fbvElement type="checkbox" id="testMode" label="plugins.importexport.datacite.settings.form.testMode.description" checked=$testMode|compare:true}
			{fbvElement type="text" id="testUsername" value=$testUsername label="plugins.importexport.datacite.settings.form.testUsername" maxlength="50" size=$fbvStyles.size.MEDIUM}
			{fbvElement type="text" password="true" id="testPassword" value=$testPassword label="plugins.importexport.datacite.settings.form.testPassword" maxLength="50" size=$fbvStyles.size.MEDIUM}
			<span class="instruct">{translate key="plugins.importexport.common.settings.form.password.description"}</span><br/>
			{fbvElement type="text" id="testDOIPrefix" value=$testDOIPrefix label="plugins.importexport.datacite.settings.form.testDOIPrefix" maxlength="50" size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormButtons submitText="common.save"}
	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</form>
