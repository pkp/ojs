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
		<td width="15%" align="right">{translate key="common.action"}</td>
	</tr>
	<tr><td colspan="5" class="headseparator">&nbsp;</td></tr>
{foreach name=emailTemplates from=$emailTemplates item=emailTemplate}
	<tr valign="top">
		<td>{$emailTemplate->getEmailKey()|escape|truncate:20:"..."}</td>
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
				|&nbsp;<a href="{url op="deleteCustomEmail" path=$emailTemplate->getEmailKey()}" onclick="return confirm('{translate|escape:"javascript" key="manager.emails.confirmDelete"}')" class="action">{translate key="common.delete"}</a>
			{else}
				|&nbsp;<a href="{url op="resetEmail" path=$emailTemplate->getEmailKey()}" onclick="return confirm('{translate|escape:"javascript" key="manager.emails.confirmReset"}')" class="action">{translate key="manager.emails.reset"}</a>
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

<a href="{url op="createEmail"}" class="action">{translate key="manager.emails.createEmail"}</a><br />
<a href="{url op="resetAllEmails"}" onclick="return confirm('{translate|escape:"javascript" key="manager.emails.confirmResetAll"}')" class="action">{translate key="manager.emails.resetAll"}</a>

{include file="common/footer.tpl"}
