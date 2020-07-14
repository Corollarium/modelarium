# FAQ

## How do I return the list of policies of a model?

Since you can use `@can` use a policy, you may also need to get these values in your application, to show an 'Edit' button or similar.

So, to remember, you declare a policy on a mutation or query like this:

```graphql
extend type Mutation {
    createPost(input: CreateExampleInput! @spread): Example! @create @can(ability: "create")
```

To fetch policies in your application, add a `can` field to your type. It should `@migrationSkip` since we are not creating it in the database:

```graphql
type Example {
  can: [Can!] @skipMigration
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
