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

namespace Laudis\Neo4j;

use Ds\Map;
use Ds\Vector;
use InvalidArgumentException;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Contracts\DriverInterface;
use Laudis\Neo4j\Contracts\FormatterInterface;
use Laudis\Neo4j\Contracts\SessionInterface;
use Laudis\Neo4j\Contracts\TransactionInterface;
use Laudis\Neo4j\Databags\SessionConfiguration;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Databags\TransactionConfig;
use function sprintf;

/**
 * @template T
 * @implements ClientInterface<T>
 */
final class Client implements ClientInterface
{
    /** @var Map<string, DriverInterface> */
    private Map $driverPool;
    private string $defaultDriverAlias;
    private FormatterInterface $formatter;

    /**
     * @param FormatterInterface<T>        $formatter
     * @param Map<string, DriverInterface> $connectionPool
     */
    public function __construct(Map $connectionPool, string $defaultConnectionAlias, FormatterInterface $formatter)
    {
        $this->driverPool = $connectionPool;
        $this->defaultDriverAlias = $defaultConnectionAlias;
        $this->formatter = $formatter;
    }

    public function run(string $query, iterable $parameters = [], ?string $alias = null)
    {
        return $this->startSession($alias)->run($query, $parameters);
    }

    public function runStatement(Statement $statement, ?string $alias = null)
    {
        return $this->startSession($alias)->runStatement($statement);
    }

    public function runStatements(iterable $statements, ?string $alias = null): Vector
    {
        return $this->startSession($alias)->runStatements($statements);
    }

    public function openTransaction(
        ?iterable $statements = null,
        ?string $alias = null
    ): TransactionInterface {
        return $this->startSession($alias)->beginTransaction($statements);
    }

    public function withFormatter(FormatterInterface $formatter): ClientInterface
    {
        return new self($this->driverPool, $this->defaultDriverAlias, $formatter);
    }

    public function getFormatter(): FormatterInterface
    {
        return $this->formatter;
    }

    public function getDriver(?string $alias): DriverInterface
    {
        $key = $alias ?? $this->defaultDriverAlias;
        if (!$this->driverPool->hasKey($key)) {
            $key = sprintf('The provided alias: "%s" was not found in the connection pool', $key);
            throw new InvalidArgumentException($key);
        }

        return $this->driverPool->get($key);
    }

    public function startSession(?string $alias = null, ?SessionConfiguration $config = null): SessionInterface
    {
        return $this->getDriver($alias)->createSession($config)->withFormatter($this->formatter);
    }

    public function writeTransaction(callable $tsxHandler, ?string $alias = null, ?TransactionConfig $config = null)
    {
        return $this->startSession($alias)->writeTransaction($tsxHandler, $config);
    }

    public function readTransaction(callable $tsxHandler, ?string $alias = null, ?TransactionConfig $config = null)
    {
        return $this->startSession($alias)->readTransaction($tsxHandler, $config);
    }

    public function transaction(callable $tsxHandler, ?string $alias = null, ?TransactionConfig $config = null)
    {
        return $this->startSession($alias)->transaction($tsxHandler, $config);
    }
}
