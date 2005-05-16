{**
 * selectTemplate.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of email templates the manager can choose to send.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.emails"}
{include file="common/header.tpl"}

<script type="text/javascript">
{literal}
function submitForm(key) {
	document.submit.template.value = key;
	document.submit.submit();
	return true;
}
{/literal}
</script>

<br/>

<form action="{$pageUrl}/manager/selectTemplate" method="post">
	{foreach from=$to item=toAddress}
		<input type="hidden" name="to[]" value="{$toAddress|escape}"/>
	{/foreach}
	{foreach from=$cc item=ccAddress}
		<input type="hidden" name="cc[]" value="{$ccAddress|escape}"/>
	{/foreach}
	{foreach from=$bcc item=bccAddress}
		<input type="hidden" name="bcc[]" value="{$bccAddress|escape}"/>
	{/foreach}
	<select class="selectMenu" name="locale">
		{foreach from=$locales item=thisLocale}
		<option {if $locale==$thisLocale}selected {/if}value="{$thisLocale|escape}">{$localeNames[$thisLocale]|escape}</option>
		{/foreach}
	</select>&nbsp;
	<input type="submit" class="button" value="{translate key="manager.people.emailUsers.selectLocale"}" />
	<br/><br/>
</form>

<table class="listing" width="100%">
<tr><td colspan="5" class="headseparator">&nbsp;</td></tr>
<tr class="heading" valign="bottom">
	<td width="15%">{translate key="manager.emails.emailTemplates"}</td>
	<td width="70%">{translate key="email.subject"}</td>
	<td width="15%" align="right">{translate key="common.action"}</td>
</tr>
<tr><td colspan="5" class="headseparator">&nbsp;</td></tr>
<form action="{$pageUrl}/manager/email" name="submit" method="post">
<input type="hidden" name="usePostedAddresses" value="1"/>
<input type="hidden" name="template" value=""/>
<input type="hidden" name="locale" value="{$locale|escape}"/>
{foreach from=$to item=toAddress}
	<input type="hidden" name="to[]" value="{$toAddress|escape}"/>
{/foreach}
{foreach from=$cc item=ccAddress}
	<input type="hidden" name="cc[]" value="{$ccAddress|escape}"/>
{/foreach}
{foreach from=$bcc item=bccAddress}
	<input type="hidden" name="bcc[]" value="{$bccAddress|escape}"/>
{/foreach}
{foreach name=emailTemplates from=$emailTemplates item=emailTemplate}
<tr valign="top">
	<td>{$emailTemplate->getEmailKey()}</td>
	<td>{$emailTemplate->getSubject()}</td>
	<td align="right">
		<a href="javascript:submitForm('{$emailTemplate->getEmailKey()|escape}');" class="action">{translate key="common.select"}</a>
	</td>
</tr>
<tr><td colspan="5" class="{if $smarty.foreach.emailTemplates.last}end{/if}separator">&nbsp;</td></tr>
{foreachelse}
<tr><td colspan="5" class="endseparator">&nbsp;</td></tr>
{/foreach}
</form>
</table>
<a href="{$pageUrl}/manager/resetAllEmails" onclick="return confirm('{translate|escape:"javascript" key="manager.emails.confirmResetAll"}')" class="action" onclick=>{translate key="manager.emails.resetAll"}</a>

{include file="common/footer.tpl"}
