#!/bin/bash

# @file tools/travis/install-linter.sh
#
# Copyright (c) 2014-2017 Simon Fraser University
# Copyright (c) 2010-2017 John Willinsky
# Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
#
# Script to install the JS linter.
#

set -xe

# Install python, linter, closure compiler stuff
sudo pip install six
sudo pip install https://github.com/google/closure-linter/zipball/master
# wget -O compiler.zip "http://dl.google.com/closure-compiler/compiler-20130603.zip"
# unzip compiler.zip compiler.jar
mkdir ~/bin
# mv compiler.jar ~/bin/compiler.jar
cp lib/pkp/tools/travis/compiler.jar ~/bin
wget "https://storage.googleapis.com/google-code-archive-downloads/v2/code.google.com/jslint4java/jslint4java-2.0.2-dist.zip"
unzip jslint4java-2.0.2-dist.zip
mv jslint4java-2.0.2/jslint4java-2.0.2.jar ~/bin/jslint4java.jar
