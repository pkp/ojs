{**
 * linkActionButtonWithForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Template that renders a button for a link action.
 *
 * Parameter:
 *  action: The LinkAction we create a button for.
 *  buttonId: The id of the link.
 *}
<div id="{$buttonId|escape}">
	{include file="linkAction/linkActionButton.tpl" buttonId=$buttonId|concat:"-link" action=$action}
	<form action="#" method="post">
	</form>
</div>
