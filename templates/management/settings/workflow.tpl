{**
 * templates/management/settings/publication.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * The publication process settings page.
 *}

{strip}
{assign var="pageTitle" value="manager.workflow.title"}
{include file="common/header.tpl"}
{/strip}

<script type="text/javascript">
	// Attach the JS file tab handler.
	$(function() {ldelim}
		$('#publicationTabs').pkpHandler(
				'$.pkp.controllers.TabHandler');
	{rdelim});
</script>
<div id="publicationTabs">
	<ul>
		<li><a href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.PublicationSettingsTabHandler" op="showTab" tab="genres"}">{translate key="grid.genres.title"}</a></li>
		<li><a href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.PublicationSettingsTabHandler" op="showTab" tab="submissionStage"}">{translate key="manager.publication.submissionStage"}</a></li>
		<li><a href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.PublicationSettingsTabHandler" op="showTab" tab="contentIndexing"}">{translate key="manager.workflow.contentIndexing"}</a></li>
		<li><a href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.PublicationSettingsTabHandler" op="showTab" tab="reviewStage"}">{translate key="manager.publication.reviewStage"}</a></li>
		<li><a href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.PublicationSettingsTabHandler" op="showTab" tab="library"}">{translate key="manager.publication.library"}</a></li>
		<li><a href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.PublicationSettingsTabHandler" op="showTab" tab="emailTemplates"}">{translate key="manager.publication.emails"}</a></li>
		<li><a href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.PublicationSettingsTabHandler" op="showTab" tab="productionStage"}">{translate key="manager.publication.productionStage"}</a></li>
	</ul>
</div>

{include file="common/footer.tpl"}
