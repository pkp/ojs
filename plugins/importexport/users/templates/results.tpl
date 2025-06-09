{**
 * plugins/importexport/users/templates/results.tpl
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * List of operations this plugin can perform
 *}
<div class="semantic-defaults">
{if $validationErrors}
	<h2>{translate key="plugins.importexport.common.validationErrors"}</h2>
	<ul>
		{foreach from=$validationErrors item=validationError}
			<li>{$validationError->message|escape}</li>
		{/foreach}
	</ul>
{elseif $filterErrors}
	<h2>{translate key="plugins.importexport.user.importExportErrors"}</h2>
	<ul>
		{foreach from=$filterErrors item=filterError}
			<li>{$filterError|escape}</li>
		{/foreach}
	</ul>
{else}
	{translate key="plugins.importexport.users.importComplete"}
{/if}
</div>