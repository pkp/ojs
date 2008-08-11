{**
 * emails.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of email templates in journal management.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="manager.emails"}
{include file="common/header.tpl"}
{/strip}

<br/>

<a name="emails"></a>

<table class="listing" width="100%">
	<tr><td colspan="5" class="headseparator">&nbsp;</td></tr>
	<tr class="heading" valign="bottom">
		<td width="15%">{translate key="manager.emails.emailTemplates"}</td>
		<td width="10%">{translate key="email.sender"}</td>
		<td width="10%">{translate key="email.recipient"}</td>
		<td width="50%">{translate key="email.subject"}</td>
		<td width="15%" align="right">{translate key="common.action"}</td>
	</tr>
	<tr><td colspan="5" class="headseparator">&nbsp;</td></tr>
{iterate from=emailTemplates item=emailTemplate}
	<tr valign="top">
		<td>
			{url|assign:"emailUrl" op="email" template=$emailTemplate->getEmailKey()}
			{$emailTemplate->getEmailKey()|escape|truncate:20:"..."}&nbsp;{icon name="mail" url=$emailUrl}
		</td>
		<td>{translate key=$emailTemplate->getFromRoleName()}</td>
		<td>{translate key=$emailTemplate->getToRoleName()}</td>
		<td>{$emailTemplate->getSubject()|escape|truncate:50:"..."}</td>
		<td align="right" class="nowrap">
			<a href="{url op="editEmail" path=$emailTemplate->getEmailKey()}" class="action">{translate key="common.edit"}</a>
			{if $emailTemplate->getCanDisable() && !$emailTemplate->isCustomTemplate()}
				{if $emailTemplate->getEnabled() == 1}
					|&nbsp;<a href="{url op="disableEmail" path=$emailTemplate->getEmailKey()}" class="action">{translate key="manager.emails.disable"}</a>
				{else}
					|&nbsp;<a href="{url op="enableEmail" path=$emailTemplate->getEmailKey()}" class="action">{translate key="manager.emails.enable"}</a>
				{/if}
			{/if}
			{if $emailTemplate->isCustomTemplate()}
				|&nbsp;<a href="{url op="deleteCustomEmail" path=$emailTemplate->getEmailKey()}" onclick="return confirm('{translate|escape:"jsparam" key="manager.emails.confirmDelete"}')" class="action">{translate key="common.delete"}</a>
			{else}
				|&nbsp;<a href="{url op="resetEmail" path=$emailTemplate->getEmailKey()}" onclick="return confirm('{translate|escape:"jsparam" key="manager.emails.confirmReset"}')" class="action">{translate key="manager.emails.reset"}</a>
			{/if}
		</td>
	</tr>
	<tr>
		<td colspan="5" class="{if $emailTemplates->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $emailTemplates->wasEmpty()}
	<tr>
		<td colspan="5" class="nodata">{translate key="common.none"}</td>
	</tr>
	<tr>
		<td colspan="5" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="3" align="left">{page_info iterator=$emailTemplates}</td>
		<td align="right" colspan="2">{page_links anchor="emails" name="emails" iterator=$emailTemplates}</td>
	</tr>
{/if}
</table>

<br />

<a href="{url op="createEmail"}" class="action">{translate key="manager.emails.createEmail"}</a><br />
<a href="{url op="resetAllEmails"}" onclick="return confirm('{translate|escape:"jsparam" key="manager.emails.confirmResetAll"}')" class="action">{translate key="manager.emails.resetAll"}</a>

{include file="common/footer.tpl"}
