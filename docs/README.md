# API Platform documentation

build preload data:

```
docker run -v $(pwd):/src -v $(pwd)/public/php-wasm:/public -w /public emscripten/emsdk:3.1.35 python3 /emsdk/upstream/emscripten/tools/file_packager.py php-web.data --use-preload-cache --lz4 --preload "/src" --js-output=php-web.data.js --no-node --exclude '*Tests*' '*features*' '*public*' '*/.*'
```
