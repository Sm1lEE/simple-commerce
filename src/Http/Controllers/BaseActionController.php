<?php

namespace DoubleThreeDigital\SimpleCommerce\Http\Controllers;

use Illuminate\Http\Request;
use Statamic\Http\Controllers\Controller;

class BaseActionController extends Controller
{
    protected function withSuccess(Request $request, array $data = [])
    {
        if ($request->wantsJson()) {
            $data = array_merge($data, [
                'status'  => 'success',
                'message' => null,
            ]);

            return response()->json($data);
        }

        if (isset($data['is_checkout_request'])) {
            $request->session()->put('simple-commerce.checkout.success', [
                'order_id' => $data['cart']['id'],
                'expiry' => now()->addMinutes(30),
                'url' => $request->_redirect,
            ]);
        }

        return $request->_redirect ?
            redirect($request->_redirect)->with($data)
            : back()->with($data);
    }

    protected function withErrors(Request $request, string $errorMessage)
    {
        if ($request->wantsJson()) {
            return response()->json([
                'status'  => 'error',
                'message' => $errorMessage,
            ]);
        }

        return $request->_error_redirect
            ? redirect($request->_error_redirect)->withErrors($errorMessage)
            : back()->withErrors($errorMessage);
    }
}
