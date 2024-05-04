# Contributing Guide

## Preparing

Install `^php8.2` and the following extenstions:
- `php8.2-yaml`
- `php8.2-xml`
- `php8.2-mbstring`

> **Note** I suggest you also install the following optional extensions:
> - `php8.2-curl`
> - `php8.2-xdebug`


Clone the repository with 
```sh
git clone git@github.com:tncrazvan/catpaw.git
```

Install dependencies with

```sh
composer update
```

This will also download [catpaw.phar](https://github.com/tncrazvan/catpaw/releases).


## Writing the changes

- All source code should follow the `PSR-12` rules and remove all unused 
  imports before being committed, this can be done by running `composer fix`.
- Whenever possible, convert all methods into functions or at least 
  all classes into [services](https://github.com/tncrazvan/catpaw/blob/master/docs/13.Services.md).
- Avoid exposing `__construct` to the end user.
- Avoid returning `null` to the end user, return `false` or a proper default value instead.
- Avoid throwing exceptions to the end user, follow the error [management guide](./docs/0.error-managament.md).

## Submitting your changes

Simply create a pull request, we'll discuss details on github.

You don't need to submit an issue explaining the reason for the contribution, you can explain everything in the pull request itself.

Nevertheless, submitting issues before sending a pull request or before starting to write any changes is encouraged.
