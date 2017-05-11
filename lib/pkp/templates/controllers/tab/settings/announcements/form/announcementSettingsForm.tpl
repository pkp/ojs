{**
 * controllers/tab/settings/announcements/form/announcementSettingsForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Announcement settings form.
 *
 *}

{* Help Link *}
{help file="settings.md" section="website" class="pkp_help_tab"}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#announcementSettingsForm').pkpHandler('$.pkp.controllers.tab.settings.announcements.form.AnnouncementSettingsFormHandler',
			{ldelim}
				publishChangeEvents: ['updateHeader']
			{rdelim}
		);
	{rdelim});
</script>

<form id="announcementSettingsForm" class="pkp_form" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.WebsiteSettingsTabHandler" op="saveFormData" tab="announcements"}">
	{csrf}
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="announcementSettingsFormNotification"}

	{fbvFormArea id="announcements" title="manager.setup.announcements"}
		{fbvFormSection list=true description="manager.setup.announcementsDescription"}
			{fbvElement type="checkbox" id="enableAnnouncements" label="manager.setup.enableAnnouncements" value="1" checked=$enableAnnouncements}
			{fbvElement type="checkbox" id="enableAnnouncementsHomepage" label="manager.setup.enableAnnouncementsHomepage1" value="1" checked=$enableAnnouncementsHomepage inline=true}
			{fbvElement type="select" id="numAnnouncementsHomepage" from=$numAnnouncementsHomepageOptions selected=$numAnnouncementsHomepage defaultValue="1" translate=false disabled=$disableAnnouncementsHomepage inline=true}
			<span>{translate key="manager.setup.enableAnnouncementsHomepage2"}</span>
		{/fbvFormSection}
		{fbvFormSection description="manager.setup.announcementsIntroductionDescription"}
			{fbvElement type="textarea" multilingual="true" id="announcementsIntroduction" value=$announcementsIntroduction rich=true}
		{/fbvFormSection}
	{/fbvFormArea}

	{url|assign:announcementTypeGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.announcements.AnnouncementTypeGridHandler" op="fetchGrid" escape=false}
	{load_url_in_div id="announcementTypeGridContainer" url=$announcementTypeGridUrl}

	{url|assign:announcementGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.announcements.ManageAnnouncementGridHandler" op="fetchGrid" escape=false}
	{load_url_in_div id="announcementGridContainer" url=$announcementGridUrl}

	{fbvFormButtons id="announcementSettingsFormSubmit" submitText="common.save" hideCancel=true}
</form>
