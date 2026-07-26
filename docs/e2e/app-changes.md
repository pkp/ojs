# App changes & build blockers

Per `docs/product/RUNBOOK.md` "What goes where", this file records ONLY:

- **actual app-code changes** made by the campaign (what, why, where, commit);
- **build blockers**: app defects that prevented tests from running green
  (race conditions, nondeterministic UI, harness-hostile behavior) and the
  workaround taken.

Product findings — bugs, divergences, open questions — live in each feature
spec's Findings register, never here.

**Reset 2026-07-26**: the previous contents (8 production fixes and a 272-row
findings archive) were deleted and the fixes reverted (git history keeps both);
the restarted process rediscovers what matters on its own evidence.

| # | What & why | Files | Commit | Notes |
|---|-----------|-------|--------|-------|
