#!/bin/bash

for d in ./guide/*.php; do
    php src/generate-guide.php $d > pages/guide/$(basename $d .php).mdx
done

mkdir pages/reference/Metadata
for d in ../src/Metadata/*.php; do
    php src/generate-reference.php $d > pages/reference/Metadata/$(basename $d .php).mdx
done

php src/generate-sidebar.php > ./pages/sidebar.mdx
