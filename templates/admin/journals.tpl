{**
 * journals.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of journals in site administration.
 *
 * $Id$
 *}

{assign var="pageTitle" value="journal.journals"}
{include file="common/header.tpl"}

<table width="100%" class="listing">
<tr valign="top">
	<td width="35%" class="heading">{translate key="manager.setup.journalTitle"}</td>
	<td width="35%" class="heading">{translate key="journal.path"}</td>
	<td></td>
	<td></td>
	<td></td>
</tr>
{foreach from=$journals item=journal}
<tr valign="top">
	<td><a class="action" href="{$indexUrl}/{$journal->getPath()}/manager">{$journal->getTitle()}</a></td>
	<td>{$journal->getPath()}</td>
	<td><a class="action" href="{$pageUrl}/admin/deleteJournal/{$journal->getJournalId()}" onclick="return confirm('{translate|escape:"javascript" key="admin.journals.confirmDelete"}')">{translate key="common.delete"}</a></td>
	<td><a href="{$pageUrl}/admin/editJournal/{$journal->getJournalId()}" class="action">{translate key="common.edit"}</a></td>
	<td><nobr><a href="{$pageUrl}/admin/moveJournal?d=u&amp;journalId={$journal->getJournalId()}">&uarr;</a> <a href="{$pageUrl}/admin/moveJournal?d=d&amp;journalId={$journal->getJournalId()}">&darr;</a></nobr></td>
</tr>
{foreachelse}
<tr>
<td colspan="5" class="nodata">{translate key="admin.journals.noneCreated"}</td>
</tr>
{/foreach}
</table>

<div class="separator"></div>

<a href="{$pageUrl}/admin/createJournal" class="tableButton">{translate key="admin.journals.create"}</a>

{include file="common/footer.tpl"}
