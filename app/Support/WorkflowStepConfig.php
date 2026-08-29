<?php

namespace App\Support;

class WorkflowStepConfig
{
    /**
     * @return list<string>
     */
    public static function colors(): array
    {
        return ['muted', 'teal', 'amber', 'violet', 'indigo', 'emerald', 'destructive'];
    }

    /**
     * @return list<string>
     */
    public static function inputTypes(): array
    {
        return ['text', 'textarea', 'number', 'date', 'datetime'];
    }

    /**
     * @return array{statuses: list<array{value: string, label: string, color: string, completes: bool}>, inputs: list<array{key: string, label: string, type: string, show_on_calendar: bool}>}
     */
    public static function defaultsFor(string $key): array
    {
        return match ($key) {
            'title_proposal', 'completed' => [
                'statuses' => [],
                'inputs' => [],
            ],
            'ric_review' => [
                'statuses' => [
                    self::status('pending', 'Pending', 'muted', false),
                    self::status('approved', 'Approved', 'teal', true),
                    self::status('returned', 'Return to student', 'amber', false),
                    self::status('rejected', 'Rejected', 'destructive', false),
                ],
                'inputs' => [],
            ],
            'outline_defense' => [
                'statuses' => [
                    self::status('pending', 'Pending', 'muted', false),
                    self::status('passed', 'Passed', 'teal', true),
                    self::status('re_defense', 'Re-Defense', 'amber', false),
                ],
                'inputs' => [
                    self::input('schedule', 'Schedule', 'datetime', true),
                ],
            ],
            'data_gathering' => [
                'statuses' => [
                    self::status('pending', 'Pending', 'muted', false),
                    self::status('completed', 'Completed', 'violet', true),
                ],
                'inputs' => [],
            ],
            'rating' => [
                'statuses' => [
                    self::status('pending', 'Pending', 'muted', false),
                    self::status('rated', 'Rated', 'amber', true),
                ],
                'inputs' => [
                    self::input('grade', 'Grade', 'number'),
                ],
            ],
            'final_manuscript' => [
                'statuses' => [
                    self::status('pending', 'Pending', 'muted', false),
                    self::status('submitted', 'Submitted', 'indigo', true),
                ],
                'inputs' => [],
            ],
            'final_defense' => [
                'statuses' => [
                    self::status('pending', 'Pending', 'muted', false),
                    self::status('passed', 'Passed', 'teal', true),
                    self::status('re_defense', 'Re-Defense', 'amber', false),
                ],
                'inputs' => [
                    self::input('schedule', 'Schedule', 'datetime', true),
                ],
            ],
            'hard_bound' => [
                'statuses' => [
                    self::status('ongoing', 'Ongoing', 'muted', false),
                    self::status('submitted', 'Submitted', 'emerald', true),
                ],
                'inputs' => [],
            ],
            default => [
                'statuses' => [
                    self::status('pending', 'Pending', 'muted', false),
                    self::status('completed', 'Completed', 'violet', true),
                ],
                'inputs' => [],
            ],
        };
    }

    /**
     * @param  array<string, mixed>|null  $config
     * @return array{statuses: list<array{value: string, label: string, color: string, completes: bool}>, inputs: list<array{key: string, label: string, type: string, show_on_calendar: bool}>}
     */
    public static function normalize(?array $config, string $key): array
    {
        if ($config === null) {
            return self::defaultsFor($key);
        }

        $statuses = [];
        $seenStatusValues = [];

        foreach ($config['statuses'] ?? [] as $status) {
            if (! is_array($status)) {
                continue;
            }

            $value = self::slug((string) ($status['value'] ?? ''));
            if ($value === '' || isset($seenStatusValues[$value])) {
                continue;
            }

            $seenStatusValues[$value] = true;
            $statuses[] = self::status(
                $value,
                (string) ($status['label'] ?? $value),
                (string) ($status['color'] ?? 'muted'),
                (bool) ($status['completes'] ?? false),
            );
        }

        $inputs = [];
        $seenInputKeys = [];

        foreach ($config['inputs'] ?? [] as $input) {
            if (! is_array($input)) {
                continue;
            }

            $inputKey = self::slug((string) ($input['key'] ?? ''));
            if ($inputKey === '' || isset($seenInputKeys[$inputKey])) {
                continue;
            }

            $seenInputKeys[$inputKey] = true;
            $resolvedType = (string) ($input['type'] ?? 'text');
            $showOnCalendar = array_key_exists('show_on_calendar', $input)
                ? (bool) $input['show_on_calendar']
                : $inputKey === 'schedule' && self::canShowOnCalendar($resolvedType);

            $inputs[] = self::input(
                $inputKey,
                (string) ($input['label'] ?? $inputKey),
                $resolvedType,
                $showOnCalendar,
            );
        }

        return [
            'statuses' => $statuses,
            'inputs' => $inputs,
        ];
    }

    /**
     * @param  array{statuses?: list<array{value: string, completes?: bool}>}  $config
     */
    public static function statusCompletes(array $config, string $status): bool
    {
        foreach ($config['statuses'] ?? [] as $option) {
            if (($option['value'] ?? null) === $status) {
                return (bool) ($option['completes'] ?? false);
            }
        }

        return false;
    }

    /**
     * @return array{value: string, label: string, color: string, completes: bool}
     */
    private static function status(string $value, string $label, string $color, bool $completes): array
    {
        $colors = self::colors();

        return [
            'value' => $value,
            'label' => $label !== '' ? $label : $value,
            'color' => in_array($color, $colors, true) ? $color : 'muted',
            'completes' => $completes,
        ];
    }

    /**
     * @return array{key: string, label: string, type: string, show_on_calendar: bool}
     */
    private static function input(string $key, string $label, string $type, bool $showOnCalendar = false): array
    {
        $types = self::inputTypes();
        $resolvedType = in_array($type, $types, true) ? $type : 'text';

        return [
            'key' => $key,
            'label' => $label !== '' ? $label : $key,
            'type' => $resolvedType,
            'show_on_calendar' => self::canShowOnCalendar($resolvedType) && $showOnCalendar,
        ];
    }

    public static function canShowOnCalendar(string $type): bool
    {
        return in_array($type, ['datetime', 'date'], true);
    }

    private static function slug(string $value): string
    {
        $slug = strtolower(trim($value));
        $slug = (string) preg_replace('/[^a-z0-9_]+/', '_', $slug);
        $slug = trim($slug, '_');

        return substr($slug, 0, 64);
    }
}
