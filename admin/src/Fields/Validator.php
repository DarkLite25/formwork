<?php

namespace Formwork\Admin\Fields;

use Formwork\Core\Formwork;
use Formwork\Data\Collection;
use Formwork\Data\DataGetter;
use DateTime;

class Validator
{
    /**
     * Fields not to validate
     *
     * @var array
     */
    protected const IGNORED_FIELDS = ['column', 'header', 'row', 'rows'];

    /**
     * Validate all Fields against given data
     */
    public static function validate(Fields $fields, DataGetter $data)
    {
        foreach ($fields as $field) {
            if ($field->has('fields')) {
                $field->get('fields')->validate($data);
            }
            if (in_array($field->type(), self::IGNORED_FIELDS, true)) {
                continue;
            }
            $method = 'validate' . ucfirst(strtolower($field->type()));
            if (method_exists(static::class, $method)) {
                $value = static::$method($data->get($field->name()), $field);
            } else {
                $value = $data->get($field->name(), $field->get('default'));
            }
            $field->set('value', $value);
        }
    }

    /**
     * Validate checkbox field
     */
    public static function validateCheckbox($value): bool
    {
        return !empty($value);
    }

    /**
     * Validate togglegroup field
     */
    public static function validateTogglegroup($value)
    {
        if ($value === '0' || $value === 'false' || $value === '') {
            return false;
        }
        if ($value === '1' || $value === 'true') {
            return true;
        }
        return static::parse($value);
    }

    /**
     * Validate date field
     */
    public static function validateDate($value): ?string
    {
        if (!empty($value)) {
            $format = Formwork::instance()->option('date.format');
            $date = date_create_from_format($format, $value);
            if ($date instanceof DateTime) {
                return date_format($date, 'Y-m-d');
            }
        }
        return $value;
    }

    /**
     * Validate number field
     *
     * @return float|int
     */
    public static function validateNumber($value, Field $field)
    {
        $number = static::parse($value);
        if ($value !== null) {
            if ($field->has('min')) {
                $number = max($number, (int) $field->get('min'));
            }
            if ($field->has('max')) {
                $number = min($number, (int) $field->get('max'));
            }
        }
        return $number;
    }

    /**
     * Validate range field
     *

     * @return float|int
     */
    public static function validateRange($value, Field $field)
    {
        return static::validateNumber($value, $field);
    }

    /**
     * Validate select field
     */
    public static function validateSelect($value)
    {
        return static::parse($value);
    }

    /**
     * Validate tags field
     */
    public static function validateTags($value, Field $field): array
    {
        $tags = is_array($value) ? $value : explode(', ', $value);
        if ($field->has('pattern')) {
            $pattern = $field->get('pattern');
            $tags = array_filter($tags, static function ($item) use ($pattern) {
                return static::regex($item, $pattern);
            });
        }
        return array_values(array_filter($tags));
    }

    /**
     * Validate array field
     */
    public static function validateArray($value, Field $field): array
    {
        $array = [];
        if (is_array($value)) {
            $array = $value;
        } elseif ($value instanceof Collection || $value instanceof DataGetter) {
            $array = $value->toArray();
        }
        if ($field->get('associative')) {
            foreach ($array as $key => $value) {
                if (is_int($key)) {
                    unset($array[$key]);
                }
            }
        } else {
            $array = array_filter($array);
        }
        return $array;
    }

    /**
     * Cast a value to its correct type
     */
    private static function parse($value)
    {
        if (is_numeric($value)) {
            if ($value == (int) $value) {
                return (int) $value;
            }
            if ($value == (float) $value) {
                return (float) $value;
            }
        }
        return $value;
    }

    /**
     * Return whether a values matches to a regex
     */
    private static function regex($value, string $regex): bool
    {
        return (bool) @preg_match('/' . $regex . '/', $value);
    }
}
