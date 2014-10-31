#!/bin/bash

cd "$( dirname "${BASH_SOURCE[0]}" )"
DIR=${PWD}
PLATFORM=`uname`

if [[ -f /etc/redhat-release ]]; then
  # todo: pick repo based on centos version in /etc/redhat-release
  # 5.x
  # wget http://dl.fedoraproject.org/pub/epel/5/x86_64/epel-release-5-4.noarch.rpm
  # sudo rpm -Uvh epel-release-5*.rpm
  # 6.x
  wget http://dl.fedoraproject.org/pub/epel/6/x86_64/epel-release-6-8.noarch.rpm
  sudo rpm -Uvh epel-release-6*.rpm
  # 7.x
  # wget http://dl.fedoraproject.org/pub/epel/7/x86_64/e/epel-release-7-1.noarch.rpm
  # sudo rpm -Uvh epel-release-7*.rpm
  # install for centos
  sudo yum -y update
  sudo yum -y install npm
  sudo yum -y install ruby
  gem install bundle
elif [[ "$PLATFORM" == 'Linux' ]]; then
  # add repo for nodejs
  sudo apt-get install python-software-properties
  sudo add-apt-repository -y ppa:chris-lea/node.js
  sudo apt-get update

  # install ruby for compass and grunt
  sudo apt-get -y install ruby-full rubygems python-software-properties python g++ make nodejs ruby-bundler
elif [[ "$PLATFORM" == 'Darwin' ]]; then
  # install ruby for mac
  \curl -L https://get.rvm.io | bash -s stable
fi

# Install gems.
sudo gem install sass
sudo gem install compass
sudo gem install foundation

  # Some git repos will rime out on git://
  git config --global url.https://.insteadOf git://

  # Install foundation dependencies with npm and bower.
  cd ${DIR}/Vendor/foundation
  sudo npm install -g bower grunt-cli

  # for centos, install with bundler
  if [[ -f /etc/redhat-release ]]; then
    sudo bundle install
  fi

  # install other dependences
  npm install
  bower install --allow-root
  # If github times out, try: git config --global url."https://".insteadOf git://
  grunt build

# Install jquery Validation
cd ${DIR}/Vendor/jquery-validation
sudo npm install
grunt

# Install ckeditor config.
if [[ ! -f "${DIR}/../Source/Resources/sass/ckeditor_contents.scss" ]]; then
  echo "Copy CKEditor css to foundation"
  cp ${DIR}/Vendor/ckeditor/contents.css ${DIR}/../Source/Resources/sass/ckeditor_contents.scss
fi

# Install lightning dependencies with grunt
cd ${DIR}/../Source/Resources
npm install
grunt build
