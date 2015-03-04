{**
 * plugins/importexport/doaj/index.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
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
<ul>
	<li><a href="{plugin_url path="unregistered"}">{translate key="plugins.importexport.crossref.export.unregistered"}</a></li>
	<li><a href="{plugin_url path="issues"}">{translate key="plugins.importexport.doaj.export.issue"}</a>: {translate key="plugins.importexport.doaj.export.issueInfo"}</li>
	<li><a href="{plugin_url path="articles"}">{translate key="plugins.importexport.doaj.export.article"}</a>: {translate key="plugins.importexport.doaj.export.articleInfo"}</li>
</ul>
<br />
<a href="http://www.doaj.org/application/new">{translate key="plugins.importexport.doaj.export.contact"}</a>: {translate key="plugins.importexport.doaj.export.contactInfo"}

{include file="common/footer.tpl"}
