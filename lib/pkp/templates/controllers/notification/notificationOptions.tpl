{**
 * controllers/notification/notificationOptions.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Notification options.
 *}

fetchNotificationUrl: {url|json_encode router=$smarty.const.ROUTE_PAGE page='notification' op='fetchNotification' escape=false},
hasSystemNotifications: {$hasSystemNotifications|json_encode}
{if $requestOptions}
	,
	requestOptions: {ldelim}
		{foreach name=levels from=$requestOptions key=level item=levelOptions}
			{$level|json_encode}: {if $levelOptions} {ldelim}
				{foreach name=types from=$levelOptions key=type item=typeOptions}
					{$type|json_encode}: {if $typeOptions} {ldelim}
						assocType: {$typeOptions[0]|json_encode},
						assocId: {$typeOptions[1]|json_encode}
					{rdelim}{else}0{/if}{if !$smarty.foreach.types.last},{/if}
				{/foreach}
			{rdelim}{else}0{/if}{if !$smarty.foreach.levels.last},{/if}
		{/foreach}
	{rdelim}
{/if}

