{**
 * plugins/importexport/pubmed/templates/settingsForm.tpl
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Pubmed plugin settings
 *
 *}
<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#pubmedSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
		{rdelim});
</script>
<div class="legacyDefaults">
	<form class="pkp_form" id="pubmedSettingsForm" method="post" action="{url router=PKP\core\PKPApplication::ROUTE_COMPONENT op="manage" plugin="PubMedExportPlugin" category="importexport" verb="save"}">
		{csrf}
		{fbvFormArea id="pubmedSettingsFormArea"}
			{fbvFormSection}
				<span class="instruct">{translate key="plugins.importexport.pubmed.settings.form.nlmTitle.description"}</span><br/>
				{fbvElement type="text" id="nlmTitle" value=$nlmTitle label="plugins.importexport.pubmed.settings.form.nlmTitle" maxlength="100" size=$fbvStyles.size.MEDIUM}
			{/fbvFormSection}
		{/fbvFormArea}
		{fbvFormButtons submitText="common.save"}
		<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
	</form>
</div>
