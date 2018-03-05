{**
 * plugins/importexport/medra/templates/settingsForm.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * mEDRA plugin settings
 *
 *}
<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#medraSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>
<form class="pkp_form" id="medraSettingsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" plugin="MedraExportPlugin" category="importexport" verb="save"}">
	{if $doiPluginSettingsLinkAction}
		{fbvFormArea id="doiPluginSettingsLink"}
			{fbvFormSection}
				{include file="linkAction/linkAction.tpl" action=$doiPluginSettingsLinkAction}
			{/fbvFormSection}
		{/fbvFormArea}
	{/if}
	{fbvFormArea id="medraSettingsFormArea"}
		<p class="pkp_help">{translate key="plugins.importexport.medra.settings.form.description"}</p>
		{fbvFormSection}
			{fbvElement type="text" id="registrantName" value=$registrantName required="true" label="plugins.importexport.medra.settings.form.registrantName" maxlength="60" size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
		{fbvFormSection}
			<p class="pkp_help">{translate key="plugins.importexport.medra.settings.form.fromFields"}</p>
			{fbvElement type="text" id="fromCompany" value=$fromCompany required="true" label="plugins.importexport.medra.settings.form.fromCompany" maxlength="60" size=$fbvStyles.size.MEDIUM}
			{fbvElement type="text" id="fromName" value=$fromName required="true" label="plugins.importexport.medra.settings.form.fromName" maxlength="60" size=$fbvStyles.size.MEDIUM}
			{fbvElement type="text" id="fromEmail" value=$fromEmail required="true" label="plugins.importexport.medra.settings.form.fromEmail" maxlength="90" size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
		{fbvFormSection}
			<p class="pkp_help">{translate key="plugins.importexport.medra.settings.form.publicationCountry"}</p>
			{fbvElement type="select" id="publicationCountry" required="true" from=$countries selected=$publicationCountry translate=false size=$fbvStyles.size.MEDIUM label="common.country"}
		{/fbvFormSection}
		{fbvFormSection}
			<p class="pkp_help">{translate key="plugins.importexport.medra.settings.form.exportIssuesAs"}</p>
			{fbvElement type="select" id="exportIssuesAs" required="true" from=$exportIssueOptions selected=$exportIssuesAs translate=false size=$fbvStyles.size.MEDIUM label="plugins.importexport.medra.settings.form.exportIssuesAs.label"}
			<span class="instruct">{translate key="plugins.importexport.medra.workOrProduct"}</span><br/>
		{/fbvFormSection}
		{fbvFormSection}
			<p class="pkp_help">{translate key="plugins.importexport.medra.intro"}</p>
			{fbvElement type="text" id="username" value=$username label="plugins.importexport.medra.settings.form.username" maxlength="50" size=$fbvStyles.size.MEDIUM}
			{fbvElement type="text" password="true" id="password" value=$password label="plugins.importexport.common.settings.form.password" maxLength="50" size=$fbvStyles.size.MEDIUM}
			<span class="instruct">{translate key="plugins.importexport.common.settings.form.password.description"}</span><br/>
		{/fbvFormSection}
		{fbvFormSection list="true"}
			{fbvElement type="checkbox" id="automaticRegistration" label="plugins.importexport.medra.settings.form.automaticRegistration.description" checked=$automaticRegistration|compare:true}
		{/fbvFormSection}
		{fbvFormSection list="true"}
			{fbvElement type="checkbox" id="testMode" label="plugins.importexport.medra.settings.form.testMode.description" checked=$testMode|compare:true}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormButtons submitText="common.save"}
	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</form>
