{**
 * templates/frontend/components/languageSwitcher.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief A re-usable template for displaying a language switcher dropdown.
 *
 * @uses $currentLocale string Locale key for the locale being displayed
 * @uses $languageToggleLocales array All supported locales
 * @uses $id string A unique ID for this language toggle
 *}

<ul id="{$id|escape}" class="language-toggle nav">
	<li class="nav-item dropdown">
		<a class="main-header__lang-link dropdown-toggle" id="languageToggleMenu{$id|escape}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
			<span class="visually-hidden">{translate key="plugins.themes.immersion.language.toggle"}</span>
			{$languageToggleLocales[$currentLocale]|escape}
		</a>

		<ul class="dropdown-menu dropdown-menu-left" aria-labelledby="languageToggleMenu{$id|escape}">
			{foreach from=$languageToggleLocales item=localeName key=localeKey}
				{if $localeKey !== $currentLocale}
					<li class="dropdown-item">
						<a class="nav-link" href="{url router=$smarty.const.ROUTE_PAGE page="user" op="setLocale" path=$localeKey source=$smarty.server.REQUEST_URI}">
							{$localeName|escape}
						</a>
					</li>
				{/if}
			{/foreach}
		</ul>
	</li>
</ul>
