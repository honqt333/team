<?php

namespace App\Support;

/**
 * Converts Arabic-Indic numerals (Eastern Arabic) to Western Arabic numerals.
 * 
 * This class provides utility methods to automatically convert Arabic numerals
 * (٠١٢٣٤٥٦٧٨٩) to their English equivalents (0123456789).
 */
class ArabicNumeralConverter
{
    /**
     * Arabic-Indic numerals (Eastern Arabic)
     */
    private const ARABIC_NUMERALS = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    
    /**
     * Western Arabic numerals
     */
    private const ENGLISH_NUMERALS = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    /**
     * Convert Arabic numerals to English numerals in the given value.
     * 
     * Recursively processes arrays and converts string values.
     *
     * @param mixed $value The value to convert (string, array, or other)
     * @return mixed The converted value with the same type
     */
    public static function toEnglish(mixed $value): mixed
    {
        if (is_string($value)) {
            return str_replace(self::ARABIC_NUMERALS, self::ENGLISH_NUMERALS, $value);
        }
        
        if (is_array($value)) {
            return array_map([self::class, 'toEnglish'], $value);
        }
        
        return $value;
    }

    /**
     * Convert English numerals to Arabic numerals in the given value.
     * 
     * @param mixed $value The value to convert
     * @return mixed The converted value
     */
    public static function toArabic(mixed $value): mixed
    {
        if (is_string($value)) {
            return str_replace(self::ENGLISH_NUMERALS, self::ARABIC_NUMERALS, $value);
        }
        
        if (is_array($value)) {
            return array_map([self::class, 'toArabic'], $value);
        }
        
        return $value;
    }
}
