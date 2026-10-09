# Cron race integration repro (wp-proudcity#2961)

`cron-race.sh` reproduces the wp-cron read-modify-write race that silently
dropped scheduled-post events (Somerville posts 15393/15464), and confirms
`proud-cron-integrity.php` closes it.

It needs a local WordPress install with `wp` on PATH (defaults to
`$HOME/Sites/proudtest`). It does not touch git, composer, or the cluster; it
only copies the mu-plugin into that site's `wp-content/mu-plugins/` for the
duration of the run, and schedules/clears two throwaway hooks
(`proud2961_a`, `proud2961_b`).

```sh
tests/integration/cron-race.sh [path-to-local-wp-install]
```

Expected output: the two control runs (no mu-plugin) print
`proud2961_a=true proud2961_b=false` -- B's event is lost -- and all three
runs with the mu-plugin installed print `proud2961_a=true proud2961_b=true`
every time.

## Only run this against a throwaway local site

The control runs (no mu-plugin) reproduce the real bug on the target site: any
real cron event another process schedules during the 15-second sleep can be
lost. Never point this at an imported DB you care about or a shared
environment.

If a run is killed hard (e.g. `kill -9`), the EXIT trap doesn't fire. Check
`wp-content/mu-plugins/` on the target and remove `proud-cron-integrity.php`
if it was left behind.
