<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class JsonContains
{
    /**
     * Match when a JSON array of objects contains an object with the given key/value pairs.
     */
    public static function whereArrayObjectContains(
        EloquentBuilder $query,
        string $column,
        array $partial,
        bool $or = false,
    ): EloquentBuilder {
        $driver = $query->getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $conditions = [];
            $bindings = [];

            foreach ($partial as $key => $value) {
                $conditions[] = "json_extract(value, '$.{$key}') = ?";
                $bindings[] = (string) $value;
            }

            $sql = "exists (select 1 from json_each({$column}) where ".implode(' and ', $conditions).')';

            return $or
                ? $query->orWhereRaw($sql, $bindings)
                : $query->whereRaw($sql, $bindings);
        }

        return $or
            ? $query->orWhereJsonContains($column, $partial)
            : $query->whereJsonContains($column, $partial);
    }

    /**
     * Match when a JSON array of scalars contains the given value.
     */
    public static function whereArrayContains(
        EloquentBuilder $query,
        string $column,
        string|int|float $value,
        bool $or = false,
    ): EloquentBuilder {
        return $or
            ? $query->orWhereJsonContains($column, $value)
            : $query->whereJsonContains($column, $value);
    }
}
