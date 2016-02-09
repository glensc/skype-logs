skype-logs
==========

Simple CLI Application (PHP) to extract sqlite3 logs from Skype into csv, json or screen.

# Installing

Download pre-built PHAR file from [Releases page](https://github.com/edderrd/skype-logs/releases)

# Usage

By running the CLI application without parameters you be able to see existing commands and documentation.

## Export command

You can export or view message logs on the screen or dumped into a file by running:

```
$ php skype-logs.phar export <your skype user> <other user>
```

The command will output a table with logs found, notice that the body is a shorter version for screen real state.

# Compile PHAR on your own

This application uses [box](https://github.com/box-project/box2#as-a-phar-recommended) to generate a `phar` file.

A compiled version __binary__ is available on the [Releases page](https://github.com/edderrd/skype-logs/releases).

Also you can build you own by running box build in the project root folder:

```
$ composer install
$ composer phar
```