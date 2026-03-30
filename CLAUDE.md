# OpenRiC — Claude Code Rules

## PROJECT CONTEXT
- **Mission**: Port all Heratio packages to OpenRiC with RiC-O data model adaptation
- **Reference**: `/usr/share/nginx/OpenRiC/docs/PORTING_REPORT.md`
- **Status**: 10 complete, 21 partial (need 611 files), 46 not started

## GIT RULES

- **NEVER run `git push`.** You are not allowed to push to any remote. Ever. No exceptions.
- Commit locally only. The user will push when ready.
- Always update `version.json` and create a `git tag` with each commit.
