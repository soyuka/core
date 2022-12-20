#!/bin/bash

for d in ./guide/*.php; do
    name=$(basename $d .php)
    # name=$(echo "$name" | sed -E 's/^[0-9]+\-//g')
    php src/generate-guide.php "$d" > "pages/guide/$name.mdx"
done

find ../src -type d -exec bash -c '
    for pathname do
            mkdir -p pages/reference/"${pathname:7}"
    done' bash {} +

find ../src -type f -name "*.php" -exec bash -c '
    for file do
    file=${file:7}
    length=${#file}
    file=${file::length-4}
            php src/generate-reference.php ../src/$file.php > pages/reference/${file}.mdx
    done
' bash {} +

mkdir -p pages/reference/Symfony/Bundle/DependencyInjection
php src/generate-reference.php ../src/Symfony/Bundle/DependencyInjection/Configuration.php > pages/reference/Symfony/Bundle/DependencyInjection/Configuration.mdx

php src/generate-sidebar.php > ./pages/sidebar.mdx

for d in ./pages/guide/*.mdx; do
    name=$(basename $d .mdx)
    name=$(echo "$name" | sed -E 's/^[0-9]+\-//g')
    name=$(echo "$name" | awk '{print tolower($0)}')
    mv "$d" "pages/guide/$name.mdx"
done

