<?php

require_once('collection.php');
require_once("utils.php");

class Workspace {
	
	// The title of the workspace
	public $sac_workspacetitle;

	// Collections in the workspace
	public $sac_collections;

	// Construct a new workspace by passing in a title
	function __construct($sac_newworkspacetitle) {
		// Store the title
		$this->sac_workspacetitle = $sac_newworkspacetitle;
	}

	// Build the collection hierarchy
	function buildhierarchy($sac_colls, $sac_ns) {
		// Build the collections
		foreach ($sac_colls as $sac_collection) {
			// Create the new collection object
			$sac_newcollection = new Collection(sac_clean($sac_collection->children($sac_ns['atom'])->title));
			
			// The location of the service document
			$href = $sac_collection->xpath("@href");
			$sac_newcollection->sac_href = $href[0]['href'];
			
			// An array of the accepted deposit types
		    foreach ($sac_collection->accept as $sac_accept) {
                if ($sac_accept->attributes()->alternate == 'multipart-related') {
                    $sac_newcollection->sac_acceptalternative[] = $sac_accept;
                } else {
                    $sac_newcollection->sac_accept[] = $sac_accept;
                }
            }
            
			// An array of the accepted packages
			$sac_collection->registerXPathNamespace('sword', 'http://purl.org/net/sword/terms/');
            foreach ($sac_collection->xpath("sword:acceptPackaging") as $sac_acceptpackaging) {
				$sac_newcollection->addAcceptPackaging($sac_acceptpackaging[0]);
			}

			// Add the collection policy
			$sac_newcollection->sac_collpolicy = sac_clean($sac_collection->children($sac_ns['sword'])->collectionPolicy);
			
			// Add the collection abstract
			// Check if dcterms is in the known namespaces. If not, might not be an abstract
			if (array_key_exists('dcterms', $sac_ns)) {
				$sac_newcollection->sac_abstract = sac_clean($sac_collection->children($sac_ns['dcterms'])->abstract);
			}

			// Find out if mediation is allowed
			if ($sac_collection->children($sac_ns['sword'])->mediation == 'true') {
				$sac_newcollection->sac_mediation = true;
			} else {
				$sac_newcollection->sac_mediation = false;
			}
			
			// Add a nested service document if there is one
			$sac_newcollection->sac_service = sac_clean($sac_collection->children($sac_ns['sword'])->service);

			// Add to the  collections in this workspace
			$this->sac_collections[] = $sac_newcollection;
		}
	}
}

?>
