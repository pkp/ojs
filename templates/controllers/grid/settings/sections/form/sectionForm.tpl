{**
 * templates/controllers/grid/settings/section/form/sectionForm.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Section form under journal management.
 *}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#sectionForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="sectionForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="grid.settings.sections.SectionGridHandler" op="updateSection" sectionId=$sectionId}">
	{csrf}
	<input type="hidden" name="sectionId" value="{$sectionId|escape}"/>

	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="sectionFormNotification"}

	{if $sectionEditorCount == 0}
		<span class="pkp_form_error"><p>{translate key="manager.section.noSectionEditors"}</p></span>
	{/if}

	{fbvFormArea id="sectionInfo"}
		{fbvFormSection}
			{fbvElement type="text" multilingual=true id="title" label="section.title" value=$title maxlength="80" size=$fbvStyles.size.MEDIUM inline=true required=true}
			{fbvElement type="text" multilingual=true id="abbrev" label="section.abbreviation" value=$abbrev maxlength="80" size=$fbvStyles.size.SMALL inline=true required=true}
		{/fbvFormSection}

		{fbvFormSection title="manager.sections.policy" for="policy"}
			{fbvElement type="textarea" multilingual=true id="policy" value=$policy rich=true}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormArea id="sectionMisc"}
		{fbvFormSection title="manager.sections.wordCount" for="wordCount" inline=true size=$fbvStyles.size.MEDIUM}
			{fbvElement type="text" id="wordCount" value=$wordCount maxlength="80" label="manager.sections.wordCountInstructions"}
		{/fbvFormSection}

		{call_hook name="Templates::Manager::Sections::SectionForm::AdditionalMetadata" sectionId=$sectionId}
	{/fbvFormArea}

	{fbvFormArea id="indexingInfo" title="submission.sectionOptions"}
		{fbvFormSection list=true}
			{fbvElement type="checkbox" id="abstractsNotRequired" checked=$abstractsNotRequired label="manager.sections.abstractsNotRequired"}
			{fbvElement type="checkbox" id="metaIndexed" checked=$metaIndexed label="manager.sections.submissionIndexing"}
			{fbvElement type="checkbox" id="editorRestriction" checked=$editorRestriction label="manager.sections.editorRestriction"}
			{fbvElement type="checkbox" id="hideTitle" checked=$hideTitle label="manager.sections.hideTocTitle"}
			{fbvElement type="checkbox" id="hideAuthor" checked=$hideAuthor label="manager.sections.hideTocAuthor"}
		{/fbvFormSection}

		{fbvFormSection for="identifyType" title="manager.sections.identifyType"}
			{fbvElement type="text" id="identifyType" label="manager.sections.identifyTypeExamples" value=$identifyType multilingual=true size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
	{/fbvFormArea}

	{if $hasSubEditors}
		{fbvFormSection}
			{assign var="uuid" value=""|uniqid|escape}
			<div id="subeditors-{$uuid}">
				<list-panel
					v-bind="components.subeditors"
					@set="set"
				/>
			</div>
			<script type="text/javascript">
				pkp.registry.init('subeditors-{$uuid}', 'Container', {$subEditorsListData|json_encode});
			</script>
		{/fbvFormSection}
	{/if}

	{fbvFormButtons submitText="common.save"}
</form>
