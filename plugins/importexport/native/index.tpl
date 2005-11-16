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

<h3>{translate key="plugins.importexport.native.import"}</h3>
<p>{translate key="plugins.importexport.native.import.description"}</p>
<form action="{$pluginUrl}/import" method="post" enctype="multipart/form-data">
<input type="file" class="uploadField" name="importFile" id="import" /> <input name="import" type="submit" class="button" value="{translate key="common.import"}" />
</form>

{include file="common/footer.tpl"}
