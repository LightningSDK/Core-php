#!/bin/bash

# After this, you should run install-dependences-{operating-system}.sh

cd "$( dirname "${BASH_SOURCE[0]}" )"

git submodule update --init
cp install/index.php ../
cp install/.htaccess-router ../.htaccess
chmod 777 Vendor/htmlpurifier/library/HTMLPurifier/DefinitionCache/Serializer

# Create the source paths
mkdir ../Source
cp install/.htaccess-protected ../Source/.htaccess

cp -r install/lightning-foundation ../Source/foundation
