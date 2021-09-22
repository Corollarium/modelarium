# Modelarium

This is an [open source general backend/frontend scaffold generator and validator for PHP](https://github.com/Corollarium/modelarium/).

Modelarium is based on data types, allowing you to specify exactly what you expect of each field in a unified way for validation, database, model and frontend generation. Your fields are not strings, stop treating them as such.

Your models and operations are described as a Graphql files. It uses [Formularium to validate data and generate frontend scaffolding](https://github.com/Corollarium/Formularium/). Forms are generated from a simple structure, which can be serialized as JSON. It's easy to create new datatypes, either from zero or extending the base types provided. The generated code can be used as is or customized with fine tuning for those pesky cases that no tool ever gets right.

While you can use it as a standalone package, it includes integrations with Laravel, which is the easiest way to use it.

## Documentation

- ["I don't read docs but want a tutorial with Laravel please"](./laraveltutorial.md)
- ["I don't read tutorials, I want to see hello world code"](https://github.com/Corollarium/modelarium-helloworld)
- ["I don't read tutorials and I want something more complicated than hello world"](https://github.com/Corollarium/modelarium-example)
- [why modelarium was created](./philosophy.md)
- [creating datatypes](./datatype.md)
- [creating new validators](./validator.md)
- [relationships](./relationships.md)
- [Laravel scaffolding in detail](./laravel.md)

Reference:

- [creating a new directive](./directive.md)
- [all supported datatypes](./api-datatypes.md)
- [all supported directives](./api-directives.md)

## Sponsors

[![Corollarium](https://corollarium.github.com/modelarium/logo-horizontal-400px.png)](https://corollarium.com)
