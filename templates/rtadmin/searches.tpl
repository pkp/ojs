{**
 * searches.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * RTAdmin search list
 *
 * $Id$
 *}

{assign var="pageTitle" value="rt.searches"}
{include file="common/header.tpl"}

<ul class="menu">
	<li><a href="{$requestPageUrl}/editContext/{$version->getVersionId()}/{$context->getContextId()}" class="action">{translate key="rt.admin.contexts.metadata"}</a></li>
	<li class="current"><a href="{$requestPageUrl}/searches/{$version->getVersionId()}/{$context->getContextId()}" class="action">{translate key="rt.searches"}</a></li>
</ul>

<br />

<table class="listing" width="100%">
	<tr><td class="headseparator" colspan="3">&nbsp;</td></tr>
	<tr valign="top">
		<td class="heading" width="50%">{translate key="rt.search.title"}</td>
		<td class="heading" width="30%">{translate key="rt.search.url"}</td>
		<td class="heading" width="20%" align="right">&nbsp;</td>
	</tr>
	<tr><td class="headseparator" colspan="3">&nbsp;</td></tr>
	{foreach from=$searches item=search name=searches}
		<tr valign="top">
			<td>{$search->getTitle()}</td>
			<td>{$search->getUrl()}</td>
			<td align="right"><a href="{$requestPageUrl}/editSearch/{$version->getVersionId()}/{$context->getContextId()}/{$search->getSearchId()}" class="action">{translate key="common.edit"}</a>&nbsp;&nbsp;<a href="{$requestPageUrl}/deleteSearch/{$version->getVersionId()}/{$context->getContextId()}/{$search->getSearchId()}" onclick="return confirm('{translate|escape:"javascript" key="rt.admin.searches.confirmDelete"}')" class="action">{translate key="common.delete"}</a></td>
		</tr>
		<tr><td class="{if $smarty.foreach.searches.last}end{/if}separator" colspan="3"></td></tr>
	{foreachelse}
		<tr valign="top">
			<td class="nodata" colspan="3">{translate key="common.none"}</td>
		</tr>
		<tr><td class="endseparator" colspan="3"></td></tr>
	{/foreach}
</table>
<br/>

<a href="{$requestPageUrl}/createSearch/{$version->getVersionId()}/{$context->getContextId()}" class="action">{translate key="rt.admin.searches.createSearch"}</a><br/>

{include file="common/footer.tpl"}
