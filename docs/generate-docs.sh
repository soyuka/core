#!/bin/bash

for d in ./guide/*.php; do
    name=$(basename $d .php)
    # name=$(echo "$name" | sed -E 's/^[0-9]+\-//g')
    php src/generate-guide.php "$d" > "pages/guide/$name.mdx"
done

mkdir pages/reference/Metadata
for d in ../src/Metadata/*.php; do
    echo "test";
    php src/generate-reference.php $d > pages/reference/Metadata/$(basename $d .php).mdx
done

mkdir -p pages/reference/Symfony/Bundle/DependencyInjection
php src/generate-reference.php ../src/Symfony/Bundle/DependencyInjection/Configuration.php > pages/reference/Symfony/Bundle/DependencyInjection/Configuration.mdx

php src/generate-sidebar.php > ./pages/sidebar.mdx

for d in ./pages/guide/*.mdx; do
    name=$(basename $d .mdx)
    name=$(echo "$name" | sed -E 's/^[0-9]+\-//g')
    name=$(echo "$name" | awk '{print tolower($0)}')
    mv "$d" "pages/guide/$name.mdx"
done

