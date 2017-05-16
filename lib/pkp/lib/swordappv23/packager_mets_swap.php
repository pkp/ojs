<?php

class PackagerMetsSwap {

    // The location of the files (without final directory)
    public $sac_root_in;

    // The directory to zip up in the $sac_root_in directory
    public $sac_dir_in;

    // The location to write the package out to
    public $sac_root_out;

    // The filename to save the package as
    public $sac_file_out;

    // The name of the metadata file
    public $sac_metadata_filename = "mets.xml";

    // The type (e.g. ScholarlyWork)
    public $sac_type;
    
    // The title of the item
    public $sac_title;

    // The abstract of the item
    public $sac_abstract;

    // Creators
    public $sac_creators;

    // Subjects
    public $sac_subjects;

    // Identifier
    public $sac_identifier;

    // Date made available
    public $sac_dateavailable;

    // Status
    public $sac_statusstatement;

    // Copyright holder
    public $sac_copyrightholder;
    
    // Custodian
    public $sac_custodian;

    // Bibliographic citation
    public $sac_citation;

    // Language
    public $sac_language;

    // File name
    public $sac_files;

    // MIME type
    public $sac_mimetypes;

    // Provenances
    public $sac_provenances;

    // Rights
    public $sac_rights;

    // Publisher
    public $sac_publisher;

    // Number of files added
    public $sac_filecount;


    function __construct($sac_rootin, $sac_dirin, $sac_rootout, $sac_fileout) {
        // Store the values
        $this->sac_root_in = $sac_rootin;
        $this->sac_dir_in = $sac_dirin;
        $this->sac_root_out = $sac_rootout;
        $this->sac_file_out = $sac_fileout;
        $this->sac_creators = array();
        $this->sac_subjects = array();
        $this->sac_files = array();
        $this->sac_mimetypes = array();
        $this->sac_provenances = array();
        $this->sac_rights = array();
        $this->sac_filecount = 0;
    }

    function setType($sac_thetype) {
        $this->sac_type = $sac_thetype;
    }

    function setTitle($sac_thetitle) {
        $this->sac_title = $this->clean($sac_thetitle);
    }

    function setAbstract($sac_thetitle) {
        $this->sac_abstract = $this->clean($sac_thetitle);
    }

    function addCreator($sac_creator) {
        array_push($this->sac_creators, $this->clean($sac_creator));
    }

    function addSubject($sac_subject) {
        array_push($this->sac_subjects, $this->clean($sac_subject));
    }

    function addProvenance($sac_provenance) {
        array_push($this->sac_provenances, $this->clean($sac_provenance));
    }

    function addRights($sac_right) {
        array_push($this->sac_rights, $this->clean($sac_right));
    }

    function setIdentifier($sac_theidentifier) {
        $this->sac_identifier = $sac_theidentifier;
    }
    
    function setStatusStatement($sac_thestatus) {
        $this->sac_statusstatement = $sac_thestatus;
    }

    function setCopyrightHolder($sac_thecopyrightholder) {
        $this->sac_copyrightholder = $this->clean($sac_thecopyrightholder);
    }
    
    function setCustodian($sac_thecustodian) {
        $this->sac_custodian = $this->clean($sac_thecustodian);
    }

    function setCitation($sac_thecitation) {
        $this->sac_citation = $this->clean($sac_thecitation);
    }

    function setLanguage($sac_thelanguage) {
        $this->sac_language = $this->clean($sac_thelanguage);
    }

    function setDateAvailable($sac_thedta) {
        $this->sac_dateavailable = $sac_thedta;
    }

    function setPublisher($sac_thepublisher) {
        $this->sac_publisher = $sac_thepublisher;
    }

    function addFile($sac_thefile, $sac_themimetype) {
        array_push($this->sac_files, $sac_thefile);
        array_push($this->sac_mimetypes, $sac_themimetype);
        $this->sac_filecount++;
    }

    function addMetadata($sac_theelement, $sac_thevalue) {
        switch ($sac_theelement) {
            case "abstract":
                $this->setAbstract($sac_thevalue);
                break;
            case "available":
                $this->setDateAvailable($sac_thevalue);
                break;
            case "bibliographicCitation":
                $this->setCitation($sac_thevalue);
                break;
            case "creator":
                $this->addCreator($sac_thevalue);
                break;
            case "identifier":
                $this->setIdentifier($sac_thevalue);
                break;
            case "publisher":
                $this->setPublisher($sac_thevalue);
                break;
            case "title":
                $this->setTitle($sac_thevalue);
                break;
        }
    }

    function create() {
        // Write the metadata (mets) file
        $fh = @fopen($this->sac_root_in . '/' . $this->sac_dir_in . '/' . $this->sac_metadata_filename, 'w');
        if (!$fh) {
            throw new Exception("Error writing metadata file (" . 
                                $this->sac_root_in . '/' . $this->sac_dir_in . '/' . $this->sac_metadata_filename . ")");
        }
        $this->writeHeader($fh);
        $this->writeDmdSec($fh);
        $this->writeFileGrp($fh);
        $this->writeStructMap($fh);
        $this->writeFooter($fh);    
        fclose($fh);
        
        // Create the zipped package (force an overwrite if it already exists)
        $zip = new ZipArchive();
        $zip->open($this->sac_root_out . '/' . $this->sac_file_out, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);
        $zip->addFile($this->sac_root_in . '/' . $this->sac_dir_in . '/mets.xml', 
                     'mets.xml');
        for ($i = 0; $i < $this->sac_filecount; $i++) {
            $zip->addFile($this->sac_root_in . '/' . $this->sac_dir_in . '/' . $this->sac_files[$i], 
                          $this->sac_files[$i]);
        }
        $zip->close();
    }

    function writeheader($fh) {
        fwrite($fh, "<?xml version=\"1.0\" encoding=\"utf-8\" standalone=\"no\" ?" . ">\n");
        fwrite($fh, "<mets ID=\"sort-mets_mets\" OBJID=\"sword-mets\" LABEL=\"DSpace SWORD Item\" PROFILE=\"DSpace METS SIP Profile 1.0\" xmlns=\"http://www.loc.gov/METS/\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://www.loc.gov/METS/ http://www.loc.gov/standards/mets/mets.xsd\">\n");
        fwrite($fh, "\t<metsHdr CREATEDATE=\"2008-09-04T00:00:00\">\n");
        fwrite($fh, "\t\t<agent ROLE=\"CUSTODIAN\" TYPE=\"ORGANIZATION\">\n");
        if (isset($this->sac_custodian)) { fwrite($fh, "\t\t\t<name>$this->sac_custodian</name>\n"); }
        else { fwrite($fh, "\t\t\t<name>Unknown</name>\n"); }
        fwrite($fh, "\t\t</agent>\n");
        fwrite($fh, "\t</metsHdr>\n");
    }

    function writeDmdSec($fh) {
        fwrite($fh, "<dmdSec ID=\"sword-mets-dmd-1\" GROUPID=\"sword-mets-dmd-1_group-1\">\n");
        fwrite($fh, "<mdWrap LABEL=\"SWAP Metadata\" MDTYPE=\"OTHER\" OTHERMDTYPE=\"EPDCX\" MIMETYPE=\"text/xml\">\n");
        fwrite($fh, "<xmlData>\n");
        fwrite($fh, "<epdcx:descriptionSet xmlns:epdcx=\"http://purl.org/eprint/epdcx/2006-11-16/\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://purl.org/eprint/epdcx/2006-11-16/ http://purl.org/eprint/epdcx/xsd/2006-11-16/epdcx.xsd\">\n");
        fwrite($fh, "<epdcx:description epdcx:resourceId=\"sword-mets-epdcx-1\">\n");

        if (isset($this->sac_type)) {
            $this->statementVesURIValueURI($fh, 
                                           "http://purl.org/dc/elements/1.1/type",
                                           "http://purl.org/eprint/terms/Type",
                                           $this->sac_type);
        }

        if (isset($this->sac_title)) {
            $this->statement($fh, 
                             "http://purl.org/dc/elements/1.1/title", 
                             $this->valueString($this->sac_title));
        }

        if (isset($this->sac_abstract)) {
            $this->statement($fh, 
                             "http://purl.org/dc/terms/abstract",
                             $this->valueString($this->sac_abstract));
        }

        foreach ($this->sac_creators as $sac_creator) {
            $this->statement($fh,
                             "http://purl.org/dc/elements/1.1/creator",
                             $this->valueString($sac_creator));
        }

        foreach ($this->sac_subjects as $sac_subject) {
            $this->statement($fh,
                             "http://purl.org/dc/elements/1.1/subject",
                             $this->valueString($sac_subject));
        }

        foreach ($this->sac_provenances as $sac_provenance) {
            $this->statement($fh,
                             "http://purl.org/dc/terms/provenance",
                             $this->valueString($sac_provenance));
        }

        foreach ($this->sac_rights as $sac_right) {
            $this->statement($fh,
                             "http://purl.org/dc/terms/rights",
                             $this->valueString($sac_right));
        }

        if (isset($this->sac_identifier)) {
            $this->statement($fh,
                             "http://purl.org/dc/elements/1.1/identifier", 
                             $this->valueString($this->sac_identifier));
        }

        if (isset($this->sac_publisher)) {
            $this->statement($fh,
                             "http://purl.org/dc/elements/1.1/publisher",
                             $this->valueString($this->sac_publisher));
        }

        fwrite($fh, "<epdcx:statement epdcx:propertyURI=\"http://purl.org/eprint/terms/isExpressedAs\" " .
                    "epdcx:valueRef=\"sword-mets-expr-1\" />\n");

        fwrite($fh, "</epdcx:description>\n");
        
        fwrite($fh, "<epdcx:description epdcx:resourceId=\"sword-mets-expr-1\">\n");
        
        $this->statementValueURI($fh, 
                                 "http://purl.org/dc/elements/1.1/type", 
                                 "http://purl.org/eprint/entityType/Expression");
        
        if (isset($this->sac_language)) {
	    $this->statementVesURI($fh, 
                               "http://purl.org/dc/elements/1.1/language",
                               "http://purl.org/dc/terms/RFC3066",
                                $this->valueString($this->sac_language));
    	}
        
        $this->statementVesURIValueURI($fh, 
                                       "http://purl.org/dc/elements/1.1/type",
                                       "http://purl.org/eprint/terms/Type",
                                       "http://purl.org/eprint/entityType/Expression");
    
        if (isset($this->sac_dateavailable)) {
            $this->statement($fh, 
                             "http://purl.org/dc/terms/available",
                             $this->valueStringSesURI("http://purl.org/dc/terms/W3CDTF",
                             $this->sac_dateavailable));
        }

        if (isset($this->sac_statusstatement)) {
            $this->statementVesURIValueURI($fh, 
                                           "http://purl.org/eprint/terms/Status",
                                           "http://purl.org/eprint/terms/Status",
                                           $this->sac_statusstatement);
        }

        if (isset($this->sac_copyrightholder)) {
            $this->statement($fh, 
                             "http://purl.org/eprint/terms/copyrightHolder", 
                             $this->valueString($this->sac_copyrightholder));
        }

        if (isset($this->sac_citation)) {
            $this->statement($fh, 
                             "http://purl.org/eprint/terms/bibliographicCitation", 
                             $this->valueString($this->sac_citation));
        }

        fwrite($fh, "</epdcx:description>\n");
        
        fwrite($fh, "</epdcx:descriptionSet>\n");
        fwrite($fh, "</xmlData>\n");
        fwrite($fh, "</mdWrap>\n");
        fwrite($fh, "</dmdSec>\n");
    }

    function writeFileGrp($fh) {
        fwrite($fh, "\t<fileSec>\n");
        fwrite($fh, "\t\t<fileGrp ID=\"sword-mets-fgrp-1\" USE=\"CONTENT\">\n");
        for ($i = 0; $i < $this->sac_filecount; $i++) {
            fwrite($fh, "\t\t\t<file GROUPID=\"sword-mets-fgid-0\" ID=\"sword-mets-file-" . $i ."\" " .
                        "MIMETYPE=\"" . $this->sac_mimetypes[$i] . "\">\n");
            fwrite($fh, "\t\t\t\t<FLocat LOCTYPE=\"URL\" xlink:href=\"" . $this->clean($this->sac_files[$i]) . "\" />\n");
            fwrite($fh, "\t\t\t</file>\n");
        }
        fwrite($fh, "\t\t</fileGrp>\n");
        fwrite($fh, "\t</fileSec>\n");
    }

    function writeStructMap($fh) {
        fwrite($fh, "\t<structMap ID=\"sword-mets-struct-1\" LABEL=\"structure\" TYPE=\"LOGICAL\">\n");
        fwrite($fh, "\t\t<div ID=\"sword-mets-div-1\" DMDID=\"sword-mets-dmd-1\" TYPE=\"SWORD Object\">\n");
        fwrite($fh, "\t\t\t<div ID=\"sword-mets-div-2\" TYPE=\"File\">\n");
        for ($i = 0; $i < $this->sac_filecount; $i++) {
            fwrite($fh, "\t\t\t\t<fptr FILEID=\"sword-mets-file-" . $i . "\" />\n");
        }
        fwrite($fh, "\t\t\t</div>\n");
        fwrite($fh, "\t\t</div>\n");
        fwrite($fh, "\t</structMap>\n");
    }

    function writeFooter($fh) {
        fwrite($fh, "</mets>\n");
    }

    function valueString($value) {
        return "<epdcx:valueString>" .
               $value . 
               "</epdcx:valueString>\n";
    }

    function valueStringSesURI($sesURI, $value) {
        return "<epdcx:valueString epdcx:sesURI=\"" . $sesURI . "\">" .
               $value . 
               "</epdcx:valueString>\n";
    }

    function statement($fh, $propertyURI, $value) {
        fwrite($fh, "<epdcx:statement epdcx:propertyURI=\"" . $propertyURI . "\">\n" .
               $value .
               "</epdcx:statement>\n");
    }

    function statementValueURI($fh, $propertyURI, $value) {
        fwrite($fh, "<epdcx:statement epdcx:propertyURI=\"" . $propertyURI . "\" " .
               "epdcx:valueURI=\"" . $value . "\" />\n");
    }

    function statementVesURI($fh, $propertyURI, $vesURI, $value) {
        fwrite($fh, "<epdcx:statement epdcx:propertyURI=\"" . $propertyURI . "\" " .
               "epdcx:vesURI=\"" . $vesURI . "\">\n" .
               $value . 
               "</epdcx:statement>\n");
    }
    
    function statementVesURIValueURI($fh, $propertyURI, $vesURI, $value) {
        fwrite($fh, "<epdcx:statement epdcx:propertyURI=\"" . $propertyURI . "\" " .
               "epdcx:vesURI=\"" . $vesURI . "\" " .
               "epdcx:valueURI=\"" . $value . "\" />\n");
    }

    function clean($data) {
        return str_replace('&#039;', '&apos;', htmlspecialchars($data, ENT_QUOTES));
    }
}
?>
