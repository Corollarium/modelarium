---
nav_order: 4
---

# Relationships

Modelarium handles relationships pretty automatically, making seeds and factories work trivially and providing an easy way to pick relationships in forms.

## Declaring relationships

Declaring relationships is done through simple directives. This is 100% compatible with [LighthousePHP](https://lighthouse-php.com) and follows the same structure of [Eloquent](https://laravel.com/docs/8.x/eloquent-relationships)

### One-to-one

Example of a one-to-one relationship:

```graphql
type User {
  id: ID!
  phone: Phone! @hasOne
}

type Phone {
  id: ID!
  user: User!
    @belongsTo
    @migrationForeign(onDelete: "cascade", onUpdate: "cascade")
}
```

## One-to-many

```graphql
type Post {
  id: ID!
  comments: [Comment!]! @hasMany
}

type Comment {
  id: ID!
  post: Post!
    @belongsTo
    @migrationForeign(onDelete: "cascade", onUpdate: "cascade")
}
```

## Many-to-many

Many-to-many relationships will create an auxiliary table.

```graphql
type User {
  id: ID!
  roles: [Role!]! @belongsToMany
}

type Role {
  id: ID!
  users: [User!]! @belongsToMany
}
```

### Polymorphic one-to-one

```graphql
type Post {
  id: ID!
  name: String!
  image: Image! @morphOne
}

type User {
  id: ID!
  name: String!
  image: Image @morphOne
}

union Imageable = Post | User

type Image {
  id: ID!
  url: String!
  imageable: Imageable! @morphTo
}
```

### Polymorphic one-to-many

```graphql
type User {
  id: ID!
  name: String!
  images: [Image!] @morphMany
}

type Post {
  images: [Image!] @morphMany
}

type Image {
  id: ID!
  imageable: Imageable! @morphTo
}

union Imageable = Post | User
```

### Polymorphic many-to-many

```graphql
type Post {
  id: ID!
  name: String!
  tags: [Tag!] @morphToMany
}

type Video {
  id: ID!
  name: String!
  tags: [Tag!] @morphToMany
}

type Tag {
  id: ID!
  name: String!
  taggable: [Taggable!]! @morphedByMany
}

union Taggable = Video | Post
```

## Frontend implementation

### Single selector

You can use the Selector component to automatically pick relationships in forms. This works well if the number of relationships is reasonably small (in the tens), since it uses `<select>` and loads data just once.

This is fully automated by Modelarium, and as long as you declare the relationship and a `@modelFillable` directive it will be work as expected. If you need to customize the

### Autocomplete

TODO

### Multiple

TODO

# Customizing relationship data types

Relationship datatypes are generated automatically for you. They all implement the `Datatype_relationship` class through a [special factory](https://github.com/Corollarium/modelarium/blob/master/Modelarium/Datatypes/RelationshipFactory.php).

When parsing GraphQL, Modelarium creates a new data type for each relationship with this name: `relationship[:inverse?]:[mode]:[source]:[target]`. Example: `relationship:inverse:1N:post:user`.

- [:inverse?] if present, this means it's an inverse relationship, otherwise it's a direct relationship. We define a direct relationship as the one going from the original model (BelongsTo) to its target (HasMany) and the inverse as its opposite. For example, if a `User` can make `Posts`, `User -> Post` is the direct relationship and `Post -> User` is the inverse relationship.

- [mode] the cardinality of relationship. The possible types are declared as constants in [RelationshipFactory](https://github.com/Corollarium/modelarium/blob/master/Modelarium/Datatypes/RelationshipFactory.php): RELATIONSHIP_ONE_TO_ONE, RELATIONSHIP_ONE_TO_MANY, RELATIONSHIP_MANY_TO_MANY, MORPH_ONE_TO_ONE, MORPH_ONE_TO_MANY, MORPH_MANY_TO_MANY.

- [source]: the origin relationship model.

- [target]: the origin relationship model.
