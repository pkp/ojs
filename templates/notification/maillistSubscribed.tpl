{**
 * index.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Displays the notification settings page and unchecks  
 *
 *}
{strip}
{assign var="pageTitle" value="notification.mailList"}
{include file="common/header.tpl"}
{/strip}

{if $status == 'subscribeError'}
	<ul>
		<li><span class="formError">{translate key="notification.subscribeError"}</span></li>
	<ul>
{elseif $status == 'subscribeSuccess'}
	<ul>
		<li>{translate key="notification.subscribeSuccess"}</li>
	</ul>
{elseif $status == 'confirmError'}
	<ul>
		<li><span class="formError">{translate key="notification.confirmError"}</span></li>
	<ul>
{elseif $status == 'confirmSuccess'}
	<ul>
		<li>{translate key="notification.confirmSuccess"}</li>
	</ul>
{/if}


{include file="common/footer.tpl"}

