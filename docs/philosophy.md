---
nav_order: 2
---

# Philosophy

The idea behind Modelarium is to avoid tedious coding and increase safety.

## Why web programming became tedious and error prone

Imagine you are changing a model and adding a single new field. The usual way to do it in Laravel is more or less this:

1. Add a migration.
2. Review the model file.
3. Review the `FormRequest` file if you are using it.
4. Review controllers.
5. Review seeders.
6. Review factories.
7. Review all affected `Resource` files.
8. Review your TypeScript interfaces/classes to add this field to them.
9. Review your Blade/React/Vue files to add the field to the forms.
10. Review your React/Vue/JS code to add frontend validation.
11. Review your view pages to show the field.
12. Review your backend tests to add the field wherever necessary.
13. Review your backend tests to check validation of this field on any endpoints that use it.
14. Review your frontend unit/integration tests to add the field wherever necessary.
15. Review your end to end tests to add the field wherever necessary.

This is a lot of work, very prone to typos, bugs, or forgetting something. Most of it is easy to automatize. That's why Modelarium was created.

## What Modelarium does to solve it

Modelarium generates scaffolding for your project based on a GraphQL description of your API. It can create the entire backend and frontend files, leaving only the logic and design for you.

- **generates backend scaffolding for you**: model, database migration, seed, factory, events, policies. Everything from your graphql descriptions. No more tedious creation of files and repeated code.
- **datatypes**: your data is more than strings. You have models for your structures, so have datatypes for your fields. Create the correct database fields without thinking about it. Uses [Formularium for datatypes](https://corollarium.github.io/Formularium/).
- **validation**: transparent data validation made automatically based on your datatypes. Your data is always safely validated.
- **no performance penalty**: other than data validation all data is generate at development time. It's just automatic scaffolding, everything is just as fast as before.
- **no new standards**: code is generated following existing standards for existing tools. Generate code and use it freely. Nothing is tied down.
- **frontend generation**: get HTML forms, cards, lists and views generated for you automatically with your favorite CSS framework: Bootstrap, Bulma, Materialize, Buefy with simple declarations and a standard description. You can tweak details afterwards -- it's just code.
- **reactive frameworks**: Get Vue and React components like cards, forms, lists and tables, ready to use with their Graphql calls.
- **integration with Laravel and Lighthouse**. Get GraphQL endpoints automatically.

What it doesn't do:

- magic. you still have to write your code logic to process requests in the backend, like in mutations or special conditions in your models. You also get only a basic design, so pretty CSS is up to you.
- REST endpoints generation. At this point only GraphQL is supported through Laravel and Lighthouse. REST endpoints might come later.
- other backend frameworks. Currently only Laravel is supported.

## Why data types?

Even in strongly typed languages we often use just the base types, such as float or string, for different things. For example, we use a string for an email field, or a float for a velocity field. This is not rare [and a space probe was once lost due to different units used in computation](https://www.nasa.gov/centers/ames/research/exploringtheuniverse/exploringtheuniverse-computercheck.html).

When we're dealing with user input, particularly on the web when it's easy to make automated requests, we want site-wide consistency. This means that a `title` and a `subtitle` field, though both are strings, may have different expectations as to minimum length, required-ness or formatting.

This is why Modelarium favors using types. If you declare a field with a type it's easy to validate it anywhere in the application, to generate code automatically, to generate valid random values and to generate frontend components.

## Why GraphQL?

GraphQL is becoming a well established standard, and provides an extensible domain-specific language that can declare types, fields and directives. We take advantage of that to avoid creating yet-another-standard. Besides, you write one code that describes the endpoints and the backend data at once.

It becomes a single source of truth that includes a lot of information that can be used for automatic parsing.

## Why a PHP implementation?

PHP [still has an enormous lead in server-side programming languages for the web as of 2021](https://arstechnica.com/gadgets/2021/09/php-maintains-an-enormous-lead-in-server-side-programming-languages/).

Modelarium can generate code for JS/TS as well, which is required for frontend. The same code can be used for a backend JS/TS implementation.

Other target languages can be easily added.

## Why generating code?

Modelarium takes the approach of generating PHP and JS code and saving it to editable files at development-time instead of handling it at run-time.

It does this to [avoid new standards](https://xkcd.com/927/). If it handled all code internally it'd need ways to customize this code, and then you'd have an entirely new framework -- perhaps two, one for the frontend and another one for the backend. Nobody wants yet another frontend.

By generating human editable code which uses existing frameworks you don't have to learn anything, and you can use all the knowledge and tools that already exist and you are used to. Since it generates ordinary code for other frameworks it does not add another abstraction level or performance penalty. It's all done at coding time.
