# Modelarium

This is an [open source general backend/frontend scaffold generator and validator for PHP](https://github.com/Corollarium/modelarium/).

The main feature is that it provides high level data types, allowing you to specify exactly what you expect of each field in a unified way for validation, database, model and frontend generation. Your fields are not strings, stop treating them as such.

It uses [Formularium to validate data and generate frontend scaffolding](https://github.com/Corollarium/Formularium/).
Forms are generated from a simple structure, which can be serialized as JSON. It's easy to create new datatypes, either from zero or extending the base types provided. The generated code can be used as is or customized with fine tuning for those pesky cases that no tool ever gets right.

## Documentation

- "I don't read docs just a hello world please": read next section below.
- [directive documentation](./directives.md)
- [creating new datatypes](./datatype.md)
- [creating new validators](./validator.md)

## Getting started tutorial: a Laravel Graphql backend application in two minutes

So let's create an application using Laravel and Lighthouse. Create a base Laravel project:

```
composer create-project laravel/laravel myawesomeproject
```

Install deps with composer:

```
composer required Corollarium/modelarium Corollarium/Formularium nuwave/lighthouse
```

We suggest `mll-lab/laravel-graphql-playground` as well to test your endpoints easily.

Init the basic data. This will publish a base Graphql file and a `User` graphql schema that matches Laravel's defaults. Note that it will delete `app/User.php` and `database/migrations/2014_10_12_000000_create_users_table.php` -- if you just need the .graphql files, run `php artisan vendor:publish --provider="Modelarium\Laravel\ServiceProvider" --tag=schema` instead.

```
php artisan modelarium:init
```

At this point you are ready to go, just write your schema. We extend Graphql SDL to support `#import file` syntax, similar to other projects. Let's create a new model `Post`. Add this to `graphql/post.graphql`:

```graphql
extend type Query {
  posts: [Post!]! @paginate(defaultCount: 10)
  post(id: ID @eq): Post @find
}

type Post @migrationTimestamps {
  id: ID!
  title: String! @MinLength(value: 5) @MaxLength(value: 25)
  description: Text! @MinLength(value: 15) @MaxLength(value: 1000)
  user: User!
    @belongsTo
    @migrationForeign(onDelete: "cascade", onUpdate: "cascade")
}
```

Note that we are using `extend type Query` (and should use `extend type Mutation` too) since we'll merge all the .graphql files together. `Post` has a series of directives to control its behavior, such as creating timestamps for the database entry, declaring minimum and maximum lengths for the fields and foreign keys.

Since we are declaring a `belongsTo` relationship, We'll also need to declare the relationship to `User`, adding this to the `type User` in `user.graphql`:

```graphql
    posts: [Post!] @hasMany
```

Once you are done you can generate all scaffolding with:

```
php artisan modelarium:scaffold '*' --everything --overwrite --lighthouse
```

Look at the `app` directory and you'll see the `BaseUser.php`, `User.php`, `BasePost.php` and `Post.php` files. The `Base*` files are automatically generated, so you should only alter `User.php` and `Post.php`, which inherit from them. This way you can easily add new methods to the models and yet regenerate the base data whenever it's changed. With Modelarium you can regenerate scaffolding whenever you need.

In the `database` directory the migration, seeder and factory code will also have been created. We are not doing this in the example, but events and policies can also be created automatically.

From now on you are on a regular Laravel project, so after you setup your `.env` file with the app database information, just run the usual process to migrate and seed:

```
php artisan key:generate
php artisan migrate:fresh --seed
```

Serve the site:

```
php artisan serve --host 0.0.0.0 --port 8000
```

If you installed `mll-lab/laravel-graphql-playground` you can test the graphql endpoint at `http://localhost:8000/graphql-playground`. Run a simple query to check it all:

```graphql
{
  post(id: 1) {
    id
    title
    description
    user {
      id
      name
    }
  }
}
```

Let's add a mutation to create a post.

## Sponsors

[![Corollarium](https://modelarium.github.com/logo-horizontal-400px.png)](https://corollarium.com)
