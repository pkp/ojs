{**
 * plugins/generic/metadataExport/templates/block.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common site sidebar menu -- metadata export.
 *
 *}

{if $articleId}
	{assign var="elementId" value=$articleId}
	{assign var="op" value="exportArticle"}
{elseif $issueId}
	{assign var="elementId" value=$issueId}
	{assign var="op" value="exportIssue"}
{elseif $journalId}
	{assign var="elementId" value=$journalId}
	{assign var="op" value="exportJournal"}
{/if}

<script type="text/javascript">
	{literal}
	function showCopyrightHelp(url) {
		window.open(url, 'plugin_info', 'width=600,height=250,screenX=300,screenY=300,toolbar=0,resizable=1,scrollbars=0');
	}
	{/literal}
</script>

<div class="block">
	<br />
	<form method="post" action="{url page="metadata" op="export"}">
	{if $journalId}
		<span class="blockTitle">{translate key="plugins.generic.metadataExport.title"}</span>
		<input type="radio" name="exportScope" value="exportJournal" checked />{translate key="plugins.generic.metadataExport.exportJournal"}<br />
	{/if}

	{if $articleId || $issueId}
		<input type="radio" name="exportScope" value="{$op|escape}" />{translate key="plugins.generic.metadataExport.$op"}<br />
	{/if}	
		<br />
		<select name="exportPluginName">
			{foreach from=$metadataExportPlugins item=thisMetadataExportPlugin}
				<option 
					{if $metadataExportPlugin && $metadataExportPlugin == $thisMetadataExportPlugin->getName()}selected="selected" {/if}
					 value="{$thisMetadataExportPlugin->getName()|escape}">{$thisMetadataExportPlugin->getMetadataExportFormatName()|escape}
				</option>
			{/foreach}
		</select>
		
		<input type="hidden" name="elementId" value="{$elementId|escape}" />
		<input type="hidden" name="referrer" value="{$currentUrl|escape}" />
		<input type="submit" class="button" value="{translate key="plugins.generic.metadataExport.buttonTitle"}" />
	</form>
	<a href='javascript:showCopyrightHelp("{url page="metadata" op="info"}")' style="cursor: help; display: inline-block; margin: 5px 0 10px 0;" title='{translate key="plugins.generic.metadataExport.copyrightTooltip"}'>
		{translate key="plugins.generic.metadataExport.copyrightLink"}
	</a>
</div>