# Modelarium

This is an [open source general backend/frontend scaffold generator and validator for PHP](https://github.com/Corollarium/modelarium/).

Modelarium is based on data types, allowing you to specify exactly what you expect of each field in a unified way for validation, database, model and frontend generation. Your fields are not strings, stop treating them as such.

Your models and operations are described as a Graphql files. It uses [Formularium to validate data and generate frontend scaffolding](https://github.com/Corollarium/Formularium/). Forms are generated from a simple structure, which can be serialized as JSON. It's easy to create new datatypes, either from zero or extending the base types provided. The generated code can be used as is or customized with fine tuning for those pesky cases that no tool ever gets right.

## Documentation

- ["I don't read docs just a hello world tutorial please"](./laraveltutorial.md)
- ["I don't read hello world tutorials just sample code please"](https://github.com/Corollarium/modelarium-example)
- [directive documentation](./directives.md)
- [creating new datatypes](./datatype.md)
- [creating new validators](./validator.md)
- [Laravel scaffolding in detail](./laravel.md)

Reference:

- [all supported datatypes](./datatypes.md)
- [all supported directives](./directives.md)

## Sponsors

[![Corollarium](https://corollarium.github.com/modelarium/logo-horizontal-400px.png)](https://corollarium.com)
