<?php

class SWORDAppStatementEntry {

    // The scheme of the entry
    public $sac_scheme;

    // The term of the entry
    public $sac_term;

    // The label for the entry
    public $sac_label;

    // The content type
    public $sac_content_type;

    // The content source
    public $sac_content_source;

    // The packaging format used
    public $sac_packaging;

    // When it was deposited
    public $sac_deposited_on;

    // Who deposited it
    public $sac_deposited_by;

    // Construct a new statement atom entry
    function __construct($sac_scheme, $sac_term, $sac_label) {
        $this->sac_scheme = $sac_scheme;
        $this->sac_term = $sac_term;
        $this->sac_label = $sac_label;
    }

    // Set the content type and source
    function addContent($sac_type, $sac_src) {
        $this->sac_content_type = $sac_type;
        $this->sac_content_source = $sac_src;
    }

    // Set the packaging
    function setPackaging($sac_packaging) {
        $this->sac_packaging = $sac_packaging;
    }

    // Set the deposited date
    function setDepositedOn($sac_deposited_on) {
        $this->sac_deposited_on = $sac_deposited_on;
    }

    // Set the deposited by
    function setDepositedBy($sac_deposited_by) {
        $this->sac_deposited_by = $sac_deposited_by;
    }

    // Print out a representation of the statement
    function toString() {
        print "  - Entry:\n";
        print "   - Scheme: " . $this->sac_scheme . "\n";
        print "   - Term: " . $this->sac_term . "\n";
        print "   - Label: " . $this->sac_label . "\n";
        print "   - Content: Type=" . $this->sac_content_type . " Source=" . $this->sac_content_source . "\n";
        print "   - Packaging: " . $this->sac_packaging . "\n";
        print "   - Deposited On: " . $this->sac_deposited_on . "\n";
        print "   - Deposited By: " . $this->sac_deposited_by . "\n";
    }
}

?>