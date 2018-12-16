# Security Goals for HERD

HERD relies on the immutable, append-only property of [Chronicle](https://github.com/paragonie/chronicle).

Given the same Chronicle instances and local configuration file, the final
database of public keys and update metadata HERD is deterministic.

Third-party auditors **MUST** be able to rebuild the same database from scratch,
compare it with the local collection, and verify that nothing has been tampered
with locally.

At an ecosystem level, ensuring that every end user sees the same history of
public keys and software releases as part of an automatic security update feature
provides a property similar to what biologists call *herd immunity*: 

In order to penetrate any system, you must first make *every* system vulnerable.
