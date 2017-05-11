#!/usr/bin/env bash

# create deployment with revision
aws deploy create-deployment --profile aelr --application-name Journal --deployment-config-name CodeDeployDefault.OneAtATime --deployment-group-name JournalProduction --description "Journal Deployment" --github-location commitId=$1,repository=jdgriffith/ojs
