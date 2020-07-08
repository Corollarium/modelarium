<h1 align="center">Modelarium - Scaffolding for PHP and JS</h1>

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Build Status](https://travis-ci.com/Corollarium/modelarium.svg?branch=master)](https://travis-ci.com/Corollarium/modelarium)
[![Code Coverage](https://scrutinizer-ci.com/g/Corollarium/modelarium/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Corollarium/modelarium/?branch=master)
[![Latest Stable Version](https://img.shields.io/packagist/v/corollarium/modelarium.svg?style=flat-square)](https://packagist.org/packages/corollarium/modelarium)
[![Total Downloads](https://img.shields.io/packagist/dt/corollarium/modelarium.svg?style=flat-square)](https://packagist.org/packages/corollarium/modelarium)

---

Modelarium generates scaffolding for your project based on a GraphQL description of your API.

What it does?

- **generates backend scaffolding for you**: model, database migration, seed, factory, events, policies. Everything from your graphql descriptions. No more tedious creation of files and repeated code.
- **datatypes**: your data is more than strings. You have models for your structures, so have datatypes for your fields. Create the correct database fields without thinking about it.
- **validation**: transparent data validation made automatically based on your datatypes. Your data is always safely validated.
- **no performance penalty**: other than data validation all data is generate at development time. It's just automatic scaffolding, everything is just as fast as before.
- **no new standards**: code is generated following existing standards for existing tools. Generate code and use it freely. Nothing is tied down.
- **frontend generation**: get HTML forms, cards, lists and views generated for you automatically with your favorite CSS framework: Bootstrap, Bulma, Materialize, Buefy. You can tweak them afterwards -- it's just code. Get Vue and React components if you use them.
- **integration with Laravel and Lighthouse**. Get GraphQL endpoints automatically.

What it doesn't do:

- magic. you still have to write your code logic to process requests in the backend, like in mutations or special conditions in your models.
- REST endpoints generation. At this point only GraphQL is supported through Laravel and Lighthouse. REST endpoints might come later.
- other backend frameworks. Currently only Laravel is supported.

## Documentation

See [the full documentation for Modelarium](https://corollarium.github.io/modelarium/).

See [a sample project](https://github.com/Corollarium/modelarium-example).

## Sponsors

[![Corollarium](https://modelarium.github.com/logo-horizontal-400px.png)](https://corollarium.com)

## Quick overview

This a Graphql file that reproduces Laravel's default `User` model. Notice the `Email` datatype, as well as the `@migration` directives for the database creation.

```graphql
type User
  @migrationRememberToken
  @migrationSoftDeletes
  @migrationTimestamps
  @modelExtends(class: "Illuminate\\Foundation\\Auth\\User")
  @modelNotifiable {
  id: ID!
  name: String!
    @modelFillable
    @renderable(label: "Name", comment: "Please fill with your fullname")
  password: String! @modelHidden @modelFillable
  email_verified_at: Timestamp @casts(type: "datetime")
  email: Email! @migrationUniqueIndex @modelFillable
}
```

Here's a sample `Post` Model, with validation of the length of the fields and foreign keys:

```graphql
type Post @migrationTimestamps {
  id: ID!
  title: String! @MinLength(value: 5) @MaxLength(value: 25)
  description: Text! @MinLength(value: 15) @MaxLength(value: 1000)
  user: User! @belongsTo @foreign(onDelete: "cascade", onUpdate: "cascade")
}
```

## Contributing [![contributions welcome](https://img.shields.io/badge/contributions-welcome-brightgreen.svg?style=flat)](https://github.com/Corollarium/modelarium/issues)

Any contributions are welcome. Please send a PR.
