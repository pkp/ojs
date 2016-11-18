{**
 * templates/controllers/tab/settings/versioning/form/versioningForm.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display options for versioning in journal management.
 *
 *}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#versioningForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="versioningForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.JournalSettingsTabHandler" op="saveFormData" tab="versioning"}">

	{fbvFormArea id="versioningSettings"}

		{fbvFormSection for="versioningEnabled" list=true}
			{fbvElement type="checkbox" label="manager.setup.versioningEnabled" id="versioningEnabled" checked=$versioningEnabled}
		{/fbvFormSection}

	{/fbvFormArea}

	{if !$wizardMode}
		{fbvFormButtons id="versioningFormSubmit" submitText="common.save" hideCancel=true}
	{/if}

</form>
