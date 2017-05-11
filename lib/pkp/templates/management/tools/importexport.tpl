{**
 * templates/manager/importexport/plugins.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List available import/export plugins.
 *}
<div class="pkp_page_content pkp_page_importexport_plugins">
	{help file="tools.md" section="import-export" class="pkp_help_tab"}

	<ul>
		{foreach from=$plugins item=plugin}
		<li><a href="{url op="importexport" path="plugin"|to_array:$plugin->getName()}">{$plugin->getDisplayName()|escape}</a>:&nbsp;{$plugin->getDescription()|escape}</li>
		{/foreach}
	</ul>
</div>
