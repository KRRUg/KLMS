<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Validates that uploaded files are safe image formats (PNG, JPEG, WebP).
 * Blocks dangerous formats like SVG and other documents.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class SafeImageFile extends Constraint
{
    public const INVALID_MIME_TYPE_ERROR = 'bdf8b4f8-1ad8-49f8-a568-92eb5f5f3d4d';
    public const FILE_TYPE_NOT_ALLOWED_ERROR = 'b7f3d2f4-5e1a-4f2c-8d3b-1c2e3f4a5b6c';
    public const SVG_NOT_ALLOWED_ERROR = 'svg-not-allowed-1234567890';

    protected const ERROR_NAMES = [
        self::INVALID_MIME_TYPE_ERROR => 'INVALID_MIME_TYPE_ERROR',
        self::FILE_TYPE_NOT_ALLOWED_ERROR => 'FILE_TYPE_NOT_ALLOWED_ERROR',
        self::SVG_NOT_ALLOWED_ERROR => 'SVG_NOT_ALLOWED_ERROR',
    ];

    public string $message = 'Die Datei "{{ filename }}" ist in diesem Format nicht erlaubt. Nur PNG, JPEG und WebP sind erlaubt.';
    public string $invalidMimeTypeMessage = 'Die Datei "{{ filename }}" hat einen ungültigen MIME-Type "{{ mimeType }}". Nur image/png, image/jpeg und image/webp sind erlaubt.';
    public string $svgNotAllowedMessage = 'SVG-Dateien sind aus Sicherheitsgründen nicht erlaubt.';

    /**
     * Allowed MIME types for images.
     * @var string[]
     */
    public array $mimeTypes = [
        'image/png',
        'image/jpeg',
        'image/webp',
    ];

    /**
     * Allowed file extensions (case-insensitive).
     * @var string[]
     */
    public array $extensions = [
        'png',
        'jpg',
        'jpeg',
        'webp',
    ];
}
