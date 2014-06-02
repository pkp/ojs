#!/bin/bash
libModuleHash=$(git show HEAD | grep "+Subproject commit" | cut -f3 -d" ")
strLength=${#libModuleHash}
if [ \( -n "$libModuleHash" \) -a \( "$strLength" -eq 40 \) ]; then
	gitUser=$(cat .git/config | grep -A2 "remote \"origin\"" | grep "url" | cut -f2 -d":" | cut -f4 -d"/")
	branch=$(git branch | grep -A1 "*" | grep "^ " | tr -d " ")
	if [ \( "$gitUser" = 'ojs' \) -o \( "$gitUser" = 'omp' \) -o \( "$gitUser" = 'ocs' \) ]; then
		git submodule update --init --recursive
	else  
		git reset --hard HEAD^
		git submodule update --init --recursive
		cd lib/pkp
		git remote add "$gitUser" git://github.com/"$gitUser"/pkp-lib
		git pull "$gitUser" "$branch"
	fi
else
	git submodule update --init --recursive
fi
