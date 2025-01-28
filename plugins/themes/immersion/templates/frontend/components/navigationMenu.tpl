{**
 * templates/frontend/components/navigationMenu.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Primary navigation menu list for OJS
 *
 * @uses navigationMenu array Hierarchical array of navigation menu item assignments
 * @uses id string Element ID to assign the outer <ul>
 * @uses ulClass string Class name(s) to assign the outer <ul>
 * @uses liClass string Class name(s) to assign all <li> elements
 *}

{if $navigationMenu}
	<ul id="{$id|escape}" class="{$ulClass|escape} pkp_nav_list{if $id==="navigationPrimary"} navbar-nav mx-auto{/if}">
		{foreach key=field item=navigationMenuItemAssignment from=$navigationMenu->menuTree}
			{if !$navigationMenuItemAssignment->navigationMenuItem->getIsDisplayed()}
				{continue}
			{/if}

			{if $navigationMenuItemAssignment->navigationMenuItem->getIsChildVisible()}
				{assign var=hasSubmenu value=true}
			{else}
				{assign var=hasSubmenu value=false}
			{/if}
			{if $navigationMenuItemAssignment->navigationMenuItem->getType() == "NMI_TYPE_USER_LOGIN" && $requestedOp|escape == "register"}
				<li class="{$liClass|escape} nav-item">
					<a class="{if $id === "navigationUser"}main-header__admin-link{elseif $id === "navigationPrimary"}main-header__nav-link{/if}"
					   href="{$navigationMenuItemAssignment->navigationMenuItem->getUrl()}">
						{$navigationMenuItemAssignment->navigationMenuItem->getLocalizedTitle()}
					</a>
				</li>
			{else}
				<li class="{$liClass|escape} {$navigationMenuItemAssignment->navigationMenuItem->getType()|lower} nav-item{if $hasSubmenu} dropdown{/if}">
					<a class="{if $id === "navigationUser"}main-header__admin-link{elseif $id === "navigationPrimary"}main-header__nav-link{/if}{if $hasSubmenu} dropdown-toggle{/if}"
					   href="{$navigationMenuItemAssignment->navigationMenuItem->getUrl()}"{if $hasSubmenu} role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"{/if}>
						{$navigationMenuItemAssignment->navigationMenuItem->getLocalizedTitle()}
					</a>
					{if $hasSubmenu}
						<ul class="dropdown-menu{if $id==="navigationUser"} dropdown-menu-right{/if}">
							{foreach key=childField item=childNavigationMenuItemAssignment from=$navigationMenuItemAssignment->children}
								{if $childNavigationMenuItemAssignment->navigationMenuItem->getIsDisplayed()}
									<li class="{$liClass|escape} dropdown-item">
										<a class="nav-link"
										   href="{$childNavigationMenuItemAssignment->navigationMenuItem->getUrl()}">
											{$childNavigationMenuItemAssignment->navigationMenuItem->getLocalizedTitle()}
										</a>
									</li>
								{/if}
							{/foreach}
						</ul>
					{/if}
				</li>
			{/if}
		{/foreach}
	</ul>
{/if}
