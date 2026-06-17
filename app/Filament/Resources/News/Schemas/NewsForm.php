<?php

namespace App\Filament\Resources\News\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class NewsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Заголовок')
                    ->required(),
                TextInput::make('slug')
                    ->label('Адрес')
                    ->required(),
                Textarea::make('excerpt')
                    ->label('Краткое описание')
                    ->rows(3)
                    ->columnSpanFull(),
                Textarea::make('content')
                    ->label('Текст новости')
                    ->rows(10)
                    ->required()
                    ->columnSpanFull(),
                FileUpload::make('image_path')
                    ->label('Изображение')
                    ->disk('public')
                    ->directory('news')
                    ->image()
                    ->helperText('Можно загрузить файл или указать внешний URL ниже.'),
                TextInput::make('image_url')
                    ->label('URL изображения')
                    ->url()
                    ->placeholder('https://example.com/news-cover.jpg'),
                DateTimePicker::make('published_at')
                    ->label('Дата публикации')
                    ->required()
                    ->native(false)
                    ->default(now()),
                Toggle::make('is_active')
                    ->label('Опубликовано')
                    ->required()
                    ->default(true),
                TextInput::make('sort_order')
                    ->label('Порядок')
                    ->numeric()
                    ->required()
                    ->default(0),
            ]);
    }
}
