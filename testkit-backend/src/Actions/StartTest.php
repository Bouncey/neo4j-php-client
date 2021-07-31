<?php

declare(strict_types=1);

/*
 * This file is part of the Laudis Neo4j package.
 *
 * (c) Laudis technologies <http://laudis.tech>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Laudis\Neo4j\TestkitBackend\Actions;

use Laudis\Neo4j\TestkitBackend\Contracts\ActionInterface;
use Psr\Container\ContainerInterface;

final class StartTest implements ActionInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function handle(array $data): array
    {
        if ($this->container->has($data['testName'] ?? '')) {
            return ['name' => 'RunTest'];
        }

        return ['name' => 'SkipTest', 'data' => ['reason' => 'Not Implemented']];
    }
}
