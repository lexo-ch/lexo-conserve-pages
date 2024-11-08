#/bin/bash

NEXT_VERSION=$1
CURRENT_VERSION=$(cat composer.json | grep version | head -1 | awk -F= "{ print $2 }" | sed 's/[version:,\",]//g' | tr -d '[[:space:]]')

sed -ie "s/\"version\": \"$CURRENT_VERSION\"/\"version\": \"$NEXT_VERSION\"/g" composer.json
rm -rf composer.jsone

sed -ie "s/Version:           $CURRENT_VERSION/Version:           $NEXT_VERSION/g" lexo-conserve-pages.php
rm -rf lexo-conserve-pages.phpe

sed -ie "s/Stable tag: $CURRENT_VERSION/Stable tag: $NEXT_VERSION/g" readme.txt
rm -rf readme.txte

sed -ie "s/\"version\": \"$CURRENT_VERSION\"/\"version\": \"$NEXT_VERSION\"/g" info.json
rm -rf info.jsone

sed -ie "s/v$CURRENT_VERSION/v$NEXT_VERSION/g" info.json
rm -rf info.jsone

sed -ie "s/$CURRENT_VERSION.zip/$NEXT_VERSION.zip/g" info.json
rm -rf info.jsone

npx mix --production
sudo composer dump-autoload -oa

mkdir lexo-conserve-pages

cp -r assets lexo-conserve-pages
cp -r languages lexo-conserve-pages
cp -r dist lexo-conserve-pages
cp -r src lexo-conserve-pages
cp -r vendor lexo-conserve-pages

cp ./*.php lexo-conserve-pages
cp LICENSE lexo-conserve-pages
cp readme.txt lexo-conserve-pages
cp README.md lexo-conserve-pages
cp CHANGELOG.md lexo-conserve-pages

zip -r ./build/lexo-conserve-pages-$NEXT_VERSION.zip lexo-conserve-pages -q
