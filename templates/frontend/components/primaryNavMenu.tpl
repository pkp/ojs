{**
 * templates/frontend/components/primaryNavMenu.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Primary navigation menu list for OJS
 *}
<ul id="navigationPrimary" class="pkp_navigation_primary pkp_nav_list">

	{if $enableAnnouncements}
		<li>
			<a href="{url router=$smarty.const.ROUTE_PAGE page="announcement"}">
				{translate key="announcement.announcements"}
			</a>
		</li>
	{/if}

	{if $currentJournal}

		<li>
			<a href="{url router=$smarty.const.ROUTE_PAGE page="about"}">
				{translate key="navigation.about"}
			</a>
			<ul>
				<li>
					<a href="{url router=$smarty.const.ROUTE_PAGE page="about"}">
						{translate key="about.aboutContext"}
					</a>
				</li>
				{if $currentJournal->getLocalizedData('editorialTeam')}
					<li>
						<a href="{url router=$smarty.const.ROUTE_PAGE page="about" op="editorialTeam"}">
							{translate key="about.editorialTeam"}
						</a>
					</li>
				{/if}
				<li>
					<a href="{url router=$smarty.const.ROUTE_PAGE page="about" op="submissions"}">
						{translate key="about.submissions"}
					</a>
				</li>
				{if $currentJournal->getData('mailingAddress') || $currentJournal->getData('contactName')}
					<li>
						<a href="{url router=$smarty.const.ROUTE_PAGE page="about" op="contact"}">
							{translate key="about.contact"}
						</a>
					</li>
				{/if}
			</ul>
		</li>
	{/if}
</ul>
