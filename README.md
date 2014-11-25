unframed52
===
A library of PHP conveniences for database applications.

Requirements
---
Unframed52 must :

- support PHP 5.2 or higher with `ignore_user_abort(true)` effectively enabled;
- implement the boilerplate of JSON web APIs that fail fast to HTTP errors;
- be able to verify JSON message signatures against public RSA keys;
- schedule jobs and poll queues without the `fnctl_*` functions;
- provide practical conveniences for common SQL statements;
- implement a fast-to-read protocol to map JSON to SQL;
- offer developpers a convenient API in a single prototype class.
