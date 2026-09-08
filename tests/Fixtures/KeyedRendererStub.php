<?php

declare(strict_types=1);

namespace CoolMS\DtmplBundle\Tests\Fixtures;

use CoolMS\Dtmpl\Widget\WidgetRendererInterface;
use Stringable;

/** A renderer that declares its key as a constant, the way production ones do. */
final class KeyedRendererStub implements WidgetRendererInterface
{
    public const string KEY = 'stub:keyed';

    public string $key { get => self::KEY; }

    public function __invoke(array $context, array $params = []): ?Stringable
    {
        return null;
    }
}
