skype-logs
==========

Simple CLI Application (PHP) to extract MAC OS (OSX) sqlite lite logs from skype into csv, json or screen.

# Compile

This application uses [box](https://github.com/box-project/box2#as-a-phar-recommended) to generate a `phar` file.

A compiled version __binary__ is available on the root repository folder.

Also you can build you own by running box build in the project root folder:

```
$ box build 
```

# Usage

By running the CLI application without parameters you be able to see existing commands and documentation.

## Export command

You can export or view message logs on the screen or dumped into a file by running:

```
$ skype-logs.phar export <your skype user> <other user>
```

The command will output a table with logs found, notice that the body is a shorter version for screen real state.
