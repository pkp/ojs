<?php

require_once('utils.php');

class PackagerAtomMultipart {

    // The location of the files (without final directory)
    private $sac_root_in;

    // The directory to zip up in the $sac_root_in directory
    private $sac_dir_in;

    // The location to write the package out to
    private $sac_root_out;

    // The filename to save the package as
    private $sac_file_out;

    // File names
    private $sac_files;

    // Number of files added
    private $sac_filecount;

    // The dcterms metadata
    private $sac_entry_dctermsFields;
    private $sac_entry_dctermsValues;
    private $sac_entry_dctermsAttributes;

    // The entry title
    private $sac_entry_title;

    // The entry id
    private $sac_entry_id;

    // The entry updated date / time stamp
    private $sac_entry_updated;

    // The entry author names
    private $sac_entry_authors;

    // The entry summary text
    private $sac_entry_summary;


    function __construct($sac_rootin, $sac_dirin, $sac_rootout, $sac_fileout) {
        // Store the values
        $this->sac_root_in = $sac_rootin;
        $this->sac_dir_in = $sac_dirin;
        $this->sac_root_out = $sac_rootout;
        $this->sac_file_out = $sac_fileout;

        $this->sac_files = array();
        $this->sac_mimetypes = array();
        $this->sac_filecount = 0;

        $this->sac_entry_dctermsFields = array();
        $this->sac_entry_dctermsValues = array();
        $this->sac_entry_dctermsAttributes = array();

        $this->sac_entry_authors = array();
    }

    function setTitle($sac_thetitle) {
        $this->sac_entry_title = $this->clean($sac_thetitle);
    }

    function setIdentifier($sac_theID) {
        $this->sac_entry_id = $this->clean($sac_theID);
    }

    function setUpdated($sac_theUpdated) {
        $this->sac_entry_updated = $this->clean($sac_theUpdated);
    }

    function addEntryAuthor($sac_theauthor) {
        array_push($this->sac_entry_authors, $this->clean($sac_theauthor));
    }

    function setSummary($sac_theSummary) {
        $this->sac_entry_summary = $this->clean($sac_theSummary);
    }

    function addMetadata($sac_theElement, $sac_theValue, $sac_theAttributes = array()) {
        array_push($this->sac_entry_dctermsFields, $this->clean($sac_theElement));
        array_push($this->sac_entry_dctermsValues, $this->clean($sac_theValue));
        $sac_cleanAttributes = array();
        foreach ($sac_theAttributes as $attrName => $attrValue) {
          $sac_cleanAttributes[$this->clean($attrName)] = $this->clean($attrValue);
        }
        array_push($this->sac_entry_dctermsAttributes, $sac_cleanAttributes);        
    }

    function addFile($sac_thefile) {
        array_push($this->sac_files, $sac_thefile);
        $this->sac_filecount++;
    }

    function create() {
        // Write the atom entry manifest
        $sac_atom = $this->sac_root_in . '/' . $this->sac_dir_in . '/atom';
        $fh = @fopen($sac_atom, 'w');
        if (!$fh) {
            throw new Exception("Error writing atom entry manifest (" .
                $this->sac_root_in . '/' . $this->sac_dir_in . '/atom)');
        }

        // Write the atom entry header
        fwrite($fh, "<?xml version=\"1.0\"?>\n");
        fwrite($fh, "<entry xmlns=\"http://www.w3.org/2005/Atom\" xmlns:dcterms=\"http://purl.org/dc/terms/\">\n");
        if (!empty($this->sac_entry_title)) fwrite($fh, "\t<title>" . $this->sac_entry_title . "</title>\n");
        if (!empty($this->sac_entry_id)) fwrite($fh, "\t<id>" . $this->sac_entry_id . "</id>\n");
        if (!empty($this->sac_entry_updated)) fwrite($fh, "\t<updated>" . $this->sac_entry_updated . "</updated>\n");
        foreach ($this->sac_entry_authors as $sac_author) {
            fwrite($fh, "\t<author><name>" . $sac_author . "</name></author>\n");
        }
        if (!empty($this->sac_entry_summary)) fwrite($fh, "\t<summary>" . $this->sac_entry_summary . "</summary>\n");

        // Write the dcterms metadata
        for ($i = 0; $i < count($this->sac_entry_dctermsFields); $i++) {
            $dcElement = "\t<dcterms:" . $this->sac_entry_dctermsFields[$i];
            if (!empty($this->sac_entry_dctermsAttributes[$i])) {
              foreach ($this->sac_entry_dctermsAttributes[$i] as $attrName => $attrValue) {
                $dcElement .= " $attrName=\"$attrValue\"";
              }
            }
            $dcElement .= ">" . $this->sac_entry_dctermsValues[$i] . "</dcterms:" . $this->sac_entry_dctermsFields[$i] . ">\n";
            fwrite($fh, $dcElement);
        }

        // Close the file
        fwrite($fh, "</entry>\n");
        fclose($fh);

        // Create the multipart package
        $temp = $this->sac_root_out . '/' . $this->sac_file_out;
        $atom = file_get_contents($sac_atom);
        $xml = "\r\nMedia Post\r\n";
        $xml .= "--===============SWORDPARTS==\r\n";
        $xml .= "Content-Type: application/atom+xml\r\n";
        $xml .= "MIME-Version: 1.0\r\n";
        $xml .= "Content-Disposition: attachment; name=\"atom\"\r\n";
        $xml .= "\r\n";
        $xml .= $atom;
        unset($atom);
        file_put_contents($temp, $xml);

        // Add the files
        for ($i = 0; $i < $this->sac_filecount; $i++) {
            $xml = "";
            $sac_filename = $this->sac_files[$i];
            $sac_fullfilename = $this->sac_root_in . '/' . $this->sac_dir_in . '/' . $sac_filename;

            $xml .= "--===============SWORDPARTS==\r\n";
            $xml .= "Content-Type: " . mime_content_type($sac_fullfilename) . "\r\n";
            $xml .= "Content-MD5: " . md5_file($sac_fullfilename) . "\r\n";
            $xml .= "MIME-Version: 1.0\r\n";
            $xml .= "Content-Disposition: attachment; name=\"payload\"; filename=\"" . $sac_filename . "\"\r\n";
            $xml .= "Packaging: http://purl.org/net/sword/package/Binary\r\n";
            $xml .= "Content-Transfer-Encoding: base64\r\n\r\n";
            file_put_contents($temp, $xml, FILE_APPEND);
            base64chunk($sac_fullfilename, $temp);
        }

        $xml = "--===============SWORDPARTS==--\r\n";
        file_put_contents($temp, $xml, FILE_APPEND);
    }

    function clean($data) {
        return str_replace('&#039;', '&apos;', htmlspecialchars($data, ENT_QUOTES));
    }
}
?>
