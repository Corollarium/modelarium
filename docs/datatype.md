# Datatype

Here's how to create a new Datatype in your application that works in your application. [Datatypes are created with Formularium](https://github.com/Corollarium/Formularium/).

## Laravel

Let's create a datatype called `Myname` that extends string. Run:

```
composer modelarium:datatype myname --basetype=string
```

this will generate a `app/Datatypes/Datatype_myname.php` and register its type in your application. The file will look like this:

```php
<?php declare(strict_types=1);

namespace App\Datatypes;

use Formularium\Model;
use Formularium\Exception\ValidatorException;

class Datatype_mytype extends \Formularium\Datatype_string
{
    public function __construct(string $typename = 'mytype', string $basetype = 'string')
    {
        parent::__construct($typename, $basetype);
    }

    /**
     * Returns a random valid value for this datatype, considering the validators
     *
     * @param array $validators
     * @throws Exception If cannot generate a random value.
     * @return mixed
     */
    public function getRandom(array $validators = [])
    {
        throw new ValidatorException('Not implemented');
    }

    /**
     * Checks if $value is a valid value for this datatype considering the validators.
     *
     * @param mixed $value The value you are checking.
     * @param Model $model The entire model, if your field depends on other things of the model. may be null.
     * @throws Exception If invalid, with the message.
     * @return mixed The validated value.
     */
    public function validate($value, Model $model = null)
    {
        throw new ValidatorException('Not implemented');
    }
}
```

As you can see, there are only two methods to be implemented. `getRandom` should generate a random valid value, and `validate` check if data matches the expected values.

For a concrete example, let's suppose that `mytype` consists of any string that is only composed of `abcdef` characters and that is at least 4 characters long and at most `23` characters long.

```php
public function getRandom(array $validators = [])
{
  $length = rand(4, 23);
  $characters = 'abcdef';
  $charactersLength = strlen($characters);
  $randomString = '';
  for ($i = 0; $i < $length; $i++) {
      $randomString .= $characters[rand(0, $charactersLength - 1)];
  }
  return $randomString;
}

public function validate($value, Model $model = null)
{
  if (preg_match('/^[abcdefABCDEF]+$/', $string) === false) {
    throw new ValidatorException('Only "abcdef" characters should be used.');
  }
  // minimum length, use an existing validator
  MinLength::validate($value, ['value' => '4'], $this, $model);

  // minimum length, use an existing validator
  MaxLength::validate($value, ['value' => '23'], $this, $model);

  return mb_strtolower($value);
}
```

Things to notice:

1. Throw a `ValidatorException` whenever the value is invalid. The message will be returned to the user.
1. Use existing validators whenever possible. They make your life easier and are pre-tested. You can use multiple validators. There's a `Regex` validator too, but we're using `preg_match` directly to show how to make your own test.
1. You have to return the value, which can be converted to a standard. In this case we are making sure the value is converted to lower case.
1. `Model $model` can be used when your validation depends on other fields.
