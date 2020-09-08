# Relationships

Modelarium handles relationships pretty automatically, making seeds and factories work trivially and providing an easy way to pick relationships in forms.

# Frontend

## Single selector

You can use the Selector component to automatically pick relationships in forms. This works well if the number of relationships is reasonably small (in the tens), since it uses `<select>` and loads data just once.

This is fully automated by Modelarium, and as long as you declare the relationship and a `@modelFillable` directive it will be work as expected. If you need to customize the

## Autocomplete

## Multiple

# Customizing relationship data types

Relationship datatypes are generated automatically for you. They all implement the `Datatype_relationship` class through a [special factory](https://github.com/Corollarium/modelarium/blob/master/Modelarium/Datatypes/RelationshipFactory.php).

When parsing GraphQL, Modelarium creates a new data type for each relationship with this name: `relationship[:inverse?]:[mode]:[source]:[target]`. Example: `relationship:inverse:1N:post:user`.

- [:inverse?] if present, this means it's an inverse relationship, otherwise it's a direct relationship. We define a direct relationship as the one going from the original model (BelongsTo) to its target (HasMany) and the inverse as its opposite. For example, if a `User` can make `Posts`, `User -> Post` is the direct relationship and `Post -> User` is the inverse relationship.

- [mode] the cardinality of relationship. The possible types are declared as constants in [RelationshipFactory](https://github.com/Corollarium/modelarium/blob/master/Modelarium/Datatypes/RelationshipFactory.php): RELATIONSHIP_ONE_TO_ONE, RELATIONSHIP_ONE_TO_MANY, RELATIONSHIP_MANY_TO_MANY, MORPH_ONE_TO_ONE, MORPH_ONE_TO_MANY, MORPH_MANY_TO_MANY.

- [source]: the origin relationship model.

- [target]: the origin relationship model.
