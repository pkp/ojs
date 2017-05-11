{**
 * templates/workflow/editorialLinkActions.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show editorial link actions.
 *}
{if !empty($editorActions)}
	{if array_intersect(array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR), (array)$userRoles)}
		<ul class="pkp_workflow_decisions">
			{foreach from=$editorActions item=action}
				<li>
					{include file="linkAction/linkAction.tpl" action=$action contextId=$contextId}
				</li>
			{/foreach}
		</ul>
	{/if}
{/if}
