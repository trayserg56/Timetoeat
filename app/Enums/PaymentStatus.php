<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentStatus: string implements HasLabel
{
    case AwaitingReceipt = 'awaiting_receipt';
    case ReceiptUploaded = 'receipt_uploaded';
    case Paid = 'paid';
    case Rejected = 'rejected';
    case Refunded = 'refunded';

    public function getLabel(): string
    {
        return match ($this) {
            self::AwaitingReceipt => 'Ожидается чек',
            self::ReceiptUploaded => 'Чек загружен',
            self::Paid => 'Оплачен',
            self::Rejected => 'Чек отклонён',
            self::Refunded => 'Возврат',
        };
    }
}
