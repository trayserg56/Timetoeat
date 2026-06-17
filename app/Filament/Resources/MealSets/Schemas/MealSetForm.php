<?php

namespace App\Filament\Resources\MealSets\Schemas;

use App\Filament\Forms\Components\CatalogImageUpload;
use App\Filament\Forms\Components\MultiDatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class MealSetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Название')
                    ->required(),
                TextInput::make('slug')
                    ->label('Адрес')
                    ->required(),
                Textarea::make('description')
                    ->label('Описание')
                    ->columnSpanFull(),
                TextInput::make('price')
                    ->label('Цена')
                    ->required()
                    ->numeric()
                    ->suffix('коп.'),
                CatalogImageUpload::make('image_path')
                    ->label('Изображение')
                    ->disk('public')
                    ->directory('meal-sets'),
                MultiDatePicker::make('menu_dates')
                    ->label('Даты меню')
                    ->default([
                        ['date' => now('Europe/Moscow')->addDay()->toDateString()],
                    ])
                    ->required()
                    ->rules(['array', 'min:1'])
                    ->helperText('Выберите все нужные даты по очереди в одном календаре. Нажмите на выбранную дату, чтобы удалить её.'),
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
                Section::make('Состав набора')
                    ->description('Добавьте блюда сразу при создании или редактировании набора.')
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->label('Блюда')
                            ->schema([
                                Select::make('product_id')
                                    ->label('Блюдо')
                                    ->relationship(
                                        name: 'product',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn ($query) => $query->where('is_active', true)->orderBy('name'),
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                TextInput::make('quantity')
                                    ->label('Количество')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1),
                            ])
                            ->reorderable()
                            ->orderColumn('sort_order')
                            ->addActionLabel('Добавить блюдо')
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
