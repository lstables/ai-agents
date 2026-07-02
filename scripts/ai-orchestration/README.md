# AI Orchestration Scripts

This folder contains operational helpers for turning the project into a GitHub-backed AI delivery workflow.

`bootstrap-github.sh` initialises the local repo, creates a private GitHub repository, and pushes `main`.

`agent-comments.md` contains copyable command comments for manually driving the Team Lead, Developer, QA, and Reviewer agents.

The first production-grade automation should be a GitHub App or webhook listener that reacts to those same commands and runs the correct agent with the correct prompt.
