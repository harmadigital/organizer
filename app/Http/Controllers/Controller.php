<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

abstract class Controller
{
    /**
     * Answer an AJAX request that succeeded, in the application's JSON envelope.
     */
    protected function jsonSuccess(mixed $data = null, int $status = 200): JsonResponse
    {
        return response()->json(['status' => true, 'data' => $data], $status);
    }

    /**
     * Answer an AJAX request that failed, with the HTTP status that describes why.
     *
     * @param  array<string, array<int, string>>  $errors
     */
    protected function jsonFailure(string $message, int $status = 422, array $errors = []): JsonResponse
    {
        return response()->json(['status' => false, 'data' => $message, 'errors' => (object) $errors], $status);
    }

    protected function validationErrorsToString($errArray)
    {
        $valArr = [];
        foreach ($errArray->toArray() as $key => $value) {
            $errStr = $value[0];
            array_push($valArr, $errStr);
        }
        if (! empty($valArr)) {
            $errStrFinal = implode('<br/> ', $valArr);
        }

        return $errStrFinal;
    }

    protected function remove_comma($val)
    {
        return str_replace(',', '', $val);
    }
}
