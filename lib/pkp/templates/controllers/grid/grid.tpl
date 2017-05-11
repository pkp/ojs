{**
 * templates/controllers/grid/grid.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Grid HTML markup and construction
 *}

{assign var=staticId value="component-"|concat:$grid->getId()}
{assign var=gridId value=$staticId|concat:'-'|uniqid}
{assign var=gridTableId value=$gridId|concat:"-table"}
{assign var=gridActOnId value=$gridTableId}

<script>
	$(function() {ldelim}
		$('#{$gridId|escape:javascript}').pkpHandler(
			'{$grid->getJSHandler()|escape:javascript}',
			{ldelim}
				gridId: {$grid->getId()|json_encode},
				{foreach from=$grid->getUrls() key=key item=itemUrl name=gridUrls}
					{$key|json_encode}: {$itemUrl|json_encode},
				{/foreach}
				bodySelector: '#{$gridActOnId|escape:javascript}',
				{if $grid->getPublishChangeEvents()}
					publishChangeEvents: {$grid->getPublishChangeEvents()|@json_encode},
				{/if}
				features: {include file='controllers/grid/feature/featuresOptions.tpl' features=$features}
			{rdelim}
		);
	{rdelim});
</script>

<div id="{$gridId|escape}" class="pkp_controllers_grid{if is_a($grid, 'CategoryGridHandler')} pkp_grid_category{/if}{if !$grid->getTitle()} pkp_grid_no_title{/if}">
	{include file="controllers/grid/gridHeader.tpl"}
	<table id="{$gridTableId|escape}">
		{include file="controllers/grid/columnGroup.tpl" columns=$columns}
		<thead>
			{** build the column headers **}
			<tr>
				{foreach name=columns from=$columns item=column}
					{* @todo indent columns should be killed at their source *}
					{if $column->hasFlag('indent')}
						{php}continue;{/php}
					{/if}
					{if $column->hasFlag('alignment')}
						{assign var=alignment value=$column->getFlag('alignment')}
					{else}
						{assign var=alignment value=$smarty.const.COLUMN_ALIGNMENT_LEFT}
					{/if}
					<th scope="col" style="text-align: {$alignment};">
						{$column->getLocalizedTitle()}
						{* TODO: Remove this stuff.  Actions should not ever appear in the TH of a grid. *}
						{if $smarty.foreach.columns.last && $grid->getActions($smarty.const.GRID_ACTION_POSITION_LASTCOL)}
							<span class="options pkp_linkActions">
								{foreach from=$grid->getActions($smarty.const.GRID_ACTION_POSITION_LASTCOL) item=action}
									{include file="linkAction/linkAction.tpl" action=$action contextId=$staticId}
								{/foreach}
							</span>
						{/if}
					</th>
				{/foreach}
			</tr>
		</thead>
		{if $grid->getIsSubcomponent() && !is_a($grid, 'CategoryGridHandler')}
			{* Create two separate tables so that the body part
			   can be scrolled independently from the header in a
			   cross-browser compatible way using only CSS. *}
			</table>
			<div class="scrollable">
			<table>
				{include file="controllers/grid/columnGroup.tpl" columns=$columns}
		{/if}
		{foreach from=$gridBodyParts item=bodyPart}
			{$bodyPart}
		{foreachelse}
 			<tbody></tbody>
		{/foreach}
		<tbody class="empty"{if count($gridBodyParts) > 0} style="display: none;"{/if}>
			{**
				We need the last (=empty) line even if we have rows
				so that we can restore it if the user deletes all rows.
			**}
			<tr>
				<td colspan="{$grid->getColumnsCount('indent')}">{translate key=$grid->getEmptyRowText()}</td>
			</tr>
		</tbody>
	</table>

	{if $grid->getIsSubcomponent() && !is_a($grid, 'CategoryGridHandler')}
		</div>
	{/if}

	{if $grid->getActions($smarty.const.GRID_ACTION_POSITION_BELOW) || $grid->getFootNote()}
	<div class="footer">

		{if $grid->getActions($smarty.const.GRID_ACTION_POSITION_BELOW)}
			{include file="controllers/grid/gridActionsBelow.tpl" actions=$grid->getActions($smarty.const.GRID_ACTION_POSITION_BELOW) gridId=$staticId}
		{/if}

		{if $grid->getFootNote()}
			<div class="footnote">
				{translate key=$grid->getFootNote()}
			</div>
		{/if}
	</div>
	{/if}

</div>
