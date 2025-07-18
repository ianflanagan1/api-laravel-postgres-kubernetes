<?php

declare(strict_types=1);

namespace App\Traits;

use App\DTOs\ApiError;
use App\Enums\ApiErrorCode;
use App\Services\ApiResponseBuilder;
use App\Services\LogService;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

trait HandlesDbErrors
{
    // https://www.ibm.com/docs/en/i/7.6.0?topic=codes-listing-sqlstate-values
    // https://www.postgresql.org/docs/current/errcodes-appendix.html
    protected const int SQLSTATE_FOREIGN_KEY_CONSTRAINT = 23503;

    protected const int SQLSTATE_UNIQUE_CONSTRAINT = 23505;

    protected function handleDbErrors(Closure $callback): JsonResponse
    {
        try {
            return $callback();
        } catch (QueryException $e) {

            // A transaction started inside the try/catch block will be rolled-back by Laravel
            // A transaction started outside the try/catch block (e.g. in a test) needs to be handled
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
                DB::beginTransaction(); // Restart so further queries in the test work
            }

            return $this->handleQueryException($e);
        }
    }

    protected function handleQueryException(QueryException $e): JsonResponse
    {
        $dbConnection = $e->getConnectionName();

        switch ($dbConnection) {
            case 'pgsql':
                return $this->handleForPostgres($e);
            default:
                $this->logQueryException($e, 'DB connection name was not handled');

                return ApiResponseBuilder::error();
        }
    }

    protected function handleForPostgres(QueryException $e): JsonResponse
    {
        $code = $e->getCode();

        switch ($code) {
            case self::SQLSTATE_UNIQUE_CONSTRAINT:
                $field = $this->postgresUniqueConstraintGetColumn($e->getMessage());

                if ($field == '') {
                    $this->logQueryException($e, 'Failed to extract column name from error message');

                    return ApiResponseBuilder::error(ApiErrorCode::VALIDATION_GENERAL);
                }

                return ApiResponseBuilder::errors(
                    [new ApiError(ApiErrorCode::VALIDATION_UNIQUE, ['field' => $field])],
                    SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY
                );
        }

        $this->logQueryException($e, 'Postgres error code was not handled');

        return ApiResponseBuilder::error();
    }

    protected function postgresUniqueConstraintGetColumn(string $message): string
    {
        // Example exception message
        // SQLSTATE[23505]: Unique violation: 7 ERROR:  duplicate key value violates unique constraint \"items_name_unique\"\nDETAIL:  Key (name)=(Item 1) already exists. (Connection: pgsql, SQL: insert into \"items\" (\"name\", \"type\", \"uuid\", \"updated_at\", \"created_at\") values (Item 1, Type 1, 2ee0a74c-a407-40e0-a633-f1e7ab9db7e9, 2025-06-03 08:05:40, 2025-06-03 08:05:40))

        // Note: `(`, `)` and `=`are invalid characters for column names, but in postgres are permitted if the column name is bounded by quote marks

        //  DETAIL: Literal
        //  \s*     Zero or more whitespace
        //  Key     Literal
        //  \s*     Zero or more whitespace
        //  \(      Opening bracket (
        //  (.*?)   Capturing group: zero or more (fewest possible) of any character (except newline character)
        //  \)      Closing bracket )
        //  =       Literal
        //  \(      Opening bracket (
        //  /i      Case-insensitive

        if (! preg_match('/DETAIL:\s*Key\s*\((.*?)\)=\(/i', $message, $matches)) {
            return '';
        }

        return $matches[1];
    }

    protected function logQueryException(QueryException $e, string $message): void
    {
        LogService::error('QueryException: '.$message, [
            'message' => $e->getMessage(), // todo: Remove bound values
            'code' => $e->getCode(),
            'connection' => $e->getConnectionName(),
            'query' => $e->getSql(), // Don't log bound values (which could be sensitive)
        ]);
    }

    // protected function sqliteUniqueConstraintGetColumn(string $message): string|false
    // {
    //     // Example exception message
    //     // SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: items.name (Connection: sqlite, SQL: insert into \"items\" (\"name\", \"uuid\", \"updated_at\", \"created_at\") values (Test Item, ee5a907a-2071-4e50-aeeb-7f3653ce3481, 2025-06-09 08:48:57, 2025-06-09 08:48:57)
    // }
}
