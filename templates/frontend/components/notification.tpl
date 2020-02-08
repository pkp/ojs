{**
 * templates/frontend/components/notification.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief View of an embedded notification element. Intended to be basic and highly
 *  reusable.
 *
 * @uses $type string A class which will be added to the notification element
 * @uses $message string The notification message
 * @uses $messageKey string Optional translation key to generate the message
 *}
<div class="cmp_notification {$type|escape|replace:' ':'_'}">
	{if $messageKey}
		{translate key=$messageKey}
	{else}
		{$message}
	{/if}
</div>
