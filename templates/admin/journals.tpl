{**
 * journals.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of journals in site administration.
 *
 * $Id$
 *}

{assign var="pageTitle" value="journal.journals"}
{include file="common/header.tpl"}

<br />

<table width="100%" class="listing" id="adminJournals">
	<tr>
		<td colspan="4" class="headseparator">&nbsp;</td>
	</tr>
	<tr valign="top" class="heading">
		<td width="35%">{translate key="manager.setup.journalTitle"}</td>
		<td width="35%">{translate key="journal.path"}</td>
		<td width="10%">{translate key="common.order"}</td>
		<td width="20%" align="right">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="4" class="headseparator">&nbsp;</td>
	</tr>
	{iterate from=journals item=journal}
	<tr valign="top">
		<td><a class="action" href="{url journal=$journal->getPath() page="manager"}">{$journal->getTitle()|escape}</a></td>
		<td>{$journal->getPath()|escape}</td>
		<td><a href="{url op="moveJournal" d=u journalId=$journal->getJournalId()}">&uarr;</a> <a href="{url op="moveJournal" d=d journalId=$journal->getJournalId()}">&darr;</a></td>
		<td align="right"><a href="{url op="editJournal" path=$journal->getJournalId()}" class="action">{translate key="common.edit"}</a>&nbsp;|&nbsp;<a class="action" href="{url op="deleteJournal" path=$journal->getJournalId()}" onclick="return confirm('{translate|escape:"javascript" key="admin.journals.confirmDelete"}')">{translate key="common.delete"}</a></td>
	</tr>
	<tr>
		<td colspan="4" class="{if $smarty.foreach.journals.last}end{/if}separator">&nbsp;</td>
	</tr>
	{/iterate}
	{if $journals->wasEmpty()}
	<tr>
		<td colspan="4" class="nodata">{translate key="admin.journals.noneCreated"}</td>
	</tr>
	<tr>
		<td colspan="4" class="endseparator">&nbsp;</td>
	</tr>
	{else}
		<tr>
			<td colspan="2" align="left">{page_info iterator=$journals}</td>
			<td colspan="2" align="right">{page_links name="journals" iterator=$journals}</td>
		</tr>
	{/if}
</table>

<p><a href="{url op="createJournal"}" class="action">{translate key="admin.journals.create"}</a> | <a href="{url op="importOJS1"}" class="action">{translate key="admin.journals.importOJS1"}</a></p>

{include file="common/footer.tpl"}
