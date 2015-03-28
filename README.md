![KoalaVM](http://dpr.clayfreeman.com/1kRYJ+ "KoalaVM")

`koalad`
========

`koalad` is the daemon responsible for managing virtualization servers.  
`koalad` uses a JSON-based API on SSL port `3654` by default.  Incoming messages
are signed with GPG by the connecting client (e.g. a management panel) and
verified by the GPG public key located at `data/KoalaCore/gpg.pub`.

Message Structure
=================

Incoming messages must match the following JSON format:

```json
{
  "payload64": "...",
  "signature": "..."
}
```

* `payload64` is a base-64 encoded JSON dictionary following [this](#payload64)
format.
* `signature` is a base-64 detached signature from `gnupg_sign(...)` in PHP.
Any given signature must be no older than 5 minutes (300 seconds) or it will be
considered invalid.

`payload64`
===========

`payload64` must match the following base-64 encoded format:

```json
{
  "command": "...",
  <...>
}
```

* `command` is a string containing the name of the command to execute.
* `data` is an optional *protocol agnostic* type of data that `command` expects.
