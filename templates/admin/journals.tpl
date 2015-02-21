{**
 * templates/admin/journals.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of journals in site administration.
 *
 *}
{strip}
{assign var="pageTitle" value="journal.journals"}
{include file="common/header.tpl"}
{/strip}
<script type="text/javascript">
{literal}
$(document).ready(function() { setupTableDND("#adminJournals", "moveJournal"); });
{/literal}
</script>

<br />

<div id="journals">
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
	<tr valign="top" id="journal-{$journal->getId()}" class="data">
		<td><a class="action" href="{url journal=$journal->getPath() page="manager"}">{$journal->getLocalizedTitle()|escape}</a></td>
		<td class="drag">{$journal->getPath()|escape}</td>
		<td><a href="{url op="moveJournal" d=u id=$journal->getId()}">&uarr;</a> <a href="{url op="moveJournal" d=d id=$journal->getId()}">&darr;</a></td>
		<td align="right"><a href="{url op="editJournal" path=$journal->getId()}" class="action">{translate key="common.edit"}</a>&nbsp;|&nbsp;<a class="action" href="{url op="deleteJournal" path=$journal->getId()}" onclick="return confirm('{translate|escape:"jsparam" key="admin.journals.confirmDelete"}')">{translate key="common.delete"}</a></td>
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
			<td colspan="4" class="endseparator">&nbsp;</td>
		</tr>
		<tr>
			<td colspan="2" align="left">{page_info iterator=$journals}</td>
			<td colspan="2" align="right">{page_links anchor="journals" name="journals" iterator=$journals}</td>
		</tr>
	{/if}
</table>
</div>
<p><a href="{url op="createJournal"}" class="action">{translate key="admin.journals.create"}</a></p>

{include file="common/footer.tpl"}

