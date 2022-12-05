#!/bin/bash
echo Generating sidebar. 1>&2

echo "# Guides"
cd ./pages/guides

tree -f -J | jq -r '.[] | 
def name(f):
    f | gsub("[0-9]+-"; "") | sub("./"; "") | sub(".mdx"; "") | sub("\/"; "\\");

def number(f):
    f | capture("(?<a>[0-9]+)-") | .a;

    recurse(.contents[]? // empty) |
    .name? // empty | 
    select(. != ".") | 
        (. | split("\/") | keys | map(. | select(. > 1)) | map("  ") | join(""))
            + number(.) + ". [" + name(.) + "](/guides/" + (. | sub("./"; "") | sub(".mdx"; "")) +")"
        
'

cd ../..

echo "# Reference"
cd ./pages/reference
tree -f -J | jq -r '
def spaces(f):
    .name | split("\/") | keys | map(. | select(. > 1)) | map("  ") | join("");

def name(f): 
        f | 
        .name? // empty |
        select(. != ".") | 
        "\\ApiPlatform\\" + sub("./"; "") | sub(".mdx"; "") | sub("\/"; "\\");

def className(f):
    f.name | sub(".mdx"; "") | capture("(?<a>\\w+)$") | .a;

def link(f): 
    "["+className(f)+"](/reference/"+(f | .name | sub("./"; "") | sub(".mdx"; ""))+")";

.[] | 
    recurse(.contents[]? // empty) |
    select(.name != null) |
    (select(.type == "directory") | spaces(.) + "- " + name(.))
    //
    (select(.type == "file") | spaces(.) + "- " + link(.))
'
