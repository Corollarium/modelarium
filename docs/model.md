# Laravel

Modelarium can generate an entire Laravel application from scratch using [Lighthouse](https://lighthouse-php.com/) to provide Graphql endpoints, all from your Graphql model files. This is a short tutorial on how to create your app.

Creating a project using Modelarium, Lighthouse and Laravel.

```shell

# Create a laravel project
composer create-project --prefer-dist laravel/laravel myproject

# Add Modelarium and Lighthouse
cd myproject
composer require corollarium/modelarium corollarium/formularium nuwave/lighthouse
composer install
```

At this point you have the deps ready. Let's create your schemas in graphql/.

`User.graphql`:

```graphql
#import "formularium.graphql"

type Query {
  users: [User!]! @paginate(defaultCount: 10)
  user(id: ID @eq): User @find
}

type User @timestamps @softDeletesDB {
  id: ID!
  name: String!
  email: Email!
}
```

Modelarium and Lighthouse support several directives to use Laravel's features. In this case we're adding timestamps and softDeletes to the user model/database. We are importing Formularium datatypes, so we can use types like `Email`.

```shell
artisan modelarium:scaffold '*' --everything --overwrite
```
