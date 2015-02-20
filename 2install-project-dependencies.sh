#!/bin/bash

cd "$( dirname "${BASH_SOURCE[0]}" )"
DIR=${PWD}

PLATFORM=`uname`
if [[ "$PLATFORM" == 'Linux' ]]; then
  sudo apt-get -y nodejs build-essential
fi

# Install lightning dependencies with grunt
cd ${DIR}/../Source/Resources
npm install
grunt build
