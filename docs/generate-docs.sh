#!/bin/bash

php generate-reference.php

for d in ./guides/*.php ; do
    php generate-guide.php $d > pages/guides/$(basename $d .php).mdx
done

bash generate-sidebar.sh > ./pages/sidebar.mdx
