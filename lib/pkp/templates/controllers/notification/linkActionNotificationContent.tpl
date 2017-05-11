{**
 * controllers/notification/linkActionNotificationContent.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Content for notifications with link actions.
 *
 *}
<span id="{$linkAction->getId()}" class="pkp_linkActions">
	{include file="linkAction/linkAction.tpl" action=$linkAction}
</span>
