<?php

declare(strict_types=1);

namespace CoolMS\DtmplBundle\Tests\Fixtures;

use CoolMS\Dtmpl\Widget\WidgetRendererInterface;
use Stringable;

/**
 * A renderer with no KEY constant.
 *
 * The pass must still register it -- an application outside this repository
 * may hold renderers written before the constant existed, and the registry's
 * un-keyed path is what keeps them working.
 */
final class UnkeyedRendererStub implements WidgetRendererInterface
{
    public string $key { get => 'stub:unkeyed'; }

    public function __invoke(array $context, array $params = []): ?Stringable
    {
        return null;
    }
}
