# Directives

This is about implementing new directives.

## Laravel

- run `php util/CreateDirective.php --name=[name] --processors=[targets]`
- edit the created files to implement them
- run `php composer.phar buildGraphql` to regenerate the code.
- applications will need to run `php artisan modelarium:publish`
