# API Platform documentation

build preload data:

```
docker run -v $(pwd):/src -v $(pwd)/public/php-wasm:/public -w /public php-wasm python3 /emsdk/upstream/emscripten/tools/file_packager.py php-web.data --preload "/src" --js-output=php-web.data.js --no-node --exclude '*Tests*' '*features*' '*public*' '*/.*'
```
