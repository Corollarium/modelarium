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

## @eagerLoad

```graphql
"""
Eager load relationships using Laravel Model::$with();
"""
directive @eagerLoad (
    """
    If present, use this name as the with($name) parameter. Otherwise try to guess from field.
    """
    name: String

    """
    If present, append these values to the with($name) model class.
    """
    tables: [String!] 
) on FIELD_DEFINITION | OBJECT
```

## @frontendSkip

```graphql
"""
Do not generate frontend for this type
"""
directive @frontendSkip on OBJECT
```

## @hidden

```graphql
"""
Make this field hidden. It will not show up on introspection or queries.
"""
directive @hidden on FIELD_DEFINITION
```

## @migrationAlterTable

```graphql
"""
Alters a table on migration after it was created.
"""
directive @migrationAlterTable(
    """
    The commands to run, which will be prepended with 'ALTER TABLE tablename"
    """
    values: [String!]!
) on OBJECT
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

## @migrationFulltextIndex

```graphql
"""
Add a full text index to table
"""
directive @migrationFulltextIndex(
    """
    The fields to index. Must be an array even if it is just one field.
    """
    fields: [String!]!
) on OBJECT
```

## @migrationIndex

```graphql
"""
Generates a composed index on the database for a type
"""
directive @migrationIndex(
    """
    The list of fields to compose in the index
    """
    fields: [String!]!
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
directive @migrationUniqueIndex (
    """
    The list of fields to compose in the index
    """
    fields: [String!]
) on FIELD_DEFINITION | OBJECT
```

## @modelAccessor

```graphql
"""
Creates an accessor method in the class.
"""
directive @modelAccessor on FIELD_DEFINITION
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

## @modelSkip

```graphql
"""
Do not generate model (and seeder/factory/etc) for this type
"""
directive @modelSkip on OBJECT
```

## @morphedByMany

```graphql
"""
Handles the target of morphMany
"""
directive @morphedByMany on FIELD_DEFINITION
```

## @renderable

```graphql
"""
Generate renderable
"""
directive @renderable (
    """Label for this field"""
    label: String

    """Comment for this field"""
    comment: String

    """Should this field be used in show pages?"""
    show: Boolean

    """Is this field the title field for this object?"""
    title: Boolean
    
    """Should this field be used in the form? Default is true"""
    form: Boolean
    
    """Should this field be used in card components?"""
    card: Boolean

    """Should this field be used in table components?"""
    table: Boolean

    """Field size in render"""
    size: String

    # move to schemaRenderable()
    itemtype: String

    # move to typeRenderable()
    routeBase: String
    keyAttribute: String
    name: String
) on FIELD_DEFINITION | OBJECT
```

## @typeSkip

```graphql
"""
Do not process this type in Modelarium. Will not create models, migrations, etc
"""
directive @typeSkip on OBJECT | ENUM
```
