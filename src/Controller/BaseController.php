<?php


namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

abstract class BaseController extends AbstractController
{
    /**
     * @param mixed $data Usually an object you want to serialize
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function apiResponse($data = null, $statusCode = 200)
    {
        if (empty($data)) {
            return new JsonResponse(null, $statusCode);
        }

        $json = $this->get('serializer')->serialize($data, 'json');
        return new JsonResponse($json, $statusCode, [], true);
    }

    /**
     * @param $message string The error Message you want ot send
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function apiError(string $message, $statusCode = 400)
    {
        return $this->apiResponse(["Error" => ["message" => $message]], $statusCode);
    }

    /**
     * Returns an associative array of validation errors
     *
     * {
     *     'firstName': 'This value is required',
     *     'subForm': {
     *         'someField': 'Invalid value'
     *     }
     * }
     *
     * @param FormInterface $form
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

        $errors = array();
        foreach ($form->all() as $childForm) {
            if ($childForm instanceof FormInterface) {
                if ($childError = $this->getErrorsFromForm($childForm)) {
                    $errors[$childForm->getName()] = $childError;
                }
            }
        }

        return $errors;
    }

    protected function createBadRequestException($message = "Invalid JSON")
    {
        return new BadRequestHttpException($message);
    }
}