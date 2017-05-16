<?php

require_once("swordapplink.php");
require_once("utils.php");

class SWORDAPPEntry {

    // The HTTP status code returned
    public $sac_status;

    // The XML returned by the deposit
    public $sac_xml;

    // The human readable status code
    public $sac_statusmessage;

    // The atom:id identifier
    public $sac_id;

    // The atom:content values
    public $sac_content_src;
    public $sac_content_type;

    // The authors
    public $sac_authors;

    // The contributors
    public $sac_contributors;

    // The links
    public $sac_links;

    // The title
    public $sac_title;

    // The summary
    public $sac_summary;

    // The rights
    public $sac_rights;

    // The treatment
    public $sac_treatment;

    // The verbose description
    public $sac_verbose_treatment;

    // The update date
    public $sac_updated;

    // The packaging format used
    public $sac_packaging;

    // The generator
    public $sac_generator;
    public $sac_generator_uri;

    // The user agent
    public $sac_useragent;

    // The noOp status
    public $sac_noOp;

    // Any dcterms metadata
    public $sac_dcterms;

    // The Edit IRI
    public $sac_edit_iri;

    // The SE-IRI
    public $sac_se_iri;

    // The Atom Statement IRI
    public $sac_state_iri_atom;

    // The Atom Statement IRI
    public $sac_state_iri_ore;

    // The Edit Media IRI
    public $sac_edit_media_iri;
    
    // The Atom feed representation of media resources
    public $sac_edit_media_iri_atom;

    // Construct a new deposit response by passing in the http status code
    function __construct($sac_newstatus, $sac_thexml) {
        // Store the status
        $this->sac_status = $sac_newstatus;

        // Store the xml
        $this->sac_xml = $sac_thexml;

        // Store the status message
        switch($this->sac_status) {
            case 200:
                $this->sac_statusmessage = "OK";
                break;
            case 201:
                $this->sac_statusmessage = "Created";
                break;
            case 202:
                $this->sac_statusmessage = "Accepted";
                break;
            case 400:
                $this->sac_statusmessage = "Bad request";
                break;
            case 401:
                $this->sac_statusmessage = "Unauthorized";
                break;
            case 403:
                $this->sac_statusmessage = "Forbidden";
                break;
            case 412:
                $this->sac_statusmessage = "Precondition failed";
                break;
            case 413:
                $this->sac_statusmessage = "Request entity too large";
                break;
            case 415:
                $this->sac_statusmessage = "Unsupported media type";
                break;
            default:
                $this->sac_statusmessage = "Unknown error (status code " . $this->sac_status . ")";
            break;
        }

        // Initalise arrays
        $this->sac_authors = array();
        $this->sac_contributors = array();
        $this->sac_links = array();
        $this->sac_dcterms = array();

        // Assume noOp is false unless we change it later
        $this->sac_noOp = false;
    }

    // Build the workspace hierarchy
    function buildhierarchy($sac_dr, $sac_ns) {
        // Set the default namespace
        $sac_dr->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');
        if (!isset($sac_ns['atom'])) $sac_ns['atom'] = 'http://www.w3.org/2005/Atom';
        if (!isset($sac_ns['dcterms'])) $sac_ns['dcterms'] = 'http://purl.org/dc/terms/';
        if (!isset($sac_ns['sword'])) $sac_ns['sword'] = 'http://purl.org/net/sword/';

        // Parse the results
        $this->sac_id = $sac_dr->children($sac_ns['atom'])->id;
        $sac_contentbits = $sac_dr->xpath("atom:content");
        if (!empty($sac_contentbits)) {
            $this->sac_content_src = $sac_contentbits[0]->attributes()->src;
            $this->sac_content_type = $sac_contentbits[0]->attributes()->type;
        }

        // Store the authors
        foreach ($sac_dr->children($sac_ns['atom'])->author as $sac_author) {
            $sac_theauthor = $sac_author->children($sac_ns['atom'])->name . "";
            $this->sac_authors[] = $sac_theauthor;
        }

        // Store the contributors
        foreach ($sac_dr->children($sac_ns['atom'])->contributor as $sac_contributor) {
            $sac_thecontributor = $sac_contributor->children($sac_ns['atom'])->name . "";
            $this->sac_contributors[] = $sac_thecontributor;
        }

        // Store the links
        foreach ($sac_dr->xpath("atom:link") as $sac_link) {
            $sac_linkobject = new SWORDAPPLink($sac_link->attributes()->rel, $sac_link->attributes()->href, $sac_link->attributes()->type);
            array_push($this->sac_links, $sac_linkobject);

            // Store the Edit IRI
            if ($sac_linkobject->sac_linkrel == 'edit') $this->sac_edit_iri = $sac_linkobject->sac_linkhref;

            // Store the SE-IRI
            if ($sac_linkobject->sac_linkrel == 'http://purl.org/net/sword/terms/add') $this->sac_se_iri = $sac_linkobject->sac_linkhref;

            // Store the Statement IRIs
            if ($sac_linkobject->sac_linkrel == 'http://purl.org/net/sword/terms/statement') {
                if (($sac_linkobject->sac_linktype == 'application/atom+xml;type=feed') ||
                    ($sac_linkobject->sac_linktype == 'application/atom+xml; type=feed')) {
                    $this->sac_state_iri_atom = $sac_linkobject->sac_linkhref;
                } else if ($sac_linkobject->sac_linktype == 'application/rdf+xml') {
                    $this->sac_state_iri_ore = $sac_linkobject->sac_linkhref;
                }
            }
            // Store the Edit Media IRIs
            if ($sac_linkobject->sac_linkrel == 'edit-media') {
              // Edit media IRI as Atom feed
              if (($sac_linkobject->sac_linktype == 'application/atom+xml;type=feed') ||
                  ($sac_linkobject->sac_linktype == 'application/atom+xml; type=feed')) {
                $this->sac_edit_media_iri_atom = $sac_linkobject->sac_linkhref;
              }
              else {
                // Edit media IRI
                $this->sac_edit_media_iri = $sac_linkobject->sac_linkhref;
              }
            }
        }
        
        // Store the title and summary
        $this->sac_title = sac_clean($sac_dr->children($sac_ns['atom'])->title);
        $this->sac_summary = sac_clean($sac_dr->children($sac_ns['atom'])->summary);

        // Store the updated date
        $this->sac_updated = $sac_dr->children($sac_ns['atom'])->updated;

        // Store the rights
        $this->sac_rights = sac_clean($sac_dr->children($sac_ns['atom'])->rights);

        // Store the treatment
        $this->sac_treatment = sac_clean($sac_dr->children($sac_ns['sword'])->treatment);

        // Store the verboseDescription
        $this->sac_verbose_treatment = sac_clean($sac_dr->children($sac_ns['sword'])->verboseDescription);

        // Store the format namespace
        $this->sac_packaging = $sac_dr->children($sac_ns['sword'])->packaging;

        // Store the generator
        $this->sac_generator = sac_clean($sac_dr->children($sac_ns['atom'])->generator);
        $sac_gen = $sac_dr->xpath("atom:generator");
        if (!empty($sac_gen)) { $this->sac_generator_uri = $sac_gen[0]->attributes()->uri; }

        // Store the user agent
        $this->sac_useragent = sac_clean($sac_dr->children($sac_ns['sword'])->userAgent);

        // Store any embedded metadata
        foreach ($sac_dr->children($sac_ns['dcterms']) as $sac_dcterm) {
            if (!isset($this->sac_dcterms[$sac_dcterm->getName()])) {
                $this->sac_dcterms[$sac_dcterm->getName()] = array();
            }
            array_push($this->sac_dcterms[$sac_dcterm->getName()], $sac_dcterm);
        }

        // Store the noOp status
        if (strtolower((string)$sac_dr->children($sac_ns['sword'])->noOp) == 'true') {
            $this->sac_noOp = true;
        }
    }

    function toString() {
        print " - ID: " . $this->sac_id . "\n";
        print " - Title: " . $this->sac_title . "\n";
        print " - Content: " . $this->sac_content_src ." (" . $this->sac_content_type . ")\n";
        foreach ($this->sac_authors as $author) {
            print "  - Author: " . $author . "\n";
        }
        foreach ($this->sac_contributors as $contributor) {
            print "  - Contributor: " . $contributor . "\n";
        }
        foreach ($this->sac_links as $links) {
            print '  - Link: rel=' . $links->sac_linkrel . ' ';
            print 'href=' . $links->sac_linkhref . ' ';
            if (isset($links->sac_linktype)) {
                print 'type=' . $links->sac_linktype;
            }
            print "\n";
        }
        print " - Summary: " . $this->sac_summary . "\n";
        print " - Updated: " . $this->sac_updated . "\n";
        print " - Rights: " . $this->sac_rights . "\n";
        print " - Treatment: " . $this->sac_treatment . "\n";
        print " - Verbose description: " . $this->sac_verbose_treatment . "\n";
        print " - Packaging: " . $this->sac_packaging . "\n";
        print " - Generator: " . $this->sac_generator . " (" . $this->sac_generator_uri . ")\n";
        print " - User agent: " . $this->sac_useragent . "\n";
        if (!empty($this->sac_noOp)) { print " - noOp: " . $this->sac_noOp . "\n"; }

        foreach ($this->sac_dcterms as $dcterm => $dcvalues) {
            print ' - Dublin Core Metadata: ' . $dcterm . "\n";
            foreach ($dcvalues as $dcvalue) {
                print '    - ' . $dcvalue . "\n";
            }
        }
    }
}

?>
