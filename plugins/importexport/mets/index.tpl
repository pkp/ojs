{**
 * plugins/importexport/mets/index.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List of operations this plugin can perform
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.importexport.METSExport.displayName"}
{include file="common/header.tpl"}
{/strip}

<br/>

<h3>{translate key="plugins.importexport.METSExport.export"}</h3>
<ul>
	<li><a href="{plugin_url path="issues"}">{translate key="plugins.importexport.METSExport.export.issues"}</a></li>
</ul>

{include file="common/footer.tpl"}
