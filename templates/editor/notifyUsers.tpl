{**
 * templates/editor/notifyUsers.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Editor's "Notify Users" email template form
 *
 *}
{strip}
{assign var="pageTitle" value="email.compose"}
{assign var="pageCrumbTitle" value="email.email"}
{include file="common/header.tpl"}
{/strip}

<div id="notifyUsers">
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
		{translate|assign:"message" key="email.invalid" email=$message.address}
		<li>{$message|escape}</li>
	{/if}
{/foreach}

{if $notFirstMessage}
	</ul>
	<br/>
{/if}

<div id="recipients">
<h3>{translate key="email.recipients"}</h3>
<table id="recipients" class="data" width="100%">
<tr valign="top">
	<td><input type="radio" id="allUsers" name="whichUsers" value="allUsers"/></td>
	<td class="label">
		<label for="allUsers">{translate key="editor.notifyUsers.allUsers" count=$allUsersCount|default:0}</label>
	</td>
</tr>
<tr valign="top">
	<td><input type="radio" id="allReaders" name="whichUsers" value="allReaders"/></td>
	<td class="label">
		<label for="allReaders">{translate key="editor.notifyUsers.allReaders" count=$allReadersCount|default:0}</label>
	</td>
</tr>
<tr valign="top">
	<td><input type="radio" id="allAuthors" name="whichUsers" value="allAuthors"/></td>
	<td class="label">
		<label for="allAuthors">{translate key="editor.notifyUsers.allAuthors" count=$allAuthorsCount|default:0}</label>
	</td>
</tr>
{if $currentJournal->getSetting('publishingMode') == $smarty.const.PUBLISHING_MODE_SUBSCRIPTION}
<tr valign="top">
	<td><input type="radio" id="allIndividualSubscribers" name="whichUsers" value="allIndividualSubscribers"/></td>
	<td class="label">
		<label for="allIndividualSubscribers">{translate key="editor.notifyUsers.allIndividualSubscribers" count=$allIndividualSubscribersCount|default:0}</label>
	</td>
</tr>
<tr valign="top">
	<td><input type="radio" id="allInstitutionalSubscribers" name="whichUsers" value="allInstitutionalSubscribers"/></td>
	<td class="label">
		<label for="allInstitutionalSubscribers">{translate key="editor.notifyUsers.allInstitutionalSubscribers" count=$allInstitutionalSubscribersCount|default:0}</label>
	</td>
</tr>
{/if}{* publishingMode is PUBLISHING_MODE_SUBSCRIPTION *}
<tr valign="top">
	<td><input type="checkbox" name="sendToMailList" /></td>
	<td class="label">
		{translate key="editor.notifyUsers.allMailingList" count=$allMailListCount|default:0}
	</td>
</tr>
{if $senderEmail}
	<tr valign="top">
		<td><input type="checkbox" name="ccSelf" /></td>
		<td class="label">
			{translate key="email.bccSender" address=$senderEmail|escape}
		</td>
	</tr>
{/if}
</table>
</div>{* recipients *}

<br/>

<div id="issue">
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
				<option {if $issue->getCurrent()}checked {/if}value="{$issue->getId()}">{$issue->getIssueIdentification()|strip_tags|truncate:40:"..."|escape}</option>
			{/iterate}
		</select>
	</td>
</tr>
</table>
</div>

<br/>


<table id="email" class="data" width="100%">
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
</div>
{include file="common/footer.tpl"}

