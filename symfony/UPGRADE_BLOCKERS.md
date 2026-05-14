# Upgrade Blockers

## Symfony 7.4 → 8.0

**Blocker:** `craue/formflow-bundle`

- Latest stable: `3.7.0` (released ahead of Symfony 8); its `composer.json` constrains all `symfony/*` siblings to `^4.4 || ^5.4 || ^6.3 || ^7.0`.
- `3.8.x-dev` (the bundle's working branch) still constrains `symfony/form` to `^5.4 || ^6.4 || ^7.2`.
- `dev-master` is identical to `3.8.x-dev` on this point.
- Upstream maintainer has not published a Symfony 8 compatible release.

**Why this package is load-bearing.** The bundle drives the campaign-creation wizard (`CampaignFlow`, `SmsTriggerFlow`, `CallTriggerFlow`, `EmailTriggerFlow`). These flows wire 5–6 controllers and are listed as core in `CLAUDE.md`. Replacing them with a hand-rolled multi-step form is a non-trivial refactor that is outside the scope of a dependency-upgrade branch.

**Confirmed not blocked.** Every other `symfony/*` package has a working `^8.0` release; the rest of the ecosystem (Doctrine, Twig, DAMA, maker-bundle, web-profiler-bundle) all support Symfony 8 once their own minor bumps are taken (e.g. `doctrine/doctrine-bundle` 2.18 → 3.2). So if/when CraueFormFlowBundle ships a Symfony 8 release, Symfony 8 should be a single coordinated commit away.

**Suggested follow-ups (out of scope for this branch):**
1. Watch [craue/CraueFormFlowBundle](https://github.com/craue/CraueFormFlowBundle) for a Symfony 8 release.
2. If the upstream is quiet, open a PR there with the constraint bumps. The internal API used in this repo (`FormFlow::nextStep`, `FormFlow::getCurrentStepNumber`, `FormFlow::getCurrentStep`, validation groups, `skip()`) does not touch anything Symfony 8 broke; a constraint-only PR is likely enough.
3. Failing that, fork the bundle to an in-tree `bundles/formflow/` directory (the repo already hosts several first-party bundles that way).
