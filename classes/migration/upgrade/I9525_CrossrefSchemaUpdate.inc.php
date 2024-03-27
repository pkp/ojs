<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use PKP\install\DowngradeNotSupportedException;

class I9525_CrossrefSchemaUpdate extends \Illuminate\Database\Migrations\Migration
{
	/**
	 * Run the migrations.
	 * @return void
	 */
	public function up()
	{
		Capsule::table('filter_groups')
			->whereIn('symbolic', ['issue=>crossref-xml', 'article=>crossref-xml'])
			->update(['output_type' => 'xml::schema(https://www.crossref.org/schemas/crossref5.3.1.xsd)']);
	}

	/**
	 * @throws DowngradeNotSupportedException
	 */
	public function down()
	{
		throw new DowngradeNotSupportedException();
	}
}
