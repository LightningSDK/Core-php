#!/bin/bash

cd "$( dirname "${BASH_SOURCE[0]}" )"
DIR=${PWD}

# Install lightning dependencies with grunt
cd ${DIR}/../Source/Resources
npm install
grunt build
