{**
 * templates/management/settings/journal.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
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
		<li><a href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.JournalSettingsTabHandler" op="showTab" tab="details"}">{translate key="manager.setup.details"}</a></li>
		<li><a href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.JournalSettingsTabHandler" op="showTab" tab="submissions"}">{translate key="manager.setup.submissions"}</a></li>
		<li><a href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.JournalSettingsTabHandler" op="showTab" tab="policies"}">{translate key="manager.setup.policies"}</a></li>
		<li><a href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.JournalSettingsTabHandler" op="showTab" tab="management"}">{translate key="manager.setup.management"}</a></li>
		<li><a href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.JournalSettingsTabHandler" op="showTab" tab="affiliationAndSupport"}">{translate key="manager.affiliationAndSupport"}</a></li>
	</ul>
</div>

{include file="common/footer.tpl"}
