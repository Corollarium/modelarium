# Getting started tutorial: a Laravel Graphql backend application in two minutes

So let's create a web application using Laravel and Lighthouse with a Graphql endpoint. A [full source code ready to use is available](https://github.com/Corollarium/modelarium-example).

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
artisan modelarium:init
```

At this point you are ready to go, just write your schema. We extend Graphql SDL to support `#import file` syntax, similar to other projects. Let's create a new model `Post`. Add a line `#import data/post.graphql` to `schema.graphql`, then create a file in `graphql/data/post.graphql` with the following:

```graphql
type Post @migrationTimestamps {
  id: ID!
  title: String! @MinLength(value: 5) @MaxLength(value: 25)
  content: Text! @MinLength(value: 15) @MaxLength(value: 1000)
  user: User!
    @belongsTo
    @migrationForeign(onDelete: "cascade", onUpdate: "cascade")
}
```

`Post` has a series of directives to control its behavior, such as creating timestamps for the database entry, declaring minimum and maximum lengths for the fields and foreign keys.

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

TODO: filling user_id.

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

## Frontend

Modelarium can generate frontend components too. Let's see how this works. We'll generate Vue components for the tutorial. Start by adding UI support deps to the application:

```shell
# add laravel ui deps
composer require laravel/ui

# install ui deps
php artisan ui vue --auth

# install npm deps
npm install

# add prettier to generate well formatted code
npm add prettier
```

It's time to generate our Vue code now. Let's add some new directives to control rendering:

```graphql
type Post @migrationTimestamps {
  id: ID!
  title: String!
    @modelFillable
    @MinLength(value: 5)
    @MaxLength(value: 25)
    @renderable(
      label: "Title"
      comment: "Please add a descriptive title"
      placeholder: "Type here"
      size: "large"
    )
  content: Text!
    @modelFillable
    @MinLength(value: 15)
    @MaxLength(value: 1000)
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

If you think your Graphql files become too verbose with the extra directives, you can add the code to the Model itself instead, extending the parameters from the base class:

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
artisan modelarium:frontend '\App\Post' --framework=HTML --framework=Bootstrap --framework=Vue --overwrite --prettier
```

This will generate the files in `resources/js/components/Post/`, ready for you to use.

## Authentication