# Command Line Interface

The command line interface can be accessed by running `bin/shepherd` from the project's
root directory, followed by a command.

For example, `bin/shepherd help` invokes the `help` command. The full list of commands
is available below:

* [`add-remote`](#add-remote)
* [`fact`](#fact)
* [`get-update`](#get-update)
* [`help`](#help)
* [`list-products`](#list-products)
* [`list-updates`](#list-updates)
* [`list-vendors`](#list-vendors)
* [`review`](#review)
* [`transcribe`](#transcribe)
* [`vendor-keys`](#vendor-keys)

## `add-remote`

Adds a remote source to the configuration file. Returns a JSON blob.

Usage: `shepherd add-remote <url> <public-key>`

Options:

* `-c FILE` / `--config=FILE` specify the configuration file path
* `-p` / `--primary` Mark the new Remote as a primary source (i.e. not a replica)

## `fact`

Learn about a historical event. Returns a JSON blob.

Usage: `shepherd fact <summary-hash>`

Options:

* `-c FILE` / `--config=FILE` specify the configuration file path
* `-r` / `--remote` If the summary hash is not found, query a Remote source
  (i.e. a Chronicle instance) for this information instead

## `get-update`

Get information about a particular software update. Returns a JSON blob.

Usage: `shepherd get-update <vendor> <product> <version>`

Options:

* `-c FILE` / `--config=FILE` specify the configuration file path

## `help`

Learn how to use each command available to this CLI API. Outputs human-readable
text to the terminal window.

(Effectively, it's the same as this page, except you can use it offline.)

Usage: `shepherd help <command-name>`

## `list-products`

List the products available for a given vendor. Returns a JSON blob.

Usage: `shepherd list-products <vendor>`

Options:

* `-c FILE` / `--config=FILE` specify the configuration file path

## `list-updates`

List the updates available for a given product. Returns a JSON blob.

Usage: `shepherd list-updates <vendor> <product>`

Options:

* `-c FILE` / `--config=FILE` specify the configuration file path

## `list-vendors`

List/search vendors. The `search` parameter is optional. Returns a JSON blob.

Usage: `shepherd list-vendors <search>?`

Options:

* `-c FILE` / `--config=FILE` specify the configuration file path

## `review`

Review uncommitted updates. Interactive command.

Usage: `shepherd review`

Options:

* `-c FILE` / `--config=FILE` specify the configuration file path

## `transcribe`

Update local history from one or more of the remote Chronicles.

Usage: `shepherd transcribe`

Options:

* `-c FILE` / `--config=FILE` specify the configuration file path

The `transcribe` command should be run regularly (i.e. via cron jobs).

## `vendor-keys`

List all the trusted public keys for a vendor.

Usage: `shepherd vendor-keys` 

Options:

* `-c FILE` / `--config=FILE` specify the configuration file path
