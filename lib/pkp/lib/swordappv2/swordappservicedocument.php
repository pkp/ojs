<?php

require_once('workspace.php');

class SWORDAPPServiceDocument {

	// The URL of this Service Document
	public $sac_url;
	
	// The HTTP status code returned
	public $sac_status;
	
	// The XML of the service doucment
	public $sac_xml;
	
	// The human readable status code
	public $sac_statusmessage;

	// The version of the SWORD server
	public $sac_version;

	// Whether or not verbose output is supported
	public $sac_verbose;

	// Whether or not the noOp command is supported
	public $sac_noop;

	// The max upload size of deposits
	public $sac_maxuploadsize;
	
	// Workspaces in the servicedocument
	public $sac_workspaces;

	// Construct a new servicedocument
	function __construct($sac_theurl, $sac_newstatus, $sac_thexml = '') {
		// Store the URL
		$this->sac_url = $sac_theurl;
		
		// Store the status
		$this->sac_status = $sac_newstatus;
		
		// Store the raw xml
		$this->sac_xml = $sac_thexml;

		// Store the status message
		switch($this->sac_status) {
			case 200:
				$this->sac_statusmessage = "OK";
				break;
			case 401:
				$this->sac_statusmessage = "Unauthorized";
				break;
			case 404:
				$this->sac_statusmessage = "Service document not found";
				break;
			default:
				$this->sac_statusmessage = "Unknown error (status code " . $this->sac_status . ")";
				break;
		}
		
		// Parse the xml if there is some
		if ($sac_thexml != '') {
			$sac_xml = @new SimpleXMLElement($sac_thexml);
        	$sac_ns = $sac_xml->getNamespaces(true);
            if (!isset($sac_ns['sword'])) $sac_ns['sword'] = 'http://purl.org/net/sword/terms/';
			$this->sac_version = $sac_xml->children($sac_ns['sword'])->version;
            $this->sac_verbose = $sac_xml->children($sac_ns['sword'])->verbose;
            $this->sac_noop = $sac_xml->children($sac_ns['sword'])->noOp;
            $this->sac_maxuploadsize = $sac_xml->children($sac_ns['sword'])->maxUploadSize;
				
			// Build the workspaces
			$sac_ws = @$sac_xml->children($sac_ns['app'])->workspace;
			foreach ($sac_ws as $sac_workspace) {
                $sac_newworkspace = new Workspace($sac_workspace->children($sac_ns['atom'])->title);
				$sac_newworkspace->buildhierarchy(@$sac_workspace->children($sac_ns['app']), $sac_ns);
				$this->sac_workspaces[] = $sac_newworkspace;
			}
		}
	}

    function toString() {
        print " - Version: " . $this->sac_version . "\n";
        if (!empty($this->sac_verbose)) print " - Supports Verbose: " . $this->sac_verbose . "\n";
        if (!empty($this->sac_noop)) print " - Supports NoOp: " . $this->sac_noop . "\n";
        print " - Maximum uplaod size: ";
        if (!empty($this->sac_maxuploadsize)) {
            print $this->sac_maxuploadsize . " kB\n";
        } else {
            print "undefined\n";
        }

        foreach ($this->sac_workspaces as $workspace) {
            $wstitle = $workspace->sac_workspacetitle;
            echo "   - Workspace: ".$wstitle."\n";
            $collections = $workspace->sac_collections;
            foreach ($collections as $collection) {
                $ctitle = $collection->sac_colltitle;
                echo "     - Collection: " . $ctitle . " (" . $collection->sac_href . ")\n";
                if (count($collection->sac_accept) > 0) {
                    foreach ($collection->sac_accept as $accept) {
                        echo "        - Accepts: " . $accept . "\n";
                    }
                }
                if (count($collection->sac_acceptalternative) > 0) {
                    foreach ($collection->sac_acceptalternative as $accept) {
                        echo "        - Accepts: " . $accept . " alternative='multipart-related'\n";
                    }
                }
                if (count($collection->sac_acceptpackaging) > 0) {
                    foreach ($collection->sac_acceptpackaging as $acceptpackaging => $q) {
                        echo "        - Accepted packaging format: " . $acceptpackaging . " (q=" . $q . ")\n";
                    }
                }
                if (!empty($collection->sac_collpolicy)) {
                    echo "        - Collection Policy: " . $collection->sac_collpolicy . "\n";
                }
                echo "        - Collection abstract: " . $collection->sac_abstract . "\n";
                $mediation = "false";
                if ($collection->sac_mediation == true) { $mediation = "true"; }
                echo "        - Mediation: " . $mediation . "\n";
                if (!empty($collection->sac_service)) {
                    echo "        - Service document: " . $collection->sac_service . "\n";
                }
            }
        }
    }
}

?>
