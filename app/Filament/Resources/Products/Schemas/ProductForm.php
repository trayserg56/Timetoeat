<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Filament\Forms\Components\CatalogImageUpload;
use App\Filament\Forms\Components\MultiDatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')
                    ->label('Категория')
                    ->relationship('category', 'name'),
                TextInput::make('name')
                    ->label('Название')
                    ->required(),
                TextInput::make('slug')
                    ->label('Адрес')
                    ->required(),
                Textarea::make('description')
                    ->label('Описание')
                    ->columnSpanFull(),
                Textarea::make('ingredients')
                    ->label('Состав')
                    ->rows(3)
                    ->helperText('Например: курица, лапша, морковь, зелень.')
                    ->columnSpanFull(),
                TextInput::make('price')
                    ->label('Цена')
                    ->required()
                    ->numeric()
                    ->suffix('коп.'),
                CatalogImageUpload::make('image_path')
                    ->label('Изображение')
                    ->disk('public')
                    ->directory('products'),
                TextInput::make('weight_grams')
                    ->label('Вес')
                    ->suffix('г')
                    ->numeric(),
                MultiDatePicker::make('menu_dates')
                    ->label('Даты меню')
                    ->default([
                        ['date' => now('Europe/Moscow')->addDay()->toDateString()],
                    ])
                    ->helperText('Выберите даты по очереди в одном календаре. Нажмите на выбранную дату, чтобы удалить её. Пустой список означает доступность каждый день.'),
                Select::make('tags')
                    ->label('Теги')
                    ->relationship(
                        name: 'tags',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query->where('is_active', true)->orderBy('sort_order'),
                    )
                    ->multiple()
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
                    ->helperText('Можно выбрать существующие теги или создать новый.'),
                Toggle::make('is_active')
                    ->label('Показывать')
                    ->required(),
                Toggle::make('is_available')
                    ->label('Доступно для заказа')
                    ->required(),
                TextInput::make('sort_order')
                    ->label('Порядок')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
