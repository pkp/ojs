{**
 * contexts.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * RTAdmin context list
 *
 * $Id$
 *}

{assign var="pageTitle" value="rt.researchTools"}
{include file="common/header.tpl"}

<ul class="menu">
	<li><a href="{$requestPageUrl}/editVersion/{$version->getVersionId()}" class="action">{translate key="rt.admin.versions.metadata"}</a></li>
	<li class="current"><a href="{$requestPageUrl}/contexts/{$version->getVersionId()}" class="action">{translate key="rt.contexts"}</a></li>
</ul>

<br />

<h3>{translate key="rt.contexts"}</h3>

<table class="listing" width="100%">
	<tr><td class="headseparator" colspan="3">&nbsp;</td></tr>
	<tr valign="top">
		<td class="heading" width="50%">{translate key="rt.context.title"}</td>
		<td class="heading" width="30%">{translate key="rt.context.abbrev"}</td>
		<td class="heading" width="20%" align="right">&nbsp;</td>
	</tr>
	<tr><td class="headseparator" colspan="3">&nbsp;</td></tr>
	{foreach from=$contexts item=context name=contexts}
		<tr valign="top">
			<td>{$context->getTitle()}</td>
			<td>{$context->getAbbrev()}</td>
			<td align="right"><a href="{$requestPageUrl}/editContext/{$version->getVersionId()}/{$context->getContextId()}" class="action">{translate key="common.edit"}</a>&nbsp;&nbsp;<a href="{$requestPageUrl}/deleteContext/{$version->getVersionId()}/{$context->getContextId()}" onclick="return confirm('{translate|escape:"javascript" key="rt.admin.contexts.confirmDelete"}')" class="action">{translate key="common.delete"}</a></td>
		</tr>
		<tr><td class="{if $smarty.foreach.contexts.last}end{/if}separator" colspan="3"></td></tr>
	{foreachelse}
		<tr valign="top">
			<td class="nodata" colspan="3">{translate key="common.none"}</td>
		</tr>
		<tr><td class="endseparator" colspan="3"></td></tr>
	{/foreach}
</table>
<br/>

{include file="common/footer.tpl"}
