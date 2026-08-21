<?php

namespace App\Services\Admin;

use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class AuditService
{
    /**
     * Persist an administrator action without retaining credentials, payment
     * details, personal contact data, or provider payloads in audit JSON.
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public function record(
        User $admin,
        string $action,
        Model|string $entity,
        array $before = [],
        array $after = [],
    ): AdminAuditLog {
        $action = trim($action);

        if ($action === '' || strlen($action) > 120) {
            throw new InvalidArgumentException('Audit actions must contain at most 120 characters.');
        }

        [$entityType, $entityId] = $this->entityDetails($entity);

        return AdminAuditLog::query()->create([
            'admin_user_id' => $admin->getKey(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'before_values' => $this->redact($before),
            'after_values' => $this->redact($after),
        ]);
    }

    /**
     * Re-apply the same redaction rules before rendering historical audit rows.
     * This protects the viewer even if an older row predates current writers.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function sanitizeForDisplay(array $attributes): array
    {
        return $this->redact($attributes);
    }

    /**
     * @return array{string, string|null}
     */
    private function entityDetails(Model|string $entity): array
    {
        if (is_string($entity)) {
            $entityType = trim($entity);

            if ($entityType === '' || strlen($entityType) > 191) {
                throw new InvalidArgumentException('Audit entity types must contain at most 191 characters.');
            }

            return [$entityType, null];
        }

        $entityType = $entity::class;
        $entityId = $entity->getKey();

        if (strlen($entityType) > 191) {
            throw new InvalidArgumentException('Audit entity types must contain at most 191 characters.');
        }

        if ($entityId !== null && strlen((string) $entityId) > 191) {
            throw new InvalidArgumentException('Audit entity IDs must contain at most 191 characters.');
        }

        return [$entityType, $entityId === null ? null : (string) $entityId];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function redact(array $attributes): array
    {
        $redacted = [];

        foreach ($attributes as $key => $value) {
            $key = (string) $key;

            if ($this->isSensitiveKey($key)) {
                $redacted[$key] = '[redacted]';

                continue;
            }

            $redacted[$key] = $this->redactValue($value);
        }

        return $redacted;
    }

    private function redactValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return $this->redact($value);
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if (is_bool($value) || is_int($value) || is_float($value) || is_string($value) || $value === null) {
            return $value;
        }

        // Models, request objects and provider SDK objects can carry fields
        // that are not safe to serialize into a durable audit record.
        return '[omitted non-scalar value]';
    }

    private function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $key));

        if ((bool) preg_match(
            '/(?:^|_)(?:password|password_hash|password_confirmation|current_password|new_password|remember_token|token|access_token|refresh_token|api_key|private_key|secret|client_secret|authorization|cookie|credential|cvv|cvc|security_code|card_number|credit_card|bank_account|account_number|routing_number|iban|swift|payment_method|provider_data)(?:$|_)/',
            $normalized,
        )) {
            return true;
        }

        if (str_contains($normalized, 'provider_data')
            || str_contains($normalized, 'provider_payload')
            || str_contains($normalized, 'provider_response')
            || str_contains($normalized, 'secret_payload')
            || str_contains($normalized, 'payment_payload')
            || str_contains($normalized, 'gateway_payload')) {
            return true;
        }

        return in_array($normalized, [
            'email',
            'phone',
            'full_name',
            'address',
            'resume_path',
            'resume_disk',
            'ip_address',
            'date_of_birth',
            'passport_number',
            'national_id',
        ], true);
    }
}
