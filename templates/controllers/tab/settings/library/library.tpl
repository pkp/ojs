{**
 * controllers/tab/settings/submissionDocuments/submissionDocuments.tpl
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * File library management.
 *
 *}
{url|assign:libraryGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.settings.library.LibraryFileAdminGridHandler" op="fetchGrid" canEdit=$canEdit escape=false}
{load_url_in_div id="libraryGridDiv" url=$libraryGridUrl}
