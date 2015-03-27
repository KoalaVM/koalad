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
* `signature` is the output from `payload64` piped into `gpg --detach-sig` or
some equivalent detached signature process (e.g. `gnupg_sign(...)` in PHP).

`payload64`
===========

`payload64` must match the following base-64 encoded format:

```json
{
  "command": "...",
  "data":    <data>
}
```

* `command` is a string containing the name of the command to execute.
* `data` is a *protocol agnostic* type of data that `command` is expecting.
