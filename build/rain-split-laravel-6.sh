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

        HEADDIR="${HEAD//\/}"

        mkdir -p $HEADDIR

        pushd $HEADDIR

        ./../../git-subsplit.sh init git@github.com:octobercms/october.git
        ./../../git-subsplit.sh update

        time ./../../git-subsplit.sh publish --heads="$HEAD" --no-tags "$SPLIT"

        popd

    done

    popd
}

split backend      modules/backend:git@github.com:octoberrain/backend.git                 "wip/laravel-6"
split cms          modules/cms:git@github.com:octoberrain/cms.git                         "wip/laravel-6"
split system       modules/system:git@github.com:octoberrain/system.git                   "wip/laravel-6"
