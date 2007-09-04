{**
 * editEmail.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Email editor dialog
 *
 * $Id$
 *}
{translate|escape|assign:"pageTitleTranslated" key="plugins.generic.translator.email.edit" emailKey=$emailKey}
{include file="common/header.tpl"}

<p>{translate key="plugins.generic.translator.email.description"}</p>

<form method="post" action="{url op="saveEmail" path=$locale|to_array:$emailKey}" name="editor">
<input type="hidden" name="returnToCheck" value="{$returnToCheck|default:0}" />

<h3>{translate key="plugins.generic.translator.email.reference"}</h3>
<input type="text" class="textField" name="referenceSubject" value="{$referenceEmail.subject|escape}" size="80" readonly="true" />
<textarea readonly="true" name="referenceBody" rows="12" cols="80" class="textArea">
{$referenceEmail.body|escape}
</textarea><br/>
<textarea readonly="true" name="referenceDescription" rows="3" cols="80" class="textArea">
{$referenceEmail.description|escape}
</textarea><br/>

<h3>{translate key="plugins.generic.translator.email.translation"}</h3>
<input type="text" class="textField" name="subject" value="{$email.subject|escape}" size="80" />
<textarea name="body" rows="12" cols="80" class="textArea">
{$email.body|escape}
</textarea><br/>
<textarea name="description" rows="3" cols="80" class="textArea">
{$email.description|escape}
</textarea><br/>

<input type="submit" class="button defaultButton" value="{translate key="common.save"}" /> <input type="button" class="button" value="{translate key="common.cancel"}" onclick="document.location.href='{url op="edit" path=$locale escape=false}'" /> <input type="reset" class="button" value="{translate key="plugins.generic.translator.email.reset"}" onclick="return confirm('{translate|escape:"javascript" key="plugins.generic.translator.email.resetConfirm"}')" /> <input type="button" class="button" value="{translate key="plugins.generic.translator.email.resetToReference"}" onclick="if (confirm('{translate|escape:"javascript" key="plugins.generic.translator.email.resetConfirm"}')) {literal}{document.editor.body.value = document.editor.referenceBody.value; document.editor.subject.value = document.editor.referenceSubject.value; document.editor.description.value = document.editor.referenceDescription.value;}{/literal}" />
</form>

{include file="common/footer.tpl"}
