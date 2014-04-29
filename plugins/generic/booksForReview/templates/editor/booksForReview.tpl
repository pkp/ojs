{**
 * @file plugins/generic/booksForReview/templates/editor/booksForReview.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of books for review for editor management.
 *
 *}
<form action="#">
<ul class="filter">
	<li>{translate key="editor.submissions.assignedTo"}: <select name="filterEditor" onchange="location.href='{url|escape path=$returnPage searchField=$searchField searchMatch=$searchMatch search=$search filterEditor="EDITOR" escape=false}'.replace('EDITOR', this.options[this.selectedIndex].value)" size="1" class="selectMenu">{html_options options=$editorOptions selected=$filterEditor}</select></li>
</ul>
</form>

<form method="post" action="{url op="booksForReview" path=$returnPage}">
	<select name="searchField" size="1" class="selectMenu">
		{html_options_translate options=$fieldOptions selected=$searchField}
	</select>
	<select name="searchMatch" size="1" class="selectMenu">
		<option value="contains"{if $searchMatch == 'contains'} selected="selected"{/if}>{translate key="form.contains"}</option>
		<option value="is"{if $searchMatch == 'is'} selected="selected"{/if}>{translate key="form.is"}</option>
	</select>
	<input type="text" size="30" name="search" class="textField" value="{$search|escape}" />
	<input type="submit" value="{translate key="common.search"}" class="button" />
</form>

<br />

{if $mode == $smarty.const.BFR_MODE_FULL}
	{assign var=colspan value="6"}
	{assign var=colspanPage value="3"}
{else}
	{assign var=colspan value="4"}
	{assign var=colspanPage value="2"}
{/if}
<table width="100%" class="listing">
	<tr>
		<td colspan="{$colspan}" class="headseparator">&nbsp;</td>
	</tr>
{if $mode == $smarty.const.BFR_MODE_FULL}
	<tr class="heading" valign="bottom">
		<td width="30%">{translate key="plugins.generic.booksForReview.editor.title"}</td>
		<td width="7%">{translate key="plugins.generic.booksForReview.editor.status"}</td>
		<td width="25%">{translate key="plugins.generic.booksForReview.editor.bookReviewer"}</td>
		<td width="15%">{translate key="plugins.generic.booksForReview.editor.dueDate"}</td>
		<td width="18%" align="right">{translate key="plugins.generic.booksForReview.editor.submission"}</td>
		<td width="5%" align="right">{translate key="plugins.generic.booksForReview.editor.editor"}</td>
	</tr>
{else}
	<tr class="heading" valign="bottom">
		<td width="70%">{translate key="plugins.generic.booksForReview.editor.title"}</td>
		<td width="7%">{translate key="plugins.generic.booksForReview.editor.status"}</td>
		<td width="18%" align="right">{translate key="plugins.generic.booksForReview.editor.submission"}</td>
		<td width="5%" align="right">{translate key="plugins.generic.booksForReview.editor.editor"}</td>
{/if}
	<tr>
		<td colspan="{$colspan}" class="headseparator">&nbsp;</td>
	</tr>
{iterate from=booksForReview item=bookForReview}
	<tr {if $bookForReview->isLate() && $bookForReview->getStatus() != $smarty.const.BFR_STATUS_SUBMITTED}class="highlight"{/if} valign="top">
		<td><a href="{url op="editBookForReview" path=$bookForReview->getId() returnPage=$returnPage}" class="action">{$bookForReview->getLocalizedTitle()|escape|truncate:40:"..."}</a></td>
		{assign var=status value=$bookForReview->getStatus()}
		{assign var=statusString value=$bookForReview->getStatusString()}
		{assign var=userId value=$bookForReview->getUserId()}
		<td>{translate key=$statusString}</td>
		{if $mode == $smarty.const.BFR_MODE_FULL}
			{if $userId}
				{assign var=userFullName value=$bookForReview->getUserFullName()}
				{assign var=userEmail value=$bookForReview->getUserEmail()}
				{assign var=emailString value="$userFullName <$userEmail>"}
				{url|assign:"url" page="user" op="email" to=$emailString|to_array redirectUrl=$currentUrl}
				<td>{$userFullName|escape}&nbsp;{icon name="mail" url=$url}
			{else}
				<td>
			{/if}
			{if $status == $smarty.const.BFR_STATUS_AVAILABLE}
				<a href="{url op="selectBookForReviewAuthor" path=$bookForReview->getId() returnPage=$returnPage}" class="action">{translate key="plugins.generic.booksForReview.editor.assignBookReviewer"}</a></td>
			{elseif $status == $smarty.const.BFR_STATUS_REQUESTED}
				<br />
				<a href="{url op="assignBookForReviewAuthor" path=$bookForReview->getId() returnPage=$returnPage}" class="action">{translate key="plugins.generic.booksForReview.editor.acceptBookReviewer"}</a>&nbsp;|&nbsp;<a href="{url op="denyBookForReviewAuthor" path=$bookForReview->getId() returnPage=$returnPage}" class="action">{translate key="plugins.generic.booksForReview.editor.denyBookReviewer"}</a></td>
			{elseif $status == $smarty.const.BFR_STATUS_ASSIGNED}
				<br />
				{if $bookForReview->getCopy()}
					<a href="{url op="notifyBookForReviewMailed" path=$bookForReview->getId() returnPage=$returnPage}" class="action">{translate key="plugins.generic.booksForReview.editor.notifyBookMailed"}</a>&nbsp;|
				{/if}
				<a href="{url op="removeBookForReviewAuthor" path=$bookForReview->getId() returnPage=$returnPage}" class="action" onclick="return confirm('{translate|escape:"jsparam" key="plugins.generic.booksForReview.editor.confirmRemove"}')">{translate key="plugins.generic.booksForReview.editor.removeBookReviewer"}</a></td>
			{elseif $status == $smarty.const.BFR_STATUS_MAILED}
				<br />
				<a href="{url op="removeBookForReviewAuthor" path=$bookForReview->getId() returnPage=$returnPage}" class="action" onclick="return confirm('{translate|escape:"jsparam" key="plugins.generic.booksForReview.editor.confirmRemove"}')">{translate key="plugins.generic.booksForReview.editor.removeBookReviewer"}</a></td>
			{elseif $userId && $status == $smarty.const.BFR_STATUS_SUBMITTED}
				<br />
				<a href="{url op="removeBookForReviewAuthor" path=$bookForReview->getId() returnPage=$returnPage}" class="action" onclick="return confirm('{translate|escape:"jsparam" key="plugins.generic.booksForReview.editor.confirmRemove"}')">{translate key="plugins.generic.booksForReview.editor.removeBookReviewer"}</a></td>
			{else}
				&nbsp;</td>
			{/if}
			<td>{$bookForReview->getDateDue()|date_format:$dateFormatTrunc}</td>
		{/if}
		<td align="right">
		{assign var=articleId value=$bookForReview->getArticleId()}
		{if $articleId}
			{translate key="common.id"}: {$articleId|escape}
			<br />
		{/if}
		<a href="{url op="selectBookForReviewSubmission" path=$bookForReview->getId() returnPage=$returnPage}" class="action">{translate key="plugins.generic.booksForReview.editor.select"}</a>
		{if $articleId}
			|&nbsp;<a href="{url page="editor" op="submission" path=$bookForReview->getArticleId()}" class="action">{translate key="plugins.generic.booksForReview.editor.edit"}</a>
		{/if}
		</td>
		<td align="right">{$bookForReview->getEditorInitials()|escape}</td>
	</tr>
	<tr>
		<td colspan="{$colspan}" class="{if $booksForReview->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $booksForReview->wasEmpty() and $search != ""}
	<tr>
		<td colspan="{$colspan}" class="nodata">{translate key="plugins.generic.booksForReview.editor.noResults"}</td>
	</tr>
	<tr>
		<td colspan="{$colspan}" class="endseparator">&nbsp;</td>
	</tr>
{elseif $booksForReview->wasEmpty()}
	<tr>
		<td colspan="{$colspan}" class="nodata">{translate key="plugins.generic.booksForReview.editor.noneCreated"}</td>
	</tr>
	<tr>
		<td colspan="{$colspan}" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="{$colspanPage}" align="left">{page_info iterator=$booksForReview}</td>
		<td colspan="{$colspanPage}" align="right">{page_links anchor="booksForReview" name="booksForReview" iterator=$booksForReview}</td>
	</tr>
{/if}
</table>
