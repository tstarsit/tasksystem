<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Support\Str;

class Helpers
{
    public static function transliterateArabicToEnglish(string $text): string
    {
        $arabic = [
            'ا', 'أ', 'إ', 'آ', 'ب', 'ت', 'ث', 'ج', 'ح', 'خ', 'د', 'ذ', 'ر', 'ز', 'س', 'ش', 'ص', 'ض', 'ط', 'ظ', 'ع', 'غ', 'ف', 'ق', 'ك', 'ل', 'م', 'ن', 'ه', 'و', 'ي', 'ى', 'ة', 'ئ', 'ء', 'ؤ'
        ];

        $english = [
            'a', 'a', 'a', 'a', 'b', 't', 'th', 'j', 'h', 'kh', 'd', 'th', 'r', 'z', 's', 'sh', 's', 'd', 't', 'z', 'a', 'gh', 'f', 'q', 'k', 'l', 'm', 'n', 'h', 'w', 'y', 'a', 'a', 'e', 'a', 'o'
        ];

        return str_replace($arabic, $english, $text);
    }

    public static function generateUsername(?string $name): string
    {
        if (empty(trim($name))) {
            return '';
        }

        // Normalize hyphens and transliterate Arabic
        $normalizedName = str_replace(['-', 'ـ'], ' ', $name);
        $transliteratedName = self::transliterateArabicToEnglish($normalizedName);

        // Clean and get name parts
        $cleanName = preg_replace('/[^a-zA-Z\s]/', '', $transliteratedName);
        $nameParts = array_values(array_filter(explode(' ', trim($cleanName))));

        if (empty($nameParts)) {
            return '';
        }

        $firstPart = strtolower($nameParts[0] ?? '');
        $secondPart = strtolower($nameParts[1] ?? '');
        $username = '';

        // Strategy 1: First letter of each part + 'h' (min 3 chars)
        if (!empty($secondPart)) {
            $username = substr($firstPart, 0, 1) . substr($secondPart, 0, 1) . 'h';
            if (strlen($username) >= 3 && !self::usernameExists($username)) {
                return $username;
            }
        }

        // Strategy 2: First 2 letters from first part + 'h'
        $username = substr($firstPart, 0, 2) . 'h';
        if (strlen($username) >= 3 && !self::usernameExists($username)) {
            return $username;
        }

        // Strategy 3: First 3 letters from first part (replace last char with 'h' if needed)
        $username = substr($firstPart, 0, 2) . 'h';
        if (!self::usernameExists($username)) {
            return $username;
        }

        // Strategy 4: Progressive letters + 'h'
        for ($i = 2; $i < strlen($firstPart); $i++) {
            $username = substr($firstPart, 0, $i) . 'h';
            if (strlen($username) >= 3 && !self::usernameExists($username)) {
                return $username;
            }

            // Try including second part if available
            if (!empty($secondPart)) {
                $username = substr($firstPart, 0, 1) . substr($secondPart, 0, $i-1) . 'h';
                if (strlen($username) >= 3 && !self::usernameExists($username)) {
                    return $username;
                }
            }
        }

        // Final fallback: Random 3-char with 'h'
        $random = substr(str_shuffle('abcdefghjkmnpqrstuvwxyz'), 0, 2);
        return $random . 'h';
    }

    private static function usernameExists(string $username): bool
    {
        return User::whereRaw('LOWER(username) = ?', [strtolower($username)])->exists();
    }
}
