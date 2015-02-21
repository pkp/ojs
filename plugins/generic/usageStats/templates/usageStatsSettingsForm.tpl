{**
 * plugins/generic/usageStats/templates/usageStatsSettingsForm.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
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
			{fbvElement type="text" id="accessLogFileParseRegex" value=$accessLogFileParseRegex"}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormButtons id="usageStatsSettingsFormSubmit" submitText="common.save" hideCancel=true}
</form>
{include file="common/footer.tpl"}
