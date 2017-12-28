# Herd - Configuration

The default configuration file lives in `data/config.json` from the project's root directory.
However, you can override this when passing arguments to the [cli](Command Line Interface).

A sample configuration file looks like:

```json
{
  "core-vendor": "paragonie",
  "database": {
    "dsn": "sqlite:/home/user/.herd/local.sql",
    "username": "",
    "password": "",
    "options": []
  },
  "policies": {
    "core-vendor-manage-keys-allow": true,
    "core-vendor-manage-keys-auto": true,
    "minimal-history": true,
    "quorum": 2
  },
  "remotes": [
    {
      "url": "https://chronicle-public-test.paragonie.com/chronicle",
      "public-key": "3BK4hOYTWJbLV5QdqS-DFKEYOMKd-G5M9BvfbqG1ICI=",
      "primary": true
    },
    {
      "url": "https://localhost/chronicle/replica/foo",
      "public-key": "3BK4hOYTWJbLV5QdqS-DFKEYOMKd-G5M9BvfbqG1ICI=",
      "primary": false
    },
    {
      "url": "https://web-host-chronicle-mirror.example.com/chronicle/replica/bar",
      "public-key": "3BK4hOYTWJbLV5QdqS-DFKEYOMKd-G5M9BvfbqG1ICI=",
      "primary": false
    },
    {
      "url": "https://alternative-mirror.example.com/chronicle/replica/baz",
      "public-key": "3BK4hOYTWJbLV5QdqS-DFKEYOMKd-G5M9BvfbqG1ICI=",
      "primary": false
    }
  ]
}
```

* [`core-vendor`](#core-vendor)
* [`database`](#database)
* [`policies`](#policies)
  * [`core-vendor-manage-keys-allow`](#core-vendor-manage-keys-allow)
  * [`core-vendor-manage-keys-auto`](#core-vendor-manage-keys-auto)
  * [`minimal-history`](#minimal-history)
  * [`quorum`](#quorum)
* [`remotes`](#remotes)

## Configuration Directives

### `core-vendor`

> Type: `string`

The `core-vendor` attribute is the name of the `vendor` who represents
the project's core team.

### `database`

> Type: `object`

Configures the local database connection. If you're familiar with
[PDO](https://secure.php.net/manual/en/class.pdo.php), this will already
be familiar for you.

Properties:

* `dsn` (string)
* `username` (string, optional)
* `password` (string, optional)
* `options` (array, optional)

### `policies`

> Type: `object`

#### `core-vendor-manage-keys-allow`

> Type: `bool`

Should the core vendor be allowed to manage keys for users?

If you enable this option, and a vendor needs their signing keys replaced,
the core vendor will be permitted to issue replacements. However, it will not
be performed automatically, unless `core-vendor-manage-keys-auto` is also enabled.

#### `core-vendor-manage-keys-auto`

> Type: `bool`

If `core-vendor-manage-keys-allow` is enabled, this applies key changes
automatically when the core vendor signs them on behalf of vendors.

If `core-vendor-manage-keys-allow` is disabled, this has no effect. 

#### `minimal-history`

> Type: `bool`

If enabled, the local history is automatically pruned of non-essential
information to minimize disk space usage.

Note that some commands (e.g. [`fact`](cli#fact)) will not work well if
local history is cleared.

#### `quorum`

> Type: `int`

The number of Chronicles that have to agree to seeing the same record
before it's accepted locally.

Cannot exceed the number of configured remotes.

### `remotes`

> Type `array<object>`

This defines the remote sources (Chronicle instances) that Herd uses as
its data source. Each array element is an object with the following
properties:

* `url` (string) is the URL of the Chronicle API (or replica). It must be a valid
  document root.
  * https://php-chronicle.pie-hosted.com/chronicle is **valid**
  * https://php-chronicle.pie-hosted.com/ is **invalid**
* `public-key` (string) is the base64url-encoded Ed25519 public key for
  the Chronicle instance
* `primary` (bool) indicates whether or not this is the primary source.
  Herd prioritizes secondary sources to minimize network usage (especially for
  the primary Chronicle).
