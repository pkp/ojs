{**
 * controllers/tab/settings/genres.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Publication process genres (submission file types).
 *
 *}

{* Help Link *}
{help file="settings.md" section="workflow-components" class="pkp_help_tab"}

<div class="genres">
	{url|assign:genresUrl router=$smarty.const.ROUTE_COMPONENT component="grid.settings.genre.GenreGridHandler" op="fetchGrid" escape=false}
	{load_url_in_div id="genresContainer" url=$genresUrl}
</div>
