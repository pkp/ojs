{**
 * email.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Basic journal settings under site administration.
 *
 * $Id$
 *}

{assign var=pageTitle value="email.compose"}

{include file="common/header.tpl"}

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

<br/>
<form method="post" name="emailForm" action="{$requestPageUrl}/email"{if $attachmentsEnabled} enctype="multipart/form-data"{/if}>
{if $attachmentsEnabled}
	<input type="hidden" name="deleteAttachment" value="">
	{foreach from=$persistAttachments item=temporaryFile}
		<input type="hidden" name="persistAttachments[]" value="{$temporaryFile->getFileId()}" />
	{/foreach}
{/if}


{include file="common/formErrors.tpl"}

<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label"><label for="to">{translate key="email.to"}</label></td>
	<td width="80%" class="value">
		{if $to[0]}
			{foreach from=$to item=toAddress}
				<input type="text" name="to[]" id="to" value="{$toAddress|escape}" size="40" maxlength="120" class="textField" /><br/>
			{/foreach}
		{else}
			<input type="text" name="to[]" id="to" value="{$to|escape}" size="40" maxlength="120" class="textField" />
		{/if}

		{if $blankTo}
			<input type="text" name="to[]" id="to" value="" size="40" maxlength="120" class="textField" />
		{/if}
	</td>
</tr>
<tr valign="top">
	<td class="label"><label for="cc">{translate key="email.cc"}</label></td>
	<td class="value">
		{if $cc[0]}
			{foreach from=$cc item=ccAddress}
				<input type="text" name="cc[]" id="cc" value="{$ccAddress|escape}" size="40" maxlength="120" class="textField" /><br/>
			{/foreach}
		{else}
			<input type="text" name="cc[]" id="cc" value="{$cc|escape}" size="40" maxlength="120" class="textField" />
		{/if}

		{if $blankCc}
			<input type="text" name="cc[]" id="cc" value="" size="40" maxlength="120" class="textField" />
		{/if}
	</td>
</tr>
<tr valign="top">
	<td class="label"><label for="bcc">{translate key="email.bcc"}</label></td>
	<td class="value">
		{if $bcc[0]}
			{foreach from=$bcc item=bccAddress}
				<input type="text" name="bcc[]" id="bcc" value="{$bccAddress|escape}" size="40" maxlength="120" class="textField" /><br/>
			{/foreach}
		{else}
			<input type="text" name="bcc[]" id="bcc" value="{$bcc|escape}" size="40" maxlength="120" class="textField" />
		{/if}

		{if $blankBcc}
			<input type="text" name="bcc[]" id="bcc" value="" size="40" maxlength="120" class="textField" />
		{/if}
	</td>
</tr>
<tr valign="top">
	<td></td>
	<td class="value">
		<input type="submit" name="blankTo" class="button" value="{translate key="email.addToRecipient"}"/>
		<input type="submit" name="blankCc" class="button" value="{translate key="email.addCcRecipient"}"/>
		<input type="submit" name="blankBcc" class="button" value="{translate key="email.addBccRecipient"}"/>
	</td>
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
	<td colspan="3">&nbsp;</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="email.from"}</td>
	<td class="value" colspan="2">{$user->getFullName()|escape} &lt;{$user->getEmail()|escape}&gt;</td>
</tr>
</table>

<table width="100%" class="data">
<tr valign="top">
	<td width="20%" class="label"><label for="subject">{translate key="email.subject"}</label></td>
	<td width="80%" class="value"><input type="text" name="subject" id="subject" value="{$subject|escape}" size="60" maxlength="120" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label"><label for="bodyfield">{translate key="email.body"}</label></td>
	<td class="value"><textarea name="body" id="bodyfield" cols="60" rows="20" class="textArea">{$body|escape}</textarea></td>
</tr>
</table>

<p><input type="submit" value="{translate key="email.send"}" name="send" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="history.go(-1)" /></p>
</form>

{include file="common/footer.tpl"}
