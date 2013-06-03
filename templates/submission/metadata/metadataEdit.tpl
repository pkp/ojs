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
	{url|assign:authorGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.users.author.AuthorGridHandler" op="fetchGrid" articleId=$articleId escape=false}
	{load_url_in_div id="authorsGridContainer" url="$authorGridUrl"}
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

<p>{translate key="author.submit.submissionIndexingDescription"}</p>

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

