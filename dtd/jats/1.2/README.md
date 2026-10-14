# JATS 1.2 Journal Publishing DTD

Local copy of the ANSI/NISO JATS (Z39.96-2019) **Journal Publishing** DTD suite,
version 1.2 (20190208), so that JATS output can be validated without network access.

- Source: <https://ftp.ncbi.nlm.nih.gov/pub/jats/publishing/1.2/JATS-Publishing-1-2-MathML2-DTD.zip>
- Documentation: <https://jats.nlm.nih.gov/publishing/1.2/>
- Public identifier: `-//NLM//DTD JATS (Z39.96) Journal Publishing DTD v1.2 20190208//EN`
- System identifier: `http://jats.nlm.nih.gov/publishing/1.2/JATS-journalpublishing1.dtd`

This is the MathML 2 / XHTML-table flavour, which is the one the above public and
system identifiers name — the same identifiers emitted by the jatsTemplate plugin.
The MathML 3 and OASIS-table flavours are published as separate packages at the same
FTP location; every file they share with this one is byte-identical.

The DTD suite is in the public domain (see the notice in `JATS-journalpublishing1.dtd`).

## Layout

The package is unpacked verbatim. `JATS-journalpublishing1.dtd` is the driver; it pulls
in the `JATS-*.ent` modules beside it and the character-entity and MathML modules under
`iso8879/`, `iso9573-13/`, `mathml/` and `xmlchars/`, all by relative path.

`BITS-embedded-index2.ent` belongs here despite the name: JATS 1.2 borrows the inline
index-term model from BITS, and the driver invokes it unconditionally, so removing the
file breaks the DTD. It declares only `index-term`, `index-term-range-end`, `see` and
`see-also` — no book structure. Nothing here can validate BITS (book) content, since
`book` and `book-part-wrapper` are declared in no file in this directory; BITS is a
separate package under <https://public.nlm.nih.gov/projects/jats/extensions/bits/>.

## Validating against this copy

Either point the document type's system identifier at
`dtd/jats/1.2/JATS-journalpublishing1.dtd`, or keep the canonical identifiers in the
document and let the bundled OASIS catalog resolve them:

```
XML_CATALOG_FILES=dtd/jats/1.2/catalog-jats-v1-2-no-base.xml
```

Validate with `LIBXML_NONET` to be sure nothing is being fetched over the network.

## Updating

Download the package from the FTP location above and unpack its contents into this
directory, replacing what is here. A new JATS version belongs in a sibling directory
(`dtd/jats/1.3`, …) rather than on top of this one.
