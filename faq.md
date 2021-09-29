# FAQ

## How to make a `type` in GraphQL not generate a model?

Sometimes you have `types` in GraphQL that should not create a model class or migration in PHP. To skip it, just add a `@typeSkip` directive to the type like this:

```graphql
type AuthPayload @typeSkip {
  access_token: String
  refresh_token: String
  expires_in: Int
  token_type: String
  user: User
}
```

## What code formatting standards do you use?

Generated code is optionally ran through ESlint and Prettier, so it will follow your conventions. You can of course run it through other formatters as well.

You can enable these on the Laravel binding with `--eslint --prettier` as arguments, or change these settings on `config/modelarium.php` instead:

```php
        /*
         * Should we run prettier after generating the components?
         */
        "prettier" => true,

        /*
         * Should we run eslint after generating the components?
         */
        "eslint" => true,
```

## I'm getting errors with foreign keys not being created.

If you get an error like this:

```
SQLSTATE[HY000]: General error: 1005 Can't create table `mydatabase`.`projects` (errno: 150 "Foreign key constraint is incorrectly formed") (SQL: alter table `projects` add constraint `projects_account_id_foreign` foreign key (`account_id`) references `accounts` (`id`))
```

While GraphQL doesn't care about the order of the fields, SQL does and you cannot create a foreign index before creating the target table. Tables are created in the same order that types appear in the GraphQL files, so reorder the fields to make sure that they appear in order of dependencies, or sort manually the migration files.

## How can I get the list of policy permissions of a model?

Since you can use `@can` use a policy, you may also need to get these values in your application, to show an 'Edit' button or similar.

So, to remember, you declare a policy on a mutation or query like this:

```graphql
extend type Mutation {
    createPost(input: CreateExampleInput! @spread): Example! @create @can(ability: "create")
```

To fetch policies in your application, add a `can` field to your type. It should `@migrationSkip` since we are not creating it in the database:

```graphql
type Example {
  can: [Can!] @migrationSkip
}
```

The `Can` type is automatically imported:

```graphql
type Can {
  ability: String!
  value: Boolean!
}
```

Now, on the `BaseModel` class there's a `getCanAttribute` method that returns the abilities and that you can override on your `Model` class:

```php
class BaseExample extends Model {
    public function getCanAttribute() {
        $policy = new ExamplePolicy();
        $user = Auth::user();
        return [
            [ 'ability' => 'create', 'value' => $policy->create($user) ],
            [ 'ability' => 'update', 'value' => $policy->update($user, $this) ]
            // add other policies you want here
        ];
    }
}
```
