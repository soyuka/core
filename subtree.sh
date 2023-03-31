#!/bin/bash

set -x

# Subtree split on tag this script gets called using find:
# find src -maxdepth 2 -name composer.json -exec bash subtree.sh {} refs/tags/3.1.5 \;
# find src -maxdepth 2 -name composer.json -exec bash subtree.sh {} refs/heads/3.1 \;
# See the subtree workflow
package=$(jq -r .name $1)
directory=$(dirname $1)
repository="https://github.com/$package"
git remote add $package $repository
sha=$(splitsh-lite --prefix=$directory)
git push $package $sha:$2

if [[ $2 == "refs/tags"*  ]]; then
    tag=${2//refs\/tags\//}
    gh release create -R $package $tag
fi
