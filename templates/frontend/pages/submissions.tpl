{**
 * templates/frontend/pages/submissions.tpl
 *
 * Copyright (c) 2014-2023 Simon Fraser University
 * Copyright (c) 2003-2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Display the page to view information about submissions.
 *}
{capture assign="submissionChecklistAfterContent"}
    {foreach from=$sections item="section"}
        {if $section->getLocalizedPolicy()}
            <div class="section_policy">
                <h2>{$section->getLocalizedTitle()|escape}</h2>
                {$section->getLocalizedPolicy()}
                {if $isUserLoggedIn}
                    {capture assign="sectionSubmissionUrl"}{url page="submission" sectionId=$section->getId()}{/capture}
                    <p>
                        {translate key="about.onlineSubmissions.submitToSection" name=$section->getLocalizedTitle()|escape url=$sectionSubmissionUrl}
                    </p>
                {/if}
            </div>
        {/if}
    {/foreach}
{/capture}

{include file="core:frontend/pages/submissions.tpl"}
