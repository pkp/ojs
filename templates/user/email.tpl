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

<br/>
<form method="post" action="{$requestPageUrl}/email/send">

{include file="common/formErrors.tpl"}

<table class="data" width="100%">
<tr align="top">
	<td width="20%" class="label"><label for="to">{translate key="email.to"}</label></td>
	<td width="80%" class="value">
		<input type="text" name="to" id="to" value="{$to|escape}" size="40" maxlength="120" class="textField" />
	</td>
</tr>
<tr valign="top">
	<td class="label"><label for="cc">{translate key="email.cc"}</label></td>
	<td class="value">
		<input type="text" name="cc" id="cc" value="{$cc|escape}" size="40" maxlength="120" class="textField" />
	</td>
</tr>
<tr valign="top">
	<td class="label"><label for="bcc">{translate key="email.bcc"}</label></td>
	<td class="value">
		<input type="text" name="bcc" id="bcc" value="{$bcc|escape}" size="40" maxlength="120" class="textField" />
	</td>
</tr>
<tr>
	<td colspan="2">&nbsp;</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="email.from"}</td>
	<td class="value">{$user->getFullName()} &lt;{$user->getEmail()|escape}&gt;</td>
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

<p><input type="submit" value="{translate key="email.send"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="history.go(-1)" /></p>
</form>

{include file="common/footer.tpl"}
