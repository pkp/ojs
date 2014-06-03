#!/bin/bash
isMergeCommit=$(git log -m --pretty=oneline -n1 | grep -P "Merge [0-9a-z]{40} into [0-9a-z]{40}")
gitUser=$(cat .git/config | grep -A2 "remote \"origin\"" | grep "url" | cut -f2 -d":" | cut -f4 -d"/")
if [ \( -n "$isMergeCommit" \) -a \( "$gitUser" = "pkp" \) ]; then
	previousCommitHash=$(git log -m --pretty=oneline -n2 | sed -n 2p | grep -P "[0-9a-z]{40}")
	libModuleHash=$(git show "$previousCommitHash" | grep "+Subproject commit" | cut -f3 -d" ")
else
	libModuleHash=$(git show HEAD | grep "+Subproject commit" | cut -f3 -d" ")
fi
strLength=${#libModuleHash}
if [ \( -n "$libModuleHash" \) -a \( "$strLength" -eq 40 \) ]; then
	userAndBranch=$(git log --pretty=oneline -n1 | grep -o "##.*##" | sed "s:^##\(.*\)##$:\1:")
	gitUser=$(echo "$userAndBranch" | cut -f1 -d"/")
	branch=$(echo "$userAndBranch" | cut -f2 -d"/")
	if [ \( -n "$gitUser" \) -a \( -n "$branch" \) ]; then
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
