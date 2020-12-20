# Getting started tutorial: a Laravel Graphql backend application in two minutes

So let's create a web application using Laravel and Lighthouse with a Graphql endpoint. A [full example with source code ready to use is available](https://github.com/Corollarium/modelarium-example).

## Deps

Create a base Laravel project:

```
composer create-project laravel/laravel myawesomeproject
```

Install deps with composer:

```
composer require corollarium/modelarium corollarium/formularium nuwave/lighthouse
```

We strongly suggest installing `mll-lab/laravel-graphql-playground` as well to test your endpoints easily.

## My first model

Init the basic data. This will publish a base Graphql file and a `User` graphql schema that matches Laravel's defaults. Note that it will delete `app/User.php` and `database/migrations/2014_10_12_000000_create_users_table.php` -- if you just need the .graphql files, run `php artisan vendor:publish --provider="Modelarium\Laravel\ServiceProvider" --tag=schema` instead.

```
artisan modelarium:publish
```

At this point you are ready to go, just write your schema. We extend Graphql SDL to support `#import file` syntax, similar to other projects. Let's create a new model `Post`. Add a line `#import data/post.graphql` to `schema.graphql`, then create a file in `graphql/data/post.graphql` with the following:

```graphql
type Post @migrationTimestamps {
  id: ID!
  title: String!
  content: Text!
  user: User!
    @belongsTo
    @migrationForeign(onDelete: "cascade", onUpdate: "cascade")
}
```

`Post` has a series of directives to control its behavior, such as creating timestamps for the database entry and foreign keys.

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

## Queries

You need to declare queries to fetch data from your database. Let's declare two, one to list and one to fetch data from an individual item. Add this to your `post.graphql` file:

```graphql
extend type Query {
  posts: [Post!]! @paginate(defaultCount: 10)
  post(id: ID @eq): Post @find
}
```

Note that we are using `extend type Query` (and should use `extend type Mutation` too) since we'll merge all the .graphql files together and have many queries and mutations. We haven't changed anything that needs scaffolding, so there's no need to run anything else.

If you installed `mll-lab/laravel-graphql-playground` you can test the graphql endpoint at `http://localhost:8000/graphql-playground`. Run a simple query to check it all works:

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

We can also get a list of posts, with pagination information:

```graphql
{
  posts(page: 1) {
    data {
      id
      title
    }

    paginatorInfo {
      currentPage
      lastPage
    }
  }
}
```

## Mutations

Now let's add a mutation to create a post.

```graphql
input CreatePostInput {
  title: String!
  content: String!
}

extend type Mutation {
  createPost(input: CreatePostInput! @spread): Post!
    @create
    @can(ability: "create")
}
```

We use Lighthouse's `@create` directive to automatically get a create endpoint, and `@can` to create a policy at `app/Policies/PostPolicy.php`. The policy is blocked by default. So let's generate the new scaffolding:

```
php artisan modelarium:scaffold '*' --everything --lighthouse
```

You can change the policy to this to avoid needing to authenticate for this tutorial. This opens it for everyone:

```php
    public function create(?User $user): bool
    {
        return true;
    }
```

Here's a mutation query that will create your post:

```graphql
mutation {
  createPost(title: "Title", content: "My first mutation works") {
    id
    title
    content
    user {
      id
      name
    }
  }
}
```

## Data types

We want the titles of our posts to have between 10 and 50 characters, so let's create a new data type. By creating a new data type you ensure that there's a single point for validation, random generation, metadata etc for that type. This allows you to customize SQL row types, for example, and if any point in the future you need to modify the validation it's easy to do it.

Create it:

```
$ ./artisan modelarium:datatype title --basetype=string
Created title at modelarium-example/app/Datatypes/Datatype_title.php.
Created title test at modelarium-example/tests/Unit/titleTest.php.
Finished. You might want to run `composer dump-autoload`
Remember to add `#import types.graphql` to your `graphql/schema.graphql` file.
```

So add `#import types.graphql` to your `graphql/schema.graphql` file. `types.graphql` has all the types you generate in your application and is regenerated when a new one is created.

In `Datatypes/Datatype_title.php` we set this up. We're inheriting from `string`, so it's easy to just modify its parameters:

```php
class Datatype_title extends \Formularium\Datatype\Datatype_string
{
    public function __construct(string $typename = 'title', string $basetype = 'string')
    {
        $this->MIN_STRING_LENGTH = 15;
        $this->MAX_STRING_LENGTH = 50;
        parent::__construct($typename, $basetype);
    }
}
```

That's it, you can now use `Title` (note the upper case, as it's usual in graphql):

```graphql
type Post @migrationTimestamps {
  id: ID!
  title: Title!
  # ...
}
```

You should create data types before you use them, and ideally before you generate your scaffolding, since they might change the migrations, models etc. While Modelarium regenerates files, it's best to avoid unnecessary migrations converting rows.

If you needed something more complex you could override `public function validate($value, Model $model = null)`. Formularium [provides several validators](https://corollarium.github.io/Formularium/api-validators.html) that you can use, and you can always implement tests yourself.

## Frontend

Modelarium can generate frontend components too. Let's see how this works. We'll generate Vue components for the tutorial. We'll use this integrated with Laravel, but you can also output the scaffolding to a separate project. Start by adding UI support deps to the application:

```shell
# add laravel ui deps
composer require laravel/ui

# install ui deps
php artisan ui vue --auth

# install npm deps
npm install

# add npm deps for modelarium scaffolding
npm add prettier raw-loader vue-router
```

It's time to generate our Vue code now. Let's add some new directives to control rendering. The `@renderable` directive passes arguments to the frontend generator.

```graphql
type Post @migrationTimestamps {
  id: ID!
  title: String!
    @modelFillable
    @renderable(
      label: "Title"
      comment: "Please add a descriptive title"
      placeholder: "Type here"
      size: "large"
    )
  content: Text!
    @modelFillable
    @renderable(label: "Content", comment: "Your post contents")
  user: User!
    @belongsTo
    @migrationForeign(onDelete: "cascade", onUpdate: "cascade")
  comments: [Comment!]! @hasMany
}
```

Since we changed the schema, let's export the `Post` model again:

```shell
php artisan modelarium:scaffold Post --model --overwrite --lighthouse
```

If you think your Graphql files become too verbose with the extra directives, you can instead add the code to the Model itself, extending the parameters from the base class:

```php
class Post {
  /**
   * @return array
   */
  public static function getFields(): array
  {
    $data = parent::getFields();
    $data['title']['renderable'] = [
      'label' => 'Title',
      'comment' => 'Please add a descriptive title',
      'placeholder' => 'Type here',
    ];
    $data['content']['renderable'] = [
      'label' => 'Content',
      'comment' => 'Your post contents'
    ];
    return $data;
  }
}
```

Now let's export the actual frontend files. Let's pick a HTML/Bootstrap/Vue frontend:

```
artisan modelarium:frontend '\App\Models\Post' --framework=HTML --framework=Bootstrap --framework=Vue --overwrite --prettier
```

This will generate the component files in `resources/js/components/Post/`, ready for you to use in your Vue app.

### Setup Laravel Mix in 5 seconds

Here's a very quick and dirty documentation of how to setup Laravel with [Mix](https://laravel.com/docs/7.x/mix#running-mix) to use your components. Setup your [frontend](https://laravel.com/docs/7.x/frontend):

```shell
// Generate basic scaffolding...
php artisan ui vue

// Generate login / registration scaffolding...
php artisan ui vue --auth

npm install
```

To build it while developing:

```
npm run watch
```

## Authentication

By default Modelarium creates models in `app/Models` instead of `app`. For this to work with authentication you should change this in `config/auth.php`. You can also pass `--modelDir=app` if you prefer Laravel's default behavior.

```php
    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],
    ]
```
