# Philosophy

The idea behind Modelarium is to avoid tedious coding and increase safety.

## Why datatypes?

Even in strongly typed languages we often use base types, such as float or string, for different things. This is not rare [and a space probe was once lost due to different units used in computation](https://www.nasa.gov/centers/ames/research/exploringtheuniverse/exploringtheuniverse-computercheck.html). When we're dealing with user input, particularly on the web when it's easy to make automated requests, we want site-wide consistency. This means that a `title` and a `subtitle` field, though both are strings, may have different expectations as to minimum length, requiredness or formatting.

This is why Modelarium favors using types. If you declare a field with a type it's easier to validate it anywhere in the application, to generate valid random values and to generate frontend components.

## Why GraphQL?

GraphQL is becoming a well established standard, and provides an extensible domain-specific language that can declare types, fields and directives. We take advantage of that to avoid creating yet-another-standard. Besides, you write one code that describes the endpoints and the backend data at once.

## Why generating code?

Modelarium takes the approach of generating PHP and JS code instead of handling it all internally.

It does this to [avoid new standards](https://xkcd.com/927/). If it handled all code internally it'd be necessary to customize this code, and then you'd have an entirely new framework -- perhaps two, one for the frontend and another one for the backend. By generating code using existing frameworks you don't have to learn anything and you can use all the knowledge and tools that already exist. Since it generates ordinary code following for other frameworks it does not add another abstraction level or performance penalty. It's all done at coding time.
