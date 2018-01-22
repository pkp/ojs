{**
 * plugins/importexport/native/templates/index.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List of operations this plugin can perform
 *}
{strip}
{assign var="pageTitle" value="plugins.importexport.native.displayName"}
{include file="common/header.tpl"}
{/strip}

<script type="text/javascript">
	// Attach the JS file tab handler.
	$(function() {ldelim}
		$('#importExportTabs').pkpHandler('$.pkp.controllers.TabHandler');
		$('#importExportTabs').tabs('option', 'cache', true);
	{rdelim});
</script>
<div id="importExportTabs">
	<ul>
		<li><a href="#import-tab">{translate key="plugins.importexport.native.import"}</a></li>
		<li><a href="#exportSubmissions-tab">{translate key="plugins.importexport.native.exportSubmissions"}</a></li>
		<li><a href="#exportIssues-tab">{translate key="plugins.importexport.native.exportIssues"}</a></li>
	</ul>
	<div id="import-tab">
		<script type="text/javascript">
			$(function() {ldelim}
				// Attach the form handler.
				$('#importXmlForm').pkpHandler('$.pkp.controllers.form.FileUploadFormHandler',
					{ldelim}
						$uploader: $('#plupload'),
							uploaderOptions: {ldelim}
								uploadUrl: {plugin_url|json_encode path="uploadImportXML" escape=false},
								baseUrl: {$baseUrl|json_encode}
							{rdelim}
					{rdelim}
				);
			{rdelim});
		</script>
		<form id="importXmlForm" class="pkp_form" action="{plugin_url path="importBounce"}" method="post">
			{csrf}
			{fbvFormArea id="importForm"}
				{* Container for uploaded file *}
				<input type="hidden" name="temporaryFileId" id="temporaryFileId" value="" />

				{fbvFormArea id="file"}
					{fbvFormSection title="plugins.importexport.native.import.instructions"}
						{include file="controllers/fileUploadContainer.tpl" id="plupload"}
					{/fbvFormSection}
				{/fbvFormArea}

				{fbvFormButtons submitText="plugins.importexport.native.import" hideCancel="true"}
			{/fbvFormArea}
		</form>
	</div>
	<div id="exportSubmissions-tab">
		<script type="text/javascript">
			$(function() {ldelim}
				// Attach the form handler.
				$('#exportSubmissionXmlForm').pkpHandler('$.pkp.controllers.form.FormHandler');
			{rdelim});
		</script>
		<form id="exportSubmissionXmlForm" class="pkp_form" action="{plugin_url path="exportSubmissions"}" method="post">
			{csrf}
			{fbvFormArea id="submissionsXmlForm"}
				{fbvFormSection}
					{assign var="uuid" value=""|uniqid|escape}
					<div id="export-submissions-list-handler-{$uuid}">
						<script type="text/javascript">
							pkp.registry.init('export-submissions-list-handler-{$uuid}', 'SelectSubmissionsListPanel', {$exportSubmissionsListData});
						</script>
					</div>
				{/fbvFormSection}
				{fbvFormButtons submitText="plugins.importexport.native.exportSubmissions" hideCancel="true"}
			{/fbvFormArea}
		</form>
	</div>
	<div id="exportIssues-tab">
		<script type="text/javascript">
			$(function() {ldelim}
				// Attach the form handler.
				$('#exportIssuesXmlForm').pkpHandler('$.pkp.controllers.form.FormHandler');
			{rdelim});
		</script>
		<form id="exportIssuesXmlForm" class="pkp_form" action="{plugin_url path="exportIssues"}" method="post">
			{csrf}
			{fbvFormArea id="issuesXmlForm"}
				{url|assign:issuesListGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.issues.ExportableIssuesListGridHandler" op="fetchGrid" escape=false}
				{load_url_in_div id="issuesListGridContainer" url=$issuesListGridUrl}
				{fbvFormButtons submitText="plugins.importexport.native.exportIssues" hideCancel="true"}
			{/fbvFormArea}
		</form>
	</div>
</div>

{include file="common/footer.tpl"}
