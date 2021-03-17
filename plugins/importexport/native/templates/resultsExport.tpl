{**
 * plugins/importexport/native/templates/results.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Result of operations this plugin performed
 *}
{if $errorsFound}
	{translate key="plugins.importexport.native.processFailed"}
{else}
	{translate key="plugins.importexport.native.export.completed"}

	<div id="exportIssues-tab">
		<script type="text/javascript">
			$(function() {ldelim}
				// Attach the form handler.
				$('#exportIssuesXmlForm').pkpHandler('$.pkp.controllers.form.FormHandler');
			{rdelim});
		</script>
		<form id="exportIssuesXmlForm" class="pkp_form" action="{plugin_url path="downloadExportFile"}" method="post">
			{csrf}
			<input type="hidden" name="downloadFilePath" id="downloadFilePath" value="{$exportPath}" />
			{fbvFormArea id="issuesXmlForm"}
				{fbvFormButtons submitText="plugins.importexport.native.export.download.results" hideCancel="true"}
			{/fbvFormArea}
		</form>
	</div>
{/if}

{if array_key_exists('warnings', $errorsAndWarnings) && $errorsAndWarnings.warnings|@count > 0}
	<h2>{translate key="plugins.importexport.common.warningsEncountered"}</h2>
	{foreach from=$errorsAndWarnings.warnings item=allRelatedTypes key=relatedTypeName}
		{foreach from=$allRelatedTypes item=thisTypeIds key=thisTypeId}
			{if $thisTypeIds|@count > 0}
				<p>{$relatedTypeName} {if $thisTypeId > 0} (Id: {$thisTypeId}) {/if}</p>
				<ul>
					{foreach from=$thisTypeIds item=idRelatedItems}
						{foreach from=$idRelatedItems item=relatedItemMessage}
							<li>{$relatedItemMessage|escape}</li>
						{/foreach}
					{/foreach}
				</ul>
			{/if}
		{/foreach}
	{/foreach}
{/if}

{if array_key_exists('errors', $errorsAndWarnings) && $errorsAndWarnings.errors|@count > 0}
	<h2>{translate key="plugins.importexport.common.errorsOccured"}</h2>
	{foreach from=$errorsAndWarnings.errors item=allRelatedTypes key=relatedTypeName}
		{foreach from=$allRelatedTypes item=thisTypeIds key=thisTypeId}
			{if $thisTypeIds|@count > 0}
				<p>{$relatedTypeName} {if $thisTypeId > 0} (Id: {$thisTypeId}) {/if}</p>
				<ul>
					{foreach from=$thisTypeIds item=idRelatedItems}
						{foreach from=$idRelatedItems item=relatedItemMessage}
							<li>{$relatedItemMessage|escape}</li>
						{/foreach}
					{/foreach}
				</ul>
			{/if}
		{/foreach}
	{/foreach}
{/if}

{if $validationErrors}
	<h2>{translate key="plugins.importexport.common.validationErrors"}</h2>
	<ul>
		{foreach from=$validationErrors item=validationError}
			<li>{$validationError->message|escape}</li>
		{/foreach}
	</ul>
{/if}
