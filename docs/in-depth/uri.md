## URI

URIs are at the heart of the API Platform framework. By default, we recommend the use of the [JSON-LD](https://json-ld.org/) (JSON for Linking Data) format. A JSON-LD document uses fields starting with an `@` character to enhance a document and provide linking and auto-discovery mechanisms:

```
{
    "@context": "https://json-ld.org/contexts/person.jsonld",
    "@id": "http://dbpedia.org/resource/John_Lennon",
    "name": "John Lennon",
    "born": "1940-10-09",
    "spouse": "http://dbpedia.org/resource/Cynthia_Lennon"
}
```

[IRIs](https://www.rfc-editor.org/rfc/rfc3987) used in the `@id` field, are at the [heart of the specification](https://www.w3.org/TR/json-ld/#iris). As an IRI is also an URI, we use the later term as it has a wider scope. 

### URI Template

https://www.rfc-editor.org/rfc/rfc6570.html

### URI Variables

Explain linking