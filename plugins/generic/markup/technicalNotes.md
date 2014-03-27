Document Markup Server Notes
--------------------------------------
Copyright (c) 2003-2013 John Willinsky
Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.

This technical_notes.md file contains a description of the data protocol/process that the OJS Document Markup Plugin (markup plugin) uses to communicate with the Document Markup Server (markup server).  All settings required for the plugin / server functionality are managed on the plugin settings page.

The Document Markup Server, provided by the PKP Group (http://pkp.sfu.ca) provides a service for converting pdf, doc, docx or odt documents into XML and HTML.  It includes a citation parsing engine.  The description of the interation below will focus on the markup plugin interaction; there is a similar process for conversions done through the Markup Server website's html form.

The markup server service has very little required input - just a file name and a citation style language name for input; the markup plugin  also sends additional meta info from OJS records related to the article to be converted.  The data sent (to the markup server's process.php script) is structured in a curl HTTP POST as JSON data.

The markup plugin first requests a conversion using _refresh().  If the file upload is a success and is of the right type, the markup server generates a randomly named 32 character long alphanumeric folder which contains the converted documents and bibliography information.  This folder name is known as the "jobId".  The job folder and files are available for 24 hours although the markup plugin fetches the results immediately.

1) CURL POST FIELD INPUT:

	userfile	// Form field file attachment
	jit_events	// Form field containing JSON string
	
Here is a minimal pseudo example of the "jit_events" field JSON content:
		[{
			"type":"PDFX.fileUpload",
			"data":{
				"cslStyle":"chicago-author-date.csl",
			}
		}]

A full spec of the data parameter shows the OJS article information that the markup plugin typically sends for use in the final converted documents:

	"data":{
		"cslStyle": (string), //eg. "chicago-author-date.csl"
		"cssURL": (string), //eg. "http://domain/folder.../" , URL to css files for html
		"reviewVersion": (boolean), //eg. "true", triggers creation of document-review.pdf
		"articleId": (integer), // OJS articleId
		"cssHeaderImageURL": (string), //eg. "http://domain/folder/image.{png|jpg}"

		"title": (string) // Article title,
		"authors": (array) // of author info
		"journalId": (integer),
		"publicationName": (string) , //Title of journal
		"copyright" : (string), //Copyright text message
		"publisher" : (string), // Name of publisher
		"eISSN" : (string), // electronic ISSN
		"ISSN" : (string), // http://www.issn.org/
		"DOI" : (string), // http://dx.doi.org,
		
		"number" : (string), // Publication issue, if any
		"volume" : (string), // ditto
		"year" : (integer), // ditto
		"datePublished" : (datetime) // ditto
	}
		
		
2) SERVER SIDE RESPONSE

The Markup server response is handled by a php function called jit_PDFX_fileUpload(). It processes the file uploaded by Apache.  Response includes the above event type and data if any, as well as a newly generated "jobId" which maps to a publicly accessible folder where document conversion files exist.

The job folder now contains various files, with the key one being a zip of all the pdf,xml and html files in the job folder

	document.zip

The server responds with HTTP content consisting of a JSON string:

{jit_events:[{
		"type" : "PDFX.fileUpload",
		"data" : {
			"cslStyle":"chicago-author-date.csl"},	// eg. 
			"jobId" : "f74f3867fe944bdefc7367567994c9de",
			"links" : ["document.doc","document.pdf"]	// main doc products
			... and a reiteration of the data fields sent in by the OJS markup plugin request.	
		}
		"error: : 0 // Error code if one occurs
		"message" : "File Uploaded" // File uploaded message by default, or error message if error occured.
	}]

3) PLUGIN FETCH OF DOCUMENT.ZIP

If all goes well, the plugin fetches document.zip and incorporates desired files into an article's supplementary file folder and galleys using the _refresh() code:

	$articleFileManager->copySuppFile($archiveURL, 'application/zip');


SOME TECHNICAL NOTES ON JSON REQUEST FORMAT
	
On the markup server each incoming request is renamed an "event".  Events have associated data, events can succeed, or end badly.  Events happen in a particular sequence.

The data format of incoming requests is as follows.  "jit_events" is either a POST form field or a GET url parameter that contains 1 or more requests.
	
	jit_events=[
		{request 1},
		{request 2}, 
		etc.
	];

At the very least, each request event is formatted as (expressed in JSON):
	
	{type:"myRequestType"}
	
This would trigger (if allowed) a function call to jit_myRequestType() on the server.  On the markup server we access a function called "PDFX.fileUpload" to do our document conversion.

Other optional parameters that an event can have are:

jit_events=[{
		type: "myEventType",
		data: { attribute1: value1, //value can be a string, integer, object or array.
				attribute2: value2, 
				etc.
		}
		domID:	//HTML id of dom element that triggered this request if desired. This info is returned
				//back to browser to allow it to change state of the element for example.
	}]
	
On processing a particular event, the server function call may add or modify the event's data parameter.  The event is then sent back to the browser with the modified data.  In some cases if needed, we drop data from the round trip, e.g. passwords, or data that was only needed for submission. 
	
On encountering a php or 3rd party error while processing a particular event, the programmer may add these fields to the event:

	{	error: //numeric error code, see function jit_process_error in utilities.php
		message: //textual version of error
	}

	
Potentially requests can be sent as GET requests, though normally POST requests are done.  GET requests are handy for testing as they can be done in a browser address bar.  For example, type 
	
	[markup server url]/process.php
	
and one will receive a JSON jit_events array back:

{"jit_events":[{"type":"JSONError","error":10,"message":"No HTML POST \"jit_events\" parameter provided"}]}
		
	
