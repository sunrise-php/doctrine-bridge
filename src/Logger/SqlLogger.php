<?php declare(strict_types=1);

/**
 * It's free open-source software released under the MIT License.
 *
 * @author Anatoly Fenric <anatoly@fenric.ru>
 * @copyright Copyright (c) 2020, Anatoly Fenric
 * @license https://github.com/sunrise-php/doctrine-bridge/blob/master/LICENSE
 * @link https://github.com/sunrise-php/doctrine-bridge
 */

namespace Sunrise\Bridge\Doctrine\Logger;

/**
 * Import classes
 */
use Doctrine\DBAL\Logging\SQLLogger as DoctrineLoggerInterface;
use Psr\Log\LoggerInterface as PsrLoggerInterface;

/**
 * Import functions
 */
use function array_pop;
use function microtime;
use function sprintf;

/**
 * SqlLogger
 */
final class SqlLogger implements DoctrineLoggerInterface
{

    /**
     * @var PsrLoggerInterface
     */
    private $psrLogger;

    /**
     * @var array
     */
    private $queries = [];

    /**
     * @param PsrLoggerInterface $psrLogger
     */
    public function __construct(PsrLoggerInterface $psrLogger)
    {
        $this->psrLogger = $psrLogger;
    }

    /**
     * Returns the assigned PSR-logger
     *
     * @return PsrLoggerInterface
     */
    public function getPsrLogger() : PsrLoggerInterface
    {
        return $this->psrLogger;
    }

    /**
     * {@inheritdoc}
     */
    public function startQuery($sql, ?array $params = null, ?array $types = null) : void
    {
        $this->queries[] = [
            'ts' => microtime(true),
            'sql' => $sql,
            'params' => $params,
            'types' => $types,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function stopQuery() : void
    {
        if (empty($this->queries)) {
            return;
        }

        $query = array_pop($this->queries);
        $elapsed = (microtime(true) - $query['ts']) * 1000;
        $text = sprintf('[%2.3fµs] %s', $elapsed, $query['sql']);

        $this->psrLogger->debug($text, [
            'params' => $query['params'],
            'types' => $query['types'],
        ]);
    }
}
