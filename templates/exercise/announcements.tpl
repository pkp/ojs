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
    {foreach from=$announcements item=currentAnnouncement}
        <h2>
            <a href="{{$currentAnnouncement['uri']}}">
                {{$currentAnnouncement['title']}}
            </a> (ID: {{$currentAnnouncement['id']}})
        </h2>
        <small>{{__('posted.on')}} {{$currentAnnouncement['datePosted']|date_format:$datetimeFormatShort}}</small>
            <br />
        {{$currentAnnouncement['descriptionShort']}}
            <br />
        {if $currentAnnouncement['keyword']}
        <small>{{__('announcements.keyword')}} <strong>{{$currentAnnouncement['keyword']}}</strong></small>
            <br />
        {/if}
    {/foreach}
        <br />
    <a href="{$exerciseLink}">{{__('go.back.text')}}</a>
</div>
{include file="frontend/components/footer.tpl"}