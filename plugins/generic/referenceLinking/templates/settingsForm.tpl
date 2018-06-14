{**
 * plugins/generic/referenceLinking/templates/settingsForm.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * ReferenceLinking plugin settings
 *
 *}
<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#referenceLinkingSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>
<form class="pkp_form" id="referenceLinkingSettingsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="save"}">
	{csrf}
	{fbvFormArea id="referenceLinkingRequirements"}
	<p class="pkp_help">
		{translate key="plugins.generic.referenceLinking.description.long"}
		<br />
		{translate key="plugins.generic.referenceLinking.description.note"}
	</p>
	<p class="pkp_help">{translate key="plugins.generic.referenceLinking.description.requirements"}</p>
	{if $doiPluginSettingsLinkAction}
		<p>{include file="linkAction/linkAction.tpl" action=$doiPluginSettingsLinkAction}</p>
	{/if}
	<p>{include file="linkAction/linkAction.tpl" action=$submissionSettingsLinkAction}</p>
	<p>{include file="linkAction/linkAction.tpl" action=$crossrefSettingsLinkAction}</p>
	{/fbvFormArea}
	{fbvFormArea id="referenceLinkingSettingsFormArea"}
		{fbvFormSection}
			<p class="pkp_help">{translate key="plugins.generic.referenceLinking.registrationIntro"}</p>
			{fbvElement type="text" id="username" value=$username label="plugins.generic.referenceLinking.settings.form.username" maxlength="50" size=$fbvStyles.size.MEDIUM required=true}
			{fbvElement type="text" password="true" id="password" value=$password label="plugins.importexport.common.settings.form.password" maxLength="50" size=$fbvStyles.size.MEDIUM required=true}
			<span class="instruct">{translate key="plugins.importexport.common.settings.form.password.description"}</span><br/>
		{/fbvFormSection}
		{fbvFormSection list="true"}
			{fbvElement type="checkbox" id="automaticRegistration" label="plugins.generic.referenceLinking.settings.form.automaticReferenceChecking.description" checked=$automaticRegistration|compare:true}
		{/fbvFormSection}
		{fbvFormSection list="true"}
			{fbvElement type="checkbox" id="testMode" label="plugins.generic.referenceLinking.settings.form.testMode.description" checked=$testMode|compare:true}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormButtons submitText="common.save"}
	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</form>
