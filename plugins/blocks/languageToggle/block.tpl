{**
 * plugins/blocks/languageToggle/block.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common site sidebar menu -- language toggle.
 *}
{if $enableLanguageToggle}
<div class="block" id="sidebarLanguageToggle">
	<span class="blockTitle">{fieldLabel name="languageBlockPulldown" key="common.language"}</span>
	<form class="pkp_form" action="#">
		<p>
			<select {if $isPostRequest}disabled="disabled" {/if}class="applyPlugin selectMenu" size="1" name="locale" id="languageBlockPulldown" onchange="location.href={if $languageToggleNoUser}'{$currentUrl|escape}{if strstr($currentUrl, '?')}&amp;{else}?{/if}setLocale='+this.options[this.selectedIndex].value{else}('{url|escape:"javascript" router=$smarty.const.ROUTE_PAGE page="user" op="setLocale" path="NEW_LOCALE" source=$smarty.server.REQUEST_URI}'.replace('NEW_LOCALE', this.options[this.selectedIndex].value)){/if}">
				{html_options options=$languageToggleLocales selected=$currentLocale}
			</select>
		</p>
	</form>
</div>
{/if}
