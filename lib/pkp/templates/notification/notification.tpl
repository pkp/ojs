{**
 * templates/notification/notification.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display a single notification.
 *}

<table class="notifications">
	<tr>
		<td width="25"><div class="notifyIcon {$notificationIconClass|escape}">&nbsp;</div></td>
		<td class="notificationContent" colspan="2">
			{$notificationDateCreated|date_format:"%d %b %Y %T"}
		</td>
		{if $notificationUrl != null}
			<td class="notificationFunction" style="min-width:60px"><a href="{$notificationUrl|escape}">{translate key="notification.location"}</a></td>
		{else}
			<td class="notificationFunction"></td>
		{/if}
		{if $isUserLoggedIn}
			<td class="notificationFunction"><a href="{url op="delete" path=$notificationId}">{translate key="common.delete"}</a></td>
		{/if}
	</tr>
	<tr>
		<td width="25">&nbsp;</td>
		<td class="notificationContent">
			<p{if !$notificationDateRead|date_format:"%d %b %Y %T"} style="font-weight: bold"{/if}>{$notificationContents|escape:"html"}
		</td>
	</tr>
</table>
