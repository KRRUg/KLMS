<?php

namespace App\Controller;

use App\Exception\ServiceException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\SerializerInterface;

abstract class BaseController extends AbstractController
{
    private readonly SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    protected function acceptsJson(Request $request): bool
    {
        return in_array('application/json', $request->getAcceptableContentTypes());
    }

    protected function apiResponse(mixed $data = null, bool $wrap = false, int $statusCode = 200): JsonResponse
    {
        if (empty($data)) {
            return new JsonResponse(null, $statusCode);
        }

        if ($wrap) {
            $count = is_countable($data) ? count($data) : 0;
            $data = [
                'count' => $count,
                'total' => $count,
                'items' => $data,
            ];
        }
        $json = $this->serializer->serialize($data, 'json');

        return new JsonResponse($json, $statusCode, [], true);
    }

    /**
     * @param $message string The error Message you want ot send
     */
    protected function apiError(string $message, int $statusCode = 400): JsonResponse
    {
        return $this->apiResponse(['Error' => ['message' => $message]], $statusCode);
    }

    /**
     * Returns an associative array of validation errors.
     *
     * {
     *     'firstName': 'This value is required',
     *     'subForm': {
     *         'someField': 'Invalid value'
     *     }
     * }
     */
    protected function getErrorsFromForm(FormInterface $form): array|string
    {
        foreach ($form->getErrors() as $error) {
            // only supporting 1 error per field
            // and not supporting a "field" with errors, that has more
            // fields with errors below it
            return $error->getMessage();
        }

        $errors = [];
        foreach ($form->all() as $childForm) {
            if ($childForm instanceof FormInterface) {
                if ($childError = $this->getErrorsFromForm($childForm)) {
                    $errors[$childForm->getName()] = $childError;
                }
            }
        }

        return $errors;
    }

    protected function createBadRequestException($message = 'Invalid JSON'): BadRequestHttpException
    {
        return new BadRequestHttpException($message);
    }

    protected function flashException(ServiceException $e)
    {
        $cause = match ($e->getCause()) {
            ServiceException::CAUSE_DONT_EXIST => 'nicht vorhanden',
            ServiceException::CAUSE_EMPTY => 'leer',
            ServiceException::CAUSE_EXIST => 'bereits vorhanden',
            ServiceException::CAUSE_IN_USE => 'in Verwendung',
            default => '',
        };

        $msg = 'Operation kann nicht durchgeführt werden';
        if (!empty($cause)) {
            $msg = $msg." ($cause).";
        } else {
            $msg = $msg.'.';
        }
        $this->addFlash('danger', $msg);
    }
}
