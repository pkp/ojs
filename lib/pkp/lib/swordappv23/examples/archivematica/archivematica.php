<?php

require_once('../../swordappclient.php');

// Settings
$atom = 'atom.xml';
$contentzip = 'content.zip';
$metadatazip = 'metadata.zip';
$servicedocument = 'http://demo.dspace.org/swordv2/servicedocument';
$depositlocation = 'http://demo.dspace.org/swordv2/collection/10673/11';
$dspacerest = 'https://demo.dspace.org/rest';
$user = 'dspacedemo+admin@gmail.com';
$password = 'dspace';

// Initiatiate the SWORD client
$sword = new SWORDAPPClient();

// Get the service document
print "About to request servicedocument from " . $servicedocument . "\n";
$sd = $sword->servicedocument($servicedocument, $user, $password, '');
print "Received HTTP status code: " . $sd->sac_status . " (" . $sd->sac_statusmessage . ")\n";
if ($sd->sac_status == 200) {
    $sd->toString();
}
print "\n\n";

// Create the item by depositing an atom document
print "About to create new item at " . $depositlocation . "\n";
$response = $sword->depositAtomEntry($depositlocation, $user, $password, '', $atom, $sac_inprogress = true);
print "Received HTTP status code: " . $response->sac_status . " (" . $response->sac_statusmessage . ")\n";
if (($response->sac_status >= 200) || ($response->sac_status < 300)) {
    $response->toString();
}
$edit_iri = $response->sac_edit_iri;
$edit_media = $response->sac_edit_media_iri;
$statement_atom = $response->sac_state_iri_atom;
print "Edit IRI: " . $edit_iri . "\n";
print "Edit Media: " . $edit_media . "\n";
print "Statement: " . $statement_atom;
print "\n\n";

// Add the DIP content
print "About to add file (" . $contentzip . ") to " . $edit_media . "\n";
$response = $sword->addExtraFileToMediaResource($edit_media, $user, $password, '', $contentzip, "application/zip", false);
print "Received HTTP status code: " . $response->sac_status . " (" . $response->sac_statusmessage . ")\n";
print "\n\n";

// Complete the deposit
print "About to complete the deposit at " . $edit_iri . "\n";
$response = $sword->completeIncompleteDeposit($edit_iri, $user, $password, '');
print "Received HTTP status code: " . $response->sac_status . " (" . $response->sac_statusmessage . ")\n";

// Fetch the statement
print "About to request Atom serialisation of the deposit statement from " . $statement_atom . "\n";
$atomstatement = @$sword->retrieveAtomStatement($statement_atom, $user, $password, '');
if (($atomstatement->sac_status >= 200) || ($atomstatement->sac_status < 300)) {
    $atomstatement->toString();
}
print "\n\n";
$handle = $atomstatement->sac_entries[1]->sac_content_source[0];
$handle = substr($handle, strpos($handle, 'bitstream/') + 10);
$handle = substr($handle, 0, strpos($handle, '/', strpos($handle, '/') + 1));
print "Handle of new object is: " . $handle;
print "\n\n";


// Login to DSpace REST interface
$data = array("email" => $user, "password" => $password);
$data_string = json_encode($data);
// https://groups.google.com/forum/m/#!topic/dspace-tech/s7SQGhgDUPI
$url = $dspacerest . '/login';
$curl = curl_init($url);
curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data_string))
);
$token = curl_exec($curl);
print 'DSpace login token is: ' . $token . "\n\n";

// Get the item ID for the item we created
$url = $dspacerest . '/handle/' . $handle;
$curl = curl_init($url);
$headers = array();
array_push($headers, "rest-dspace-token: " . $token);
array_push($headers, "Content-Type: application/json");
array_push($headers, "Accept: application/json");
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
//curl_setopt($curl, CURLOPT_VERBOSE, true);
curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
$response = curl_exec($curl);
print 'DSpace item ID: ' . $response . "\n\n";

?>