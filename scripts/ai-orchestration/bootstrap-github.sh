#!/usr/bin/env bash
set -euo pipefail

if [[ $# -lt 1 ]]; then
    echo "Usage: scripts/ai-orchestration/bootstrap-github.sh <github-owner/repo>"
    exit 1
fi

repo="$1"

git init
git branch -M main
git add .
git commit -m "Initial ERP agent orchestration scaffold"

gh repo create "$repo" --private --source=. --remote=origin --push

echo "Created and pushed $repo"
echo "Next: configure branch protection in GitHub:"
echo "- require PR before merging"
echo "- require CI to pass"
echo "- require human reviews"
echo "- block direct pushes to main"
