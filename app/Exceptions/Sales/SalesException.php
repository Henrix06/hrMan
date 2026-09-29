<?php

namespace App\Exceptions\Sales;

use RuntimeException;

class SalesException extends RuntimeException
{
    public static function noItems(): self
    {
        return new self('Add at least one line item to the order.');
    }

    public static function notEditable(): self
    {
        return new self('Only draft orders can be changed. Cancel it and log a new one instead.');
    }

    public static function invalidTransition(string $from, string $to): self
    {
        return new self(
            'An order that is '.self::label($from).' cannot be marked '.self::label($to).'.'
        );
    }

    public static function transportNeedsConfirmedOrder(string $status): self
    {
        return new self(
            'Transport can only be arranged once the order is confirmed — this one is still '.self::label($status).'.'
        );
    }

    public static function duplicateTransportRequest(): self
    {
        return new self('This order already has an open transport request.');
    }

    public static function notYourOrder(): self
    {
        return new self('You can only arrange transport for orders you logged.');
    }

    public static function driverRequired(): self
    {
        return new self('Choose a driver before approving the transport request.');
    }

    public static function invalidTransportTransition(string $from, string $to): self
    {
        return new self(
            'A transport request that is '.self::label($from).' cannot be marked '.self::label($to).'.'
        );
    }

    private static function label(string $status): string
    {
        return str_replace('_', ' ', $status);
    }
}
