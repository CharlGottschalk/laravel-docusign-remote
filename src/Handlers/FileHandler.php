<?php

namespace CharlGottschalk\DocuSign\Handlers;

use CharlGottschalk\DocuSign\Exceptions\MissingData;
use CharlGottschalk\DocuSign\Models\Envelope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileHandler
{
    /**
     * The model that represents an envelope in the database
     *
     * @var Envelope
     */
    protected Envelope $envelope;

    /**
     * Hold the path into which to save the document
     *
     * @var string
     */
    protected string $path;

    /**
     * Hold the document name
     *
     * @var string
     */
    protected string $name;

    /**
     * Instantiate a new FileHandler
     * @param Envelope|null $envelope
     */
    public function __construct(Envelope $envelope = null)
    {
        if (! empty($envelope)) {
            $this->envelope = $envelope;
        }

        $this->path = config('docusign.storage_directory');
    }

    /**
     * Set the document for the request
     *
     * @param string $originalName
     * @param string $extension
     * @return void
     */
    private function setDocument(string $originalName, string $extension, bool $hash = true): void
    {
        # Generate a unique name to store the document as.
        $hash = $hash ? md5($originalName . time()) : $originalName;
        $this->name = "$hash.$extension";

        # Set envelope's document attributes
        $this->envelope->original_filename = $originalName;
        $this->envelope->extension = $extension;
        $this->envelope->path = $this->path;
        $this->envelope->name = $hash;
    }

    /**
     * Append a directory to the document's storage path
     *
     * @param string $append
     * @return string
     */
    public function appendPath(string $append): string
    {
        $this->path = $this->path . '/' . rtrim(ltrim($append, '/'), '/');

        return $this->path;
    }

    /**
     * Set the envelope to handle
     *
     * @param Envelope $envelope
     * @return FileHandler
     */
    public function for(Envelope $envelope): FileHandler
    {
        $this->envelope = $envelope;

        return $this;
    }

    /**
     * Store the uploaded document in storage and insert database entries
     *
     * @param Request $request
     * @param string $key
     * @return Envelope
     */
    public function upload(Request $request, string $key = 'document'): Envelope
    {
        # Get the uploaded document.
        $document = $request->file($key);

        $originalName = pathinfo($document->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $document->getClientOriginalExtension();

        $this->setDocument($originalName, $extension);

        # Store document in file storage under configured disk.
        $document->storeAs($this->path, $this->name, config('docusign.storage_disk'));

        return $this->envelope;
    }

    /**
     * Select a document from the configured storage
     *
     * @param string $name
     * @return Envelope
     * @throws MissingData
     */
    public function selectDocument(string $name): Envelope
    {
        $document = $this->path . '/' . ltrim($name, '/');

        if (! Storage::disk(config('docusign.storage_disk'))->exists($document)) {
            throw MissingData::documentDoesNotExist();
        }

        $originalName = pathinfo($name, PATHINFO_FILENAME);
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $this->setDocument($originalName, $extension, false);

        return $this->envelope;
    }

    /**
     * Get the document path including name
     *
     * @return string
     */
    public function document(): string
    {
        return $this->path . '/' . $this->name;
    }

    /**
     * Get the document content
     *
     * @return string
     */
    public function documentContent(): string
    {
        $path = $this->path . '/' . $this->name;

        return Storage::disk(config('docusign.storage_disk'))->get($path);
    }

    /**
     * Get the document content as a Base64 string
     *
     * @return string
     */
    public function documentBase64(): string
    {
        return base64_encode($this->documentContent());
    }

    /**
     * Get the document path
     *
     * @return string
     */
    public function documentPath(): string
    {
        return $this->path;
    }

    /**
     * Get the document name
     *
     * @return string
     */
    public function documentName(): string
    {
        return $this->name;
    }
}
