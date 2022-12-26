#!/bin/bash

for d in ./guide/*.php; do
    name=$(basename $d .php)
    # name=$(echo "$name" | sed -E 's/^[0-9]+\-//g')
    php src/generate-guide.php "$d" > "pages/guide/$name.mdx"
done

php src/generate-references.php

php src/generate-sidebar.php > ./pages/sidebar.mdx

for d in ./pages/guide/*.mdx; do
    name=$(basename $d .mdx)
    name=$(echo "$name" | sed -E 's/^[0-9]+\-//g')
    name=$(echo "$name" | awk '{print tolower($0)}')
    mv "$d" "pages/guide/$name.mdx"
done

