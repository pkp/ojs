{**
 * index.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * COUNTER stats index
 *
 * $Id$
 *}

{assign var="pageTitle" value="plugins.generic.counter.browseLog.logBrowser"}
{include file="common/header.tpl"}

<a name="entries"></a>

<table width="100%" class="listing">
<tr><td colspan="6" class="headseparator">&nbsp;</td></tr>
<tr class="heading" valign="bottom">
	<td width="10%">{translate key="plugins.generic.counter.entry.timestamp"}</td>
	<td width="15%">{translate key="plugins.generic.counter.entry.site"}</td>
	<td width="15%">{translate key="plugins.generic.counter.entry.journal"}</td>
	<td width="10%">{translate key="plugins.generic.counter.entry.user"}</td>
	<td width="7%">{translate key="plugins.generic.counter.entry.type"}</td>
	<td>{translate key="plugins.generic.counter.entry.value"}</td>
</tr>
<tr><td colspan="6" class="headseparator">&nbsp;</td></tr>
{assign var=userCount value=0}
{iterate from=entries item=entry}
{assign var=curUserSession value=$entry->getUser()}
<tr>
	<td>{$entry->getStamp()|date_format:$datetimeFormatShort}</td>
	<td>{$entry->getSite()|escape}</td>
	<td><a href="{$entry->getJournalUrl()|escape}">{$entry->getJournal()|escape}</a></td>
	<td>{$sessions[$curUserSession]|escape}</td>
	<td>
		{if $entry->getType() == LOG_ENTRY_TYPE_SEARCH}
			{translate key="plugins.generic.counter.entry.type.search"}
		{else}
			{translate key="plugins.generic.counter.entry.type.article"}
		{/if}
	</td>
	<td>{$entry->getValue()|escape}</td>
</tr>
<tr><td colspan="6" class="{if $entries->eof()}end{/if}separator">&nbsp;</td></tr>
{/iterate}
{if $entries->wasEmpty()}
	<tr>
	<td colspan="6" class="nodata">{translate key="common.none"}</td>
	</tr>
	<tr><td colspan="6" class="endseparator">&nbsp;</td></tr>
{else}
	<tr>
		<td colspan="3" align="left">{page_info iterator=$entries}</td>
		<td colspan="2" align="right">{page_links anchor="entries" name="entries" iterator=$entries}</td>
	</tr>
{/if}
</table>

{include file="common/footer.tpl"}
