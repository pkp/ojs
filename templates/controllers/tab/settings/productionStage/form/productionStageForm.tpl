{**
 * controllers/tab/settings/productionStage/form/productionStageForm.tpl
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Production Stage settings management form.
 *
 *}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#productionStageForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="productionStageForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.PublicationSettingsTabHandler" op="saveFormData" tab="productionStage"}">
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="productionStageFormNotification"}

	<p class="pkp_help">{translate key="manager.setup.publisherDescription"}</p>
	{fbvFormArea id="publisherInformation"}
		{fbvFormSection id="publisherNoteSection" for="publisherNote" label="manager.setup.note"}
			{fbvElement type="textarea" name="publisherNote" id="publisherNote" value=$publisherNote multilingual=true label="manager.setup.publisherNoteDescription"}
		{/fbvFormSection}
		{fbvFormSection id="publisherInstitutionSection" for="publisherInstitution" label="manager.setup.institution"}
			{fbvElement type="text" name="publisherInstitution" id="publisherInstitution" value=$publisherInstitution maxlength="255"}
		{/fbvFormSection}
		{fbvFormSection id="publisherUrlSection" for="publisherUrl" label="common.url"}
			{fbvElement type="text" name="publisherUrl" required="true" id="publisherUrl" value=$publisherUrl maxlength="255"}
		{/fbvFormSection}
	{/fbvFormArea}

	<div class="separator"></div>

	{if !$wizardMode}
		{fbvFormButtons id="productionStageFormSubmit" submitText="common.save" hideCancel=true}
	{/if}
</form>
