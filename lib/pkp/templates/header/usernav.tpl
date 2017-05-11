{**
 * templates/header/usernav.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Site-Wide Navigation Bar
 *}
{if $currentContext}
	{url|assign:"homeUrl" page="index" router=$smarty.const.ROUTE_PAGE}
{elseif $multipleContexts}
	{url|assign:"homeUrl" context="index" router=$smarty.const.ROUTE_PAGE}
{/if}

<script type="text/javascript">
	// Attach the JS file tab handler.
	$(function() {ldelim}
		$('#navigationContextMenu').pkpHandler(
				'$.pkp.controllers.MenuHandler');
	{rdelim});
</script>

<ul id="navigationContextMenu" class="pkp_nav_context pkp_nav_list" role="navigation" aria-label="{translate|escape key="common.navigation.siteContext"}">

	<li {if $multipleContexts}class="submenuOpensBelow"{/if} aria-haspopup="true" aria-expanded="false">
		<span class="pkp_screen_reader">
			{translate key="context.current"}
		</span>

		<a href="#" class="pkp_current_context">
			{if $displayPageHeaderTitle && is_string($displayPageHeaderTitle)}
				{$displayPageHeaderTitle}
			{elseif $currentContextName}
				{$currentContextName}
			{else}
				{$applicationName}
			{/if}
		</a>

		{if $multipleContexts}
			<h3 class="pkp_screen_reader">
				{translate key="context.select"}
			</h3>
			<ul class="pkp_contexts">
				{foreach from=$contextsNameAndUrl key=url item=name}
					{if $currentContextName == $name}{php}continue;{/php}{/if}
					<li>
						<a href="{$url}">
							{$name}
						</a>
					</li>
				{/foreach}
			</ul>
		{/if}
	</li>
</ul>

<script type="text/javascript">
	// Attach the JS file tab handler.
	$(function() {ldelim}
		$('#navigationUser').pkpHandler(
				'$.pkp.controllers.MenuHandler');
	{rdelim});
</script>
<ul id="navigationUser" class="pkp_nav_user pkp_nav_list" role="navigation" aria-label="{translate|escape key="common.navigation.user"}">
	{if $supportedLocales|@count}
		<li class="languages" aria-haspopup="true" aria-expanded="false">
			<a href="#">
				<span class="fa fa-globe"></span>
				{$supportedLocales.$currentLocale}
			</a>
			<ul>
				{foreach from=$supportedLocales item=localeName key=localeKey}
					{if $localeKey != $currentLocale}
						<li>
							<a href="{url router=$smarty.const.ROUTE_PAGE page="user" op="setLocale" path=$localeKey source=$smarty.server.REQUEST_URI}">
								{$localeName}
							</a>
						</li>
					{/if}
				{/foreach}
			</ul>
		</li>
	{/if}
	{if $homeUrl}
		<li class="view_frontend">
			<a href="{$homeUrl}">
				<span class="fa fa-eye"></span>
				{translate key="navigation.viewFrontend"}
			</a>
		</li>
	{/if}
	{if $isUserLoggedIn}
		<li class="user" aria-haspopup="true" aria-expanded="false">
			<a href="{url router=$smarty.const.ROUTE_PAGE page="user" op="profile"}">
				<span class="fa fa-user"></span>
				{$loggedInUsername|escape}
			</a>
			<ul>
				<li>
					<a href="{url router=$smarty.const.ROUTE_PAGE page="user" op="profile"}">
						{translate key="common.viewProfile"}
					</a>
				</li>
				<li>
					{if $isUserLoggedInAs}
						<a href="{url router=$smarty.const.ROUTE_PAGE page="login" op="signOutAsUser"}">
							{translate key="user.logOutAs"} {$loggedInUsername|escape}
						</a>
					{else}
						<a href="{url router=$smarty.const.ROUTE_PAGE page="login" op="signOut"}">
							{translate key="user.logOut"}
						</a>
					{/if}
				</li>
			</ul>
		</li>
	{/if}
</ul>
