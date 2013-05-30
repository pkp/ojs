{**
 * templates/management/settings/journal.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * The journal settings page.
 *}

{strip}
{assign var="pageTitle" value="manager.setup"}
{include file="common/header.tpl"}
{/strip}

<script type="text/javascript">
	// Attach the JS file tab handler.
	$(function() {ldelim}
		$('#journalSettingsTabs').pkpHandler(
				'$.pkp.controllers.TabHandler');
	{rdelim});
</script>
<div id="journalSettingsTabs">
	<ul>
		<li><a href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.JournalSettingsTabHandler" op="showTab" tab="masthead"}">{translate key="manager.setup.masthead"}</a></li>
		<li><a href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.JournalSettingsTabHandler" op="showTab" tab="contact"}">{translate key="about.contact"}</a></li>
		<li><a href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.JournalSettingsTabHandler" op="showTab" tab="citations"}">{translate key="manager.setup.citations"}</a></li>
		<li><a href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.JournalSettingsTabHandler" op="showTab" tab="sections"}">{translate key="section.sections"}</a></li>
		<li><a href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.JournalSettingsTabHandler" op="showTab" tab="policies"}">{translate key="manager.setup.policies"}</a></li>
		<li><a href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.JournalSettingsTabHandler" op="showTab" tab="guidelines"}">{translate key="about.guidelines"}</a></li>
		<li><a href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.JournalSettingsTabHandler" op="showTab" tab="affiliationAndSupport"}">{translate key="manager.affiliationAndSupport"}</a></li>
	</ul>
</div>

{include file="common/footer.tpl"}
