<?php

namespace App\Filament\Admin\Resources\OrderResource\RelationManagers;

use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Table;

class OrderDetailRelationManager extends RelationManager
{
    protected static string $relationship = 'orderdetail';

    protected static ?string $recordTitleAttribute = 'id';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                        $product = Product::find($state);
                        $price = $product->price ?? 0;
                        $set('price', $price);
                        $qty = $get('qty') ?? 1;
                        $set('subtotal', $price * $qty);
                    }),

                Forms\Components\TextInput::make('price')
                    ->numeric()
                    ->disabled()
                    ->dehydrated()
                    ->formatStateUsing(fn ($state, Get $get) =>
                        $state ?? Product::find($get('product_id'))?->price ?? 0),

                Forms\Components\TextInput::make('qty')
                    ->numeric()
                    ->default(1)
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                        $price = $get('price') ?? 0;
                        $set('subtotal', $price * $state);
                    }),

                Forms\Components\TextInput::make('subtotal')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(),
            ])->columns(4);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Order Detail')
            ->columns([
                ImageColumn::make('product.image')
                    ->label('Image')
                    ->disk('public'),
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product'),
                Tables\Columns\TextColumn::make('product.price')
                    ->label('Price'),
                Tables\Columns\TextColumn::make('qty')
                    ->label('Qty'),
                Tables\Columns\TextColumn::make('subtotal')
                    ->label('Subtotal'),
            ])
            ->filters([])
            ->headerActions([
                Tables\Actions\CreateAction::make(),    
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
