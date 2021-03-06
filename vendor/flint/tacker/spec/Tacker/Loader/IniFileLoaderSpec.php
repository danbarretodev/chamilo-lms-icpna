<?php

namespace spec\Tacker\Loader;

use Prophecy\Argument;
use Symfony\Component\Config\FileLocator;

class IniFileLoaderSpec extends \PhpSpec\ObjectBehavior
{
    /**
     * @param Tacker\ResourceCollection $resources
     */
    function let($resources)
    {
        $locator = new FileLocator(array(__DIR__ . '/../Fixtures/ini'));

        $this->beConstructedWith($locator, $resources);
    }

    function it_supports_inherited_configs()
    {
        $this->load('inherit.ini')->shouldReturn(array(
            'hello' => 'world',
        ));
    }
}
