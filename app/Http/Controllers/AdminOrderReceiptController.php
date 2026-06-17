<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminOrderReceiptController extends Controller
{
    public function show(Request $request, Order $order): BinaryFileResponse
    {
        abort_unless($request->user()?->can('viewReceipt', $order), 404);

        $receiptPath = $order->receiptAbsolutePath();

        abort_unless($receiptPath !== null, 404);

        return response()->file($receiptPath, [
            'Content-Type' => $order->receiptMimeType() ?? 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'self'; img-src 'self' data: blob:; frame-ancestors 'self'; style-src 'unsafe-inline' 'self';",
        ]);
    }
}
