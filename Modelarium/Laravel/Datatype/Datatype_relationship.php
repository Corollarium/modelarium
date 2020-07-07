<?php declare(strict_types=1);

namespace Formularium\Datatype;

use Formularium\Exception\ValidatorException;
use Formularium\Field;
use Formularium\Model;

abstract class Datatype_relationship extends \Formularium\Datatype
{
    public const RELATIONSHIP = "RELATIONSHIP";
    public const RELATIONSHIP_ONE_TO_ONE = "RELATIONSHIP_ONE_TO_ONE";
    public const RELATIONSHIP_ONE_TO_MANY = "RELATIONSHIP_ONE_TO_MANY";
    public const RELATIONSHIP_MANY_TO_MANY  = "RELATIONSHIP_MANY_TO_MANY";

    /**
     * @var string
     */
    protected $relationship = '';

    /**
     * @var string
     */
    protected $source = '';

    /**
     * @var string
     */
    protected $target = '';

    public function __construct(string $typename = 'association', string $basetype = 'association')
    {
        parent::__construct($typename, $basetype);
    }

    public function getDefault()
    {
        return 0;
    }

    public function getRandom(array $params = [])
    {
        throw new ValidatorException('Implementation defined');
    }

    public function validate($value, Model $model = null)
    {
        throw new ValidatorException('Invalid boolean value');
    }
}
