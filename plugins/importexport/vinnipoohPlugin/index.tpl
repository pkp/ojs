{**
 * plugins/importexport/vinnipoohPlugin/index.tpl
 *
 * Copyright (c) 2013 Simon Fraser University Library
 * Copyright (c) 2003-2013 John Willinsky
 * Copyright (c) 2013-2014 Artem Gusarenko Ufa State Aviation Technical University (redactormail@gmail.com)
 * Copyright (c) 2013-2014 Valeriy Mironov Ufa State Aviation Technical University
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List of operations this plugin can perform
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.importexport.vinnipoohPlugin.displayName"}
{include file="common/header.tpl"}
{/strip}

<h3>{translate key="plugins.importexport.vinnipoohPlugin.export"}</h3>
<ul class="plain">
	<li>&#187; <a href="{plugin_url path="issues"}">{translate key="plugins.importexport.vinnipoohPlugin.export.issues"}</a></li>
</ul>

{include file="common/footer.tpl"}
