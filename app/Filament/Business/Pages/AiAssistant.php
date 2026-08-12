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

    protected static ?string $navigationLabel = 'Ask Stox';

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
     * @var array<string, string>
     */
    protected const SUGGESTED_QUESTIONS = [
        'top_products' => 'What are my top-selling products?',
        'low_stock' => 'Which products are low in stock?',
        'recent_sales' => 'Analyze my recent sales',
        'business_summary' => 'Give me a business summary',
    ];

    /**
     * @return array<string, string>
     */
    public function suggestedQuestions(): array
    {
        return self::SUGGESTED_QUESTIONS;
    }

    /**
     * Bound by key rather than the raw question text, since the text
     * contains apostrophes/spaces that would need escaping in a Livewire
     * wire:click argument — a key is simplest and can't be malformed.
     */
    public function askSuggestion(string $key): void
    {
        $question = self::SUGGESTED_QUESTIONS[$key] ?? null;

        if ($question === null) {
            return;
        }

        $this->question = $question;
        $this->ask();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('clearConversation')
                ->label('Clear Conversation')
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
            $this->conversation[] = ['role' => 'assistant', 'content' => "Sorry, I couldn't answer that: {$e->getMessage()}"];
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
