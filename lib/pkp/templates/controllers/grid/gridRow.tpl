{**
 * templates/controllers/grid/gridRow.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * A grid row.
 *}
{if !is_null($row->getId())}
	{assign var=rowIdPrefix value="component-"|concat:$row->getGridId()}
	{if $categoryId}
		{assign var=rowIdPrefix value=$rowIdPrefix|concat:"-category-":$categoryId|escape}
	{/if}
	{assign var=rowId value=$rowIdPrefix|concat:"-row-":$row->getId()}
{else}
	{assign var=rowId value=""}
{/if}

{assign var="row_class" value="gridRow"}
{if is_a($row, 'GridCategoryRow')}
	{assign var="row_class" value=$row_class|cat:' category'}
	{if !$row->hasFlag('gridRowStyle')}
		{assign var="row_class" value=$row_class|cat:' default_category_style'}
	{/if}
{/if}
{if $row->getActions($smarty.const.GRID_ACTION_POSITION_DEFAULT)}
	{assign var="row_class" value=$row_class|cat:' has_extras'}
{/if}

<tr {if $rowId}id="{$rowId|escape}" {/if} class="{$row_class}">
	{foreach name=columnLoop from=$columns key=columnId item=column}

		{* @todo indent columns should be killed at their source *}
		{if $column->hasFlag('indent')}
			{php}continue;{/php}
		{/if}

		{assign var=col_class value=""}
		{if $column->hasFlag('firstColumn')}
			{assign var="col_class" value=$col_class|cat:'first_column'}
		{/if}

		{if $column->hasFlag('alignment')}
			{assign var="col_class" value=$col_class|cat:' pkp_helpers_text_'}
			{assign var="col_class" value=$col_class|cat:$column->getFlag('alignment')}
		{/if}

		<td{if $col_class} class="{$col_class}"{/if}>
			{if $row->hasActions() && $column->hasFlag('firstColumn')}
				{if $row->getActions($smarty.const.GRID_ACTION_POSITION_DEFAULT)}
					<a href="#" class="show_extras">
						<span class="pkp_screen_reader">{translate key="grid.settings"}</span>
					</a>
				{/if}
				{$cells[$smarty.foreach.columnLoop.index]}
				{if is_a($row, 'GridCategoryRow') && $column->hasFlag('showTotalItemsNumber')}
					<span class="category_items_number">({$grid->getCategoryItemsCount($categoryRow->getData(), $request)})</span>
				{/if}
				<div class="row_actions">
					{if $row->getActions($smarty.const.GRID_ACTION_POSITION_ROW_LEFT)}
						{foreach from=$row->getActions($smarty.const.GRID_ACTION_POSITION_ROW_LEFT) item=action}
							{include file="linkAction/linkAction.tpl" action=$action contextId=$rowId}
						{/foreach}
					{/if}
				</div>
			{else}
				{$cells[$smarty.foreach.columnLoop.index]}
				{if is_a($row, 'GridCategoryRow') && $column->hasFlag('showTotalItemsNumber')}
					<span class="category_items_number">({$grid->getCategoryItemsCount($categoryRow->getData(), $request)})</span>
				{/if}
			{/if}
		</td>
	{/foreach}
</tr>
{if $row->getActions($smarty.const.GRID_ACTION_POSITION_DEFAULT)}
	<tr id="{$rowId|escape}-control-row" class="row_controls{if is_a($row, 'GridCategoryRow')} category_controls{/if}">
		<td colspan="{$grid->getColumnsCount('indent')}">
			{if $row->getActions($smarty.const.GRID_ACTION_POSITION_DEFAULT)}
				{foreach from=$row->getActions($smarty.const.GRID_ACTION_POSITION_DEFAULT) item=action}
					{include file="linkAction/linkAction.tpl" action=$action contextId=$rowId}
				{/foreach}
			{/if}
		</td>
	</tr>
{/if}
