<?php

require_once("swordappentry.php");

class SWORDAPPResponse extends SWORDAPPEntry {

    // Construct a new deposit response by passing in the http status code
    function __construct($sac_newstatus, $sac_thexml) {
        // Call the super constructor
	    parent::__construct($sac_newstatus, $sac_thexml);
        
    }

}

?>
