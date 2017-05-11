{**
 * controllers/tab/settings/users.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * User management.
 *
 *}

{* Help Link *}
{help file="users-and-roles.md" section="users" class="pkp_help_tab"}

{url|assign:usersUrl router=$smarty.const.ROUTE_COMPONENT component="grid.settings.user.UserGridHandler" op="fetchGrid" oldUserId=$oldUserId escape=false}
{assign var=gridContainerId value="userGridContainer"}
{load_url_in_div id=$gridContainerId url=$usersUrl}
