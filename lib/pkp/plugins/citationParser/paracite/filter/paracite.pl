#
# paracite.pl
#
# Copyright (c) 2015-2017 Simon Fraser University
# Copyright (c) 2008-2009 MJ Suhonos
# Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
#
# Wrapper script to use the ParaCite parser within PHP
#

use HTML::Entities;
use Text::Unidecode;
use URI;
use Biblio::Citation::Parser 1.10;

	# first parameter is the parser to use
	$parser_type = $ARGV[0];

	if ($parser_type eq 'Jiao') {
		use Biblio::Citation::Parser::Jiao;
		$parser = new Biblio::Citation::Parser::Jiao;

	} elsif ($parser_type eq 'Citebase') {
		use Biblio::Citation::Parser::Citebase;
		$parser = new Biblio::Citation::Parser::Citebase;

	} else {
		use Biblio::Citation::Parser::Standard;
		$parser = new Biblio::Citation::Parser::Standard;
	}

	# second parameter is the text to parse
	$ref = $ARGV[1];

	# parse the citation and output the marked result
	$metadata = $parser->parse($ref);

	# create a root node
	print "<element-citation>\n";

	# loop through all keys in the array and generate XML
	foreach my $key (keys %$metadata) {
		print "<$key>";

		# if the key is the author list, go down the heirarchy
		if ($key eq 'authors' && ref $metadata->{$key} eq 'ARRAY') {
			$author_array = $metadata->{$key};

			# loop through the list of authors
			# serialize to be consistent with Citebase parser
			foreach $author (@$author_array) {
				print encode_entities($author->{given} . "." . $author->{family} . ":");
			}
		} else {
			# HTML encode the tag value
			print encode_entities($metadata->{$key});
		}

		print "</$key>\n";
	}

	# close the root node
	print "</element-citation>\n";
