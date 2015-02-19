{**
 * plugins/importexport/pubIds/templates/importResults.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display the import results: the errors occured and the list of the successfully-imported public identifiers.
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.importexport.pubIds.import.results"}
{include file="common/header.tpl"}
{/strip}

{if $errors}
<div id="importError">
<h3>{translate key="plugins.importexport.pubIds.import.errors"}</h3>
<p>{translate key="plugins.importexport.pubIds.import.errors.description"}</p>
{if $error}
	<!-- A single error occurred. -->
	<p>{translate key=$error}</p>
{else}
	<!-- Multiple errors occurred. List them. -->
	<ul>
	{foreach from=$errors item=error}
		<li>{translate key=$error[0] params=$error[1]}</li>
	{/foreach}
	</ul>
{/if}
</div>
{/if}

{if $pubIds}
<div id="importSuccess">
<h3>{translate key="plugins.importexport.pubIds.import.success"}</h3>
<p>{translate key="plugins.importexport.pubIds.import.success.description"}</p>
<ul>
	{foreach from=$pubIds item=pubId}
		<li>{$pubId.value|strip_unsafe_html} ({$pubId.pubObjectType|strip_unsafe_html} {$pubId.pubObjectId|strip_unsafe_html})</li>
	{/foreach}
	</ul>
</div>
{/if}

{include file="common/footer.tpl"}
