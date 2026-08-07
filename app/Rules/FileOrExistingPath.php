<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileOrExistingPath implements ValidationRule
{
    public function __construct(
        protected array $mimes = ['jpg', 'jpeg', 'png', 'pdf','doc','docx'],
        protected int $maxKb = 5120,
        protected bool $checkExists = false, // set true kung gusto mong i-verify na existing sa storage
        protected string $disk = 'public',
    ) {}

    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        // Case 1: bagong upload
        if ($value instanceof UploadedFile) {
            $ext = strtolower($value->getClientOriginalExtension());

            if (!in_array($ext, $this->mimes)) {
                $fail("The {$attribute} must be a file of type: " . implode(', ', $this->mimes) . '.');
                return;
            }

            if ($value->getSize() > $this->maxKb * 1024) {
                $fail("The {$attribute} must not be greater than {$this->maxKb} kilobytes.");
            }
            return;
        }

        // Case 2: existing path string (galing sa dating fetch)
        if (is_string($value) && trim($value) !== '') {
            if ($this->checkExists && !Storage::disk($this->disk)->exists($value)) {
                $fail("The {$attribute} refers to a file that no longer exists.");
            }
            return;
        }

        $fail("The {$attribute} must be a file or a valid existing file path.");
    }
}