{**
 * @file plugins/importexport/doaj/index.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List of operations this plugin can perform
 *}
{strip}
{include file="common/header.tpl" pageTitle="plugins.importexport.doaj.displayName"}
{/strip}

<script type="text/javascript">
	// Attach the JS file tab handler.
	$(function() {ldelim}
		$('#importExportTabs').pkpHandler('$.pkp.controllers.TabHandler');
	{rdelim});
</script>
<div id="importExportTabs">
	<ul>
		<li><a href="#settings-tab">{translate key="plugins.importexport.common.settings"}</a></li>
		<li><a href="#exportSubmissions-tab">{translate key="plugins.importexport.common.export.articles"}</a></li>
	</ul>
	<div id="settings-tab">
		<p><a href="http://www.doaj.org/application/new" target="_blank">{translate key="plugins.importexport.doaj.export.contact"}</a></p>
	</div>
	<div id="exportSubmissions-tab">
		<script type="text/javascript">
			$(function() {ldelim}
				// Attach the form handler.
				$('#exportSubmissionXmlForm').pkpHandler('$.pkp.controllers.form.FormHandler');
			{rdelim});
		</script>
		<form id="exportSubmissionXmlForm" class="pkp_form" action="{plugin_url path="exportSubmissions"}" method="post">
			<input type="hidden" name="tab" value="exportSubmissions-tab" />
			{fbvFormArea id="submissionsXmlForm"}
				{url|assign:submissionsListGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.submissions.ExportPublishedSubmissionsListGridHandler" op="fetchGrid" plugin="doaj" category="importexport" escape=false}
				{load_url_in_div id="submissionsListGridContainer" url=$submissionsListGridUrl}
				{fbvElement type="submit" label="plugins.importexport.common.action.export" id="export" name="export" value="1" class="export" inline=true}
				{fbvElement type="submit" label="plugins.importexport.common.action.markRegistered" id="markRegistered" name="markRegistered" value="1" class="markRegistered" inline=true}
			{/fbvFormArea}
		</form>
	</div>
</div>

{include file="common/footer.tpl"}
