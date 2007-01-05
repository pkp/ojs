<div class="block">
{if $enableLanguageToggle}
	<span class="blockTitle">{translate key="common.language"}</span>
	<form action="#">
		<select size="1" name="locale" onchange="location.href={if $languageToggleNoUser}'{$currentUrl}{if strstr($currentUrl, '?')}&{else}?{/if}setLocale='+this.options[this.selectedIndex].value{else}('{url page="user" op="setLocale" path="NEW_LOCALE" source=$smarty.server.REQUEST_URI escape=false}'.replace('NEW_LOCALE', this.options[this.selectedIndex].value)){/if}" class="selectMenu">{html_options options=$languageToggleLocales selected=$currentLocale}</select>
	</form>
{/if}
</div>
