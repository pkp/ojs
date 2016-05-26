{**
 * templates/management/settings/index.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Settings index.
 *}
{strip}
{assign var="pageTitle" value="manager.setup"}
{include file="common/header.tpl"}
{/strip}

<div class="pkp_page_content pkp_page_settings">
    <h4>{translate key="manager.setup"}</h4>
    <p>{translate key="manager.settings.journalDescription"}</p>
    <a href="{url page="management" op="settings" path="journal"}" class="button defaultButton">{translate key="common.takeMeThere"}</a>

    <h4>{translate key="manager.website"}</h4>
    <p>{translate key="manager.settings.websiteDescription"}</p>
    <a href="{url page="management" op="settings" path="website"}" class="button defaultButton">{translate key="common.takeMeThere"}</a>

    <h4>{translate key="manager.workflow"}</h4>
    <p>{translate key="manager.settings.publicationDescription"}</p>
    <a href="{url page="management" op="settings" path="publication"}" class="button defaultButton">{translate key="common.takeMeThere"}</a>

    <h4>{translate key="manager.distribution"}</h4>
    <p>{translate key="manager.settings.distributionDescription"}</p>
    <a href="{url page="management" op="settings" path="distribution"}" class="button defaultButton">{translate key="common.takeMeThere"}</a>
</div>

{include file="common/footer.tpl"}
