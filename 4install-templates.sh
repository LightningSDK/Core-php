#!/bin/bash

cd "$( dirname "${BASH_SOURCE[0]}" )"
DIR=$PWD

# Install main templates.
echo "Copying default templates to source folder."
cp -r $DIR/install/Templates $DIR/../Source/

echo "Copying default images to web root."
cp -r $DIR/install/images $DIR/../

echo "Complete."
