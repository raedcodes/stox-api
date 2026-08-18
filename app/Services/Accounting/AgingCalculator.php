<?php

namespace App\Services\Accounting;

use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Neither CustomerTransaction nor SupplierTransaction links a reducing
 * entry (payment/return) to which specific increasing entry (credit sale /
 * charge) it pays down — Customer::balance()/Supplier::balance() are grand
 * totals only. To bucket "how old is the still-unpaid portion" without any
 * schema change, this allocates FIFO: walk a party's transactions
 * oldest-first, and have each reducing transaction pay down the oldest
 * still-outstanding increasing transaction(s) first. Whatever remains
 * unpaid per entry is aged by its own due date.
 */
class AgingCalculator
{
    public const BUCKETS = ['current', '1_30', '31_60', '61_90', 'over_90'];

    /**
     * @param  Collection<int, object{type: object{value: string}, amount: mixed}>  $transactions  ordered oldest-first (by transaction_date, then id)
     * @param  object{value: string}  $increasingType
     * @param  array<object{value: string}>  $reducingTypes
     * @param  Closure(object): Carbon  $dueDateResolver
     * @return array{current: float, 1_30: float, 31_60: float, 61_90: float, over_90: float, total: float}
     */
    public static function bucket(Collection $transactions, object $increasingType, array $reducingTypes, Closure $dueDateResolver, Carbon $asOf): array
    {
        $reducingValues = array_map(fn (object $type) => $type->value, $reducingTypes);

        /** @var array<int, array{remaining: float, due_date: Carbon}> $queue */
        $queue = [];

        // Two passes, not one: build the full oldest-first queue of
        // increasing entries FIRST, then apply every reducing transaction
        // against it. A single combined pass would fail to net a reducing
        // transaction recorded with an earlier (or same-day, earlier
        // time-of-day) transaction_date than the increasing entry it
        // actually pays down — the queue would still be empty when that
        // reducing transaction is processed, silently dropping it instead
        // of applying it, and the bucketed total would no longer match
        // Customer::balance()/Supplier::balance().
        foreach ($transactions as $transaction) {
            if ($transaction->type === $increasingType) {
                $queue[] = [
                    'remaining' => (float) $transaction->amount,
                    'due_date' => $dueDateResolver($transaction),
                ];
            }
        }

        foreach ($transactions as $transaction) {
            if (! in_array($transaction->type->value, $reducingValues, true)) {
                continue;
            }

            $toConsume = (float) $transaction->amount;

            foreach ($queue as &$entry) {
                if ($toConsume <= 0) {
                    break;
                }

                $consume = min($entry['remaining'], $toConsume);
                $entry['remaining'] -= $consume;
                $toConsume -= $consume;
            }

            unset($entry);
        }

        // A party that has overpaid (reducing transactions exceed
        // increasing ones) simply floors at all-zero buckets here — there's
        // nothing currently owed to age. That's a real credit balance owed
        // *back* to them, a different concern from aging exposure, and
        // still visible via Customer::balance()/Supplier::balance() going
        // negative; it deliberately isn't a negative bucket in this report.
        $buckets = array_fill_keys(self::BUCKETS, 0.0);

        foreach ($queue as $entry) {
            if ($entry['remaining'] <= 0.005) {
                continue;
            }

            $daysPastDue = $asOf->greaterThan($entry['due_date']) ? $entry['due_date']->diffInDays($asOf) : 0;

            $bucket = match (true) {
                $daysPastDue <= 0 => 'current',
                $daysPastDue <= 30 => '1_30',
                $daysPastDue <= 60 => '31_60',
                $daysPastDue <= 90 => '61_90',
                default => 'over_90',
            };

            $buckets[$bucket] += $entry['remaining'];
        }

        $buckets['total'] = array_sum($buckets);

        return $buckets;
    }
}
