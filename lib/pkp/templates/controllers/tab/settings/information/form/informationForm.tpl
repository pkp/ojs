{**
 * controllers/tab/settings/information/form/informationForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Information management form.
 *
 *}

{* Help Link *}
{help file="settings.md" section="website" class="pkp_help_tab"}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#informationForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler', {ldelim}
			baseUrl: {$baseUrl|json_encode}
		{rdelim});
	{rdelim});
</script>

<form id="informationForm" class="pkp_form" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.WebsiteSettingsTabHandler" op="saveFormData" tab="information"}">
	{csrf}

	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="informationFormNotification"}

	{fbvFormArea id="information"}
		{fbvFormSection label="manager.setup.information.descriptionTitle" for="readerInformation" description="manager.setup.information.description"}{/fbvFormSection}
		{fbvFormSection label="manager.setup.information.forReaders" for="readerInformation"}
			{fbvElement type="textarea" multilingual=true id="readerInformation" value=$readerInformation rich=true}
		{/fbvFormSection}
		{fbvFormSection label="manager.setup.information.forAuthors" for="authorInformation"}
			{fbvElement type="textarea" multilingual=true id="authorInformation" value=$authorInformation rich=true}
		{/fbvFormSection}
		{fbvFormSection label="manager.setup.information.forLibrarians" for="librarianInformation"}
			{fbvElement type="textarea" multilingual=true id="librarianInformation" value=$librarianInformation rich=true}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormButtons id="informationFormSubmit" submitText="common.save" hideCancel=true}
</form>
