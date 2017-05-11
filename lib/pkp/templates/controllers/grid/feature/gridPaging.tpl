{**
 * templates/controllers/grid/feature/gridPaging.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Grid paging markup.
 *}

<div class="gridPaging">
	<div class="gridItemsPerPage">
		{translate key=common.itemsPerPage}:<select class="itemsPerPage"></select>
	</div>
	<div class="gridPages">
		{page_info iterator=$iterator itemsPerPage=$currentItemsPerPage}
		{page_links name=$grid->getId() iterator=$iterator}
	</div>
</div>
