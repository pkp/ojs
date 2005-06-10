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

{assign var="pageTitle" value="plugins.importexport.native.displayName"}
{include file="common/header.tpl"}

<br/>

<h3>{translate key="plugins.importexport.native.export"}</h3>
<ul class="plain">
	<li>&#187; <a href="{$pluginUrl}/issues">{translate key="plugins.importexport.native.export.issues"}</a></li>
	<li>&#187; <a href="{$pluginUrl}/articles">{translate key="plugins.importexport.native.export.articles"}</a></li>
</ul>

{include file="common/footer.tpl"}
