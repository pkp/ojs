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
        <tabs :track-history="true">
            <tab id="announcement" label="{translate key="announcement.title"}">
        {foreach from=$announcements item=currentAnnouncement}
            <h2>
                {{$currentAnnouncement['title']}} (ID: {{$currentAnnouncement['id']}})
            </h2>
            <small>{{__('posted.on')}} {{$currentAnnouncement['datePosted']|date_format:$datetimeFormatShort}}</small>
            <pkp-button
                element="a"
                href="{{$currentAnnouncement['uri']}}"
            >
                {{ __('common.view') }}
            </pkp-button>
        {/foreach}
            </tab>
        </tabs>
    </div>
{/block}