# Lined data for ORCID

Fetch ORCID JSON-LD and convert to triples.

Lots of minor problems with ORCID JSON-LD that need to be dealt with, such as URLs that aren’t really URLs (contain spaces, bad characters, etc.). Also need to ensure URLs don’t contain characters such as `<>[]`.

Note also that I generate globally unique bnode ids so that we can split the triples into chunks and still upload without causing issues.

Fetching mode is to grab JSON-LD, cache it, convert each file to triples and store in same cache, then to upload we can retrieve each tripes file and upload that (great for testing).

For distribution we would concatenate all triples into one big file and distribute that.

## Issues

0000-0002-0633-5974 is 2.8 mb in size and breaks `triples.php`. Need to increase memory:

```
php -d memory_limit=-1 triples.php
```

## ORCID errors

### grid ids not URIs

See https://github.com/ORCID/ORCID-Source/issues/6519 ORCID uses grid as `@id` but doesn’t render themas URIs, so triples break, e.g.

```
"affiliation" : [ {
    "@type" : "Organization",
    "@id" : "grid.1214.6",
    "name" : "Smithsonian Institution"
  }
```

