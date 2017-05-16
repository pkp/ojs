<?php

require_once("utils.php");

class Collection {
	
	// The title of the collection
	public $sac_colltitle;

	// The URL of the collection (where you can deposit to)
	public $sac_href;

	// The types of content accepted
	public $sac_accept;

	// The  alternative types of content accepted
	public $sac_acceptalternative;

	// The accepted packaging formats
	public $sac_acceptpackaging;

	// The collection policy
	public $sac_collpolicy;

	// The colelction abstract (dcterms)
	public $sac_abstract;

	// Whether mediation is allowed or not
	public $sac_mediation;

	// A nested service document
	public $sac_service;
	
	// Construct a new collection by passing in a title
	function __construct($sac_newcolltitle) {
		// Store the title
		$this->sac_colltitle = sac_clean($sac_newcolltitle);

		// Create the accepts arrays
		$sac_accept = array();
        $sac_acceptalternative = array();
        $sac_acceptpackaging = array();
	}

	// Add a new supported packaging type
	function addAcceptPackaging($ap) {
		$format = (string)$ap[0];
		$q = (string)$ap[0]['q'];
		if (empty($q)) {
			$q = "1.0";
		}
		$this->sac_acceptpackaging[$format] = $q;
	}
}

?>
