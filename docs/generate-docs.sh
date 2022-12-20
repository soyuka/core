#!/bin/bash

for d in ./guide/*.php; do
    php src/generate-guide.php $d > pages/guide/$(basename $d .php).mdx
done

php src/generate-references.php

php src/generate-sidebar.php > ./pages/sidebar.mdx
