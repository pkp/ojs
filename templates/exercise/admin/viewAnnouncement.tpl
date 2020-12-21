{**
 * templates/exercise/admin/announcements.tpl
 *
 * Copyright (c) 2020 Simon Fraser University
 * Copyright (c) 2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Display announcements.
 *}
{extends file="layouts/backend.tpl"}

{block name="page"}
    <pkp-header :is-one-line="true" class="pkpWorkflow__header">
        <h1 class="app__pageHeading">
            {translate key="announcement.title"}
        </h1>
    </pkp-header>
    <div class="app__contentPanel">
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
{/block}