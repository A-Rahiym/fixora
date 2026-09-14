<?php

namespace App\Services;

use App\Models\Customer;

/**
 * Duplicate-customer guard (brief G6, database-design §6).
 *
 * Dedup is application-layer on purpose: household phone reuse is
 * legitimate, so there is no DB unique constraint — the service
 * normalizes the phone (strip spaces, dashes, parens, leading +)
 * and returns the existing record for a 409-style warning.
 */
class CustomerDedupService
{
    public function normalizePhone(string $phone): string
    {
        $normalized = preg_replace('/[\s\-().]/', '', $phone) ?? '';

        return ltrim($normalized, '+');
    }

    public function findDuplicate(string $phone, ?int $ignoreId = null): ?Customer
    {
        $normalized = $this->normalizePhone($phone);

        return Customer::query()
            ->get(['id', 'phone'])
            ->first(function (Customer $customer) use ($normalized, $ignoreId): bool {
                if ($ignoreId !== null && $customer->id === $ignoreId) {
                    return false;
                }

                return $this->normalizePhone($customer->phone) === $normalized;
            });
    }
}
