{**
 * templates/controllers/listbuilder/multipleListsListbuilder.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Displays a MultipleListsListbuilder object
 *}

{assign var=staticId value="component-"|concat:$grid->getId()}
{assign var=gridId value=$staticId|concat:'-'|uniqid}
{assign var=gridActOnId value=$gridId}
{if count($lists) == 2}
	{assign var=widthClass value="pkp_helpers_half"}
{elseif count($lists) == 3}
	{assign var=widthClass value="pkp_helpers_third"}
{/if}
<script>
	$(function() {ldelim}
		$('#{$gridId|escape}').pkpHandler(
			'$.pkp.controllers.listbuilder.MultipleListsListbuilderHandler',
			{ldelim}
				{include file="controllers/listbuilder/listbuilderOptions.tpl"}
				listsId: [
				{foreach from=$lists item=list}
					'{$list->getId()}',
				{/foreach} ]
			{rdelim}
		);
	});
</script>


<div id="{$gridId|escape}" class="pkp_controllers_grid pkp_controllers_listbuilder formWidget">

	{* Use this disabled input to store LB deletions. See ListbuilderHandler.js *}
	<input disabled="disabled" type="hidden" class="deletions" />

	<div class="wrapper">
		{include file="controllers/grid/gridHeader.tpl"}
		{foreach from=$lists item=list}
			{assign var=listId value=$list->getId()}
			<div class="list_wrapper {$widthClass} list_{$listId|escape}">
				{if $grid->getActions($smarty.const.GRID_ACTION_POSITION_ABOVE)}
					{include file="controllers/grid/gridActionsAbove.tpl" actions=$grid->getActions($smarty.const.GRID_ACTION_POSITION_ABOVE) gridId=$gridId}
				{/if}
				{if $list->getTitle()}
					<div class="list_header">
						{$list->getTitle()|translate}
					</div>
				{/if}
				{assign var=gridTableId value=$staticId|concat:"-table-":$listId}
				{include file="controllers/listbuilder/listbuilderTable.tpl gridTableId=$gridTableId rows=$listsRows[$listId]}
			</div>
		{/foreach}
	</div>
</div>
