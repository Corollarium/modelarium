---
layout: default
title: Advanced topics
nav_order: 3
---

# Advanced topics for Modelarium and Laravel

This continues [the getting started tutorial](./laraveltutorial.md).

## Authentication

There's nothing specific to Modelarium about authentication, and all the usual methods of authenticating will work. But this is a tutorial, so let's show how to add authentication to our app.

By default Modelarium creates models in `app/Models` instead of `app`. For this to work with authentication you should change this in `config/auth.php`. You can also pass `--modelDir=app` if you prefer Laravel's default behavior.

```php
    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],
    ]
```

## Relationships
