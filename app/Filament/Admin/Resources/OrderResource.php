<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\OrderResource\Pages;
use App\Filament\Admin\Resources\OrderResource\RelationManagers\OrderDetailRelationManager;
use App\Filament\Exports\OrderExporter;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $activeNavigationIcon = 'heroicon-o-shopping-bag';
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'new')->count();
    }
    public static function getNavigationBadgeColor(): string|array|null
    {
        return static::getModel()::count() > 0 ? 'info' : 'primary';
    }
    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'The number of new orders';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['id', 'customer.name'];
    }
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Order ID' => $record->id ?? 'N/A',
            'Customer name'   => $record->customer?->name ?? 'N/A',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(3)
            ->schema([
                DateTimePicker::make('order_date')
                    ->default(now())
                    ->required()
                    ->readOnly()
                    ->dehydrated()
                    ->hiddenLabel()
                    ->prefix('Date  : ')
                    ->columnSpanFull(),
                Group::make()
                    ->columnSpan(2)
                    ->schema([


                        Section::make()
                            ->description('Customer Information')
                            ->schema([
                                Select::make('customer_id')
                                    ->relationship('customer', 'name', modifyQueryUsing: fn(\Illuminate\Database\Eloquent\Builder $query) => $query)
                                    ->label('Name')
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, $set) {
                                        $customer = Customer::find($state);
                                        $set('phone', $customer->phone ?? null);
                                        $set('address', $customer->address ?? null);
                                    })
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('phone')
                                            ->tel()
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('address')
                                            ->required()
                                            ->maxLength(255),
                                    ]),

                                Placeholder::make('phone')
                                    ->content(fn(Get $get) => Customer::find($get('customer_id'))?->phone ?? '-'),
                                Placeholder::make('address')
                                    ->content(fn(Get $get) => Customer::find($get('customer_id'))?->address ?? '-'),
                            ])->columns(3),

                        Section::make()
                            ->description('Order Details')
                            ->hiddenOn('view')
                            ->schema([
                                Repeater::make('orderdetail')
                                    ->relationship('orderdetail')
                                    ->reactive()
                                    ->deletable(fn(string $operation) => in_array($operation, ['create', 'edit']))
                                    ->addable(fn(string $operation) => in_array($operation, ['create', 'edit']))
                                    ->schema([
                                        Select::make('product_id')
                                            ->relationship('product', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->columnSpanFull()
                                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                $product = Product::find($state);
                                                $price = $product->price ?? 0;
                                                $set('price', $price);
                                                $qty = $get('qty') ?? 1;
                                                $set('qty', $qty);
                                                $set('subtotal', $price * $qty);

                                                $items = $get('../../orderdetail') ?? [];
                                                $total = collect($items)->sum(fn($item) => $item['subtotal'] ?? 0);
                                                $set('../../total_price', $total);

                                                // Hitung ulang discount & total_payment berdasarkan total baru
                                                $discount = floatval($get('../../discount') ?? 0);
                                                $discount_amount = $total * $discount / 100;
                                                $set('../../discount_amount', $discount_amount);
                                                $set('../../total_payment', $total - $discount_amount);
                                            }),
                                        TextInput::make('price')
                                            ->required()
                                            ->disabled()
                                            ->dehydrated()
                                            ->numeric()
                                            ->prefix('IDR')
                                            ->formatStateUsing(fn($state, Get $get) =>
                                            $state ?? Product::find($get('product_id'))?->price ?? 0),
                                        TextInput::make('qty')
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(1)
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                $price = $get('price') ?? 0;
                                                $set('subtotal', $price * $state);

                                                $items = $get('../../orderdetail') ?? [];
                                                $total = collect($items)->sum(fn($item) => $item['subtotal'] ?? 0);
                                                $set('../../total_price', $total);
                                                $discount = floatval($get('../../discount') ?? 0);
                                                $discount_amount = $total * $discount / 100;
                                                $set('../../discount_amount', $discount_amount);
                                                $set('../../total_payment', $total - $discount_amount);
                                            }),
                                        TextInput::make('subtotal')
                                            ->required()
                                            ->numeric()
                                            ->disabled()
                                            ->dehydrated()
                                            ->default(0)
                                            ->prefix('IDR'),
                                    ])->columns(3)
                                    ->hiddenLabel()
                                    ->addAction(
                                        fn(Action $action) => $action
                                            ->label('Add Product')
                                            ->color('primary')
                                            ->icon('heroicon-o-plus')
                                    )
                            ]),
                    ]),

                Group::make()
                    ->columnSpan(1)
                    ->schema([
                        Section::make()
                            ->description('Payment Information')
                            ->schema([
                                Select::make('status')
                                    ->options([
                                        'new' => 'New',
                                        'processing' => 'Processing',
                                        'cancelled' => 'Cancelled',
                                        'completed' => 'Completed',
                                    ])->default('new')
                                    ->columnSpanFull(),
                                Select::make('payment_method')
                                    ->options([
                                        'cash'   => 'Cash',
                                        'credit' => 'Credit Card',
                                        'debit'  => 'Debit Card',
                                        'qris'   => 'QRIS',
                                    ])->default('cash')
                                    ->columnSpanFull(),
                                Select::make('payment_status')
                                    ->options([
                                        'unpaid' => 'Unpaid',
                                        'paid'   => 'Paid',
                                        'failed' => 'Failed',
                                    ])->default('unpaid')
                                    ->columnSpanFull(),
                                TextInput::make('total_price')
                                    ->label('Total Price')
                                    ->required()
                                    ->numeric()
                                    ->readOnly()
                                    ->columnSpanFull()
                                    ->prefix('IDR')
                                    ->default(0),
                                TextInput::make('discount')
                                    ->columnSpan(2)
                                    ->default(0)
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $discount = floatval($state) ?? 0;
                                        $total_price = $get('total_price') ?? 0;
                                        $discount_amount = $total_price * $discount / 100;
                                        $set('discount_amount', $discount_amount);
                                        $set('total_payment', $total_price - $discount_amount);
                                    })->suffix('%'),
                                TextInput::make('discount_amount')
                                    ->columnSpan(2)
                                    ->readOnly()
                                    ->prefix('IDR')
                                    ->default(0),
                                TextInput::make('total_payment')
                                    ->columnSpanFull()
                                    ->readOnly()
                                    ->prefix('IDR')
                                    ->default(0),
                            ])->columnSpan(1)
                            ->columns(4),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Order ID'),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_price')
                    ->label('Total price')
                    ->formatStateUsing(fn($state) => 'IDR ' . number_format($state, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('discount')
                    ->suffix('%')
                    ->toggledHiddenByDefault(),
                TextColumn::make('discount_amount')
                    ->formatStateUsing(fn($state) => 'IDR ' . number_format($state, 0, ',', '.')),
                TextColumn::make('total_payment')
                    ->formatStateUsing(fn($state) => 'IDR ' . number_format($state, 0, ',', '.')),
                TextColumn::make('payment_method')
                    ->label('Payment method')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('payment_status')
                    ->label('Payment status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'paid'   => 'success',
                        'unpaid' => 'warning',
                        'failed' => 'danger',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'new'        => 'info',
                        'processing' => 'warning',
                        'completed'  => 'success',
                        'cancelled'  => 'danger',
                    }),
                Tables\Columns\TextColumn::make('order_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(OrderExporter::class)
                    ->label('Download Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            OrderDetailRelationManager::class,
        ];
    }

    public static function getWidgets(): array
    {
        return [
            OrderResource\Widgets\OrderStats::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
