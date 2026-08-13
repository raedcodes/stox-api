<?php

namespace App\Filament\Business\Concerns;

use App\Services\Gemini\GeminiClient;
use Filament\Notifications\Notification;
use RuntimeException;

/**
 * Shared "Generate AI Summary" behavior for report pages: structured
 * (executive summary / key insights / warnings / recommendations) output
 * from Gemini, cached per tenant+scope (see HasAiSummaryCache for why that
 * needs its own bypass), with both a toast AND a persisted inline error
 * state so a failed generation doesn't just flash and disappear.
 *
 * Pages using this trait must implement:
 * - buildAiSummaryPrompt(): string — the figures to summarize, as plain text
 * - aiSummarySystemInstruction(): string — the report-specific analyst role
 * - aiSummaryCacheKey(): string — a tenant+scope-specific cache key
 * - aiSummaryKpis(): array — 2-3 headline {label, value} pairs computed
 *   directly from the report's own already-computed figures (no AI
 *   involved), shown above the AI narrative so the exact numbers are never
 *   left to the model's arithmetic.
 */
trait HasStructuredAiSummary
{
    use HasAiSummaryCache;

    public ?array $aiSummary = null;

    public ?string $aiSummaryError = null;

    /**
     * @return array<int, array{label: string, value: string}>
     */
    abstract public function aiSummaryKpis(): array;

    protected function loadCachedAiSummary(): void
    {
        $this->aiSummary = $this->aiSummaryCache()->get($this->aiSummaryCacheKey());
        $this->aiSummaryError = null;
    }

    /**
     * Key insights and warnings are split into a bold "headline" fragment
     * (a product name or a specific figure) plus a plain continuation, so
     * the Blade partial can emphasize exactly the right words without
     * parsing markdown out of free-form AI text.
     *
     * @return array<string, mixed>
     */
    protected function aiSummaryResponseSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'executive_summary' => [
                    'type' => 'string',
                    'description' => 'One or two sentences summarizing the overall picture, referencing at least one concrete figure.',
                ],
                'key_insights' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'highlight' => [
                                'type' => 'string',
                                'description' => "A specific product name or key figure to emphasize, e.g. 'Mechanical Keyboard' or '\$2,214.16 (52.7%)'. Short — a few words at most.",
                            ],
                            'detail' => [
                                'type' => 'string',
                                'description' => "A short continuation phrase with concrete numbers that follows the highlight, e.g. '\$719.88 revenue · 12 units'. Do not repeat the highlight text.",
                            ],
                        ],
                        'required' => ['highlight', 'detail'],
                    ],
                    'description' => 'Concise, scannable observations, each built from real figures — not generic commentary.',
                ],
                'warnings' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'metric' => [
                                'type' => 'string',
                                'description' => "The specific metric name causing concern, e.g. 'Average Order Value'.",
                            ],
                            'value' => [
                                'type' => 'string',
                                'description' => "That metric's current value, e.g. '\$72.49'.",
                            ],
                            'text' => [
                                'type' => 'string',
                                'description' => 'One concise sentence explaining why this is worth attention.',
                            ],
                            'severity' => ['type' => 'string', 'enum' => ['high', 'medium', 'low']],
                        ],
                        'required' => ['metric', 'value', 'text', 'severity'],
                    ],
                ],
                'recommendations' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Short, actionable, imperative recommendations, each under about 12 words, e.g. "Bundle high-volume products with higher-margin products." No full paragraphs, no restating the figures above.',
                ],
            ],
            'required' => ['executive_summary', 'key_insights', 'warnings', 'recommendations'],
        ];
    }

    /**
     * Cached for 15 minutes per tenant+scope so re-opening the report or
     * re-clicking the button doesn't re-bill the Gemini API for the same
     * figures — deliberately conservative given the free tier's daily quota.
     */
    protected function generateAiSummary(): void
    {
        $this->aiSummaryError = null;

        try {
            $this->aiSummary = $this->aiSummaryCache()->remember(
                $this->aiSummaryCacheKey(),
                now()->addMinutes(15),
                function () {
                    $data = (new GeminiClient())->generateStructured(
                        $this->buildAiSummaryPrompt(),
                        $this->aiSummarySystemInstruction(),
                        $this->aiSummaryResponseSchema()
                    );

                    $data['generated_at'] = now()->toIso8601String();

                    return $data;
                }
            );
        } catch (RuntimeException $e) {
            $this->aiSummaryError = $e->getMessage();

            Notification::make()
                ->title(__('ai_summary.notifications.cannot_generate'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
