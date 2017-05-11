{**
 * templates/controllers/grid/localeFileValueGridCell.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * A listbuilder grid cell for locale translations (with reference locale)
 *}
{if $id}
	{assign var=cellId value="cell-"|concat:$id}
{else}
	{assign var=cellId value=""}
{/if}
<span {if $cellId}id="{$cellId|escape}" {/if}class="pkp_linkActions gridCellContainer">
	<div class="gridCellDisplay">
		{* Display the current value *}
		{$translation|escape|default:"&mdash;"}
	</div>

	<div class="gridCellEdit">
		<span class="referenceLocaleName">{$referenceLocaleName|escape}</span>
		<textarea rows=4 cols=40 readonly="readonly" name="newRowId[{$column->getId()|escape}][{$referenceLocale|escape}]">{$reference|escape}</textarea>
		<span class="translationLocaleName">{$translationLocaleName|escape}</span>
		<textarea rows=4 cols=40 name="newRowId[{$column->getId()|escape}][{$translationLocale|escape}]" {if $column->getFlag('tabIndex')}tabindex="{$column->getFlag('tabIndex')}"{/if}>{$translation|escape}</textarea>
	</div>
</span>

