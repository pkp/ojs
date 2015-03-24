{**
 * templates/header/localnav.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Journal-Specific Navigation Bar
 *}

{capture assign="publicMenu"}
	{if $currentJournal}
		{if $enableAnnouncements}
			<li><a href="{url router=$smarty.const.ROUTE_PAGE page="announcement"}">{translate key="announcement.announcements"}</a></li>
		{/if}
		<li><a href="#">{translate key="navigation.about"}</a>
			<ul>
				{if not (empty($contextSettings.mailingAddress) && empty($contextSettings.contactName) && empty($contextSettings.contactAffiliation) && empty($contextSettings.contactMailingAddress) && empty($contextSettings.contactPhone) && empty($contextSettings.contactFax) && empty($contextSettings.contactEmail) && empty($contextSettings.supportName) && empty($contextSettings.supportPhone) && empty($contextSettings.supportEmail))}
					<li><a href="{url router=$smarty.const.ROUTE_PAGE page="about" op="contact"}">{translate key="about.contact"}</a></li>
				{/if}
				<li><a href="{url router=$smarty.const.ROUTE_PAGE page="about" op="description"}">{translate key="about.description"}</a></li>
				<li><a href="{url router=$smarty.const.ROUTE_PAGE page="about" op="editorialTeam"}">{translate key="about.editorialTeam"}</a></li>
				<li><a href="{url router=$smarty.const.ROUTE_PAGE page="about" op="editorialPolicies"}">{translate key="about.policies"}</a></li>
				<li><a href="{url router=$smarty.const.ROUTE_PAGE page="about" op="submissions"}">{translate key="about.submissions"}</a></li>
				{if not ($currentJournal->getLocalizedSetting('contributorNote') == '' && empty($contextSettings.contributors) && $currentJournal->getLocalizedSetting('sponsorNote') == '' && empty($contextSettings.sponsors))}<li><a href="{url router=$smarty.const.ROUTE_PAGE page="about" op="sponsorship"}">{translate key="about.journalSponsorship"}</a></li>{/if}
			</ul>
		</li>
	{/if}
{/capture}

<div class="pkp_structure_head_localNav">
	{if $isUserLoggedIn}
		<ul class="sf-menu">
			{if array_intersect(array(ROLE_ID_MANAGER, ROLE_ID_EDITOR, ROLE_ID_ASSISTANT, ROLE_ID_REVIEWER, ROLE_ID_AUTHOR), $userRoles)}
				<li><a href="{url router=$smarty.const.ROUTE_PAGE page="dashboard"}">{translate key="navigation.dashboard"}</a></li>
			{/if}
			{if $currentJournal}
				{if $currentJournal->getSetting('publishingMode') != $smarty.const.PUBLISHING_MODE_NONE}
					<li><a href="{url router=$smarty.const.ROUTE_PAGE page="issue" op="current"}">{translate key="navigation.current"}</a></li>
					<li><a href="{url router=$smarty.const.ROUTE_PAGE page="issue" op="archive"}">{translate key="navigation.archives"}</a>
				{/if}
				{if array_intersect(array(ROLE_ID_MANAGER), $userRoles)}
					<li>
						<a href="#">{translate key="navigation.management"}</a>
						<ul>
							<li>
								<a href="{url router=$smarty.const.ROUTE_PAGE page="management" op="settings" path="index"}">{translate key="navigation.settings"}</a>
								<ul>
									<li><a href="{url router=$smarty.const.ROUTE_PAGE page="management" op="settings" path="journal"}">{translate key="context.context"}</a></li>
									<li><a href="{url router=$smarty.const.ROUTE_PAGE page="management" op="settings" path="website"}">{translate key="manager.website"}</a></li>
									<li><a href="{url router=$smarty.const.ROUTE_PAGE page="management" op="settings" path="publication"}">{translate key="manager.workflow"}</a></li>
									<li><a href="{url router=$smarty.const.ROUTE_PAGE page="management" op="settings" path="distribution"}">{translate key="manager.distribution"}</a></li>
									<li><a href="{url router=$smarty.const.ROUTE_PAGE page="management" op="settings" path="access"}">{translate key="navigation.access"}</a></li>
								</ul>
							</li>
							<li>
								<a href="{url router=$smarty.const.ROUTE_PAGE page="management" op="tools" path="index"}">{translate key="navigation.tools"}</a>
								<ul>
									<li><a href="{url router=$smarty.const.ROUTE_PAGE page="management" op="importexport"}">{translate key="navigation.tools.importExport"}</a></li>
									<li><a href="{url router=$smarty.const.ROUTE_PAGE page="manager" op="statistics"}">{translate key="navigation.tools.statistics"}</a></li>
								</ul>
							</li>
						</ul>
					</li>
				{/if}{* ROLE_ID_MANAGER *}
				{$publicMenu}
			{/if}
		</ul>
	{elseif !$notInstalled}{* !$isUserLoggedIn *}
		<ul class="sf-menu">
			{if $currentJournal}
				{if $currentJournal->getSetting('publishingMode') != $smarty.const.PUBLISHING_MODE_NONE}
					<li><a href="{url router=$smarty.const.ROUTE_PAGE page="issue" op="current"}">{translate key="navigation.current"}</a></li>
					<li><a href="{url router=$smarty.const.ROUTE_PAGE page="issue" op="archive"}">{translate key="navigation.archives"}</a></li>
				{/if}
			{/if}
			{$publicMenu}
		</ul>
	{/if}{* $isUserLoggedIn *}
</div>
