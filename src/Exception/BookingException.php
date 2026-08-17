<?php

namespace App\Exception;

use RuntimeException;

class BookingException extends RuntimeException
{
    public const CODE_SLOT_FULL = 'slot_full';
    public const CODE_INVALID_SLOT = 'invalid_slot';
    public const CODE_RESOURCE_INACTIVE = 'resource_inactive';
    public const CODE_LIMIT_REACHED = 'limit_reached';
    public const CODE_ALREADY_BOOKED_SLOT = 'already_booked_slot';
    public const CODE_PAST_BOOKING = 'past_booking';
    public const CODE_NOT_OWNER = 'not_owner';
    public const CODE_ALREADY_CANCELLED = 'already_cancelled';
    public const CODE_NOT_ALLOWED = 'not_allowed';

    public static function slotFull(): self
    {
        return new self(self::CODE_SLOT_FULL);
    }

    public static function invalidSlot(): self
    {
        return new self(self::CODE_INVALID_SLOT);
    }

    public static function resourceInactive(): self
    {
        return new self(self::CODE_RESOURCE_INACTIVE);
    }

    public static function limitReached(): self
    {
        return new self(self::CODE_LIMIT_REACHED);
    }

    public static function alreadyBookedSlot(): self
    {
        return new self(self::CODE_ALREADY_BOOKED_SLOT);
    }

    public static function pastBooking(): self
    {
        return new self(self::CODE_PAST_BOOKING);
    }

    public static function notOwner(): self
    {
        return new self(self::CODE_NOT_OWNER);
    }

    public static function alreadyCancelled(): self
    {
        return new self(self::CODE_ALREADY_CANCELLED);
    }

    public static function notAllowed(): self
    {
        return new self(self::CODE_NOT_ALLOWED);
    }
}
