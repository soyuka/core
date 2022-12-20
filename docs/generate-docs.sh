#!/bin/bash

for d in ./guide/*.php; do
    php src/generate-guide.php $d > pages/guide/$(basename $d .php).mdx
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

php src/generate-sidebar.php > ./pages/sidebar.mdx
