{**
 * templates/notification/maillistSubscribed.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Displays the notification settings page and unchecks
 *
 *}
{strip}
{assign var="pageTitle" value="notification.mailList"}
{include file="common/header.tpl"}
{/strip}

<ul>
	<li>
		<span{if $error} class="pkp_form_error"{/if}>
			{translate key="notification.$status"}
		</span>
	</li>
<ul>

{include file="common/footer.tpl"}
