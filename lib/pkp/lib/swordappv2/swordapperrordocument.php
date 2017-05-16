<?php

require_once("swordappentry.php");
require_once("utils.php");

class SWORDAPPErrorDocument extends SWORDAPPEntry {

	// The error URI
	public $sac_erroruri;
	
	// Summary description of error
	public $sac_error_summary;
	
	// Verbose description of error
	public $sac_verbose_description;

	// Construct a new deposit response by passing in the http status code
	function __construct($sac_newstatus, $sac_thexml) {
		// Call the super constructor
		parent::__construct($sac_newstatus, $sac_thexml);
	}

	// Build the error document hierarchy
	function buildhierarchy($sac_dr, $sac_ns) {
		// Call the super version
		parent::buildhierarchy($sac_dr, $sac_ns);
	
		foreach($sac_dr->attributes() as $key => $value) {
			if ($key == 'href') {
				$this->sac_erroruri = (string)$value;
			}
		}
		// Set error summary & verbose description, if available
		if(isset($sac_dr->children($sac_ns['atom'])->summary)) {
			$this->sac_error_summary = (string)$sac_dr->children($sac_ns['atom'])->summary;
		}
		if(isset($sac_dr->children($sac_ns['sword'])->verboseDescription)) {
			$this->sac_verbose_description = (string)$sac_dr->children($sac_ns['sword'])->verboseDescription;
		}
	}
}

?>
