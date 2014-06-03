#!/bin/bash
libModuleHash=$(git show HEAD | grep "+Subproject commit" | cut -f3 -d" ")
strLength=${#libModuleHash}
if [ \( -n "$libModuleHash" \) -a \( "$strLength" -eq 40 \) ]; then
	gitUser=$(cat .git/config | grep -A2 "remote \"origin\"" | grep "url" | cut -f2 -d":" | cut -f4 -d"/")
	branch=$(git branch | grep -A1 "*" | grep "^ " | tr -d " ")
	if [ "$gitUser" != 'pkp' ]; then
		git reset --hard HEAD^
		git submodule update --init --recursive
		cd lib/pkp
		echo "Updating pkp-lib with code from $gitUser repository, $branch branch."
		git remote add "$gitUser" git://github.com/"$gitUser"/pkp-lib
		git pull "$gitUser" "$branch"
		exit 0
	fi
fi
git submodule update --init --recursive
