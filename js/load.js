/**
 * @file js/load.js
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Compiler entry point for building the JavaScript package. File imports
 *  using the `@` symbol are aliased to `lib/ui-library/src`.
 */
import PkpLoad from '../lib/pkp/js/load.js';

// Import controllers used by OJS
import Container from '@/components/Container/Container.vue';
import Page from '@/components/Container/Page.vue';
import AccessPage from '@/components/Container/AccessPage.vue';
import AddContextContainer from '@/components/Container/AddContextContainer.vue';
import AdminPage from '@/components/Container/AdminPage.vue';
import ImportExportPage from '@/components/Container/ImportExportPage.vue';
import SettingsPage from '@/components/Container/SettingsPage.vue';
import StatsEditorialPage from '@/components/Container/StatsEditorialPage.vue';
import StatsPublicationsPage from '@/components/Container/StatsPublicationsPage.vue';
import StatsUsersPage from '@/components/Container/StatsUsersPage.vue';
import WorkflowPage from '@/components/Container/WorkflowPageOJS.vue';

// Required by the URN plugin
import FieldText from '@/components/Form/fields/FieldText.vue';

// Expose Vue, the registry and controllers in a global var
window.pkp = Object.assign(PkpLoad, {
	controllers: {
		Container,
		Page,
		AccessPage,
		AddContextContainer,
		AdminPage,
		ImportExportPage,
		SettingsPage,
		StatsEditorialPage,
		StatsPublicationsPage,
		StatsUsersPage,
		WorkflowPage
	}
});

// Required by the URN plugin
window.pkp.Vue.component('field-text', FieldText);
