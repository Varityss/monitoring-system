<?php

function inputString(array $source, string $key, int $maxLength = 255, bool $required = false): ?string
{
    $value = $source[$key] ?? null;
    if ($value === null || $value === '') {
        if ($required) { throw new InvalidArgumentException('Поле «' . $key . '» обязательно'); }
        return null;
    }
    if (!is_string($value)) { throw new InvalidArgumentException('Некорректное значение поля «' . $key . '»'); }
    $value = trim($value);
    if ($required && $value === '') { throw new InvalidArgumentException('Поле «' . $key . '» обязательно'); }
    if (mb_strlen($value) > $maxLength) { throw new InvalidArgumentException('Поле «' . $key . '» слишком длинное'); }
    return $value === '' ? null : $value;
}

function inputPositiveInt(array $source, string $key, int $max = PHP_INT_MAX): int
{
    $value = $source[$key] ?? null;
    if (!is_scalar($value) || filter_var($value, FILTER_VALIDATE_INT) === false) { throw new InvalidArgumentException('Некорректное число в поле «' . $key . '»'); }
    $value = (int)$value;
    if ($value < 1 || $value > $max) { throw new InvalidArgumentException('Значение поля «' . $key . '» вне допустимого диапазона'); }
    return $value;
}

function inputEnum(array $source, string $key, array $allowed): string
{
    $value = inputString($source, $key, 100, true);
    if (!in_array($value, $allowed, true)) { throw new InvalidArgumentException('Недопустимое значение поля «' . $key . '»'); }
    return $value;
}

function inputIdList(array $source, string $key, int $maxItems = 100): array
{
    $values = $source[$key] ?? [];
    if (!is_array($values) || count($values) > $maxItems) { throw new InvalidArgumentException('Некорректный список «' . $key . '»'); }
    $result = [];
    foreach ($values as $value) {
        if (!is_scalar($value) || filter_var($value, FILTER_VALIDATE_INT) === false || (int)$value < 1) { throw new InvalidArgumentException('Некорректный идентификатор в списке «' . $key . '»'); }
        $result[] = (int)$value;
    }
    return array_values(array_unique($result));
}

function strictDate(string $value, string $format): DateTimeImmutable
{
    $date = DateTimeImmutable::createFromFormat('!' . $format, $value);
    $errors = DateTimeImmutable::getLastErrors();
    if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $date->format($format) !== $value) { throw new InvalidArgumentException('Некорректная дата или время'); }
    return $date;
}

function strictDateTimeLocal(string $value): DateTimeImmutable
{
    return strictDate($value, 'Y-m-d\TH:i');
}
