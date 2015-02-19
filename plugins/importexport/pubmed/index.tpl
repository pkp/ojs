{**
 * plugins/importexport/pubmed/index.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List of operations this plugin can perform
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.importexport.pubmed.displayName"}
{include file="common/header.tpl"}
{/strip}

<br/>

<h3>{translate key="plugins.importexport.pubmed.export"}</h3>
<ul>
	<li><a href="{plugin_url path="issues"}">{translate key="plugins.importexport.pubmed.export.issues"}</a></li>
	<li><a href="{plugin_url path="articles"}">{translate key="plugins.importexport.pubmed.export.articles"}</a></li>
</ul>

{include file="common/footer.tpl"}
