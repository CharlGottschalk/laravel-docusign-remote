<?php

namespace CharlGottschalk\DocuSign\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Envelope extends Model
{
    use HasFactory;

    public const ENVELOPE_STATUS_SENT = 'sent';
    public const ENVELOPE_STATUS_DRAFT = 'created';
    public const ENVELOPE_STATUS_COMPLETE = 'completed';
    public const ENVELOPE_STATUS_DECLINED = 'declined';

    /**
     * Get the recipients for the envelope.
     *
     * @return HasMany
     */
    public function recipients(): HasMany
    {
        return $this->hasMany(EnvelopeRecipient::class);
    }

    /**
     * Get the envelope's document base64 value.
     *
     * @return string|null
     */
    public function getFileAttribute(): ?string
    {
        return base64_encode(Storage::disk(config('docusign.storage.disk'))->get("$this->path/$this->name.$this->extension")) ?? null;
    }
}
