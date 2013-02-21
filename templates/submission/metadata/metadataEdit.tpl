{**
 * templates/submission/metadata/metadataEdit.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for changing metadata of an article (used in MetadataForm)
 *}
{strip}
{assign var="pageTitle" value="submission.editMetadata"}
{include file="common/header.tpl"}
{/strip}

{url|assign:"competingInterestGuidelinesUrl" page="information" op="competingInterestGuidelines"}

<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#metadata').pkpHandler('$.pkp.controllers.form.FormHandler');
	{rdelim});
</script>
<form class="pkp_form" id="metadata" method="post" action="{url op="saveMetadata"}" enctype="multipart/form-data">
<input type="hidden" name="articleId" value="{$articleId|escape}" />
{include file="common/formErrors.tpl"}

{if $canViewAuthors}
{literal}
<script>
<!--
// Move author up/down
function moveAuthor(dir, authorIndex) {
	var form = document.getElementById('metadata');
	form.moveAuthor.value = 1;
	form.moveAuthorDir.value = dir;
	form.moveAuthorIndex.value = authorIndex;
	form.submit();
}
// -->
</script>
{/literal}

<div id="authors">
<h3>{translate key="article.authors"}</h3>

<input type="hidden" name="deletedAuthors" value="{$deletedAuthors|escape}" />
<input type="hidden" name="moveAuthor" value="0" />
<input type="hidden" name="moveAuthorDir" value="" />
<input type="hidden" name="moveAuthorIndex" value="" />

<table class="data">
	{foreach name=authors from=$authors key=authorIndex item=author}
	<tr>
		<td class="label">
			<input type="hidden" name="authors[{$authorIndex|escape}][authorId]" value="{$author.authorId|escape}" />
			<input type="hidden" name="authors[{$authorIndex|escape}][seq]" value="{$authorIndex+1}" />
			{if $smarty.foreach.authors.total <= 1}
				<input type="hidden" name="primaryContact" value="{$authorIndex|escape}" />
			{/if}
			{fieldLabel name="authors-$authorIndex-firstName" required="true" key="user.firstName"}
		</td>
		<td class="value"><input type="text" name="authors[{$authorIndex|escape}][firstName]" id="authors-{$authorIndex|escape}-firstName" value="{$author.firstName|escape}" size="20" maxlength="40" class="textField" /></td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="authors-$authorIndex-middleName" key="user.middleName"}</td>
		<td class="value"><input type="text" name="authors[{$authorIndex|escape}][middleName]" id="authors-{$authorIndex|escape}-middleName" value="{$author.middleName|escape}" size="20" maxlength="40" class="textField" /></td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="authors-$authorIndex-lastName" required="true" key="user.lastName"}</td>
		<td class="value"><input type="text" name="authors[{$authorIndex|escape}][lastName]" id="authors-{$authorIndex|escape}-lastName" value="{$author.lastName|escape}" size="20" maxlength="90" class="textField" /></td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="authors-$authorIndex-email" required="true" key="user.email"}</td>
		<td class="value"><input type="text" name="authors[{$authorIndex|escape}][email]" id="authors-{$authorIndex|escape}-email" value="{$author.email|escape}" size="30" maxlength="90" class="textField" /></td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="authors-$authorIndex-url" key="user.url"}</td>
		<td class="value"><input type="text" name="authors[{$authorIndex|escape}][url]" id="authors-{$authorIndex|escape}-url" value="{$author.url|escape}" size="30" maxlength="255" class="textField" /></td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="authors-$authorIndex-affiliation" key="user.affiliation"}</td>
		<td class="value">
			<textarea name="authors[{$authorIndex|escape}][affiliation][{$formLocale|escape}]" class="textArea" id="authors-{$authorIndex|escape}-affiliation" rows="5" cols="40">{$author.affiliation[$formLocale]|escape}</textarea><br/>
			<span class="instruct">{translate key="user.affiliation.description"}</span>
		</td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="authors-$authorIndex-country" key="common.country"}</td>
		<td class="value">
			<select name="authors[{$authorIndex|escape}][country]" id="authors-{$authorIndex|escape}-country" class="selectMenu">
				<option value=""></option>
				{html_options options=$countries selected=$author.country|escape}
			</select>
		</td>
	</tr>
	{if $currentJournal->getSetting('requireAuthorCompetingInterests')}
		<tr>
			<td class="label">{fieldLabel name="authors-$authorIndex-competingInterests" key="author.competingInterests" competingInterestGuidelinesUrl=$competingInterestGuidelinesUrl}</td>
			<td class="value"><textarea name="authors[{$authorIndex|escape}][competingInterests][{$formLocale|escape}]" class="textArea richContent" id="authors-{$authorIndex|escape}-competingInterests" rows="5" cols="40">{$author.competingInterests[$formLocale]|escape}</textarea></td>
		</tr>
	{/if}{* requireAuthorCompetingInterests *}
	<tr>
		<td class="label">{fieldLabel name="authors-$authorIndex-biography" key="user.biography"}<br />{translate key="user.biography.description"}</td>
		<td class="value"><textarea name="authors[{$authorIndex|escape}][biography][{$formLocale|escape}]" id="authors-{$authorIndex|escape}-biography" rows="5" cols="40" class="textArea richContent">{$author.biography[$formLocale]|escape}</textarea></td>
	</tr>

{call_hook name="Templates::Submission::MetadataEdit::Authors"}

	{if $smarty.foreach.authors.total > 1}
	<tr>
		<td class="label">{translate key="author.submit.reorder"}</td>
		<td class="value"><a href="javascript:moveAuthor('u', '{$authorIndex|escape}')" class="action plain">&uarr;</a> <a href="javascript:moveAuthor('d', '{$authorIndex|escape}')" class="action plain">&darr;</a></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td class="label"><input type="radio" name="primaryContact" id="primaryContact-{$authorIndex|escape}" value="{$authorIndex|escape}"{if $primaryContact == $authorIndex} checked="checked"{/if} /> <label for="primaryContact-{$authorIndex|escape}">{translate key="author.submit.selectPrincipalContact"}</label></td>
		<td class="labelRightPlain">&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td class="value"><input type="submit" name="delAuthor[{$authorIndex|escape}]" value="{translate key="author.submit.deleteAuthor"}" class="button" /></td>
	</tr>
	{/if}
	{if !$smarty.foreach.authors.last}
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}

	{foreachelse}
	<input type="hidden" name="authors[0][authorId]" value="0" />
	<input type="hidden" name="primaryContact" value="0" />
	<input type="hidden" name="authors[0][seq]" value="1" />
	<tr>
		<td class="label">{fieldLabel name="authors-0-firstName" required="true" key="user.firstName"}</td>
		<td class="value"><input type="text" name="authors[0][firstName]" id="authors-0-firstName" size="20" maxlength="40" class="textField" /></td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="authors-0-middleName" key="user.middleName"}</td>
		<td class="value"><input type="text" name="authors[0][middleName]" id="authors-0-middleName" size="20" maxlength="40" class="textField" /></td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="authors-0-lastName" required="true" key="user.lastName"}</td>
		<td class="value"><input type="text" name="authors[0][lastName]" id="authors-0-lastName" size="20" maxlength="90" class="textField" /></td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="authors-0-affiliation" key="user.affiliation"}</td>
		<td class="value">
			<textarea name="authors[0][affiliation][{$formLocale|escape}]" class="textArea" id="authors-0-affiliation" rows="5" cols="40"></textarea><br/>
			<span class="instruct">{translate key="user.affiliation.description"}</span>
		</td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="authors-0-email" required="true" key="user.email"}</td>
		<td class="value"><input type="text" name="authors[0][email]" id="authors-0-email" size="30" maxlength="90" class="textField" /></td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="authors-0-url" key="user.url"}</td>
		<td class="value"><input type="text" name="authors[0][url]" id="authors-0-url" size="30" maxlength="255" class="textField" /></td>
	</tr>
	{if $currentJournal->getSetting('requireAuthorCompetingInterests')}
		<tr>
			<td class="label">{fieldLabel name="authors-0-competingInterests" key="author.competingInterests" competingInterestGuidelinesUrl=$competingInterestGuidelinesUrl}</td>
			<td class="value"><textarea name="authors[0][competingInterests][{$formLocale|escape}]" class="textArea richContent" id="authors-0-competingInterests" rows="5" cols="40"></textarea></td>
		</tr>
	{/if}
	<tr>
		<td class="label">{fieldLabel name="authors-0-biography" key="user.biography"}<br />{translate key="user.biography.description"}</td>
		<td class="value"><textarea name="authors[0][biography][{$formLocale|escape}]" id="authors-0-biography" rows="5" cols="40" class="textArea richContent"></textarea></td>
	</tr>
	{/foreach}
</table>

<p><input type="submit" class="button" name="addAuthor" value="{translate key="author.submit.addAuthor"}" /></p>
</div>

{/if}

{include file="core:submission/submissionMetadataFormTitleFields.tpl"}

<div id="cover">
<h3>{translate key="editor.article.cover"}</h3>

<input type="hidden" name="fileName[{$formLocale|escape}]" value="{$fileName[$formLocale]|escape}" />
<input type="hidden" name="originalFileName[{$formLocale|escape}]" value="{$originalFileName[$formLocale]|escape}" />

<table class="data">
	<tr>
		<td class="label" colspan="2"><input type="checkbox" name="showCoverPage[{$formLocale|escape}]" id="showCoverPage" value="1" {if $showCoverPage[$formLocale]} checked="checked"{/if} /> <label for="showCoverPage">{translate key="editor.article.showCoverPage"}</label></td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="coverPage" key="editor.article.coverPage"}</td>
		<td class="value"><input type="file" name="coverPage" id="coverPage" class="uploadField" />&nbsp;&nbsp;{translate key="form.saveToUpload"}<br />{translate key="editor.article.coverPageInstructions"}<br />{translate key="editor.article.uploaded"}:&nbsp;{if $fileName[$formLocale]}<a href="javascript:openWindow('{$publicFilesDir}/{$fileName[$formLocale]|escape:"url"}');" class="file">{$originalFileName[$formLocale]}</a>&nbsp;<a href="{url op="removeArticleCoverPage" path=$articleId|to_array:$formLocale}" onclick="return confirm('{translate|escape:"jsparam" key="editor.article.removeCoverPage"}')">{translate key="editor.article.remove"}</a>{else}&mdash;{/if}</td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="coverPageAltText" key="common.altText"}</td>
		<td class="value"><input type="text" name="coverPageAltText[{$formLocale|escape}]" value="{$coverPageAltText[$formLocale]|escape}" size="40" maxlength="255" class="textField" /></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td class="value"><span class="instruct">{translate key="common.altTextInstructions"}</span></td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="hideCoverPageToc" key="editor.article.coverPageDisplay"}</td>
		<td class="value"><input type="checkbox" name="hideCoverPageToc[{$formLocale|escape}]" id="hideCoverPageToc" value="1" {if $hideCoverPageToc[$formLocale]} checked="checked"{/if} /> <label for="hideCoverPageToc">{translate key="editor.article.hideCoverPageToc"}</label></td>
	</tr>
	<tr>
		<td class="label">&nbsp;</td>
		<td class="value"><input type="checkbox" name="hideCoverPageAbstract[{$formLocale|escape}]" id="hideCoverPageAbstract" value="1" {if $hideCoverPageAbstract[$formLocale]} checked="checked"{/if} /> <label for="hideCoverPageAbstract">{translate key="editor.article.hideCoverPageAbstract"}</label></td>
	</tr>
</table>
</div>

{if $submissionSettings.metaDiscipline || $submissionSettings.metaSubjectClass || $submissionSettings.metaSubject || $submissionSettings.metaCoverage || $submissionSettings.metaType}<p>{translate key="author.submit.submissionIndexingDescription"}</p>{/if}

{include file="core:submission/submissionMetadataFormFields.tpl"}


<div class="separator"></div>

{foreach from=$pubIdPlugins item=pubIdPlugin}
	{assign var=pubIdMetadataFile value=$pubIdPlugin->getPubIdMetadataFile()}
	{include file="$pubIdMetadataFile" pubObject=$article}
{/foreach}

{call_hook name="Templates::Submission::MetadataEdit::AdditionalMetadata"}

{if $submissionSettings.metaCitations}
<div id="metaCitations">
<h3>{translate key="submission.citations"}</h3>

<p>{translate key="author.submit.submissionCitations"}</p>

<table class="data">
<tr>
	<td class="label">{fieldLabel name="citations" key="submission.citations"}</td>
	<td class="value"><textarea name="citations" id="citations" class="textArea" rows="15" cols="60">{$citations|escape}</textarea></td>
</tr>
</table>
</div>
<script>
	// Display warning when citations are being changed.
	$(function() {ldelim}
		$('#citations').change(function(e) {ldelim}
			var $this = $(this);
			var originalContent = $this.text();
			var newContent = $this.val();
			if(originalContent != newContent) {ldelim}
				// Display confirm message.
				if (!confirm('{translate key="submission.citations.metadata.changeWarning"}')) {ldelim}
					$this.val(originalContent);
				{rdelim}
			{rdelim}
		{rdelim});
	{rdelim});
</script>
<div class="separator"></div>
{/if}

{if $isEditor}
<div id="display">
<h3>{translate key="editor.article.display"}</h3>

<table class="data">
	<tr>
		<td class="label">{fieldLabel name="hideAuthor" key="issue.toc"}</td>
		<td class="value">{translate key="editor.article.hideTocAuthorDescription"}:
			<select name="hideAuthor" id="hideAuthor" class="selectMenu">
				{html_options options=$hideAuthorOptions selected=$hideAuthor|escape}
			</select>
		</td>
	</tr>
</table>
</div>
{/if}

<div class="separator"></div>


<p><input type="submit" value="{translate key="submission.saveMetadata"}" class="button defaultButton"/> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="history.go(-1)" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}

