# Modelarium

This is an [open source general backend/frontend scaffold generator and validator for PHP](https://github.com/Corollarium/modelarium/).

The main feature is that it provides high level data types, allowing you to specify exactly what you expect of each field in a unified way for validation, database, model and frontend generation. Your fields are not strings, stop treating them as such.

It uses [Formularium to validate data and generate frontend scaffolding](https://github.com/Corollarium/Formularium/).
Forms are generated from a simple structure, which can be serialized as JSON. It's easy to create new datatypes, either from zero or extending the base types provided. The generated code can be used as is or customized with fine tuning for those pesky cases that no tool ever gets right.

## Documentation

- [a full Laravel Graphql application in minutes](./laravel.md)

## Getting started

Install with composer:

```
composer required Corollarium/modelarium Corollarium/Formularium
```
