#!/usr/bin/php
<?php
function convertFileToPdf($fileId) {
	$libreOffice = '/apps/subi/sw/libreoffice/program/soffice';
	$sourceFile = '/apps/subi/ojs/files/journals/41/articles/12101/submission/review/12101-44882-2-RV.doc';
	$outDir = '/apps/subi/ojs/tmp/';
	$convertedFile = $outDir . "12101-44882-2-RV.pdf";

	if(file_exists($convertedFile)) {
		$rmExistingCmd = "rm $convertedFile";
		exec($rmExistingCmd);
	}

	$convertCmd = "$libreOffice --headless --convert-to pdf -outdir $outDir $sourceFile";
	echo "convertCmd: $convertCmd\n";
	$last_line = system($convertCmd, $return_var);
	echo "return_var: $return_var\n";
	echo "last_line: $last_line\n";
	//LibreOffice doesn't provide useful return codes, so we have to check for the PDF file and assume
	if(file_exists($convertedFile)) {
		return 0;	
	} else {
		return 1;
	}
}

$converted = convertFileToPdf(44822);
echo "converted: $converted\n";
?>
