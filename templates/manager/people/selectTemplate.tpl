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
<table class="listing" width="100%">
<tr><td colspan="5" class="headseparator"></td></tr>
<tr class="heading" valign="top">
	<td width="15%" class="heading">{translate key="manager.emails.emailTemplates"}</td>
	<td width="70%" class="heading">{translate key="email.subject"}</td>
	<td width="15%" class="heading">
		{translate key="common.action"}
	</td>
</tr>
<tr><td colspan="5" class="headseparator"></td></tr>
<form action="{$pageUrl}/manager/email" name="submit" method="post">
<input type="hidden" name="template" value=""/>
{foreach from=$to item=toAddress}
	<input type="hidden" name="to[]" value="{$toAddress|escape:'quotes'}"/>
{/foreach}
{foreach from=$cc item=ccAddress}
	<input type="hidden" name="cc[]" value="{$ccAddress|escape:'quotes'}"/>
{/foreach}
{foreach from=$bcc item=bccAddress}
	<input type="hidden" name="bcc[]" value="{$bccAddress|escape:'quotes'}"/>
{/foreach}
{foreach from=$emailTemplates item=emailTemplate}
<tr valign="top">
	<td>{$emailTemplate->getEmailKey()}</td>
	<td>{$emailTemplate->getSubject()}</td>
	<td>
		<a href="javascript:submitForm('{$emailTemplate->getEmailKey()|escape}');" class="action">{translate key="common.select"}</a>
	</td>
</tr>
{/foreach}
</form>
</table>
<br/><br/>
<a href="{$pageUrl}/manager/resetAllEmails" onclick="return confirm('{translate|escape:"javascript" key="manager.emails.confirmResetAll"}')" class="action" onclick=>{translate key="manager.emails.resetAll"}</a>

{include file="common/footer.tpl"}
