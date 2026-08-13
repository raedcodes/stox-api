<?php

namespace App\Filament\Business\Pages;

use App\Enums\UserRole;
use App\Services\AiAssistant\StoxAssistantTools;
use App\Services\Gemini\GeminiClient;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use RuntimeException;
use UnitEnum;

/**
 * "Ask Stox" — a chat interface over the business's own data, backed by
 * Gemini function-calling: the model never sees raw SQL, it only ever
 * calls the read-only tools declared in StoxAssistantTools, which query
 * already-tenant-scoped Eloquent models exactly like every other page in
 * this panel.
 *
 * $conversation is what the Blade view renders (plain {role, content}
 * pairs); $geminiHistory is the parallel, Gemini-wire-format history
 * (role: user/model, parts: [...]) used only to drive the API round trip —
 * kept separate so the view never has to understand functionCall/
 * functionResponse parts.
 */
class AiAssistant extends Page
{
    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->hasAnyRole([UserRole::Owner, UserRole::Manager]);
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $navigationLabel = null;

    protected static ?string $title = null;

    public static function getNavigationLabel(): string
    {
        return __('ai_assistant.navigation_label');
    }

    public function getTitle(): string
    {
        return __('ai_assistant.title');
    }

    protected static UnitEnum|string|null $navigationGroup = null;

    protected static ?int $navigationSort = -1;

    protected string $view = 'filament.business.pages.ai-assistant';

    protected const MAX_TOOL_ROUNDS = 5;

    /**
     * @var array<int, array{role: string, content: string}>
     */
    public array $conversation = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $geminiHistory = [];

    public string $question = '';

    /**
     * @var array<int, string>
     */
    protected const SUGGESTED_QUESTION_KEYS = ['top_products', 'low_stock', 'profit_this_month', 'expiring_batches', 'business_summary'];

    /**
     * @return array<string, string>
     */
    public function suggestedQuestions(): array
    {
        return collect(self::SUGGESTED_QUESTION_KEYS)
            ->mapWithKeys(fn (string $key) => [$key => __('ai_assistant.suggested_questions.'.$key)])
            ->all();
    }

    /**
     * Gemini's plain-text answers sometimes include basic markdown
     * (**bold**, "* " bullet lines, *italic* emphasis) even though this
     * chat has no markdown renderer — the raw asterisks were showing up
     * literally. Escapes first (never trust AI-echoed content as safe HTML
     * — e.g. a customer name it reads back from a tool result could
     * contain arbitrary text), then converts each pattern in turn: bold
     * before bullets before italics, so a line's leading "* " marker is
     * already gone before the italic pass runs and can't be mistaken for
     * an opening emphasis delimiter. Deliberately not a full markdown
     * parser — narrow and predictable rather than open-ended.
     */
    public function formatAssistantMessage(string $content): string
    {
        $withBold = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', e($content));

        $withBullets = preg_replace('/^[ \t]*\*[ \t]+/m', '• ', $withBold);

        return preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $withBullets);
    }

    /**
     * Bound by key rather than the raw question text, since the text
     * contains apostrophes/spaces that would need escaping in a Livewire
     * wire:click argument — a key is simplest and can't be malformed.
     */
    public function askSuggestion(string $key): void
    {
        if (! in_array($key, self::SUGGESTED_QUESTION_KEYS, true)) {
            return;
        }

        $this->question = __('ai_assistant.suggested_questions.'.$key);
        $this->ask();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('clearConversation')
                ->label(__('ai_assistant.clear_conversation'))
                ->icon('heroicon-o-trash')
                ->color('gray')
                ->visible(fn () => ! empty($this->conversation))
                ->requiresConfirmation()
                ->action(fn () => $this->clearConversation()),
        ];
    }

    public function clearConversation(): void
    {
        $this->conversation = [];
        $this->geminiHistory = [];
    }

    public function ask(): void
    {
        $question = trim($this->question);

        if ($question === '') {
            return;
        }

        $this->conversation[] = ['role' => 'user', 'content' => $question];
        $this->geminiHistory[] = ['role' => 'user', 'parts' => [['text' => $question]]];
        $this->question = '';

        try {
            $answer = $this->runConversationLoop();
            $this->conversation[] = ['role' => 'assistant', 'content' => $answer];
        } catch (RuntimeException $e) {
            $this->conversation[] = ['role' => 'assistant', 'content' => __('ai_assistant.error_prefix', ['message' => $e->getMessage()])];
        }
    }

    protected function runConversationLoop(): string
    {
        $client = new GeminiClient();
        $tools = new StoxAssistantTools();
        $toolDefinitions = $tools->definitions();
        $systemInstruction = $this->systemInstruction();

        for ($round = 0; $round < self::MAX_TOOL_ROUNDS; $round++) {
            $turn = $client->sendConversation($this->geminiHistory, $systemInstruction, $toolDefinitions);
            $this->geminiHistory[] = $turn;

            $functionCalls = array_values(array_filter(
                $turn['parts'] ?? [],
                fn (array $part) => isset($part['functionCall'])
            ));

            if (empty($functionCalls)) {
                $text = $turn['parts'][0]['text'] ?? null;

                if (blank($text)) {
                    throw new RuntimeException('The assistant did not return an answer.');
                }

                return trim($text);
            }

            $responses = [];

            foreach ($functionCalls as $part) {
                $call = $part['functionCall'];
                $result = $tools->call($call['name'], $call['args'] ?? []);
                $responses[] = ['functionResponse' => ['name' => $call['name'], 'response' => $result]];
            }

            $this->geminiHistory[] = ['role' => 'user', 'parts' => $responses];
        }

        throw new RuntimeException('This question needed too many lookups to answer — try asking something more specific.');
    }

    protected function systemInstruction(): string
    {
        return "You are Stox's AI business assistant, helping the owner or manager of a small retail/wholesale business understand their own data. Today's date is ".now()->toDateString().' — interpret relative dates ("this month", "last week", "today") relative to this date.'
            ." Always use the provided tools to look up real figures before answering — never guess or make up numbers. If a tool reports zero results or an error, say so plainly rather than inventing an answer."
            .' Keep answers conversational and concise — a sentence or two for simple questions, a short list for anything involving multiple items. Reference concrete figures from the tool results.';
    }
}
