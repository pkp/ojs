{**
 * templates/controllers/listbuilder/listbuilder.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Displays a Listbuilder object
 *}

{assign var=staticId value="component-"|concat:$grid->getId()}
{assign var=gridId value=$staticId|concat:'-'|uniqid}
{assign var=gridTableId value=$gridId|concat:"-table"}
{assign var=gridActOnId value=$gridTableId|concat:">tbody:first"}

<script>
	$(function() {ldelim}
		$('#{$gridId|escape}').pkpHandler(
			'$.pkp.controllers.listbuilder.ListbuilderHandler',
			{ldelim}
				{include file="controllers/listbuilder/listbuilderOptions.tpl"}
			{rdelim}
		);
	});
</script>


<div id="{$gridId|escape}" class="pkp_controllers_grid pkp_controllers_listbuilder formWidget">

	{* Use this disabled input to store LB deletions. See ListbuilderHandler.js *}
	<input disabled="disabled" type="hidden" class="deletions" />

	<div class="wrapper">
		{include file="controllers/grid/gridHeader.tpl"}
		{include file="controllers/listbuilder/listbuilderTable.tpl"}
		{if $hasOrderLink}
			{include file="controllers/grid/gridOrderFinishControls.tpl" gridId=$staticId}
		{/if}
	</div>
</div>
