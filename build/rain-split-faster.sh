#!/bin/bash

split()
{
    SUBDIR=$1
    SPLIT=$2
    HEADS=$3

    mkdir -p $SUBDIR;

    pushd $SUBDIR;

    for HEAD in $HEADS
    do

        mkdir -p $HEAD

        pushd $HEAD

        ./../../git-subsplit.sh init git@github.com:octobercms/october.git
        ./../../git-subsplit.sh update

        time ./../../git-subsplit.sh publish --heads="$HEAD" --no-tags "$SPLIT"

        popd

    done

    popd
}

split backend      modules/backend:git@github.com:octoberrain/backend.git                 "master develop"
split cms          modules/cms:git@github.com:octoberrain/cms.git                         "master develop"
split system       modules/system:git@github.com:octoberrain/system.git                   "master develop"
