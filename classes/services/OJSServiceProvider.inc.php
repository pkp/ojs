<?php

/**
 * @file classes/services/OJSServiceProvider.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OJSServiceProvider
 * @ingroup services
 *
 * @brief Utility class to package all OJS services
 */

namespace APP\Services;

use \Pimple\Container;
use \APP\Services\PublicationService;
use \APP\Services\StatsEditorialService;
use \APP\Services\StatsService;
use \PKP\Services\PKPUserService;
use \PKP\Services\PKPAuthorService;
use \PKP\Services\PKPEmailTemplateService;
use \PKP\Services\PKPSchemaService;
use \PKP\Services\PKPSiteService;

class OJSServiceProvider implements \Pimple\ServiceProviderInterface {

	/**
	 * Registers services
	 * @param \Pimple\Container $pimple
	 */
	public function register(Container $pimple) {

		// Author service
		$pimple['author'] = function() {
			return new PKPAuthorService();
		};

		// Submission service
		$pimple['submission'] = function() {
			return new SubmissionService();
		};

		// Publication service
		$pimple['publication'] = function() {
			return new PublicationService();
		};

		// Issue service
		$pimple['issue'] = function() {
			return new IssueService();
		};

		// Section service
		$pimple['section'] = function() {
			return new SectionService();
		};

		// NavigationMenus service
		$pimple['navigationMenu'] = function() {
			return new NavigationMenuService();
		};

		// Galley service
		$pimple['galley'] = function() {
			return new GalleyService();
		};

		// User service
		$pimple['user'] = function() {
			return new PKPUserService();
		};

		// Context service
		$pimple['context'] = function() {
			return new ContextService();
		};

		// Site service
		$pimple['site'] = function() {
			return new PKPSiteService();
		};

		// Email Templates service
		$pimple['emailTemplate'] = function() {
			return new PKPEmailTemplateService();
		};

		// Schema service
		$pimple['schema'] = function() {
			return new PKPSchemaService();
		};

		// Publication statistics service
		$pimple['stats'] = function() {
			return new StatsService();
		};

		// Editorial statistics service
		$pimple['editorialStats'] = function() {
			return new StatsEditorialService();
		};
	}
}
