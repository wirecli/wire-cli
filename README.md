# wire-cli

wire-cli is a CLI (Command-Line Interface) tool designed to provide ease of use and efficiency for ProcessWire developers. With wire-cli, you can automate common tasks, manage ProcessWire projects effortlessly, and enhance your development workflow.

## Features

- Create new ProcessWire projects
- Serve ProcessWire via built-in PHP webserver
- Perform database backup and restoration
- Manage fields, templates, roles, users, and modules
- Generate boilerplate modules
- Check for core upgrades
- And more...

wire-cli is based on the defunct wireshell, built using the [Symfony Console](https://symfony.com/doc/current/components/console.html) Component, offering a powerful and intuitive command-line interface for interacting with ProcessWire projects and adding new commands. It is compatible with PHP 8.1.

Please note that wire-cli and another tool called [rockshell](https://github.com/baumrock/rockshell) share similar goals and functionality. In the future, the features of wire-cli and rockshell will be merged to provide a unified and comprehensive CLI tool for ProcessWire developers.

## Installation

To install wire-cli, you need to have Composer installed. Run the following command to install wire-cli globally:

```
composer global require wirecli/wire-cli
```

## Usage

Run `wire-cli` followed by the desired command to execute various tasks. For example:

```
wire-cli new myproject
```

For a complete list of available commands and options, use the `help` command:

## Documentation

For detailed documentation and usage examples, please refer to the [official documentation](https://github.com/wirecli/wire-cli).

## Contributing

Contributions are welcome! If you encounter any issues or have suggestions for improvements, please submit an issue or a pull request on the [GitHub repository](https://github.com/wirecli/wire-cli).

## Available commands

![](./docs/capture-cmd.jpg)

## Credits

wire-cli is inspired by the work of the following authors of the initial developers/contributors of wireshell:

- Marcus Herrmann
- Hari K T
- Bea David
- Camilo Castro
- Horst Nogajski

## License

This project is licensed under the [MIT License](LICENSE.md).
