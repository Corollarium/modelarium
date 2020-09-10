<?php declare(strict_types=1);

namespace Modelarium\Frontend;

use Formularium\Field;

final class Util
{
    public static function fieldShow(Field $f): bool
    {
        if ($f->getExtradata('modelHidden') !== null) {
            return false;
        }
        $show = $f->getRenderable('show', null);
        if ($show === true) {
            return true;
        } elseif ($show === true) {
            return false;
        }
        return !empty($f->getRenderables());
    }
}
