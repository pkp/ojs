{**
 * email.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Generic email template form to fit in the comments window
 *
 * $Id$
 *}
 
{assign var="pageTitle" value="email.compose"}
{include file="submission/comment/header.tpl"}

<script type="text/javascript">
{literal}
<!--
function deleteAttachment(fileId) {
	document.emailForm.deleteAttachment.value = fileId;
	document.emailForm.submit();
}
// -->
{/literal}
</script>

<form method="post" name="emailForm" action="{$formActionUrl}"{if $attachmentsEnabled} enctype="multipart/form-data"{/if}>
<input type="hidden" name="continued" value="1"/>
{if $attachmentsEnabled}
	<input type="hidden" name="deleteAttachment" value="">
	{foreach from=$persistAttachments item=temporaryFile}
		<input type="hidden" name="persistAttachments[]" value="{$temporaryFile->getFileId()}" />
	{/foreach}
{/if}

{if $hiddenFormParams}
	{foreach from=$hiddenFormParams item=hiddenFormParam key=key}
		<input type="hidden" name="{$key|escape}" value="{$hiddenFormParam|escape}" />
	{/foreach}
{/if}

{include file="common/formErrors.tpl"}

{foreach from=$errorMessages item=message}
	{if !$notFirstMessage}
		{assign var=notFirstMessage value=1}
		<h4>{translate key="form.errorsOccurred"}</h4>
		<ul class="plain">
	{/if}
	{if $message.type == MAIL_ERROR_INVALID_EMAIL}
		{translate|assign:"message" key="email.invalid" email=`$message.address`}
		<li>{$message|escape}</li>
	{/if}
{/foreach}

{if $notFirstMessage}
	</ul>
	<br/>
{/if}

<table class="data" width="100%">
{if $addressFieldsEnabled}
<tr valign="top">
	<td class="label">{fieldLabel name="to" key="email.to"}</td>
	<td width="80%" class="value">
		{foreach from=$to item=toAddress}
			<input type="text" name="to[]" id="to" value="{if $toAddress.name != ''}{$toAddress.name|escape} &lt;{$toAddress.email|escape}&gt;{else}{$toAddress.email|escape}{/if}" size="40" maxlength="120" class="textField" /><br/>
		{foreachelse}
			<input type="text" name="to[]" id="to" size="40" maxlength="120" class="textField" />
		{/foreach}

		{if $blankTo}
			<input type="text" name="to[]" id="to" size="40" maxlength="120" class="textField" />
		{/if}
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="cc" key="email.cc"}</td>
	<td class="value">
		{foreach from=$cc item=ccAddress}
			<input type="text" name="cc[]" id="cc" value="{if $ccAddress.name != ''}{$ccAddress.name|escape} &lt;{$ccAddress.email|escape}&gt;{else}{$ccAddress.email|escape}{/if}" size="40" maxlength="120" class="textField" /><br/>
		{foreachelse}
			<input type="text" name="cc[]" id="cc" size="40" maxlength="120" class="textField" />
		{/foreach}

		{if $blankCc}
			<input type="text" name="cc[]" id="cc" size="40" maxlength="120" class="textField" />
		{/if}
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="bcc" key="email.bcc"}</td>
	<td class="value">
		{foreach from=$bcc item=bccAddress}
			<input type="text" name="bcc[]" id="bcc" value="{if $bccAddress.name != ''}{$bccAddress.name|escape} &lt;{$bccAddress.email|escape}&gt;{else}{$bccAddress.email|escape}{/if}" size="40" maxlength="120" class="textField" /><br/>
		{foreachelse}
			<input type="text" name="bcc[]" id="bcc" size="40" maxlength="120" class="textField" />
		{/foreach}

		{if $blankBcc}
			<input type="text" name="bcc[]" id="bcc" size="40" maxlength="120" class="textField" />
		{/if}
	</td>
</tr>
<tr valign="top">
	<td></td>
	<td class="value">
		<input type="submit" name="blankTo" class="button" value="{translate key="email.addToRecipient"}"/>
		<input type="submit" name="blankCc" class="button" value="{translate key="email.addCcRecipient"}"/>
		<input type="submit" name="blankBcc" class="button" value="{translate key="email.addBccRecipient"}"/>
		{if $senderEmail}
			<br/>
			<input type="checkbox" name="bccSender" value="1"{if $bccSender} checked{/if}/>&nbsp;&nbsp;{translate key="email.bccSender" address=$senderEmail|escape}
		{/if}
	</td>
</tr>
{/if}{* addressFieldsEnabled *}

{if $attachmentsEnabled}
<tr valign="top">
	<td colspan="2">&nbsp;</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="email.attachments"}</td>
	<td class="value">
		{assign var=attachmentNum value=1}
		{foreach from=$persistAttachments item=temporaryFile}
			{$attachmentNum}.&nbsp;{$temporaryFile->getOriginalFileName()|escape}&nbsp;
			({$temporaryFile->getNiceFileSize()})&nbsp;
			<a href="javascript:deleteAttachment({$temporaryFile->getFileId()})" class="action">{translate key="common.delete"}</a>
			<br/>
			{assign var=attachmentNum value=$attachmentNum+1}
		{/foreach}

		{if $attachmentNum != 1}<br/>{/if}

		<input type="file" name="newAttachment" class="uploadField" /> <input name="addAttachment" type="submit" class="button" value="{translate key="common.upload"}" />
	</td>
</tr>
{/if}
<tr valign="top">
	<td colspan="2">&nbsp;</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="email.from"}</td>
	<td class="value" colspan="2">{$from|escape}</td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="subject" key="email.subject"}</td>
	<td width="80%" class="value"><input type="text" id="subject" name="subject" value="{$subject|escape}" size="60" maxlength="90" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="body" key="email.body"}</td>
	<td class="value"><textarea name="body" cols="60" rows="15" class="textArea">{$body|escape}</textarea></td>
</tr>
</table>

<p><input name="send" type="submit" value="{translate key="email.send"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="history.go(-1)" /></p>
</form>

{include file="submission/comment/footer.tpl"}
