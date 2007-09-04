{**
 * settingsForm.tpl
 *
 * Copyright (c) 2006-2007 Gunther Eysenbach, Juan Pablo Alperin, MJ Suhonos
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for CMS plugin settings.
 *
 * $Id$
 *}
{assign var="pageTitle" value="plugins.generic.cms.displayName"}
{include file="common/header.tpl"}

{translate key="plugins.generic.cms.form.introduction"}

<br />
<br />

<form method="post" action="{plugin_url path="edit"}">

{include file="common/formErrors.tpl"}

<table border="0">
	<tr>
		<td>
			<input type="hidden" name="current" value="{$current}" />
			<textarea name="content" cols="50" rows="30">
				{$currentHeading|escape}
				{$currentContent|escape}
			</textarea>
		</td>
	</tr>
	<tr>
		<td align="right">
			<a href="http://tinymce.moxiecode.com?id=powered_by_tinymce"><img src="http://tinymce.sourceforge.net/buttons/powered_by_tinymce.png" border="0" width="88" height="32" alt="Powered by TinyMCE" /></a>
		</td>
	</tr>
</table>

<p><input type="button" value="{translate key="common.done"}" class="button" onclick="document.location.href='{url page="manager" op="plugins" escape=false}'" /></p>

</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
