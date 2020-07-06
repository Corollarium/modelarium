# Datatype

Here's how to create a new Datatype in your application that works

## Laravel

You'll need [Formularium to create new data types](https://github.com/Corollarium/Formularium/).

Let's create a datatype called `Myname` that extends string. Run:

```
composer formularium:datatype myname --basetype=string
```

this will generate a `app/Datatypes/Datatype_myname.php` file that looks more or less like this:

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
     * @param array $validators The arguments for your validation.
     * @param Model $model The entire model, if your field depends on other things of the model. may be null.
     * @throws Exception If invalid, with the message.
     * @return mixed The validated value.
     */
    public function validate($value, array $validators = [], Model $model = null)
    {
        throw new ValidatorException('Not implemented');
    }
}
```

As you can see, there are only two methods to be implemented. `getRandom` should generate a random valid value, and `validate` check if data matches the expected values.

For a concrete example, let's suppose that `mytype` consists of any string that is only composed of `abcdef` characters and that is at least 4 characters long and at most `23` characters long.

```php
public function validate($value, array $validators = [], Model $model = null)
{

}
```
