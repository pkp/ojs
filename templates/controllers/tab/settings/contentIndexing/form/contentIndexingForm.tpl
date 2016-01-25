{**
 * controllers/tab/settings/contentIndexing/form/contentIndexingForm.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Indexing management form.
 *
 *}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#contentIndexingForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="contentIndexingForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.PublicationSettingsTabHandler" op="saveFormData" tab="contentIndexing"}">
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="contentIndexingFormNotification"}
	{include file="controllers/tab/settings/wizardMode.tpl" wizardMode=$wizardMode}

	<p>{translate key="manager.setup.forAuthorsToIndexTheirWorkDescription"}</p>

	{fbvFormArea id="contentIndexingDiscipline"}
		{fbvFormSection description="manager.setup.disciplineDescription" list=true}
			{fbvElement type="checkbox" id="metaDiscipline" checked=$metaDiscipline label="manager.setup.discipline"}
		{/fbvFormSection}
		{fbvFormSection description="manager.setup.coverageDescription" list=true}
			{fbvElement type="checkbox" id="metaCoverage" checked=$metaCoverage label="manager.setup.coverage"}
		{/fbvFormSection}
		{fbvFormSection list=true}
			{fbvElement type="checkbox" id="metaType" checked=$metaType label="manager.setup.typeMethodApproach"}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormArea id="contentIndexingSubjectClass"}
		{fbvFormSection list=true}
			{fbvElement type="checkbox" id="metaSubjectClass" checked=$metaSubjectClass label="manager.setup.subjectClassification"}
		{/fbvFormSection}
		{fbvFormSection description="manager.setup.subjectClassificationExamples"}
			{fbvElement type="text" label="common.title" id=metaSubjectClassTitle multilingual=true value=$metaSubjectClassTitle}
			{fbvElement type="text" label="common.url" id=metaSubjectClassUrl multilingual=true value=$metaSubjectClassUrl}
		{/fbvFormSection}
	{/fbvFormArea}

	{if !$wizardMode}
		{fbvFormButtons id="contentIndexingFormSubmit" submitText="common.save" hideCancel=true}
	{/if}
</form>
