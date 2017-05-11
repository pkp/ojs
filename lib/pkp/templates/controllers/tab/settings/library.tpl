{**
 * controllers/tab/settings/library.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * File library management.
 *
 * @uses $isModal bool True if this template is loaded inside of a modal.
 *}

{* Help Link *}
{assign var=helpClass value="pkp_help_tab"}
{if $isModal}
    {assign var=helpClass value="pkp_help_modal"}
{/if}
{help file="settings.md" section="workflow-library" class=$helpClass}

{url|assign:libraryGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.settings.library.LibraryFileAdminGridHandler" op="fetchGrid" canEdit=$canEdit escape=false}
{load_url_in_div id="libraryGridDiv" url=$libraryGridUrl}
