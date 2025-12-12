<?php

namespace App\Traits;

use Carbon\Carbon;
use DateTimeInterface;
use InvalidArgumentException;

trait ReturnDatetimeFormat
{
    /**
     * Convert various date/time inputs to a formatted string.
     *
     * Accepts:
     * - string (parsable date)
     * - int (UNIX timestamp)
     * - DateTimeInterface (DateTime, Carbon)
     * - null (returns null)
     *
     * @param  string|int|DateTimeInterface|null  $dateTime
     * @param  string  $format
     * @param  string|null  $timezone
     * @return string|null
     *
     * @throws \InvalidArgumentException  If the input cannot be parsed into a valid date/time.
     */
    private function formatDateTime(string|int|DateTimeInterface|null $dateTime, string $format, ?string $timezone = null): ?string
    {
        if (empty($dateTime)) {
            return null;
        }

        try {
            if ($dateTime instanceof DateTimeInterface) {
                $carbon = Carbon::instance($dateTime);
            } elseif (is_int($dateTime)) {
                $carbon = Carbon::createFromTimestamp($dateTime, $timezone);
            } else {
                $carbon = Carbon::parse($dateTime, $timezone);
            }

            return $carbon->format($format);
        } catch (\Exception $e) {
            throw new InvalidArgumentException("Invalid date/time provided: " . $e->getMessage());
        }
    }

    /**
     * Format a date/time input as "Y-m-d H:i:s".
     *
     * @param  string|int|DateTimeInterface|null  $dateTime
     * @param  string|null  $timezone
     * @return string|null
     */
    public function returnDateTime(string|int|DateTimeInterface|null $dateTime, ?string $timezone = null): ?string
    {
        return $this->formatDateTime($dateTime, 'Y-m-d H:i:s', $timezone);
    }

    /**
     * Format a date/time input as "Y-m-d" (date only).
     *
     * @param  string|int|DateTimeInterface|null  $dateTime
     * @param  string|null  $timezone
     * @return string|null
     */
    public function returnDate(string|int|DateTimeInterface|null $dateTime, ?string $timezone = null): ?string
    {
        return $this->formatDateTime($dateTime, 'Y-m-d', $timezone);
    }

    /**
     * Format a date/time input as "H:i:s" (time only).
     *
     * @param  string|int|DateTimeInterface|null  $dateTime
     * @param  string|null  $timezone
     * @return string|null
     */
    public function returnTime(string|int|DateTimeInterface|null $dateTime, ?string $timezone = null): ?string
    {
        return $this->formatDateTime($dateTime, 'H:i:s', $timezone);
    }

    /**
     * Format a date/time input as "M j, Y g:i A".
     * Ex. "May 05, 2025 5:55 PM"
     *
     * @param  string|int|DateTimeInterface|null  $dateTime
     * @param  string|null  $timezone
     * @return string|null
     */
    public function returnShortDateTime(string|int|DateTimeInterface|null $dateTime, ?string $timezone = null): ?string
    {
        return $this->formatDateTime($dateTime, 'M j, Y g:i A', $timezone);
    }

    /**
     * Format a date/time input using a custom format.
     *
     * @param  string|int|DateTimeInterface|null  $dateTime
     * @param  string  $format
     * @param  string|null  $timezone
     * @return string|null
     */
    public function returnDateCustomFormat(string|int|DateTimeInterface|null $dateTime, string $format = 'M j, Y g:i A', ?string $timezone = null): ?string
    {
        return $this->formatDateTime($dateTime, $format, $timezone);
    }
}
