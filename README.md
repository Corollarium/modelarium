<h1 align="center">Modelarium - Scaffolding for PHP and JS</h1>

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Build Status](https://travis-ci.com/Corollarium/modelarium.svg?branch=master)](https://travis-ci.com/Corollarium/modelarium)
[![Code Coverage](https://scrutinizer-ci.com/g/Corollarium/modelarium/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Corollarium/modelarium/?branch=master)
[![Latest Stable Version](https://img.shields.io/packagist/v/corollarium/modelarium.svg?style=flat-square)](https://packagist.org/packages/corollarium/modelarium)
[![Total Downloads](https://img.shields.io/packagist/dt/corollarium/modelarium.svg?style=flat-square)](https://packagist.org/packages/corollarium/modelarium)

---

Modelarium generates scaffolding for your project based on a GraphQL description of your API.

What it does?

- generates scaffolding for you: model, database migration, seed, factory, events, policies. Everything from your graphql descriptions. No more tedious creation of files and repeated code.
- datatypes: your data is more than strings. You have models for your structures, so have datatypes for your fields. Create the correct database fields without thinking about it.
- validation: transparent data validation made automatically based on your datatypes. Your data is always safely validated.
- frontend generation: get HTML forms generated for you automatically with your favorite CSS framework, as well as basic cards and lists. Get Vue and React components if you use them.
- integration with Laravel and Lighthouse. Get GraphQL endpoints automatically.

What it doesn't do:

- you still have to write your code to process requests in the backend, like in mutations or special conditions in your models.
- REST endpoints. At this point only GraphQL is supported through Lighthouse.
- frontend structure.
- magic.

## Sponsors

[![Corollarium](https://modelarium.github.com/logo-horizontal-400px.png)](https://corollarium.com)

## Documentation

## Contributing [![contributions welcome](https://img.shields.io/badge/contributions-welcome-brightgreen.svg?style=flat)](https://github.com/Corollarium/modelarium/issues)

Any contributions are welcome. Please send a PR.
