{**
 * templates/exercise/index.tpl
 *
 * Copyright (c) 2020 Simon Fraser University
 * Copyright (c) 2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Display exercise index.
 *}
{include file="frontend/components/header.tpl"}
<div class="page_index_site">
    <a href="{$announcementsLink}">{{__('announcement.title')}}</a>
        <br />
    <a href="{$usersLink}">{{__('users.title')}}</a>
</div>
{include file="frontend/components/footer.tpl"}