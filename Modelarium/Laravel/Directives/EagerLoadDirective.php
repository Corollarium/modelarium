<?php declare(strict_types=1);

namespace Modelarium\Laravel\Directives;

use Modelarium\Datatypes\Datatype_relationship;
use Modelarium\Datatypes\RelationshipFactory;
use Modelarium\Exception\DirectiveException;
use Modelarium\Laravel\Targets\ModelGenerator;
use Modelarium\Laravel\Targets\Interfaces\ModelDirectiveInterface;
use Modelarium\Parser;

class EagerLoadDirective implements ModelDirectiveInterface
{
    public static function processModelTypeDirective(
        ModelGenerator $generator,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): void {
        $target = Parser::getDirectiveArgumentByName($directive, 'tables', []);

        foreach ($target as $t) {
            $generator->with[] = $t;
        }
    }

    public static function processModelFieldDirective(
        ModelGenerator $generator,
        \GraphQL\Type\Definition\FieldDefinition $field,
        \Formularium\Field $fieldFormularium,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): void {
    }

    /**
     * Undocumented function
     *
     * @param ModelGenerator $generator
     * @param \GraphQL\Type\Definition\FieldDefinition $field
     * @param \GraphQL\Language\AST\DirectiveNode $directive
     * @return?\Formularium\Datatype The relationship datatype name. If this directive does not
     * handle the datatype, just return an empty string.
     *
     */
    public static function processModelRelationshipDirective(
        ModelGenerator $generator,
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\DirectiveNode $directive,
        \Formularium\Datatype $datatype = null
    ): ?\Formularium\Datatype {
        $target = Parser::getDirectiveArgumentByName($directive, 'name');

        if (!$target) {
            if (!$datatype) {
                throw new DirectiveException("@eagerLoad must be placed after the relationship directive (e.g. @belongsTo, @hasMany etc)");
            }
            if (!($datatype instanceof Datatype_relationship)) {
                throw new DirectiveException("@eagerLoad got a datatype that is not a relationship");
            }

            $targetSingle = mb_strtolower($generator->getInflector()->singularize($datatype->getTarget()));
            $targetPlural = $datatype->getTargetTable();
            switch ($datatype->getRelationship()) {
                case RelationshipFactory::RELATIONSHIP_ONE_TO_ONE:
                case RelationshipFactory::MORPH_ONE_TO_ONE:
                    $target = $targetSingle;
                    break;
                case RelationshipFactory::RELATIONSHIP_ONE_TO_MANY:
                case RelationshipFactory::MORPH_ONE_TO_MANY:
                    $target = $datatype->getIsInverse() ? $targetSingle : $targetPlural;
                    break;
                case RelationshipFactory::RELATIONSHIP_MANY_TO_MANY:
                case RelationshipFactory::MORPH_MANY_TO_MANY:
                    $target = $targetPlural;
                    break;
            }
        }
        $generator->with[] = $target;
        
        return null;
    }
}
