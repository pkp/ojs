{**
 * emails.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of email templates in journal management.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.emails"}
{include file="common/header.tpl"}
<br/>
<table class="listing" width="100%">
	<tr><td colspan="5" class="headseparator">&nbsp;</td></tr>
	<tr class="heading" valign="bottom">
		<td width="15%">{translate key="manager.emails.emailTemplates"}</td>
		<td width="10%">{translate key="email.sender"}</td>
		<td width="10%">{translate key="email.recipient"}</td>
		<td width="50%">{translate key="email.subject"}</td>
		<td width="10%" align="right">{translate key="common.action"}</td>
	</tr>
	<tr><td colspan="5" class="headseparator">&nbsp;</td></tr>
{foreach name=emailTemplates from=$emailTemplates item=emailTemplate}
	<tr valign="top">
		<td>{$emailTemplate->getEmailKey()|escape}</td>
		<td>{translate key=$emailTemplate->getFromRoleName()}</td>
		<td>{translate key=$emailTemplate->getToRoleName()}</td>
		<td>{$emailTemplate->getSubject()|escape}</td>
		<td align="right">
			<a href="{$pageUrl}/manager/editEmail/{$emailTemplate->getEmailKey()|escape}" class="action">{translate key="common.edit"}</a>
			{if $emailTemplate->getCanDisable() && !$emailTemplate->isCustomTemplate()}
				{if $emailTemplate->getEnabled() == 1}
					<a href="{$pageUrl}/manager/disableEmail/{$emailTemplate->getEmailKey()|escape}" class="action">{translate key="manager.emails.disable"}</a>
				{else}
					<a href="{$pageUrl}/manager/enableEmail/{$emailTemplate->getEmailKey()|escape}" class="action">{translate key="manager.emails.enable"}</a>
				{/if}
			{/if}
			{if !$emailTemplate->isCustomTemplate()}
				<a href="{$pageUrl}/manager/resetEmail/{$emailTemplate->getEmailKey()|escape}" onclick="return confirm('{translate|escape:"javascript" key="manager.emails.confirmReset"}')" class="action">{translate key="manager.emails.reset"}</a>
			{/if}
		</td>
	</tr>
	<tr>
		<td colspan="5" class="{if $smarty.foreach.emailTemplates.last}end{/if}separator">&nbsp;</td>
	</tr>
{foreachelse}
	<tr>
		<td colspan="5" class="nodata">{translate key="common.none"}</td>
	</tr>
	<tr>
		<td colspan="5" class="endseparator">&nbsp;</td>
	</tr>
{/foreach}
</table>

<br />

<a href="{$pageUrl}/manager/createEmail" class="action">{translate key="manager.emails.createEmail"}</a><br />
<a href="{$pageUrl}/manager/resetAllEmails" onclick="return confirm('{translate|escape:"javascript" key="manager.emails.confirmResetAll"}')" class="action" onclick=>{translate key="manager.emails.resetAll"}</a>

{include file="common/footer.tpl"}
