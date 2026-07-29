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
}
