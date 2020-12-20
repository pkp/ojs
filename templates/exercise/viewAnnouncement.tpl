{**
 * templates/exercise/announcements.tpl
 *
 * Copyright (c) 2020 Simon Fraser University
 * Copyright (c) 2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Display announcements.
 *}
{include file="frontend/components/header.tpl"}
<div class="page_index_site">
    <h1>{{__('announcement.title')}}</h1>
        <hr />
    <h2>
        <a href="{{$announcement['uri']}}">
            {{$announcement['title']}}
        </a> (ID: {{$announcement['id']}})
    </h2>
        <small>{{__('posted.on')}} {{$announcement['datePosted']|date_format:$datetimeFormatShort}}</small>
            <br>
        {{$announcement['description']}}
    <br />
        {if $announcement['keyword']}
        <small>{{__('announcements.keyword')}} <strong>{{$announcement['keyword']}}</strong></small>
        <br />
        {/if}
    <a href="{$announcementsLink}">{{__('go.back.text')}}</a>
</div>
{include file="frontend/components/footer.tpl"}