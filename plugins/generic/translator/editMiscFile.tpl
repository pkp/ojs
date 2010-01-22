{**
 * editMiscFile.tpl
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Misc. file editor dialog
 *
 * $Id$
 *}
{translate|escape|assign:"pageTitleTranslated" key="plugins.generic.translator.file.edit" filename=$filename}
{include file="common/header.tpl"}

{assign var=filenameEscaped value=$filename|escape:"url"|escape:"url"}
<form method="post" action="{url op="saveMiscFile" path=$locale|to_array:$filenameEscaped}" name="editor">

<h3>{translate key="plugins.generic.translator.file.reference"}</h3>
<textarea readonly="true" name="referenceContents" rows="12" cols="80" class="textArea">
{$referenceContents|escape}
</textarea><br/>

<h3>{translate key="plugins.generic.translator.file.translation"}</h3>
<textarea name="translationContents" rows="12" cols="80" class="textArea">
{$translationContents|escape}
</textarea><br/>

<input type="submit" class="button defaultButton" value="{translate key="common.save"}" /> <input type="button" class="button" value="{translate key="common.cancel"}" onclick="document.location.href='{url op="edit" path=$locale escape=false}'" /> <input type="reset" class="button" value="{translate key="plugins.generic.translator.file.reset"}" onclick="return confirm('{translate|escape:"jsparam" key="plugins.generic.translator.file.resetConfirm"}')" /> <input type="button" class="button" value="{translate key="plugins.generic.translator.file.resetToReference"}" onclick="if (confirm('{translate|escape:"jsparam" key="plugins.generic.translator.file.resetConfirm"}')) {literal}{document.editor.translationContents.value = document.editor.referenceContents.value}{/literal}" />
</form>

{include file="common/footer.tpl"}
