#!/bin/sh

# Installs ZF to library/

if [ -d "../../library/Zend" ]; then
    echo Zend Framework is already installed
    exit;
fi

echo Installing Zend Framework

tmpDir=/tmp/zf_install/

mkdir -p $tmpDir

zfGit=https://github.com/zendframework/zf1/archive/master.zip
wget $zfGit -O $tmpDir"zf.zip"

unzip $tmpDir"zf.zip" -d $tmpDir

cp -R "$tmpDir"zf1-master/library/Zend/ ../../library/
rm -rf $tmpDir;