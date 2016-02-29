{**
 * templates/common/header.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common site header.
 *}
{capture assign="appSpecificMenuItems"}
	{if array_intersect(array(ROLE_ID_MANAGER), $userRoles)}
		<li><a name="issues" href="{url router=$smarty.const.ROUTE_PAGE page="manageIssues"}">{translate key="editor.navigation.issues"}</a></li>
	{/if}
{/capture}
{include file="core:common/header.tpl" appSpecificMenuItems=$appSpecificMenuItems}
