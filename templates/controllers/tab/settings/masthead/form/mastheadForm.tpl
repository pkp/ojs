{**
 * controllers/tab/settings/masthead/form/mastheadForm.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Masthead management form.
 *
 *}

{* Help Link *}
{help file="settings.md" section="context" class="pkp_help_tab"}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#mastheadForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="mastheadForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.JournalSettingsTabHandler" op="saveFormData" tab="masthead"}">
	{csrf}

	{include file="controllers/tab/settings/wizardMode.tpl" wizardMode=$wizardMode}

	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="mastheadNotification"}

	{fbvFormArea id="mastheadNameContainer"}
		{fbvFormSection title="manager.setup.contextName" for="name" required=true inline=true size=$fbvStyles.size.MEDIUM}
			{fbvElement type="text" multilingual=true name="name" id="name" value=$name required=true}
		{/fbvFormSection}

		{fbvFormSection title="manager.setup.journalInitials" for="acronym" required=true inline=true size=$fbvStyles.size.SMALL}
			{fbvElement type="text" multilingual=true name="acronym" id="acronym" value=$acronym required=true}
		{/fbvFormSection}

		{fbvFormSection title="manager.setup.journalAbbreviation" for="abbreviation" inline=true size=$fbvStyles.size.SMALL}
			{fbvElement type="text" multilingual=true name="abbreviation" id="abbreviation" value=$abbreviation}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormArea id="mastheadPublisher"}
		{fbvFormSection label="manager.setup.publisher" for="publisherInstitution" description="manager.setup.publisherDescription"}
			{fbvElement type="text" name="publisherInstitution" id="publisherInstitution" value=$publisherInstitution maxlength="255"}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormArea id="mastheadISSNContainer"}
		{fbvFormSection label="manager.setup.Issn" for="onlineIssn"}
			{fbvElement type="text" name="onlineIssn" id="onlineIssn" value=$onlineIssn label="manager.setup.onlineIssn" size=$fbvStyles.size.SMALL inline=true}
			{fbvElement type="text" name="printIssn" id="printIssn" value=$printIssn label="manager.setup.printIssn" size=$fbvStyles.size.SMALL inline=true}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormArea id="mastheadJournalAbout"}
		{fbvFormSection label="manager.setup.journalSummary" for="summary" description="manager.setup.journalSummary.description"}
			{fbvElement type="textarea" multilingual=true name="description" id="description" value=$description rich=true}
		{/fbvFormSection}
		{fbvFormSection label="manager.setup.editorialTeam" for="editorialTeam" description="manager.setup.editorialTeam.description"}
			{fbvElement type="textarea" multilingual=true name="editorialTeam" id="editorialTeam" value=$editorialTeam rich=true}
		{/fbvFormSection}
		{fbvFormSection label="manager.setup.journalAbout" for="about" description="manager.setup.journalAbout.description"}
			{fbvElement type="textarea" multilingual=true name="about" id="about" value=$about rich="extended" rows=30}
		{/fbvFormSection}
	{/fbvFormArea}

	{if !$wizardMode}
		{fbvFormButtons id="mastheadFormSubmit" submitText="common.save" hideCancel=true}
	{/if}
</form>
