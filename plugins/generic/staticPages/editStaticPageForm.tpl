{**
 * plugins/generic/staticPages/editStaticPageForm.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for editing a Static Page
 *
 *}
{strip}
{if $staticPageId}
	{assign var="pageTitle" value="plugins.generic.staticPages.editStaticPage"}
{else}
	{assign var="pageTitle" value="plugins.generic.staticPages.addStaticPage"}
{/if}
{include file="common/header.tpl"}
{/strip}

{translate key="plugins.generic.staticPages.editInstructions"}
<br />
{translate key="plugins.generic.staticPages.viewInstructions" pagesPath=$pagesPath|replace:"REPLACEME":"%PATH%"}
<br />
<br />

<form method="post" id="editStaticPageForm" action="{if $staticPageId}{plugin_url path="save"|to_array:$staticPageId}{else}{plugin_url path="save"}{/if}" >
<input type="hidden" name="edit" value="1" />
{if $staticPageId}
	<input type="hidden" name="staticPageId" value="{$staticPageId}" />
{/if}

{include file="common/formErrors.tpl"}

<table class="data" width="100%">
{if count($formLocales) > 1}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{if $staticPageId}{plugin_url|assign:"staticPageEditUrl" path="edit"|to_array:$staticPageId}
			{else}{plugin_url|assign:"staticPageEditUrl" path="edit"|to_array:$staticPageId}{/if}
			{form_language_chooser form="editStaticPageForm" url=$staticPageEditUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
{/if}
	<tr>
		<td width="20%" class="label">{fieldLabel required="true" name="pagePath" key="plugins.generic.staticPages.path"}</td>
		<td width="80%" class="value" ><input type="text" name="pagePath" value="{$pagePath|escape}" size="40" id="path" maxlength="50" class="textField" /></td>
	</tr>
	<tr>
		<td width="20%" class="label">{fieldLabel required="true" name="title" key="plugins.generic.staticPages.pageTitle"}</td>
		<td width="80%" class="value" ><input type="text" name="title[{$formLocale|escape}]" value="{$title[$formLocale]|escape}" size="40" id="title" maxlength="50" class="textField" /></td>
	</tr>
	<tr>
		<td width="20%" class="label" valign="top">{fieldLabel required="true" name="content" key="plugins.generic.staticPages.content"}</td>
		<td>
		<textarea name="content[{$formLocale|escape}]" cols="50" rows="30">{$content[$formLocale]|escape}</textarea>
		</td>
	</tr>
	<tr>
		<td colspan="2" align="right">
			<a href="http://tinymce.moxiecode.com?id=powered_by_tinymce"><img src="http://tinymce.sourceforge.net/buttons/powered_by_tinymce.png" border="0" width="88" height="32" alt="Powered by TinyMCE" /></a>
		</td>
	</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" />
<input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{plugin_url path="settings"}'" /></p>

</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
