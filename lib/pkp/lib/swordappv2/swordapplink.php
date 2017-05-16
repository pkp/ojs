<?php

class SWORDAPPLink {

	// The 'type' of the link
	public $sac_linktype;

	// The 'rel' of the link
	public $sac_linkrel;

    // The 'href' of the link
    public $sac_linkhref;

    // Construct a new deposit response by passing in the http status code
	function __construct($rel, $href, $type = '') {
        $this->sac_linkrel = $rel;
        $this->sac_linkhref = $href;
        $this->sac_linktype = $type;
    }
}

?>