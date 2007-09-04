{**
 * index.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List of operations this plugin can perform
 *
 * $Id$
 *}
{assign var="pageTitle" value="plugins.importexport.crossref.displayName"}
{include file="common/header.tpl"}

<br/>

<h3>{translate key="plugins.importexport.crossref.export"}</h3>
{if $journal->getSetting('doiPrefix')}
<ul class="plain">
	<li>&#187; <a href="{plugin_url path="issues"}">{translate key="plugins.importexport.crossref.export.issues"}</a></li>
	<li>&#187; <a href="{plugin_url path="articles"}">{translate key="plugins.importexport.crossref.export.articles"}</a></li>
</ul>
{else}
	{translate key="plugins.importexport.crossref.errors.noDOIprefix"} <br /><br />
	{translate key="manager.setup.doiPrefixDescription"}
{/if}


{include file="common/footer.tpl"}
