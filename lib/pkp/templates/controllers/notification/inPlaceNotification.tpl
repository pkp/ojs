{**
 * controllers/notification/inPlaceNotification.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display in place notifications.
 *}

<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#{$notificationId|escape:javascript}').pkpHandler('$.pkp.controllers.NotificationHandler',
		{ldelim}
			{include file="core:controllers/notification/notificationOptions.tpl"}
		{rdelim});
	{rdelim});
</script>
<div id="{$notificationId|escape}" class="pkp_notification {$notificationStyleClass}"></div>
