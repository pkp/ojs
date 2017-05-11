{**
 * temlates/controllers/grid/gridBodyPartWithCategory.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * a set of grid rows with a category row at the beginning
 *}
{assign var=categoryId value="component-"|concat:$categoryRow->getGridId():"-category-":$categoryRow->getId()|escape}
<tbody id="{$categoryId|escape}" class="element{$categoryRow->getId()|escape} category_grid_body">
	{$renderedCategoryRow}
	{if $grid->getIsSubcomponent()}
		</tbody></table>
		<div class="scrollable"><table>
			{include file="controllers/grid/columnGroup.tpl" columns=$grid->getColumns()}
			<tbody>
	{/if}
	{** the regular data rows **}
	{foreach from=$rows item=row}
		{$row}
	{/foreach}
</tbody>
<tbody id="{$categoryId|concat:'-emptyPlaceholder'|escape}" class="empty category_placeholder"{if count($rows) > 0} style="display: none;"{/if}>
	{**
		We need the last (=empty) line even if we have rows
		so that we can restore it if the user deletes all rows.
	**}
	<tr>
		<td colspan="{$grid->getColumnsCount('indent')}">{translate key=$grid->getEmptyCategoryRowText()}</td>
	</tr>
</tbody>
{if $grid->getIsSubcomponent()}
	</table></div><table>
		{include file="controllers/grid/columnGroup.tpl" columns=$grid->getColumns()}
{/if}
