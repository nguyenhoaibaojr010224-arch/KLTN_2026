<?php

namespace App\Mail;

use App\Models\HoaDon;
use App\Models\KhachHang;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

class OrderRejectedMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public KhachHang $khachHang,
        public HoaDon $hoaDon,
        public string $reason,
        public array $items = [],
        public array $meta = []
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'PharmaGo từ chối đơn hàng ' . $this->hoaDon->ma_hoa_don,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-rejected',
            with: [
                'khachHang' => $this->khachHang,
                'hoaDon' => $this->hoaDon,
                'reason' => $this->reason,
                'items' => $this->items,
                'meta' => $this->meta,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
