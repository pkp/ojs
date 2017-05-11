{**
 * templates/controllers/page/tasks.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * User-specific tasks panel.
 *}

{**
* Unread notifications count is set here on header load, but
* can also be updated dynamically via the javascript event
* called updateUnreadNotificationsCount.
*}
<script type="text/javascript">
	// Initialise JS handler.
	$(function() {ldelim}
		$('#userTasks').pkpHandler(
			'$.pkp.pages.header.TasksHandler',
			{ldelim}
				requestedPage: {$requestedPage|json_encode},
				fetchUnreadNotificationsCountUrl: {url|json_encode router=$smarty.const.ROUTE_COMPONENT component="grid.notifications.NotificationsGridHandler" op="getUnreadNotificationsCount" escape=false}
			{rdelim}
		);
	{rdelim});
</script>
<div id="userTasks">
	<a href="#" id="notificationsToggle">
		{translate key="common.tasks"}
		<span id="unreadNotificationCount" class="task-count{if $unreadNotificationCount} hasTasks{/if}">
			{$unreadNotificationCount}
		</span>
	</a>
	<div id="notificationsPopover" class="panel">
		{url|assign:notificationsGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.notifications.TaskNotificationsGridHandler" op="fetchGrid" escape=false}
		{load_url_in_div id="notificationsGrid" url=$notificationsGridUrl}
	</div>
</div>
