{**
 * templates/management/settings/distribution.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * The distribution process settings page.
 *}

{strip}
{assign var="pageTitle" value="manager.distribution.title"}
{include file="common/header.tpl"}
{/strip}

<script type="text/javascript">
	// Attach the JS file tab handler.
	$(function() {ldelim}
		$('#distributionTabs').pkpHandler(
				'$.pkp.controllers.TabHandler');
	{rdelim});
</script>
<div id="distributionTabs">
	<ul>
		<li><a href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.OJSDistributionSettingsTabHandler" op="showTab" tab="indexing"}">{translate key="manager.distribution.indexing"}</a></li>
		<li><a href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.OJSDistributionSettingsTabHandler" op="showTab" tab="identifiers"}">{translate key="manager.distribution.identifiers"}</a></li>
		<li><a href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.OJSDistributionSettingsTabHandler" op="showTab" tab="access"}">{translate key="manager.distribution.access"}</a></li>
		<li><a href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.OJSDistributionSettingsTabHandler" op="showTab" tab="paymentMethod"}">{translate key="manager.paymentMethod"}</a></li>
	</ul>
</div>

{include file="common/footer.tpl"}
