/**
 * @file js/load.js
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Compiler entry point for building the JavaScript package. File imports
 *  using the `@` symbol are aliased to `lib/ui-library/src`.
 */
import PkpLoad from '../lib/pkp/js/load.js';

// Import controllers used by OJS
import Container from '@/components/Container/Container.vue';
import SettingsContainer from '@/components/Container/SettingsContainer.vue';
import StatsContainer from '@/components/Container/StatsContainer.vue';
import WorkflowContainer from '@/components/Container/WorkflowContainerOJS.vue';

// Expose Vue, the registry and controllers in a global var
window.pkp = Object.assign(PkpLoad, {
	controllers: {
		Container,
		SettingsContainer,
		StatsContainer,
		WorkflowContainer
	}
});
