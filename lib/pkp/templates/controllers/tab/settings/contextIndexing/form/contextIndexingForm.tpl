{**
 * controllers/tab/settings/contextIndexing/form/contextIndexingForm.tpl
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
		$('#contextIndexingForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="contextIndexingForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.DistributionSettingsTabHandler" op="saveFormData" tab="indexing"}">
	{csrf}
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="contextIndexingFormNotification"}
	{include file="controllers/tab/settings/wizardMode.tpl" wizardMode=$wizardMode}

	{fbvFormArea id="searchEngineIndexing"}
		{fbvFormSection title="common.description" description="manager.setup.searchEngineIndexingDescription" label="manager.setup.searchEngineIndexing"}
			{fbvElement type="text" multilingual="true" id="searchDescription" name="searchDescription" value=$searchDescription size=$fbvStyles.size.LARGE}
		{/fbvFormSection}
		{fbvFormSection label="manager.setup.customTags" description="manager.setup.customTagsDescription"}
			{fbvElement type="textarea" multilingual="true" id="customHeaders" name="customHeaders" value=$customHeaders}
		{/fbvFormSection}
	{/fbvFormArea}

	{if !$wizardMode}
		{fbvFormButtons id="contextIndexingFormSubmit" submitText="common.save" hideCancel=true}
	{/if}
</form>
