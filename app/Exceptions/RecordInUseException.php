<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Thrown when a record cannot be deleted because other data still refers to it.
 */
class RecordInUseException extends Exception implements ShouldntReport
{
    public function __construct()
    {
        parent::__construct('Data ini sudah dipakai oleh data lain sehingga tidak dapat dihapus. Nonaktifkan saja bila tidak digunakan lagi.');
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['status' => false, 'data' => $this->getMessage(), 'errors' => (object) []], 409);
        }

        return back()->with('error', $this->getMessage());
    }
}
