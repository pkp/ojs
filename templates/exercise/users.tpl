{**
 * templates/exercise/users.tpl
 *
 * Copyright (c) 2020 Simon Fraser University
 * Copyright (c) 2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Display users.
 *}
{include file="frontend/components/header.tpl"}
<div class="page_index_site">
    <h1>{{__('users.title')}}</h1>
        <br />
    <a href="{$exerciseLink}">{{__('go.back.text')}}</a>
</div>
{include file="frontend/components/footer.tpl"}