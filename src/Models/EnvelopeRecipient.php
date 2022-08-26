<?php

namespace CharlGottschalk\DocuSign\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnvelopeRecipient extends Model
{
    use HasFactory;

    public const RECIPIENT_STATUS_SIGNED = 'signed';
    public const RECIPIENT_STATUS_DECLINED = 'declined';
    public const RECIPIENT_STATUS_VIEWED = 'viewed';
    public const RECIPIENT_STATUS_SENT = 'sent';

    protected $fillable = [
        'envelope_id',
        'name',
        'email',
        'order',
        'status',
        'is_cc',
    ];

    /**
     * Get the envelope this envelope belongs to.
     * @return BelongsTo
     */
    public function envelope(): BelongsTo
    {
        return $this->belongsTo(Envelope::class);
    }
}
