{**
 * plugins/generic/announcementFeed/settingsForm.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Announcement Feed plugin settings
 *
 *}
<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#announcementFeedSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="announcementFeedSettingsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="settings" save=true}">
	<div id="announcementFeedSettings">
		<div id="description">{translate key="plugins.generic.announcementfeed.description"}</div>

		<div class="separator">&nbsp;</div>

		<h3>{translate key="plugins.generic.announcementfeed.settings"}</h3>

		{csrf}
		{include file="common/formErrors.tpl"}

		{fbvFormArea id="webFeedSettingsFormArea"}
			{fbvFormSection list=true}
				{fbvElement type="radio" id="displayPage-all" name="displayPage" value="all" checked=$displayPage|compare:"all" label="plugins.generic.announcementfeed.settings.all"}
				{fbvElement type="radio" id="displayPage-homepage" name="displayPage" value="homepage" checked=$displayPage|compare:"homepage" label="plugins.generic.announcementfeed.settings.homepage"}
				{fbvElement type="radio" id="displayPage-announcement" name="displayPage" value="announcement" checked=$displayPage|compare:"announcement" label="plugins.generic.announcementfeed.settings.announcement"}
			{/fbvFormSection}

			{fbvFormSection list=true}
				{translate key="plugins.generic.announcementfeed.settings.recentAnnouncements1"}
				{fbvElement type="text" id="recentItems" value=$recentItems label="plugins.generic.announcementfeed.settings.recentAnnouncements2" size=$fbvStyles.size.SMALL}
			{/fbvFormSection}
		{/fbvFormArea}

		{fbvFormButtons}

		<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
	</div>
</form>
