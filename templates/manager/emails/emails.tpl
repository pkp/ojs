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
<tr class="heading" valign="top">
	<td width="15%" class="heading">{translate key="manager.emails.emailTemplates"}</td>
	<td width="70%" class="heading">{translate key="email.subject"}</td>
	<td width="15%" colspan="3" class="heading">
		{translate key="common.action"}
	</td>
</tr>
<tr><td colspan="5" class="headseparator">&nbsp;</td></tr>
{foreach from=$emailTemplates item=emailTemplate}
<tr valign="top">
	<td>{$emailTemplate->getEmailKey()}</td>
	<td>{$emailTemplate->getSubject()}</td>
	<td><a href="{$pageUrl}/manager/editEmail/{$emailTemplate->getEmailKey()}" class="action">{translate key="common.edit"}</a></td>
	<td>
		{if $emailTemplate->getCanDisable()}
			{if $emailTemplate->getEnabled() == 1}
				<a href="{$pageUrl}/manager/disableEmail/{$emailTemplate->getEmailKey()}" class="action">{translate key="manager.emails.disable"}</a>
			{else}
				<a href="{$pageUrl}/manager/enableEmail/{$emailTemplate->getEmailKey()}" class="action">{translate key="manager.emails.enable"}</a>
			{/if}
		{/if}
	</td>
	<td><a href="{$pageUrl}/manager/resetEmail/{$emailTemplate->getEmailKey()}" onclick="return confirm('{translate|escape:"javascript" key="manager.emails.confirmReset"}')" class="action">{translate key="manager.emails.reset"}</a></td>
</tr>
{/foreach}
</table>
<br/><br/>
<a href="{$pageUrl}/manager/resetAllEmails" onclick="return confirm('{translate|escape:"javascript" key="manager.emails.confirmResetAll"}')" class="action" onclick=>{translate key="manager.emails.resetAll"}</a>

{include file="common/footer.tpl"}
