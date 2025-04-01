{**
 * plugins/importexport/native/templates/index.tpl
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * List of operations this plugin can perform
 *}
{extends file="layouts/backend.tpl"}

{block name="page"}
	<h1 class="app__pageHeading">
		{$pageTitle|escape}
	</h1>

<script type="text/javascript">
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
				$('#exportXmlForm').pkpHandler('$.pkp.controllers.form.FormHandler');

				$('#exportXmlForm').on('submit', function() {ldelim}
					setTimeout(function() {ldelim}
						$('#exportXmlForm .is_visible').removeClass('is_visible');
						$('#exportXmlForm .pkp_spinner').hide();
					{rdelim}, 2000);
				{rdelim});
			{rdelim});
		</script>
		<form id="exportXmlForm" class="pkp_form" action="{plugin_url path="exportSubmissions"}" method="post">
			{csrf}
			{fbvFormArea id="submissionsXmlForm"}
				<submissions-list-panel
					v-bind="components.submissions"
					@set="set"
				>

					<template v-slot:item="{ldelim}item{rdelim}">
						<div class="listPanel__itemSummary">
							<label>
								<input
									type="checkbox"
									name="selectedSubmissions[]"
									:value="item.id"
									v-model="selectedSubmissions"
								/>
								<span class="listPanel__itemSubTitle">
									{{ localize(item.publications.find(p => p.id == item.currentPublicationId).fullTitle) }}
								</span>
							</label>
							<pkp-button element="a" :href="item.urlWorkflow" style="margin-left: auto;">
								{{ __('common.view') }}
							</pkp-button>
						</div>
					</template>
				</submissions-list-panel>
				{fbvFormSection}
					<pkp-button :disabled="!components.submissions.itemsMax" @click="toggleSelectAll">
						<template v-if="components.submissions.itemsMax && selectedSubmissions.length >= components.submissions.itemsMax">
							{translate key="common.selectNone"}
						</template>
						<template v-else>
							{translate key="common.selectAll"}
						</template>
					</pkp-button>
					<pkp-button @click="submit('#exportXmlForm')">
						{translate key="plugins.importexport.native.exportSubmissions"}
					</pkp-button>
				{/fbvFormSection}
			{/fbvFormArea}
		</form>
	</div>
	<div id="exportIssues-tab">
		<script type="text/javascript">
			$(function() {ldelim}
				$('#exportIssuesXmlForm').pkpHandler('$.pkp.controllers.form.FormHandler');

				$('#exportIssuesXmlForm').on('submit', function() {ldelim}
					setTimeout(function() {ldelim}
						$('#exportIssuesXmlForm .is_visible').removeClass('is_visible');
						$('#exportIssuesXmlForm .pkp_spinner').hide();
					{rdelim}, 2000);
				{rdelim});
			{rdelim});
		</script>
		<form id="exportIssuesXmlForm" class="pkp_form" action="{plugin_url path="exportIssues"}" method="post">
			{csrf}
			{fbvFormArea id="issuesXmlForm"}
				{capture assign="issuesListGridUrl"}{url router=$smarty.const.ROUTE_COMPONENT component="grid.issues.ExportableIssuesListGridHandler" op="fetchGrid" escape=false}{/capture}
				{load_url_in_div id="issuesListGridContainer" url=$issuesListGridUrl}
				{fbvFormSection list="true"}
					{fbvElement type="checkbox" id="validation" label="plugins.importexport.common.validation" checked=$validation|default:true}
				{/fbvFormSection}

				{fbvFormSection list="true" title="plugins.importexport.native.exportOptions"}
					{fbvElement type="checkbox" id="no-embed" name="no-embed" label="plugins.importexport.native.noEmbedOption" checked=$noEmbed|default:false}
				{/fbvFormSection}

				{fbvFormButtons submitText="plugins.importexport.common.export" hideCancel="true"}
			{/fbvFormArea}
		</form>
	</div>
</div>

{/block}