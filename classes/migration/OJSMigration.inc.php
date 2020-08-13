<?php

/**
 * @file classes/migration/OJSMigration.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OJSMigration
 * @brief Describe database table structures.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

class OJSMigration extends Migration {
        /**
         * Run the migrations.
         * @return void
         */
        public function up() {
		// Custom sequencing information for journal sections by issue, when available.
		Capsule::schema()->create('custom_section_orders', function (Blueprint $table) {
			$table->bigInteger('issue_id');
			$table->foreign('issue_id')->references('issue_id')->on('issues');

			$table->bigInteger('section_id');
			$table->foreign('section_id')->references('section_id')->on('sections');

			$table->float('seq', 8, 2)->default(0);
			$table->unique(['issue_id', 'section_id'], 'custom_section_orders_pkey');
		});

		// Archived, removed from TOC, unscheduled or unpublished journal articles.
		Capsule::schema()->create('submission_tombstones', function (Blueprint $table) {
			$table->bigInteger('tombstone_id')->autoIncrement();

			$table->bigInteger('submission_id');
			$table->foreign('submission_id')->references('submission_id')->on('submissions');

			$table->datetime('date_deleted');

			$table->bigInteger('journal_id');
			$table->foreign('journal_id')->references('journal_id')->on('journals');

			$table->bigInteger('section_id');
			$table->foreign('section_id')->references('section_id')->on('sections');

			$table->string('set_spec', 255);
			$table->string('set_name', 255);
			$table->string('oai_identifier', 255);

			$table->index(['journal_id'], 'submission_tombstones_journal_id');
			$table->index(['submission_id'], 'submission_tombstones_submission_id');
		});

		// Publication galleys
		Capsule::schema()->create('publication_galleys', function (Blueprint $table) {
			$table->bigInteger('galley_id')->autoIncrement();
			$table->string('locale', 14)->nullable();

			$table->bigInteger('publication_id');
			$table->foreign('publication_id')->references('publication_id')->on('publications');

			$table->string('label', 255)->nullable();

			$table->bigInteger('file_id')->nullable();
			$table->foreign('file_id')->references('file_id')->on('submission_files');

			$table->float('seq', 8, 2)->default(0);
			$table->string('remote_url', 2047)->nullable();
			$table->tinyInteger('is_approved')->default(0);
			$table->string('url_path', 64)->nullable();

			$table->index(['publication_id'], 'publication_galleys_publication_id');
			$table->index(['url_path'], 'publication_galleys_url_path');
		});

		// Galley metadata.
		Capsule::schema()->create('publication_galley_settings', function (Blueprint $table) {
			$table->bigInteger('galley_id');
			$table->foreign('galley_id')->references('galley_id')->on('publication_galleys');

			$table->string('locale', 14)->default('');
			$table->string('setting_name', 255);
			$table->text('setting_value')->nullable();

			$table->index(['galley_id'], 'publication_galley_settings_galley_id');
			$table->unique(['galley_id', 'locale', 'setting_name'], 'publication_galley_settings_pkey');
		});
		// Add partial index (DBMS-specific)
		switch (Capsule::connection()->getDriverName()) {
			case 'mysql': Capsule::connection()->unprepared('CREATE INDEX publication_galley_settings_name_value ON publication_galley_settings (setting_name(50), setting_value(150))'); break;
			case 'pgsql': Capsule::connection()->unprepared("CREATE INDEX publication_galley_settings_name_value ON publication_galley_settings (setting_name, setting_value)"); break;
		}

		// Subscription types.
		Capsule::schema()->create('subscription_types', function (Blueprint $table) {
			$table->bigInteger('type_id')->autoIncrement();

			$table->bigInteger('journal_id');
			$table->foreign('journal_id')->references('journal_id')->on('journals');

			$table->float('cost', 8, 2);
			$table->string('currency_code_alpha', 3);
			$table->tinyInteger('non_expiring')->default(0);
			$table->smallInteger('duration')->nullable();
			$table->smallInteger('format');
			$table->tinyInteger('institutional')->default(0);
			$table->tinyInteger('membership')->default(0);
			$table->tinyInteger('disable_public_display');
			$table->float('seq', 8, 2);
		});

		// Locale-specific subscription type data
		Capsule::schema()->create('subscription_type_settings', function (Blueprint $table) {
			$table->bigInteger('type_id');
			$table->foreign('type_id')->references('type_id')->on('subscription_types');

			$table->string('locale', 14)->default('');
			$table->string('setting_name', 255);
			$table->text('setting_value')->nullable();
			$table->string('setting_type', 6);

			$table->index(['type_id'], 'subscription_type_settings_type_id');
			$table->unique(['type_id', 'locale', 'setting_name'], 'subscription_type_settings_pkey');
		});

		// Journal subscriptions.
		Capsule::schema()->create('subscriptions', function (Blueprint $table) {
			$table->bigInteger('subscription_id')->autoIncrement();

			$table->bigInteger('journal_id');
			$table->foreign('journal_id')->references('journal_id')->on('journals');

			$table->bigInteger('user_id');
			$table->foreign('user_id')->references('user_id')->on('users');

			$table->bigInteger('type_id');
			$table->foreign('type_id')->references('type_id')->on('subscription_types');

			$table->date('date_start')->nullable();
			$table->datetime('date_end')->nullable();
			$table->tinyInteger('status')->default(1);
			$table->string('membership', 40)->nullable();
			$table->string('reference_number', 40)->nullable();
			$table->text('notes')->nullable();
		});

		// Journal institutional subscriptions.
		Capsule::schema()->create('institutional_subscriptions', function (Blueprint $table) {
			$table->bigInteger('institutional_subscription_id')->autoIncrement();

			$table->bigInteger('subscription_id');
			$table->foreign('subscription_id')->references('subscription_id')->on('subscriptions');

			$table->string('institution_name', 255);
			$table->string('mailing_address', 255)->nullable();
			$table->string('domain', 255)->nullable();

			$table->index(['subscription_id'], 'institutional_subscriptions_subscription_id');
			$table->index(['domain'], 'institutional_subscriptions_domain');
		});

		// Journal institutional subscription IPs and IP ranges.
		Capsule::schema()->create('institutional_subscription_ip', function (Blueprint $table) {
			$table->bigInteger('institutional_subscription_ip_id')->autoIncrement();

			$table->bigInteger('subscription_id');
			$table->foreign('subscription_id')->references('subscription_id')->on('subscriptions');

			$table->string('ip_string', 40);
			$table->bigInteger('ip_start');
			$table->bigInteger('ip_end')->nullable();

			$table->index(['subscription_id'], 'institutional_subscription_ip_subscription_id');
			$table->index(['ip_start'], 'institutional_subscription_ip_start');
			$table->index(['ip_end'], 'institutional_subscription_ip_end');
		});

		// Logs queued (unfulfilled) payments.
		Capsule::schema()->create('queued_payments', function (Blueprint $table) {
			$table->bigInteger('queued_payment_id')->autoIncrement();
			$table->datetime('date_created');
			$table->datetime('date_modified');
			$table->date('expiry_date')->nullable();
			$table->text('payment_data')->nullable();
		});

		// Logs completed (fulfilled) payments.
		Capsule::schema()->create('completed_payments', function (Blueprint $table) {
			$table->bigInteger('completed_payment_id')->autoIncrement();
			$table->datetime('timestamp');
			$table->bigInteger('payment_type');
			$table->bigInteger('context_id');

			// pkp/pkp-lib#6093 FIXME: Can't set relationship constraints on assoc_type/assoc_id pairs
			$table->bigInteger('user_id')->nullable();
			$table->bigInteger('assoc_id')->nullable();

			$table->float('amount', 8, 2);
			$table->string('currency_code_alpha', 3)->nullable();
			$table->string('payment_method_plugin_name', 80)->nullable();
		});
	}

	/**
	 * Reverse the migration.
	 * @return void
	 */
	public function down() {
		Capsule::schema()->drop('completed_payments');
		Capsule::schema()->drop('queued_payments');
		Capsule::schema()->drop('institutional_subscription_ip');
		Capsule::schema()->drop('institutional_subscriptions');
		Capsule::schema()->drop('subscriptions');
		Capsule::schema()->drop('subscription_type_settings');
		Capsule::schema()->drop('subscription_types');
		Capsule::schema()->drop('publication_galley_settings');
		Capsule::schema()->drop('publication_galleys');
		Capsule::schema()->drop('submission_tombstones');
		Capsule::schema()->drop('custom_section_orders');
	}
}
