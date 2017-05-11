{**
 * linkAction/linkActionOptions.tpl
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
 *}

{* Generate the link action's options. *}
{ldelim}
	{if $selfActivate}
		selfActivate: {$selfActivate},
	{/if}
	staticId: {$staticId|json_encode},
	{assign var="actionRequest" value=$action->getActionRequest()}
	actionRequest: {$actionRequest->getJSLinkActionRequest()|json_encode},
	actionRequestOptions: {ldelim}
		{foreach name=actionRequestOptions from=$actionRequest->getLocalizedOptions() key=optionName item=optionValue}
			{$optionName|json_encode}: {$optionValue|json_encode},
		{/foreach}
	{rdelim}
{rdelim}
