# Directives



Directives supported by Modelarium.

## @casts

```graphql
"""
Casts type
"""
directive @casts(
    """
    The value
    """
    type: String!
) on FIELD_DEFINITION
```

## @migrationDefaultValue

```graphql
"""
Default value for database column
"""
directive @migrationDefaultValue(
    """
    The value
    """
    value: String!
) on FIELD_DEFINITION
```

## @migrationForeign

```graphql
"""
Foreign keys
"""
directive @migrationForeign(
    """
    What field it references
    """
    references: String

    """
    What table it references
    """
    on: String

    """
    What to do onDelete
    """
    onDelete: String

    """
    What to do on Update
    """
    onUpdate: String
) on FIELD_DEFINITION
```

## @migrationIndex

```graphql
"""
Generates a composed index on the database for a type
"""
directive @index(
    """
    The list of fields to compose in the index
    """
    fields: [String]!
) on OBJECT
```

## @migrationRememberToken

```graphql
"""
Specifies that remember me tokens should be created on DB.
"""
directive @migrationRememberToken on OBJECT
```

## @migrationSkip

```graphql
"""
Do not add this field to the migration
"""
directive @migrationSkip on FIELD_DEFINITION
```

## @migrationSoftDeletes

```graphql
"""
Specifies a soft delete mode for this type
"""
directive @migrationSoftDeletes on OBJECT
```

## @migrationSpatialIndex

```graphql
"""
Generates a migrationSpatialIndex
"""
directive @migrationSpatialIndex(
    """
    The field for the index
    """
    field: String!
) on OBJECT
```

## @migrationTimestamps

```graphql
"""
Generates a timestamps columns for a type
"""
directive @migrationTimestamps on OBJECT
```

## @migrationUniqueIndex

```graphql
"""
Generates a unique index on the database for that field
"""
directive @migrationUniqueIndex on FIELD_DEFINITION
```

## @minLength

```graphql
"""
Base class to extend on model.
"""
directive @minLength(
    """
    The base class name with namespace
    """
    value: Int!
) on OBJECT
```

## @modelExtends

```graphql
"""
Base class to extend on model.
"""
directive @modelExtends(
    """
    The base class name with namespace
    """
    class: String!
) on OBJECT
```

## @modelFillable

```graphql
"""
Field is added to Model::$fillable
"""
directive @modelFillable on FIELD_DEFINITION
```

## @modelHidden

```graphql
"""
Field is added to Model::hidden
"""
directive @modelHidden on FIELD_DEFINITION
```

## @modelMustVerifyEmail

```graphql
"""
Use a MustVerifyEmail trait on a model
"""
directive @modelMustVerifyEmail on OBJECT
```

## @modelNotifiable

```graphql
"""
Use a Notifiable trait on a model
"""
directive @modelNotifiable on OBJECT
```

## @renderable

```graphql
"""
Generate renderable
"""
directive @renderable on FIELD_DEFINITION
```
