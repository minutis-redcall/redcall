# Broken Routes

Catalogue of routes whose integration test ended up `markTestIncomplete()`'d
because the route — not the test — is misbehaving. Each entry names the file
and line the symptom surfaces at, the user-visible behaviour, the most likely
root cause traced one or two indirections back, and the test that locks it in.

This file is written *during* the route-coverage pass. Fixes are a separate
pass on top of this branch.

