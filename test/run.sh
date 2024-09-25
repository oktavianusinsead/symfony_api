#!/bin/sh

if [ $# -gt 1 ]
then
  for (( i=1;$i<=$#;i=$i+1 ))
  do
    echo "Running test for ${!i}"
    babel-node node_modules/jasmine-node/lib/jasmine-node/cli.js spec/${!i}_spec.js
  done
  exit
fi

if [ -z "$1" ]
then
  babel-node node_modules/jasmine-node/lib/jasmine-node/cli.js spec/
else
  babel-node node_modules/jasmine-node/lib/jasmine-node/cli.js spec/$1_spec.js
fi