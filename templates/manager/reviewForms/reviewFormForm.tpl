{**
 * reviewFormForm.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to create/modify a review form.
 *
 *}
{include file="common/header.tpl"}

{if $reviewFormId}
	<ul class="menu">
		<li class="current"><a href="{url op="editReviewForm" path=$reviewFormId}">{translate key="manager.reviewForms.edit"}</a></li>
		<li><a href="{url op="reviewFormElements" path=$reviewFormId}">{translate key="manager.reviewFormElements"}</a></li>
		<li><a href="{url op="previewReviewForm" path=$reviewFormId}">{translate key="manager.reviewForms.preview"}</a></li>
	</ul>
{/if}

<br/>

<form name="reviewFormForm" method="post" action="{url op="updateReviewForm"}">
{if $reviewFormId}
	<input type="hidden" name="reviewFormId" value="{$reviewFormId}"/>
{/if}

{include file="common/formErrors.tpl"}

<table class="data" width="100%">
{if count($formLocales) > 1}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{if $reviewFormId}{url|assign:"reviewFormFormUrl" op="editReviewForm" path=$reviewFormId}
			{else}{url|assign:"reviewFormFormUrl" op="createReviewForm"}
			{/if}
			{form_language_chooser form="reviewFormForm" url=$reviewFormFormUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
{/if}
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="title" required="true" key="manager.reviewForms.title"}</td>
	<td width="80%" class="value"><input type="text" name="title[{$formLocale|escape}]" value="{$title[$formLocale]|escape}" id="title" size="40" maxlength="120" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="description" key="manager.reviewForms.description"}</td>
	<td class="value"><textarea name="description[{$formLocale|escape}]" rows="4" cols="40" id="description" class="textArea">{$description[$formLocale]|escape}</textarea></td>
</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="reviewForms" escape=false}'" /></p>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
