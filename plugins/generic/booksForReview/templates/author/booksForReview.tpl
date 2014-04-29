{**
 * @file plugins/generic/booksForReview/templates/author/booksForReview.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of books for a specific author (i.e. in user home).
 *
 *}
<br />

<table width="100%" class="listing">
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="40%">{translate key="plugins.generic.booksForReview.author.title"}</td>
		<td width="15%">{translate key="plugins.generic.booksForReview.author.status"}</td>
		<td width="25%">{translate key="plugins.generic.booksForReview.author.editor"}</td>
		<td width="10%">{translate key="plugins.generic.booksForReview.author.dueDate"}</td>
		<td width="10%">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>
{iterate from=booksForReview item=bookForReview}
		{assign var=status value=$bookForReview->getStatus()}
	<tr valign="top">
		<td>{$bookForReview->getLocalizedTitle()|escape|truncate:60:"..."}</td>
		<td>{translate key=$bookForReview->getStatusString()}</td>
		{if $bookForReview->getEditorId()}
			{assign var=editorFullName value=$bookForReview->getEditorFullName()}
			{assign var=editorEmail value=$bookForReview->getEditorEmail()}
			{assign var=emailString value="$editorFullName <$editorEmail>"}
			{url|assign:"url" page="user" op="email" to=$emailString|to_array redirectUrl=$currentUrl}
			<td>{$editorFullName|escape}&nbsp;{icon name="mail" url=$url}</td>
		{else}
			<td>&nbsp;</td>
		{/if}
		<td>{$bookForReview->getDateDue()|date_format:$dateFormatTrunc}</td>
		{if $status == $smarty.const.BFR_STATUS_ASSIGNED || $status == $smarty.const.BFR_STATUS_MAILED}
			<td><a href="{url page="author" op="submit"}" class="action">{translate key="plugins.generic.booksForReview.author.submit"}</a></td>
		{else}
			<td>&nbsp;</td>
		{/if}
	</tr>
	<tr>
		<td colspan="5" class="{if $booksForReview->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $booksForReview->wasEmpty()}
	<tr>
		<td colspan="5" class="nodata">{translate key="plugins.generic.booksForReview.author.noneCreated"}</td>
	</tr>
	<tr>
		<td colspan="5" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="2" align="left">{page_info iterator=$booksForReview}</td>
		<td colspan="2" align="right">{page_links anchor="booksForReview" name="booksForReview" iterator=$booksForReview}</td>
	</tr>
{/if}
</table>
