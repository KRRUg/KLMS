<?php

namespace App\Controller;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

trait HttpExceptionTrait
{
    private static function createMethodNotAllowedException(array $allow, string $message = '', \Throwable $previous = null): MethodNotAllowedHttpException
    {
        return new MethodNotAllowedHttpException($allow, $message, $previous);
    }

    private static function createBadRequestHttpException(string $message = 'Bad request', \Throwable $previous = null): BadRequestHttpException
    {
        return new BadRequestHttpException($message, $previous);
    }
}