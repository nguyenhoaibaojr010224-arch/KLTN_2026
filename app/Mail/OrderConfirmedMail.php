<?php

namespace App\Mail;

use App\Models\HoaDon;
use App\Models\KhachHang;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public KhachHang $khachHang,
        public HoaDon $hoaDon,
        public array $items,
        public array $meta = []
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'PharmaGo xác nhận đơn hàng ' . $this->hoaDon->ma_hoa_don,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-confirmed',
            with: [
                'khachHang' => $this->khachHang,
                'hoaDon' => $this->hoaDon,
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
