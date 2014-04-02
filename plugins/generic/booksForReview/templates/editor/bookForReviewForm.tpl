{**
 * @file plugins/generic/booksForReview/templates/editor/bookForReviewForm.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Book for review form under plugin management.
 *
 *}
{assign var="pageCrumbTitle" value="$booksForReviewTitle"}
{if $bookForReview}
	{assign var="pageTitle" value="plugins.generic.booksForReview.editor.edit"}
	{assign var="bookId" value=$bookForReview->getId()}
{else}
	{assign var="pageTitle" value="plugins.generic.booksForReview.editor.create"}
{/if}
{include file="common/header.tpl"}

<br/>

<form id="bookForReviewForm" method="post" action="{url op="updateBookForReview"}" enctype="multipart/form-data">
{if $bookId}
<input type="hidden" name="bookId" value="{$bookId|escape}" />
{/if}
{if $returnPage}
<input type="hidden" name="returnPage" value="{$returnPage|escape}" />
{/if}
{include file="common/formErrors.tpl"}

{literal}
<script type="text/javascript">
<!--
// Move author up/down
function moveAuthor(dir, authorIndex) {
	var form = document.bookForReviewForm;
	form.moveAuthor.value = 1;
	form.moveAuthorDir.value = dir;
	form.moveAuthorIndex.value = authorIndex;
	form.submit();
}
// -->
</script>
{/literal}

<div id="bookForReviewDetails">

<input type="hidden" name="deletedAuthors" value="{$deletedAuthors|escape}" />
<input type="hidden" name="moveAuthor" value="0" />
<input type="hidden" name="moveAuthorDir" value="" />
<input type="hidden" name="moveAuthorIndex" value="" />

<table class="data" width="100%">
{if count($formLocales) > 1}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value" colspan="2">
			{if $bookId}{url|assign:"bookForReviewFormUrl" op="editBookForReview" path=$bookId returnPage=$returnPage escape=false}
			{else}{url|assign:"bookForReviewFormUrl" op="createBookForReview" path=$bookId returnPage=$returnPage escape=false}
			{/if}
			{form_language_chooser form="bookForReviewForm" url=$bookForReviewFormUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
{/if}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="title" required="true" key="plugins.generic.booksForReview.editor.form.title"}</td>
		<td width="80%" class="value"><input type="text" name="title[{$formLocale|escape}]" id="title" value="{$title[$formLocale]|escape}" size="60" maxlength="255" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="authorType" required="true" key="plugins.generic.booksForReview.editor.form.authorType"}</td>
		<td class="value">
			<select name="authorType" id="authorType" class="selectMenu">
				{html_options options=$validAuthorTypes selected=$authorType|escape}
			</select>
		</td>
	</tr>
	<tr valign="top">
		<td colspan="2">&nbsp;</td>
	</tr>
</table>

<table width="100%" class="data">
	{foreach name=authors from=$authors key=authorIndex item=author}
	<tr valign="top">
		<td width="20%" class="label">
			<input type="hidden" name="authors[{$authorIndex|escape}][authorId]" value="{$author.authorId|escape}" />
			<input type="hidden" name="authors[{$authorIndex|escape}][seq]" value="{$authorIndex+1}" />
			{fieldLabel name="authors-$authorIndex-firstName" required="true" key="user.firstName"}
		</td>
		<td width="80%" class="value"><input type="text" name="authors[{$authorIndex|escape}][firstName]" id="authors-{$authorIndex|escape}-firstName" value="{$author.firstName|escape}" size="20" maxlength="40" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="authors-$authorIndex-middleName" key="user.middleName"}</td>
		<td class="value"><input type="text" name="authors[{$authorIndex|escape}][middleName]" id="authors-{$authorIndex|escape}-middleName" value="{$author.middleName|escape}" size="20" maxlength="40" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="authors-$authorIndex-lastName" required="true" key="user.lastName"}</td>
		<td class="value"><input type="text" name="authors[{$authorIndex|escape}][lastName]" id="authors-{$authorIndex|escape}-lastName" value="{$author.lastName|escape}" size="20" maxlength="90" class="textField" /></td>
	</tr>
	{if $smarty.foreach.authors.total > 1}
		<tr valign="top">
			<td class="label">&nbsp;</td>
			<td class="value"><a href="javascript:moveAuthor('u', '{$authorIndex|escape}')" class="action plain">&uarr;</a> <a href="javascript:moveAuthor('d', '{$authorIndex|escape}')" class="action plain">&darr;</a>&nbsp;&nbsp;&nbsp;<input type="submit" name="delAuthor[{$authorIndex|escape}]" value="{translate key="plugins.generic.booksForReview.editor.form.deleteAuthor"}" class="button" /></td>
		</tr>
		<tr>
			<td colspan="2">&nbsp;</td>
		</tr>
	{/if}

	{foreachelse}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="authors-0-firstName" required="true" key="user.firstName"}
			<input type="hidden" name="authors[0][authorId]" value="0" />
			<input type="hidden" name="authors[0][seq]" value="1" />
		</td>
		<td width="80%" class="value"><input type="text" name="authors[0][firstName]" id="authors-0-firstName" size="20" maxlength="40" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="authors-0-middleName" key="user.middleName"}</td>
		<td class="value"><input type="text" name="authors[0][middleName]" id="authors-0-middleName" size="20" maxlength="40" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="authors-0-lastName" required="true" key="user.lastName"}</td>
		<td class="value"><input type="text" name="authors[0][lastName]" id="authors-0-lastName" size="20" maxlength="90" class="textField" /></td>
	</tr>
	{/foreach}
</table>

<table class="data" width="100%">
	<tr valign="top">
		<td width="20%" class="label">&nbsp;</td>
		<td width="80%" class="value"><input type="submit" class="button" name="addAuthor" value="{translate key="plugins.generic.booksForReview.editor.form.addAuthor"}" /></td>
	</tr>
	<tr valign="top">
		<td colspan="2">&nbsp;</td>
	</tr>
</table>

<table class="data" width="100%">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="publisher" required="true" key="plugins.generic.booksForReview.editor.form.publisher"}</td>
		<td width="80%" class="value"><input type="text" name="publisher" id="publisher" value="{$publisher|escape}" size="60" maxlength="255" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="year" required="true" key="plugins.generic.booksForReview.editor.form.year"}</td>
		<td class="value"><input type="text" name="year" id="year" value="{$year|escape}" size="5" maxlength="4" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="language" required="true" key="plugins.generic.booksForReview.editor.form.language"}</td>
		<td class="value">
			<select name="language" id="language" class="selectMenu">
				{html_options options=$validLanguages selected=$language|escape}
			</select>
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="copy" required="true" key="plugins.generic.booksForReview.editor.form.copy"}</td>
		<td class="value"><input type="checkbox" name="copy" id="copy" value="1" {if $copy} checked="checked"{/if} /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="edition" key="plugins.generic.booksForReview.editor.form.edition"}</td>
		<td class="value">
			<select name="edition" id="edition" class="selectMenu">
				{html_options options=$validEditions selected=$edition|escape}
			</select>
		</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="pages" key="plugins.generic.booksForReview.editor.form.pages"}</td>
		<td width="80%" class="value"><input type="text" name="pages" id="pages" value="{$pages|escape}" size="5" maxlength="4" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="isbn" key="plugins.generic.booksForReview.editor.form.isbn"}</td>
		<td width="80%" class="value"><input type="text" name="isbn" id="isbn" value="{$isbn|escape}" size="60" maxlength="30" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="url" key="plugins.generic.booksForReview.editor.form.url"}</td>
		<td width="80%" class="value"><input type="text" name="url" id="url" value="{$url|escape}" size="60" maxlength="255" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="description" key="plugins.generic.booksForReview.editor.form.description"}</td>
		<td class="value"><textarea name="description[{$formLocale|escape}]" id="description" rows="6" cols="60" class="textArea">{$description[$formLocale]|escape}</textarea></td>
	</tr>
	{if $bookId}
		<tr valign="top">
			<td width="20%">&nbsp;</td>
			<td width="80%"><a href="{url op="deleteBookForReview" path=$bookId returnPage=$returnPage}" onclick="return confirm('{translate|escape:"jsparam" key="plugins.generic.booksForReview.editor.confirmDelete"}')" class="action">{translate key="plugins.generic.booksForReview.editor.delete"}</a></td>
		</tr>
	{/if}
</table>

</div>

<div class="separator"></div>

<div id="bookForReviewCover">

<h3>{translate key="plugins.generic.booksForReview.editor.form.coverPage"}</h3>

<input type="hidden" name="fileName[{$formLocale|escape}]" value="{$fileName[$formLocale]|escape}" />
<input type="hidden" name="originalFileName[{$formLocale|escape}]" value="{$originalFileName[$formLocale]|escape}" />

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="coverPage" key="plugins.generic.booksForReview.editor.form.image"}</td>
		<td width="80%" class="value"><input type="file" name="coverPage" id="coverPage" class="uploadField" />&nbsp;&nbsp;{translate key="form.saveToUpload"}<br />{translate key="plugins.generic.booksForReview.editor.form.coverPageInstructions"}<br />{translate key="plugins.generic.booksForReview.editor.form.coverPageUploaded"}:&nbsp;{if $fileName[$formLocale]}<a href="javascript:openWindow('{$publicFilesDir}/{$fileName[$formLocale]|escape:"url"}');" class="file">{$originalFileName[$formLocale]}</a>&nbsp;<a href="{url op="removeBookForReviewCoverPage" path=$bookId|to_array:$formLocale returnPage=$returnPage}" class="action" onclick="return confirm('{translate|escape:"jsparam" key="plugins.generic.booksForReview.editor.form.removeCoverPage"}')">{translate key="plugins.generic.booksForReview.editor.form.remove"}</a>{else}&mdash;{/if}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="coverPageAltText" key="common.altText"}</td>
		<td width="80%" class="value"><input type="text" name="coverPageAltText[{$formLocale|escape}]" value="{$coverPageAltText[$formLocale]|escape}" size="40" maxlength="255" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td class="value"><span class="instruct">{translate key="common.altTextInstructions"}</span></td>
	</tr>
</table>

</div>

{if $bookId && $mode == $smarty.const.BFR_MODE_FULL}
<div class="separator"></div>

<div id="bookForReviewBookReviewer">

<h3>{translate key="plugins.generic.booksForReview.editor.bookReviewer"}</h3>

<table class="data" width="100%">
	<tr valign="top">
		<td width="20%" class="label">{translate key="plugins.generic.booksForReview.editor.form.status"}</td>
		<td width="80%" class="value">{$validStatus[$status]|escape}</td>
	</tr>
	{if $status != $smarty.const.BFR_STATUS_AVAILABLE}
	<tr valign="top">
		<td width="20%" class="label">{translate key="plugins.generic.booksForReview.editor.form.dateRequested"}</td>
		<td width="80%" class="value">{$dateRequested|date_format:$dateFormatShort}</td>
	</tr>
	{/if}
	{if $status != $smarty.const.BFR_STATUS_AVAILABLE && $status != $smarty.const.BFR_STATUS_REQUESTED}
		<tr valign="top">
			<td width="20%" class="label">{translate key="plugins.generic.booksForReview.editor.form.dateAssigned"}</td>
			<td width="80%" class="value">{$dateAssigned|date_format:$dateFormatShort}</td>
		</tr>
	{/if}
	{if $status == $smarty.const.BFR_STATUS_MAILED || $status == $smarty.const.BFR_STATUS_SUBMITTED}
		<tr valign="top">
			<td width="20%" class="label">{fieldLabel name="dateMailed" key="plugins.generic.booksForReview.editor.form.dateMailed"}</td>
			<td width="80%" class="value">{$dateMailed|date_format:$dateFormatShort}</td>
		</tr>
	{/if}
	{if $status != $smarty.const.BFR_STATUS_AVAILABLE && $status != $smarty.const.BFR_STATUS_REQUESTED}
		<tr valign="top">
			<td class="label">{fieldLabel name="dateDue" key="plugins.generic.booksForReview.editor.form.dateDue"}</td>
			<td class="value" id="dateDue">{html_select_date prefix="dateDue" all_extra="class=\"selectMenu\"" end_year="+5" time=$dateDue}</td>
		</tr>
	{/if}
	{if $status == $smarty.const.BFR_STATUS_SUBMITTED}
		<tr valign="top">
			<td width="20%" class="label">{fieldLabel name="dateSubmitted" key="plugins.generic.booksForReview.editor.form.dateSubmitted"}</td>
			<td width="80%" class="value">{$dateSubmitted|date_format:$dateFormatShort}</td>
		</tr>
	{/if}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="userId" key="plugins.generic.booksForReview.editor.form.user"}</td>
		<td width="80%" class="value">
		{if $userId}
			{assign var=bookReviewer value=$bookForReview->getUser()}
			{assign var=userMailingAddress value=$bookReviewer->getMailingAddress()}
			{assign var=userCountryCode value=$bookReviewer->getCountry()}
			{assign var=userCountry value=$countries.$userCountryCode}
			{assign var=userFullName value=$bookReviewer->getFullName()}
			{assign var=userEmail value=$bookReviewer->getEmail()}
			{assign var=emailString value="$userFullName <$userEmail>"}
			{url|assign:"url" page="user" op="email" to=$emailString|to_array redirectUrl=$currentUrl}
			{$userFullName|escape}&nbsp;{icon name="mail" url=$url}
		{/if}
		{if $status == $smarty.const.BFR_STATUS_AVAILABLE}
			<a href="{url op="selectBookForReviewAuthor" path=$bookId returnPage=$returnPage}" class="action">{translate key="plugins.generic.booksForReview.editor.assignBookReviewer"}</a>
		{elseif $status == $smarty.const.BFR_STATUS_REQUESTED}
			<br />
			<a href="{url op="assignBookForReviewAuthor" path=$bookId returnPage=$returnPage}" class="action">{translate key="plugins.generic.booksForReview.editor.acceptBookReviewer"}</a>&nbsp;|&nbsp;<a href="{url op="denyBookForReviewAuthor" path=$bookId returnPage=$returnPage}" class="action">{translate key="plugins.generic.booksForReview.editor.denyBookReviewer"}</a>
		{elseif $status == $smarty.const.BFR_STATUS_ASSIGNED}
			<br />
			{if $bookForReview->getCopy()}
				<a href="{url op="notifyBookForReviewMailed" path=$bookId returnPage=$returnPage}" class="action">{translate key="plugins.generic.booksForReview.editor.notifyBookMailed"}</a>&nbsp;|
			{/if}
			<a href="{url op="removeBookForReviewAuthor" path=$bookId returnPage=$returnPage}" class="action" onclick="return confirm('{translate|escape:"jsparam" key="plugins.generic.booksForReview.editor.confirmRemove"}')">{translate key="plugins.generic.booksForReview.editor.removeBookReviewer"}</a>
		{elseif $status == $smarty.const.BFR_STATUS_MAILED}
			<br />
			<a href="{url op="removeBookForReviewAuthor" path=$bookId returnPage=$returnPage}" class="action" onclick="return confirm('{translate|escape:"jsparam" key="plugins.generic.booksForReview.editor.confirmRemove"}')">{translate key="plugins.generic.booksForReview.editor.removeBookReviewer"}</a>
		{elseif $userId && $status == $smarty.const.BFR_STATUS_SUBMITTED}
			<br />
			<a href="{url op="removeBookForReviewAuthor" path=$bookId returnPage=$returnPage}" class="action" onclick="return confirm('{translate|escape:"jsparam" key="plugins.generic.booksForReview.editor.confirmRemove"}')">{translate key="plugins.generic.booksForReview.editor.removeBookReviewer"}</a>
		{/if}
		<input type="hidden" name="userId" id="userId" value="{$userId}"/>
		</td>
	</tr>
	{if $status == $smarty.const.BFR_STATUS_ASSIGNED || $status == $smarty.const.BFR_STATUS_MAILED || $status == $smarty.const.BFR_STATUS_SUBMITTED}
		<tr valign="top">
			<td class="label">{translate key="common.mailingAddress"}</td>
			<td class="value">{$userMailingAddress|nl2br|strip_unsafe_html|default:"&mdash;"}<br />{$userCountry|escape}</td>
		</tr>
	{/if}
</table>
</div>
{/if}

{if $bookId}
<div class="separator"></div>

<div id="bookForReviewBookSubmission">

<h3>{translate key="plugins.generic.booksForReview.editor.submission"}</h3>

<table class="data" width="100%">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="articleId" key="plugins.generic.booksForReview.editor.form.article"}</td>
		<td width="80%" class="value">
			{if $articleId}
				{translate key="common.id"}: {$articleId|escape}
			{/if}
			<a href="{url op="selectBookForReviewSubmission" path=$bookId returnPage=$returnPage}" class="action">{translate key="plugins.generic.booksForReview.editor.select"}</a>
			{if $articleId}
				|&nbsp;<a href="{url page="editor" op="submission" path=$articleId}" class="action">{translate key="plugins.generic.booksForReview.editor.edit"}</a>
			{/if}
			<input type="hidden" name="articleId" id="articleId" value="{$articleId}"/>
		</td>
	</tr>
</table>
</div>
{/if}

<div class="separator"></div>

<div id="bookForReviewNotes">

<h3>{translate key="plugins.generic.booksForReview.editor.notes"}</h3>
<p>{translate key="plugins.generic.booksForReview.editor.notesInstructions"}</p>

<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="notes" key="plugins.generic.booksForReview.editor.form.notes"}</td>
	<td width="80%" class="value"><textarea name="notes" id="notes" cols="60" rows="6" class="textArea">{$notes|escape}</textarea></td>
</tr>
</table>
</div>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> {if not $bookId}<input type="submit" name="createAnother" value="{translate key="plugins.generic.booksForReview.editor.form.saveAndCreateAnother"}" class="button" /> {/if}<input type="button" value="{translate key="common.cancel"}" class="button" onclick="history.go(-1);" /></p>

</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
