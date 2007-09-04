{**
 * email.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Generic email template form
 *
 * $Id$
 *}
{assign var="pageTitle" value="email.compose"}
{assign var="pageCrumbTitle" value="email.email"}
{include file="common/header.tpl"}

<form method="post" action="{$formActionUrl}">
<input type="hidden" name="continued" value="1"/>
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

<h3>{translate key="email.recipients"}</h3>
<table class="data" width="100%">
<tr valign="top">
	<td width="5%">
		<input checked type="radio" name="whichUsers" id="interestedUsers" value="interestedUsers"/>
	</td>
	<td width="75%" class="label">
		<label for="interestedUsers">{translate key="editor.notifyUsers.interestedUsers" count=$notifiableCount}</label>
	</td>
</tr>
<tr valign="top">
	<td width="5%">
		<input type="radio" id="allUsers" name="whichUsers" value="allUsers"/>
	</td>
	<td width="95%" class="label">
		<label for="allUsers">{translate key="editor.notifyUsers.allUsers" count=$allUsersCount}</label>
	</td>
</tr>
</table>

<br/>

<h3>{translate key="issue.issue"}</h3>
<table class="data" width="100%">
<tr valign="top">
	<td width="5%">
		<input type="checkbox" name="includeToc" id="includeToc" value="1"/>
	</td>
	<td width="75%" class="label">
		<label for="includeToc">{translate key="editor.notifyUsers.includeToc"}</label>&nbsp;
		<select name="issue" id="issue" class="selectMenu">
			{iterate from=issues item=issue}
				<option {if $issue->getCurrent()}checked {/if}value="{$issue->getIssueId()}">{$issue->getIssueIdentification()|escape}</option>
			{/iterate}
		</select>
	</td>
</tr>
</table>

<br/>

<table class="data" width="100%">
<tr valign="top">
	<td class="label">{translate key="email.from"}</td>
	<td class="value">{$from|escape}</td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="subject" key="email.subject"}</td>
	<td width="80%" class="value"><input type="text" id="subject" name="subject" value="{$subject|escape}" size="60" maxlength="120" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="body" key="email.body"}</td>
	<td class="value"><textarea name="body" cols="60" rows="15" class="textArea">{$body|escape}</textarea></td>
</tr>
</table>

<p><input name="send" type="submit" value="{translate key="email.send"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="history.go(-1)" /></p>
</form>

{include file="common/footer.tpl"}
