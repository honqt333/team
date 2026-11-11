<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\PhoneNumber;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    public function test_normalizes_saudi_local_format(): void
    {
        $this->assertSame('+966501234567', PhoneNumber::normalize('0501234567'));
    }

    public function test_normalizes_saudi_with_country_code(): void
    {
        $this->assertSame('+966501234567', PhoneNumber::normalize('966501234567'));
        $this->assertSame('+966501234567', PhoneNumber::normalize('+966501234567'));
        $this->assertSame('+966501234567', PhoneNumber::normalize('00966501234567'));
    }

    public function test_normalizes_saudi_no_leading_zero(): void
    {
        $this->assertSame('+966501234567', PhoneNumber::normalize('501234567'));
    }

    public function test_normalizes_arabic_digits(): void
    {
        $this->assertSame('+966501234567', PhoneNumber::normalize('٠٥٠١٢٣٤٥٦٧'));
    }

    public function test_normalizes_persian_digits(): void
    {
        $this->assertSame('+966501234567', PhoneNumber::normalize('۰۵۰۱۲۳۴۵۶۷'));
    }

    public function test_normalizes_with_spaces_and_dashes(): void
    {
        $this->assertSame('+966501234567', PhoneNumber::normalize('050-123-4567'));
        $this->assertSame('+966501234567', PhoneNumber::normalize('050 123 4567'));
    }

    public function test_returns_null_for_empty(): void
    {
        $this->assertNull(PhoneNumber::normalize(''));
        $this->assertNull(PhoneNumber::normalize('   '));
        $this->assertNull(PhoneNumber::normalize(null));
    }

    public function test_lookup_key_strips_plus(): void
    {
        $this->assertSame('966501234567', PhoneNumber::lookupKey('+966501234567'));
        $this->assertSame('966501234567', PhoneNumber::lookupKey('0501234567'));
        $this->assertSame('966501234567', PhoneNumber::lookupKey('٠٥٠١٢٣٤٥٦٧'));
    }

    public function test_two_format_variants_share_lookup_key(): void
    {
        // The whole point: 0501234567, +966501234567, 966501234567
        // all match the same stored phone row.
        $a = PhoneNumber::lookupKey('0501234567');
        $b = PhoneNumber::lookupKey('+966501234567');
        $c = PhoneNumber::lookupKey('966501234567');
        $d = PhoneNumber::lookupKey('٠٥٠١٢٣٤٥٦٧');

        $this->assertSame($a, $b);
        $this->assertSame($a, $c);
        $this->assertSame($a, $d);
    }

    public function test_normalizes_gcc_and_arab_numbers_with_default_country(): void
    {
        $this->assertSame('+971501234567', PhoneNumber::normalize('0501234567', 'AE'));
        $this->assertSame('+971501234567', PhoneNumber::normalize('501234567', 'AE'));
        $this->assertSame('+96550123456', PhoneNumber::normalize('50123456', 'KW'));
        $this->assertSame('+96550123456', PhoneNumber::normalize('0096550123456', 'SA'));
        $this->assertSame('+201012345678', PhoneNumber::normalize('01012345678', 'EG'));
        $this->assertSame('+962791234567', PhoneNumber::normalize('0791234567', 'JO'));
    }

    public function test_preserves_international_e164_numbers(): void
    {
        $this->assertSame('+447911123456', PhoneNumber::normalize('+44 7911 123456', 'SA'));
        $this->assertSame('+12025550123', PhoneNumber::normalize('0012025550123', 'AE'));
        $this->assertTrue(PhoneNumber::isValidE164('+971501234567'));
        $this->assertTrue(PhoneNumber::isValidE164('+447911123456'));
        $this->assertFalse(PhoneNumber::isValidE164('0501234567'));
    }
}
