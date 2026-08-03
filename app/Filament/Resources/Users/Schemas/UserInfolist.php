<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Business')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('tenant.name')
                            ->label('Business'),
                        TextEntry::make('tenant.domain.domain')
                            ->label('Subdomain')
                            ->url(function (User $record): ?string {
                                $subdomain = $record->tenant?->domain?->domain;

                                if (blank($subdomain)) {
                                    return null;
                                }

                                $host = request()->getHost();

                                $centralDomain = collect(config('tenancy.central_domains'))
                                    ->first(fn (string $domain): bool => $host === $domain || str_ends_with($host, ".{$domain}"));

                                $baseDomain = $centralDomain ?? $host;
                                $port = request()->getPort();
                                $portSuffix = in_array($port, [80, 443], true) ? '' : ":{$port}";

                                return request()->getScheme()."://{$subdomain}.{$baseDomain}{$portSuffix}/app";
                            })
                            ->openUrlInNewTab(),
                    ]),
                Section::make('Account')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('email')
                            ->label('Email'),
                        TextEntry::make('role')
                            ->label('Role')
                            ->state(fn (User $record) => $record->getRoleNames()->first()),
                        IconEntry::make('is_active')
                            ->label('Active')
                            ->boolean(),
                        TextEntry::make('email_verified_at')
                            ->label('Email verified at')
                            ->dateTime()
                            ->placeholder('—'),
                        TextEntry::make('created_at')
                            ->dateTime(),
                    ]),
            ]);
    }
}
