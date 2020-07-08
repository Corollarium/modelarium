# Laravel documentation

Here's an overview of what scaffolding is done for Laravel.

## Migrations

Migration file is generated for all types. Migration specific directives start with `@migration`. We try to support [most of the migration API](https://laravel.com/docs/7.x/migrations). If you miss anything send a PR or open an issue.

The `Datatype` classes declare a `getLaravelSQLType()` method, which maps it to the SQL migration code.

Any changes in the type will generate a `_patch_` migration. As of now patch files are generated but we do not generate the patch code. This is planned for a later version.

## Factories

A basic stub for a factory is generated for all types. It calls `Model::getRandomData()` to generate good fake data.

## Seeds

Seeds call `factory::create()` for the model. `DatabaseSeeder.php` is generated if you set the overwrite flag or the file does not exist.

## Models

Since it's likely you'll need to write specific code in your Model class, we structure models in two classes and files: `NameBase.php` and `Name.php`. `Name` inherits from `NameBase`. You should leave `NameBase` unedited, since it will ge automatically generate and updated when your type declarations change, and implement or override anything you need on the `Name` class.

## Policies

Policies are generated for all `@can` directives of your Graphql, in a separate file for each model. They return `false` by default, meaning everything is blocked.

## Events

Event classes are generated for each `@event` directive.

## New datatypes and validators

Run `php artisan modelarium:validator` and `php artisan modelarium:datatype` to generate scaffolding for validators and datatypes. They are created at `app/Validators` and `app/Datatypes` respectively, and automatically registered to be used in Graphql.
