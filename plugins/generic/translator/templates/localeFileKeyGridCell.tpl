{**
 * templates/controllers/grid/localeFileKeyGridCell.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * A listbuilder grid cell for locale keys
 *}
{if $id}
	{assign var=cellId value="cell-"|concat:$id}
{else}
	{assign var=cellId value=""}
{/if}
<span {if $cellId}id="{$cellId|escape}" {/if}class="pkp_linkActions gridCellContainer">
	<div class="gridCellDisplay">
		{if $strong}<strong>{/if}{$key|escape|default:"&mdash;"}{if $strong}</strong>{/if}
	</div>

	<div class="gridCellEdit">
		<input type="text" name="newRowId[{$column->getId()|escape}]" {if $column->getFlag('tabIndex')}tabindex="{$column->getFlag('tabIndex')}"{/if} value="{$key|escape}" />
	</div>
</span>

