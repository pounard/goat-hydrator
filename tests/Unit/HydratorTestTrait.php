<?php

declare(strict_types=1);

namespace Goat\Hydrator\Tests\Unit;

use GeneratedHydrator\Configuration as GeneratedConfiguration;
use Goat\Hydrator\HydratorMap;

trait HydratorTestTrait
{
    private function createTemporaryDirectory()
    {
        return \sys_get_temp_dir().'/'.\uniqid('test-');
    }

    private function createGeneratedHydratorConfiguration() /* : GeneratedConfiguration */
    {
        return new GeneratedConfiguration();
    }

    private function createHydratorMapInstance() /* : HydratorMap */
    {
        return new HydratorMap(
            $this->createGeneratedHydratorConfiguration()
        );
    }
}
