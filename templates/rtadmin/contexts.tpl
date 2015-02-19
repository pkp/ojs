{**
 * templates/rtadmin/contexts.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * RTAdmin context list
 *
 *}
{strip}
{assign var="pageTitle" value="rt.contexts"}
{include file="common/header.tpl"}
{/strip}
<script type="text/javascript">
{literal}
$(document).ready(function() { setupTableDND("#dragTable", 
{/literal}
"{url op=moveContext path=$version->getVersionId()}"
{literal}
); });
{/literal}
</script>

<ul class="menu">
	<li><a href="{url op="editVersion" path=$version->getVersionId()}" class="action">{translate key="rt.admin.versions.metadata"}</a></li>
	<li class="current"><a href="{url op="contexts" path=$version->getVersionId()}" class="action">{translate key="rt.contexts"}</a></li>
</ul>

<br />

<div id="contexts">
<table class="listing" width="100%" id="dragTable">
	<tr><td class="headseparator" colspan="3">&nbsp;</td></tr>
	<tr valign="top">
		<td class="heading" width="40%">{translate key="rt.context.title"}</td>
		<td class="heading" width="20%">{translate key="rt.context.abbrev"}</td>
		<td class="heading" width="40%" align="right">&nbsp;</td>
	</tr>
	<tr><td class="headseparator" colspan="3">&nbsp;</td></tr>
	{iterate from=contexts item=context}
		<tr valign="top" class="data" id=context-{$context->getContextId()}>
			<td class="drag">{$context->getTitle()|escape}</td>
			<td class="drag">{$context->getAbbrev()|escape}</td>
			<td align="right"><a href="{url op="moveContext" path=$version->getVersionId()|to_array id=$context->getContextId() dir=u}" class="action">&uarr;</a>&nbsp;<a href="{url op="moveContext" path=$version->getVersionId()|to_array id=$context->getContextId() dir=d}" class="action">&darr;</a>&nbsp;|&nbsp;<a href="{url op="editContext" path=$version->getVersionId()|to_array:$context->getContextId()}" class="action">{translate key="rt.admin.contexts.metadata"}</a>&nbsp;|&nbsp;<a href="{url op="searches" path=$version->getVersionId()|to_array:$context->getContextId()}" class="action">{translate key="rt.searches"}</a>&nbsp;|&nbsp;<a href="{url op="deleteContext" path=$version->getVersionId()|to_array:$context->getContextId()}" onclick="return confirm('{translate|escape:"jsparam" key="rt.admin.contexts.confirmDelete"}')" class="action">{translate key="common.delete"}</a></td>
		</tr>
		{if $contexts->eof()}
		<tr><td class="endseparator" colspan="3"></td></tr>
		{/if}
	{/iterate}
	{if $contexts->wasEmpty()}
		<tr valign="top">
			<td class="nodata" colspan="3">{translate key="common.none"}</td>
		</tr>
		<tr><td class="endseparator" colspan="3"></td></tr>
	{else}
		<tr>
			<td align="left">{page_info iterator=$contexts}</td>
			<td colspan="2" align="right">{page_links anchor="contexts" name="contexts" iterator=$contexts}</td>
		</tr>
	{/if}
	</table>
<br/>

<a href="{url op="createContext" path=$version->getVersionId()}" class="action">{translate key="rt.admin.contexts.createContext"}</a><br/>
</div>

{include file="common/footer.tpl"}

