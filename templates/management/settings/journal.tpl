{**
 * templates/management/settings/journal.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * The journal settings page.
 *}
{include file="common/header.tpl" pageTitle="manager.setup"}

{if $newVersionAvailable}
	<div class="pkp_notification">
		{translate|assign:"notificationContents" key="site.upgradeAvailable.manager" currentVersion=$currentVersion latestVersion=$latestVersion siteAdminName=$siteAdmin->getFullName() siteAdminEmail=$siteAdmin->getEmail()}
		{include file="controllers/notification/inPlaceNotificationContent.tpl" notificationId="upgradeWarning-"|uniqid notificationStyleClass="notifyWarning" notificationTitle="common.warning"|translate notificationContents=$notificationContents}
	</div>
{/if}

<script type="text/javascript">
	// Attach the JS file tab handler.
	$(function() {ldelim}
		$('#journalSettingsTabs').pkpHandler(
				'$.pkp.controllers.TabHandler');
	{rdelim});
</script>
<div id="journalSettingsTabs">
	<ul>
		<li><a name="masthead" href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.JournalSettingsTabHandler" op="showTab" tab="masthead"}">{translate key="manager.setup.masthead"}</a></li>
		<li><a name="contact" href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.JournalSettingsTabHandler" op="showTab" tab="contact"}">{translate key="about.contact"}</a></li>
		{* (Temporarily?) remove citation settings
		<li><a name="citations" href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.JournalSettingsTabHandler" op="showTab" tab="citations"}">{translate key="manager.setup.citations"}</a></li>
		*}
		<li><a name="sections" href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.JournalSettingsTabHandler" op="showTab" tab="sections"}">{translate key="section.sections"}</a></li>
		<li><a name="policies" href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.JournalSettingsTabHandler" op="showTab" tab="policies"}">{translate key="manager.setup.policies"}</a></li>
		<li><a name="guidelines" href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.JournalSettingsTabHandler" op="showTab" tab="guidelines"}">{translate key="about.guidelines"}</a></li>
		<li><a name="affiliationAndSupport" href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.JournalSettingsTabHandler" op="showTab" tab="affiliationAndSupport"}">{translate key="manager.affiliationAndSupport"}</a></li>
	</ul>
</div>

{include file="common/footer.tpl"}
