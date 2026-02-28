<?php

namespace App\Validator;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class SafeImageFileValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof SafeImageFile) {
            throw new UnexpectedTypeException($constraint, SafeImageFile::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!$value instanceof File) {
            throw new UnexpectedTypeException($value, File::class);
        }

        $filename  = $value->getFilename();
        $mimeType  = $value->getMimeType() ?? 'unknown';
        $extension = strtolower($value->guessExtension() ?? pathinfo($filename, PATHINFO_EXTENSION));

        // SVG is dangerous because it can contain JavaScript
        if ($this->isSvgFile($mimeType, $extension, $value->getRealPath())) {
            $this->context->buildViolation($constraint->svgNotAllowedMessage)
                ->addViolation();
            return;
        }

        if (!in_array($mimeType, $constraint->mimeTypes, true)) {
            $this->context->buildViolation($constraint->invalidMimeTypeMessage)
                ->setParameter('{{ filename }}', $filename)
                ->setParameter('{{ mimeType }}', $mimeType)
                ->setCode(SafeImageFile::INVALID_MIME_TYPE_ERROR)
                ->addViolation();
            return;
        }

        if (!in_array($extension, $constraint->extensions, true)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ filename }}', $filename)
                ->setCode(SafeImageFile::FILE_TYPE_NOT_ALLOWED_ERROR)
                ->addViolation();
        }
    }

    /**
     * Detects SVG files by MIME type, extension, or file content header.
     * SVG is dangerous because it can embed JavaScript.
     */
    private function isSvgFile(string $mimeType, string $extension, string|false $path): bool
    {
        if (in_array($mimeType, ['image/svg+xml', 'image/svg', 'text/svg'], true)) {
            return true;
        }

        if ($extension === 'svg') {
            return true;
        }

        if ($path === false) {
            return false;
        }

        try {
            $header = file_get_contents($path, false, null, 0, 512);
            if ($header !== false && (
                stripos($header, '<svg') !== false ||
                (stripos($header, '<?xml') !== false && stripos($header, 'svg') !== false)
            )) {
                return true;
            }
        } catch (\Exception) {
            // If we can't read the file, let other checks handle it
        }

        return false;
    }
}
