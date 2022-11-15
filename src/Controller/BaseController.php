<?php

namespace App\Controller;

use App\Exception\ServiceException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

abstract class BaseController extends AbstractController
{
    protected function acceptsJson(Request $request)
    {
        return in_array('application/json', $request->getAcceptableContentTypes());
    }

    /**
     * @param mixed $data       Usually an object you want to serialize
     * @param int   $statusCode
     *
     * @return JsonResponse
     */
    protected function apiResponse($data = null, bool $wrap = false, $statusCode = 200)
    {
        if (empty($data)) {
            return new JsonResponse(null, $statusCode);
        }

        if ($wrap) {
            $count = count($data);
            $data = [
                'count' => $count,
                'total' => $count,
                'items' => $data,
            ];
        }
        $json = $this->get('serializer')->serialize($data, 'json');

        return new JsonResponse($json, $statusCode, [], true);
    }

    /**
     * @param $message string The error Message you want ot send
     * @param int $statusCode
     *
     * @return JsonResponse
     */
    protected function apiError(string $message, $statusCode = 400)
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
     *
     * @return array|string
     */
    protected function getErrorsFromForm(FormInterface $form)
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

    protected function createBadRequestException($message = 'Invalid JSON')
    {
        return new BadRequestHttpException($message);
    }

    protected function flashException(ServiceException $e)
    {
        switch ($e->getCause()) {
            case ServiceException::CAUSE_DONT_EXIST: $cause = 'nicht vorhanden';
            break;
            case ServiceException::CAUSE_EMPTY: $cause = 'leer';
            break;
            case ServiceException::CAUSE_EXIST: $cause = 'bereits vorhanden';
            break;
            case ServiceException::CAUSE_IN_USE: $cause = 'in Verwendung';
            break;
            default: $cause = '';
            break;
        }

        $msg = 'Operation kann nicht durchgeführt werden';
        if (!empty($cause)) {
            $msg = $msg." ($cause).";
        } else {
            $msg = $msg.'.';
        }
        $this->addFlash('danger', $msg);
    }
}
