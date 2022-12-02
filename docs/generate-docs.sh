#!/bin/bash

pdg=$(composer -n config --global home)/vendor/bin/pdg

for d in ./guide/*.php; do
    name=$(basename $d .php)
    $pdg guide "$d" > "pages/guide/$name.mdx"
done
#
# pdg refences
#
# php src/generate-sidebar.php > ./pages/sidebar.mdx

php src/generate-references.php

mkdir -p pages/reference/Symfony/Bundle/DependencyInjection
php src/generate-reference.php ../src/Symfony/Bundle/DependencyInjection/Configuration.php > pages/reference/Symfony/Bundle/DependencyInjection/Configuration.mdx

php src/generate-sidebar.php > ./pages/sidebar.mdx

for d in ./pages/guide/*.mdx; do
    name=$(basename $d .mdx)
    name=$(echo "$name" | sed -E 's/^[0-9]+\-//g')
    name=$(echo "$name" | awk '{print tolower($0)}')
    mv "$d" "pages/guide/$name.mdx"
done

cp -r core create-client deployment distribution extra schema-generator pages/
