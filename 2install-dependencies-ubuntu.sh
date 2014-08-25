#!/bin/bash

# add repo for nodejs
apt-get install python-software-properties
add-apt-repository ppa:chris-lea/node.js
apt-get update

# install ruby for compass and grunt
apt-get install ruby-full rubygems

# install sass/compass gems
gem install sass
gem install compass

# install grunt
apt-get install python-software-properties python g++ make nodejs
npm install -g bower grunt-cli
gem install foundation

cd "$( dirname "${BASH_SOURCE[0]}" )"
cd ../Source/foundation
npm install -g bower grunt-cli
npm install
