{**
 * index.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List of operations this plugin can perform
 *
 * $Id$
 *}

{assign var="pageTitle" value="plugins.importexport.pubmed.displayName"}
{include file="common/header.tpl"}

<br/>

<h3>{translate key="plugins.importexport.pubmed.export"}</h3>
<ul class="plain">
	<li>&#187; <a href="{plugin_url path="issues"}">{translate key="plugins.importexport.pubmed.export.issues"}</a></li>
	<li>&#187; <a href="{plugin_url path="articles"}">{translate key="plugins.importexport.pubmed.export.articles"}</a></li>
</ul>

{include file="common/footer.tpl"}
