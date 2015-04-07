<?php

namespace Brick\Geo\Engine;

use Brick\Geo\Exception\GeometryEngineException;
use Brick\Geo\Exception\SQLite3Exception;
use Brick\Geo\Geometry;

/**
 * Database engine based on a SQLite3 driver.
 *
 * The spatialite extension must be loaded in this driver.
 */
class SQLite3Engine extends DatabaseEngine
{
    /**
     * @var \SQLite3
     */
    private $sqlite3;

    /**
     * @param \SQLite3 $sqlite3
     */
    public function __construct(\SQLite3 $sqlite3)
    {
        $this->sqlite3 = $sqlite3;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeQuery($query, array $parameters)
    {
        // Temporary set the error reporting level to 0 to avoid any warning.
        $errorReportingLevel = error_reporting(0);

        $statement = $this->sqlite3->prepare($query);

        // Restore the original error reporting level.
        error_reporting($errorReportingLevel);

        $errorCode = $this->sqlite3->lastErrorCode();

        if ($errorCode !== 0) {
            $exception = new SQLite3Exception($this->sqlite3->lastErrorMsg(), $errorCode);

            if ($errorCode === 1) {
                // SQL error cause by a missing function, this must be reported with a GeometryEngineException.
                throw GeometryEngineException::operationNotSupportedByDatabase($exception);
            } else {
                // Other SQLite3 error; we cannot trigger the original E_WARNING, so we throw this exception instead.
                throw $exception;
            }
        }

        $index = 1;

        foreach ($parameters as $parameter) {
            if ($parameter instanceof Geometry) {
                $statement->bindValue($index++, $parameter->asBinary(), SQLITE3_BLOB);
                $statement->bindValue($index++, $parameter->SRID(), SQLITE3_INTEGER);
            } else {
                if ($parameter === null) {
                    $type = SQLITE3_NULL;
                } elseif (is_int($parameter)) {
                    $type = SQLITE3_INTEGER;
                } elseif (is_float($parameter)) {
                    $type = SQLITE3_FLOAT;
                } else {
                    $type = SQLITE3_TEXT;
                }

                $statement->bindValue($index++, $parameter, $type);
            }
        }

        $result = $statement->execute();

        return $result->fetchArray(SQLITE3_NUM)[0];
    }
}
