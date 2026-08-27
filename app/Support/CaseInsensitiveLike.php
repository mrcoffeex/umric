<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class CaseInsensitiveLike
{
    public static function register(): void
    {
        EloquentBuilder::macro('whereILike', function (string $column, string $value) {
            /** @var EloquentBuilder $this */
            return CaseInsensitiveLike::applyWhere($this, $column, $value, false);
        });

        EloquentBuilder::macro('orWhereILike', function (string $column, string $value) {
            /** @var EloquentBuilder $this */
            return CaseInsensitiveLike::applyWhere($this, $column, $value, true);
        });

        QueryBuilder::macro('whereILike', function (string $column, string $value) {
            /** @var QueryBuilder $this */
            return CaseInsensitiveLike::applyWhere($this, $column, $value, false);
        });

        QueryBuilder::macro('orWhereILike', function (string $column, string $value) {
            /** @var QueryBuilder $this */
            return CaseInsensitiveLike::applyWhere($this, $column, $value, true);
        });
    }

    /**
     * @param  EloquentBuilder|QueryBuilder  $query
     * @return EloquentBuilder|QueryBuilder
     */
    public static function applyWhere(mixed $query, string $column, string $value, bool $or): mixed
    {
        $driver = $query->getConnection()->getDriverName();
        $operator = $driver === 'pgsql' ? 'ilike' : 'like';
        $method = $or ? 'orWhere' : 'where';

        return $query->{$method}($column, $operator, $value);
    }

    /**
     * Case-insensitive search against a JSON column as text.
     *
     * @param  EloquentBuilder|QueryBuilder  $query
     * @return EloquentBuilder|QueryBuilder
     */
    public static function orWhereJsonTextILike(mixed $query, string $column, string $value): mixed
    {
        $driver = $query->getConnection()->getDriverName();

        return match ($driver) {
            'pgsql' => $query->orWhereRaw("{$column}::text ILIKE ?", [$value]),
            'mysql' => $query->orWhereRaw("LOWER(CAST({$column} AS CHAR)) LIKE ?", [mb_strtolower($value)]),
            default => $query->orWhereRaw("LOWER(CAST({$column} AS TEXT)) LIKE ?", [mb_strtolower($value)]),
        };
    }
}
