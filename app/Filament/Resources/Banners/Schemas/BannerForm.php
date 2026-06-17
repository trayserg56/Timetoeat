<?php

namespace App\Filament\Resources\Banners\Schemas;

use App\Filament\Forms\Components\CatalogImageUpload;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class BannerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('meal_set_id')
                    ->label('Связанный набор')
                    ->relationship(
                        name: 'mealSet',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query->orderBy('sort_order'),
                    )
                    ->searchable()
                    ->preload(),
                Select::make('banner_tag_id')
                    ->label('Тег баннера')
                    ->relationship(
                        name: 'bannerTag',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query->where('is_active', true)->orderBy('sort_order'),
                    )
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->label('Название тега')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug((string) $state))),
                        TextInput::make('slug')
                            ->label('Адрес')
                            ->required(),
                        Toggle::make('is_active')
                            ->label('Активен')
                            ->default(true)
                            ->required(),
                        TextInput::make('sort_order')
                            ->label('Порядок')
                            ->numeric()
                            ->required()
                            ->default(0),
                    ])
                    ->helperText('Необязательно. Можно выбрать существующий тег, создать новый прямо здесь или оставить пустым.'),
                TextInput::make('title')
                    ->label('Заголовок')
                    ->required(),
                Textarea::make('description')
                    ->label('Описание')
                    ->rows(4)
                    ->columnSpanFull(),
                CatalogImageUpload::make('image_path')
                    ->label('Изображение баннера')
                    ->disk('public')
                    ->directory('banners')
                    ->helperText('JPG, PNG, WEBP или AVIF до 2 МБ. Можно также указать внешний URL ниже.'),
                TextInput::make('image_url')
                    ->label('URL изображения')
                    ->url()
                    ->placeholder('https://example.com/banner.jpg')
                    ->helperText('Если заполнено, на сайте будет использовано это изображение вместо загруженного файла.'),
                TextInput::make('link_url')
                    ->label('Ссылка баннера')
                    ->url()
                    ->placeholder('https://example.com/news')
                    ->helperText('Если заполнено, баннер на главной будет вести по этой ссылке.'),
                DatePicker::make('menu_date')
                    ->label('Дата меню')
                    ->native(false)
                    ->helperText('Оставьте пустым, чтобы баннер показывался всегда.')
                    ->default(now('Europe/Moscow')->addDay()),
                Toggle::make('is_active')
                    ->label('Показывать')
                    ->required()
                    ->default(true),
                TextInput::make('sort_order')
                    ->label('Порядок')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
