{**
 * controllers/tab/settings/masthead/form/mastheadForm.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Masthead management form.
 *
 *}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#mastheadForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="mastheadForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.JournalSettingsTabHandler" op="saveFormData" tab="masthead"}">

	{include file="controllers/tab/settings/wizardMode.tpl" wizardMode=$wizardMode}

	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="mastheadNotification"}

	{fbvFormArea id="mastheadFormArea"}

		{fbvFormSection list=true}
			{if $enabled}{assign var="enabled" value="checked"}{/if}
			{fbvElement type="checkbox" id="journalEnabled" value="1" checked=$enabled label="admin.journals.enableJournalInstructions"}
		{/fbvFormSection}

		{fbvFormSection title="manager.setup.contextName" for="name" required=true inline=true size=$fbvStyles.size.MEDIUM}
			{fbvElement type="text" multilingual=true name="name" id="name" value=$name}
		{/fbvFormSection}

		{fbvFormSection title="manager.setup.journalInitials" for="acronym" required=true inline=true size=$fbvStyles.size.SMALL}
			{fbvElement type="text" multilingual=true name="acronym" id="acronym" value=$acronym}
		{/fbvFormSection}

		{fbvFormSection title="manager.setup.journalAbbreviation" for="abbreviation" inline=true size=$fbvStyles.size.SMALL}
			{fbvElement type="text" multilingual=true name="abbreviation" id="abbreviation" value=$abbreviation}
		{/fbvFormSection}

		{fbvFormSection label="manager.setup.Issn" for="onlineIssn" description="manager.setup.issnDescription"}
			{fbvElement type="text" name="onlineIssn" id="onlineIssn" value=$onlineIssn label="manager.setup.onlineIssn" size=$fbvStyles.size.SMALL inline=true}
			{fbvElement type="text" name="printIssn" id="printIssn" value=$printIssn label="manager.setup.printIssn" size=$fbvStyles.size.SMALL inline=true}
		{/fbvFormSection}

		{fbvFormSection label="manager.setup.journalDescription" for="description"}
			{fbvElement type="textarea" multilingual=true name="description" id="description" value=$description rich=true height=$fbvStyles.height.SHORT}
		{/fbvFormSection}

		{fbvFormSection label="manager.masthead.title" for="masthead" description="manager.setup.masthead.description"}
			{fbvElement type="textarea" multilingual=true id="masthead" value=$masthead rich=true height=$fbvStyles.height.SHORT}
		{/fbvFormSection}

		{fbvFormSection label="manager.setup.history" for="history"}
			{fbvElement type="textarea" multilingual=true name="history" id="history" value=$history rich=true}
		{/fbvFormSection}

		<div {if $wizardMode}class="pkp_form_hidden"{/if}>
			{fbvFormSection label="common.mailingAddress" for="mailingAddress" group=true description="manager.setup.mailingAddressDescription"}
				{fbvElement type="textarea" id="mailingAddress" value=$mailingAddress height=$fbvStyles.height.SHORT}
			{/fbvFormSection}
		</div>

		{if $categoriesEnabled}
			{url|assign:categoriesUrl router=$smarty.const.ROUTE_COMPONENT component="listbuilder.settings.categories.CategoriesListbuilderHandler" op="fetch" escape=false}
			{load_url_in_div id="categoriesContainer" url=$categoriesUrl}
		{/if}
	{/fbvFormArea}

	{if !$wizardMode}
		{fbvFormButtons id="mastheadFormSubmit" submitText="common.save" hideCancel=true}
	{/if}
</form>
