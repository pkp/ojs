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
{assign var="pageId" value="manager.emails.emails"}
{include file="common/header.tpl"}

<table>
<tr class="heading">
	<td>{translate key="manager.emails.emailTemplates"}</td>
	<td>{translate key="manager.emails.subject"}</td>
	<td></td>
	<td></td>
	<td></td>
</tr>
{foreach from=$emailTemplates item=emailTemplate}
<tr class="{cycle values="row,rowAlt"}">
	<td><a href="{$pageUrl}/manager/editEmail/{$emailTemplate->getEmailKey()}">{$emailTemplate->getEmailKey()}</a></td>
	<td width="100%">{$emailTemplate->getSubject()}</td>
	<td><a href="{$pageUrl}/manager/editEmail/{$emailTemplate->getEmailKey()}" class="tableAction">{translate key="common.edit"}</a></td>
	<td>
		{if not $emailTemplate->getCanDisable()}
		-
		{elseif $emailTemplate->getEnabled() == 1}
			<a href="{$pageUrl}/manager/disableEmail/{$emailTemplate->getEmailKey()}" class="tableAction">{translate key="manager.emails.disable"}</a>
		{else}
			<a href="{$pageUrl}/manager/enableEmail/{$emailTemplate->getEmailKey()}" class="tableAction">{translate key="manager.emails.enable"}</a>
		{/if}
	</td>
	<td><a href="#" onclick="confirmAction('{$pageUrl}/manager/resetEmail/{$emailTemplate->getEmailKey()}', '{translate|escape:"javascript" key="manager.emails.confirmReset"}')" class="tableAction">{translate key="manager.emails.reset"}</a></td>
</tr>
{/foreach}
</table>

<a href="#" onclick="confirmAction('{$pageUrl}/manager/resetAllEmails', '{translate|escape:"javascript" key="manager.emails.confirmResetAll"}')" class="tableButton" onclick=>{translate key="manager.emails.resetAll"}</a>

{include file="common/footer.tpl"}