{**
 * searches.tpl
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * RTAdmin search list
 *
 * $Id$
 *}

{assign var="pageTitle" value="rt.searches"}
{include file="common/header.tpl"}

<ul class="menu">
	<li><a href="{url op="editContext" path=$version->getVersionId()|to_array:$context->getContextId()}" class="action">{translate key="rt.admin.contexts.metadata"}</a></li>
	<li class="current"><a href="{url op="searches" path=$version->getVersionId()|to_array:$context->getContextId()}" class="action">{translate key="rt.searches"}</a></li>
</ul>

<br />

<a name="searches"></a>

<table class="listing" width="100%">
	<tr><td class="headseparator" colspan="3">&nbsp;</td></tr>
	<tr valign="top">
		<td class="heading" width="50%">{translate key="rt.search.title"}</td>
		<td class="heading" width="30%">{translate key="rt.search.url"}</td>
		<td class="heading" width="20%" align="right">&nbsp;</td>
	</tr>
	<tr><td class="headseparator" colspan="3">&nbsp;</td></tr>
	{iterate from=searches item=search}
		<tr valign="top">
			<td>{$search->getTitle()|escape}</td>
			<td>{$search->getUrl()|truncate:30|escape}</td>
			<td align="right"><a href="{url op="moveSearch" path=$version->getVersionId()|to_array:$context->getContextId():$search->getSearchId() dir=u}" class="action">&uarr;</a>&nbsp;<a href="{url op="moveSearch" path=$version->getVersionId()|to_array:$context->getContextId():$search->getSearchId() dir=d}" class="action">&darr;</a>&nbsp;|&nbsp;<a href="{url op="editSearch" path=$version->getVersionId()|to_array:$context->getContextId():$search->getSearchId()}" class="action">{translate key="common.edit"}</a>&nbsp;|&nbsp;<a href="{url op="deleteSearch" path=$version->getVersionId()|to_array:$context->getContextId():$search->getSearchId()}" onclick="return confirm('{translate|escape:"javascript" key="rt.admin.searches.confirmDelete"}')" class="action">{translate key="common.delete"}</a></td>
		</tr>
		<tr><td class="{if $searches->eof()}end{/if}separator" colspan="3"></td></tr>
	{/iterate}
	{if $searches->wasEmpty()}
		<tr valign="top">
			<td class="nodata" colspan="3">{translate key="common.none"}</td>
		</tr>
		<tr><td class="endseparator" colspan="3"></td></tr>
	{else}
		<tr>
			<td align="left">{page_info iterator=$searches}</td>
			<td colspan="2" align="right">{page_links anchor="searches" name="searches" iterator=$searches}</td>
		</tr>
	{/if}
	</table>
<br/>

<a href="{url op="createSearch" path=$version->getVersionId()|to_array:$context->getContextId()}" class="action">{translate key="rt.admin.searches.createSearch"}</a><br/>

{include file="common/footer.tpl"}
