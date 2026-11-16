<?php

namespace App\Workflow;

enum ConditionOperator: string
{
    case EQUALS = 'equals';
    case NOT_EQUALS = 'not_equals';
    case GREATER_THAN = 'greater_than';
    case GREATER_THAN_OR_EQUAL = 'greater_than_or_equal';
    case LESS_THAN = 'less_than';
    case LESS_THAN_OR_EQUAL = 'less_than_or_equal';
    case BETWEEN = 'between';
    case CONTAINS = 'contains';
    case NOT_CONTAINS = 'not_contains';
    case STARTS_WITH = 'starts_with';
    case ENDS_WITH = 'ends_with';
    case EXISTS = 'exists';
    case NOT_EXISTS = 'not_exists';
    case CHANGED = 'changed';
    case CHANGED_TO = 'changed_to';
    case CHANGED_FROM = 'changed_from';
    case MATCHES_REGEX = 'matches_regex';

    public function label(): string
    {
        return match ($this) {
            self::EQUALS => 'برابر است',
            self::NOT_EQUALS => 'برابر نیست',
            self::GREATER_THAN => 'بزرگتر است',
            self::GREATER_THAN_OR_EQUAL => 'بزرگتر یا مساوی است',
            self::LESS_THAN => 'کوچکتر است',
            self::LESS_THAN_OR_EQUAL => 'کوچکتر یا مساوی است',
            self::BETWEEN => 'بین',
            self::CONTAINS => 'شامل است',
            self::NOT_CONTAINS => 'شامل نیست',
            self::STARTS_WITH => 'شروع می‌شود با',
            self::ENDS_WITH => 'تمام می‌شود با',
            self::EXISTS => 'وجود دارد',
            self::NOT_EXISTS => 'وجود ندارد',
            self::CHANGED => 'تغییر کرده',
            self::CHANGED_TO => 'تغییر به',
            self::CHANGED_FROM => 'تغییر از',
            self::MATCHES_REGEX => 'مطابقت regex دارد',
        };
    }
}
