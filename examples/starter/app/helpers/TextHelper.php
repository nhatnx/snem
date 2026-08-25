<?php
/**
 * Reached as $snem->helper->text — SEHelper ucfirst's the property name and
 * appends "Helper", so `text` resolves to this class. If no such class exists
 * it falls back to the framework's own app\core\helpers\SETextHelper.
 *
 * Helpers are instantiated fresh on each property access, so keep them
 * stateless.
 */

class TextHelper
{
    public function excerpt(?string $text, int $length = 100): string
    {
        $text = trim(preg_replace('/\s+/', ' ', (string)$text));

        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length - 1) . '…';
    }

    public function date(?string $value, string $format = 'j M Y'): string
    {
        if (empty($value)) {
            return '';
        }

        $timestamp = strtotime($value);

        return $timestamp ? date($format, $timestamp) : '';
    }
}
