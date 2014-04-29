{**
 * plugins/importexport/doaj/index.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List of operations this plugin can perform
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.importexport.doaj.displayName"}
{include file="common/header.tpl"}
{/strip}

<br/>

<h3>{translate key="plugins.importexport.doaj.export"}</h3>
<ul class="plain">
	<li>&#187; <a href="{plugin_url path="export"}">{translate key="plugins.importexport.doaj.export.journal"}</a>: {translate key="plugins.importexport.doaj.export.journalInfo"}</li>
	<li>&#187; <a href="http://www.doaj.org/application/new">{translate key="plugins.importexport.doaj.export.contact"}</a>: {translate key="plugins.importexport.doaj.export.contactInfo"}</li>
</ul>

{include file="common/footer.tpl"}
