{**
 * plugins/generic/usageStats/templates/usageStatsSettingsForm.tpl
 *
 * Copyright (c) 2013-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Usage statistics plugin management form.
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.generic.usageStats.displayName"}
{include file="common/header.tpl"}
{/strip}
<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#usageStatsSettingsForm').pkpHandler('$.pkp.controllers.form.FormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="usageStatsSettingsForm" method="post" action="{plugin_url path="save"}">

	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="usageStatsSettingsFormNotification"}

	{fbvFormArea id="usageStatsLogging" title="plugins.generic.usageStats.settings.logging"}
		{fbvFormSection for="createLogFiles" list=true description="plugins.generic.usageStats.settings.createLogFiles.description"}
			{fbvElement type="checkbox" id="createLogFiles" value="1" checked=$createLogFiles label="plugins.generic.usageStats.settings.createLogFiles"}
		{/fbvFormSection}
		{fbvFormSection title="plugins.generic.usageStats.settings.logParseRegex" description="plugins.generic.usageStats.settings.logParseRegex.description"}
			{fbvElement type="text" id="accessLogFileParseRegex" value=$accessLogFileParseRegex}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormArea id="usageStatsArchives" title="plugins.generic.usageStats.settings.archives"}
		{fbvFormSection for="compressArchives" list=true description="plugins.generic.usageStats.settings.compressArchives.description"}
			{fbvElement type="checkbox" id="compressArchives" value="1" checked=$compressArchives label="plugins.generic.usageStats.settings.compressArchives"}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormArea id="usageStatsDataPrivacy" title="plugins.generic.usageStats.settings.dataPrivacyOption"}
		{fbvFormSection for="saltFilepath" title="plugins.generic.usageStats.settings.dataPrivacyOption.saltFilepath" description="plugins.generic.usageStats.settings.dataPrivacyOption.requirements"}
			{fbvElement type="text" id="saltFilepath" value=$saltFilepath}
		{/fbvFormSection}
		{fbvFormSection for="dataPrivacyOption" list=true description="plugins.generic.usageStats.settings.dataPrivacyOption.description"}
			{fbvElement type="checkbox" id="dataPrivacyOption" value="1" checked=$dataPrivacyOption label="plugins.generic.usageStats.settings.dataPrivacyCheckbox" disabled=$disabled}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormArea id="usageStatsOptionalColumns" title="plugins.generic.usageStats.settings.optionalColumns"}
		{fbvFormSection for="optionalColumns" list=true description="plugins.generic.usageStats.settings.optionalColumns.description"}
			{fbvElement type="checkboxgroup" id="optionalColumns" from=$optionalColumnsOptions selected=$selectedOptionalColumns translate=false}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormButtons id="usageStatsSettingsFormSubmit" submitText="common.save" hideCancel=true}
</form>
{include file="common/footer.tpl"}
