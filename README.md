# Linked data for ORCID

Fetch ORCID JSON-LD and convert to triples.

Lots of minor problems with ORCID JSON-LD that need to be dealt with, such as URLs that aren’t really URLs (contain spaces, bad characters, etc.). Also need to ensure URLs don’t contain characters such as `<>[]`.

Note also that I generate globally unique blank node ids so that we can split the triples into chunks and still upload without causing issues.

Fetching mode is to grab JSON-LD, cache it, convert each file to triples and store in same cache, then to upload we can retrieve each triples file and upload that (great for testing).

For distribution we would concatenate all triples into one big file and distribute that.

## Problems

### Specific ORCIDs

0000-0002-0633-5974 is 2.8 mb in size and breaks `triples.php`. Need to increase memory:

```
php -d memory_limit=-1 triples.php
```

### URLs that aren’t URLs

See for example https://github.com/ORCID/ORCID-Source/issues/6542

### GRID ids are not URIs

See https://github.com/ORCID/ORCID-Source/issues/6519 ORCID uses GRID as `@id` but doesn’t render them as URIs, so triples break, e.g.

```
"affiliation" : [ {
    "@type" : "Organization",
    "@id" : "grid.1214.6",
    "name" : "Smithsonian Institution"
  }
```

### RORs 
ORCID encodes ROR ids as URLs but in a `PropertyValue`, whereas I think the URL should be used as `@id` and the slug after the `https://ror.org/` should be used as the `value`.

```
{
            "@type": "Organization",
            "name": "ORCID",
            "alternateName": "Product",
            "identifier": {
                "@type": "PropertyValue",
                "propertyID": "ROR",
                "value": "https://ror.org/04fa4r544"
            }
        },
```

See https://github.com/ORCID/ORCID-Source/issues/6520 for further discussion.


