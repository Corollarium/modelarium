---
layout: default
title: Advanced topics
nav_order: 3
---

# Advanced topics for Modelarium and Laravel

This continues [the getting started tutorial](./laraveltutorial.md).

## Relationships

Modelarium automatically creates a datatype for each relationship in your application (this is done through registering a factory method that creates the `Datatype` classes at runtime from the models, but you don't have to worry about it). But this means that relationships are typed, which allows you to add specific behavior to them as well, including validation.

TODO: explain how to perform validation

## Authentication

There's nothing specific to Modelarium about authentication, and all the usual methods of authenticating will work. But this is a tutorial, so let's show how to add authentication to our app.

By default Modelarium creates models in `app/Models` instead of `app`. For this to work with authentication you should change this in `config/auth.php`. You can also pass `--modelDir=app` if you prefer Laravel's default behavior.

```php
    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],
    ]
```

We'll use [Lighthouse GraphQL Passport Auth](https://github.com/joselfonseca/lighthouse-graphql-passport-auth) for this, integrating auth into GraphQL. Be sure to follow [their documentation for details](https://lighthouse-php-auth.com/)
