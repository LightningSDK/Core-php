#!/bin/bash

cd "$( dirname "${BASH_SOURCE[0]}" )"
DIR=$PWD

# Install main templates.
cp -r $DIR/Templates $DIR/../Source/
