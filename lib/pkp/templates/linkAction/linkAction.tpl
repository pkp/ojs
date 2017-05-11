{**
 * lib/pkp/templates/linkAction/linkAction.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Create a link action
 *
 * Parameters:
 *  action: A LinkAction object.
 *  contextId: The name of the context in which the link
 *   action is being placed. This is required to disambiguate
 *   actions with the same id on one page.
 *  anyhtml: True iff arbitrary HTML is permitted in the link action title (i.e. escaping handled elsewhere)
 *}

{* Generate the link action's button. *}
{if $contextId}
	{assign var=staticId value=$contextId|concat:"-":$action->getId():"-button"}
{else}
	{assign var=staticId value=$action->getId()|concat:"-button"}
{/if}

{assign var=buttonId value=$staticId|concat:"-"|uniqid}
{include file="linkAction/linkActionButton.tpl" action=$action buttonId=$buttonId anyhtml=$anyhtml}

<script>
	{* Attach the action handler to the button. *}
	$(function() {ldelim}
		$('#{$buttonId}').pkpHandler(
			'$.pkp.controllers.linkAction.LinkActionHandler',
				{include file="linkAction/linkActionOptions.tpl" action=$action selfActivate=$selfActivate staticId=$staticId}
			);
	{rdelim});
</script>
