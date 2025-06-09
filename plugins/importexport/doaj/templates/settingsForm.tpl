{**
 * plugins/importexport/doaj/templates/settingsForm.tpl
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * DOAJ plugin settings
 *
 *}
<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#doajSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>
<div class="semantic-defaults">
<form class="pkp_form" id="doajSettingsForm" method="post" action="{url router=PKP\core\PKPApplication::ROUTE_COMPONENT op="manage" plugin="DOAJExportPlugin" category="importexport" verb="save"}">
	{csrf}
	{fbvFormArea id="doajSettingsFormArea"}
		{fbvFormSection}
			<p class="pkp_help">{translate key="plugins.importexport.doaj.registrationIntro"}</p>
			{fbvElement type="text" id="apiKey" value=$apiKey label="plugins.importexport.doaj.settings.form.apiKey" maxlength="50" size=$fbvStyles.size.MEDIUM}
			<span class="instruct">{translate key="plugins.importexport.doaj.settings.form.apiKey.description"}</span><br/>
		{/fbvFormSection}
		{fbvFormSection list="true"}
			{fbvElement type="checkbox" id="automaticRegistration" label="plugins.importexport.doaj.settings.form.automaticRegistration.description" checked=$automaticRegistration|compare:true}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormButtons submitText="common.save"}
	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</form>
</div>