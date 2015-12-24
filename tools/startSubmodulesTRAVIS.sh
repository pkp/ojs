#!/bin/bash
git config --global user.email "pkp@mailinator.com"
git config --global user.name "PKP"
echo "1 - Retrieving git user:"
gitUser=$(cat .git/config | grep -A2 "remote \"origin\"" | grep "url" | cut -f2 -d":" | cut -f4 -d"/")
echo "    Gituser: $gitUser"
echo "2 - Verifying if we have a merge commit in HEAD:"
isMergeCommit=$(git log -m --pretty=oneline -n1 | grep -P "Merge [0-9a-z]{40} into [0-9a-z]{40}")
echo "    Merge commit message: $isMergeCommit"
if [ \( -n "$isMergeCommit" \) -a \( "$gitUser" = "pkp" \) ]; then
	commitHash=$(git log -m --pretty=oneline -n2 | sed -n 2p | grep -Po "^[0-9a-z]{40}")
	echo "    HEAD points to a merge commit."
	echo "    HEAD^ points to a common commit with hash $commitHash"
else
	commitHash=$(git log -m --pretty=oneline -n1 | grep -Po "^[0-9a-z]{40}")
	echo "    HEAD points to a common commit with hash $commitHash"
fi
echo "3 - Verifying if last non merge commit is a subproject commit:"
libModuleHash=$(git show "$commitHash" | grep "+Subproject commit" | cut -f3 -d" ")
strLength=${#libModuleHash}
echo "    Subproject commit hash: $libModuleHash"
if [ \( -n "$libModuleHash" \) -a \( "$strLength" -eq 40 \) ]; then
	echo "    Last non merge commit is subproject commit."
	echo "4 - Trying to get user and branch from commit message:"
	userAndBranch=$(git log --pretty=oneline -1 "$commitHash" | grep -o "##.*##" | sed "s:^##\(.*\)##$:\1:")
	gitUser=$(echo "$userAndBranch" | cut -f1 -d"/")
	branch=$(echo "$userAndBranch" | cut -f2 -d"/")
	echo "    User and branch: $userAndBranch - User: $gitUser - Branch: $branch"
	if [ \( -n "$gitUser" \) -a \( -n "$branch" \) ]; then
		echo "    Found user and branch in commit message."
		echo "5 - Reseting the subproject commit in application."
		git reset --hard "$commitHash"^
		echo "6 - Initializing and updating submodule from official."
		git submodule update --init --recursive
		cd lib/pkp
		echo "7 - Updating pkp-lib with code from $gitUser repository, $branch branch."
		git remote add "$gitUser" git://github.com/"$gitUser"/pkp-lib
		git reset --hard HEAD
		git pull --rebase "$gitUser" "$branch"
		exit 0
	fi
fi
git submodule update --init --recursive
