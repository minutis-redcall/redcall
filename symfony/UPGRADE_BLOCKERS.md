# Upgrade Blockers

_None._

The previous run documented `craue/formflow-bundle` as a Symfony 8 blocker
because upstream had not shipped a compatible release. That blocker was
resolved by vendoring the bundle in-tree at `bundles/formflow-bundle/`
(see commit "vendor craue/formflow-bundle in-tree with Symfony 8 support")
and then completing the Symfony 7 → 8 upgrade in the next commit. The
in-tree fork carries one local-only patch: the `composer.json` constraints
for `symfony/*` now allow `^8.0` in addition to the upstream `^7.0` cap.

If upstream eventually publishes a Symfony 8 release, the `bundles/formflow-bundle/`
directory can be removed (along with the local `path` repository in the
root `composer.json`) and the constraint in `composer.json` reverted to a
normal `^4.x` line.

Remaining caveats — none of these block any upgrade:

- `brick/math` is pinned at `0.14.8` by `ramsey/uuid 4.9.x`'s constraint
  (`^0.8 || ^0.9 || … || ^0.14`). It will bump once `ramsey/uuid` ships a
  release that opens the constraint.
- `doctrine/collections` is pinned at `2.6.0` by `doctrine/orm 3.6.5`. It
  will bump when Doctrine ORM ships a release that allows `^3`.
- `composer/package-versions-deprecated` (already noted as a follow-up):
  marked abandoned upstream, kept here for one residual dependency. Worth
  removing in a dedicated PR.
