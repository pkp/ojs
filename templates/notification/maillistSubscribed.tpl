{**
 * templates/notification/maillistSubscribed.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Displays the notification settings page and unchecks
 *
 *}
{include file="common/header.tpl" pageTitle="notification.mailList"}

<div class="pkp_page_content pkp_page_notifications">

<ul>
	<li>
		<span{if $error} class="pkp_form_error"{/if}>
			{translate key="notification.$status"}
		</span>
	</li>
<ul>

</div>

{include file="common/footer.tpl"}
