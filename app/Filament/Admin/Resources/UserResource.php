<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserResource\Pages;
use App\Models\Campus;
use App\Models\Node;
use App\Models\School;
use App\Models\User;
use App\Support\Search\LikeSearch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Formadores';

    protected static ?string $modelLabel = 'Formador';

    protected static ?string $pluralModelLabel = 'Formadores';

    private const DOCUMENT_TYPES = [
        'CEDULA_CIUDADANIA' => 'CÉDULA DE CIUDADANÍA',
        'CEDULA_EXTRANJERIA' => 'CÉDULA DE EXTRANJERÍA',
        'NUIP' => 'NUIP (MENORES DE EDAD)',
        'NIP' => 'NIP (EXTRANJEROS)',
        'PEP' => 'PERMISO ESPECIAL DE PERMANENCIA',
        'PPT' => 'PERMISO POR PROTECCIÓN TEMPORAL',
        'NIT' => 'NIT',
        'OTRO' => 'OTRO',
    ];

    private const SEX_AT_BIRTH = [
        'MASCULINO' => 'MASCULINO',
        'FEMENINO' => 'FEMENINO',
        'INTERSEXUAL' => 'INTERSEXUAL O INDETERMINADO',
        'NO_APLICA' => 'NO APLICA',
    ];

    private const GENDER_IDENTITY = [
        'HOMBRE' => 'HOMBRE',
        'MUJER' => 'MUJER',
        'HOMBRE_TRANS' => 'HOMBRE TRANS',
        'MUJER_TRANS' => 'MUJER TRANS',
        'NO_BINARIA' => 'PERSONA NO BINARIA',
        'NO_APLICA' => 'NO APLICA',
    ];

    private const SEXUAL_ORIENTATION = [
        'HETEROSEXUAL' => 'HETEROSEXUAL',
        'GAY' => 'GAY',
        'LESBIANA' => 'LESBIANA',
        'BISEXUAL' => 'BISEXUAL',
        'ASEXUAL' => 'ASEXUAL',
        'DEMISEXUAL' => 'DEMISEXUAL',
        'PANSEXUAL' => 'PANSEXUAL',
        'NO_APLICA' => 'NO APLICA',
    ];

    private const ETHNIC_BELONGING = [
        'INDIGENA' => 'INDÍGENA',
        'GITANO_RROM' => 'GITANO(A) O RROM',
        'RAIZAL' => 'RAIZAL DEL ARCHIPIÉLAGO DE SAN ANDRÉS',
        'PALENQUERO' => 'PALENQUERO(A) DE SAN BASILIO',
        'NEGRO' => 'NEGRO(A)',
        'MULATO' => 'MULATO(A)',
        'AFRODESCENDIENTE' => 'AFRODESCENDIENTE',
        'AFROCOLOMBIANO' => 'AFROCOLOMBIANO(A)',
        'NINGUNO' => 'NINGÚN GRUPO ÉTNICO',
    ];

    private const DISABILITY = [
        'NO' => 'NO',
        'AUDITIVA' => 'AUDITIVA',
        'FISICA' => 'FÍSICA',
        'INTELECTUAL' => 'INTELECTUAL',
        'VISUAL' => 'VISUAL',
        'SORDOCEGUERA' => 'SORDOSEGUERA',
        'PSICOSOCIAL' => 'PSICOSOCIAL',
        'MULTIPLE' => 'MÚLTIPLE',
    ];

    private static function municipalityOptionsForNode(?int $nodeId): array
    {
        if (! $nodeId) {
            return [];
        }

        $node = Node::query()->with('departments:id,name')->find($nodeId);
        if (! $node) {
            return [];
        }

        $departmentIds = $node->departments->pluck('id')->all();

        $query = \App\Models\Municipality::query()
            ->with('department:id,name')
            ->whereHas('schools', fn (Builder $schoolQuery) => $schoolQuery->where('node_id', $nodeId))
            ->orderBy('name');

        if (! empty($departmentIds)) {
            $query->whereIn('department_id', $departmentIds);
        }

        $grouped = [];
        foreach ($query->get() as $municipality) {
            $group = $municipality->department?->name ?? 'Sin departamento';
            $grouped[$group][$municipality->id] = $municipality->name;
        }

        ksort($grouped);

        return $grouped;
    }

    private static function roleOptions(): array
    {
        return Role::query()
            ->whereIn('name', ['super_admin', 'node_owner', 'teacher'])
            ->pluck('name')
            ->mapWithKeys(function (string $name): array {
                $label = match ($name) {
                    'super_admin' => 'Super administrador',
                    'node_owner' => 'Lider de nodo',
                    'teacher' => 'Formador',
                    default => $name,
                };

                return [$name => $label];
            })
            ->toArray();
    }

    private static function resolvedRole(Forms\Get $get): ?string
    {
        $user = auth()->user();

        if ($user?->hasRole('node_owner')) {
            return 'teacher';
        }

        $role = $get('role');

        return is_string($role) ? $role : null;
    }

    private static function isTeacherContext(Forms\Get $get): bool
    {
        return self::resolvedRole($get) === 'teacher';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('role')
                    ->label('Rol')
                    ->options(fn (): array => self::roleOptions())
                    ->required(fn () => auth()->user()?->hasRole('super_admin'))
                    ->visible(fn () => auth()->user()?->hasRole('super_admin'))
                    ->formatStateUsing(fn ($state, ?User $record) => $record?->roles->first()?->name)
                    ->dehydrated()
                    ->live(),
                Forms\Components\TextInput::make('first_name')
                    ->label('Primer Nombre')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('second_name')
                    ->label('Segundo Nombre')
                    ->maxLength(100),
                Forms\Components\TextInput::make('first_last_name')
                    ->label('Primer Apellido')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('second_last_name')
                    ->label('Segundo Apellido')
                    ->maxLength(100),
                Forms\Components\TextInput::make('email')
                    ->label('Correo')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\Section::make('Información personal')
                    ->schema([
                        Forms\Components\Select::make('document_type')
                            ->label('Tipo Documento')
                            ->options(self::DOCUMENT_TYPES)
                            ->required()
                            ->searchable(),
                        Forms\Components\TextInput::make('document_number')
                            ->label('Número Identificación')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\DatePicker::make('birth_date')
                            ->label('Fecha Nacimiento (AAAA/MM/DD)')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->format('Y-m-d')
                            ->defaultFocusedDate(now()->subYears(18)->toDateString())
                            ->maxDate(now()->subYears(18))
                            ->closeOnDateSelection()
                            ->rules([
                                'required',
                                'date',
                                'before_or_equal:' . now()->subYears(18)->toDateString(),
                            ])
                            ->validationMessages([
                                'before_or_equal' => 'La persona debe ser mayor de 18 años.',
                            ])
                            ->required(),
                        Forms\Components\Select::make('sex_at_birth')
                            ->label('Sexo asignado al nacer')
                            ->options(self::SEX_AT_BIRTH)
                            ->required(),
                        Forms\Components\Select::make('gender_identity')
                            ->label('Identidad Género')
                            ->options(self::GENDER_IDENTITY)
                            ->required(),
                        Forms\Components\Select::make('sexual_orientation')
                            ->label('Orientación Sexual')
                            ->options(self::SEXUAL_ORIENTATION)
                            ->required(),
                        Forms\Components\Select::make('ethnic_belonging')
                            ->label('Pertenencia Étnica')
                            ->options(self::ETHNIC_BELONGING)
                            ->required(),
                        Forms\Components\Select::make('disability')
                            ->label('Discapacidad')
                            ->options(self::DISABILITY)
                            ->required(),
                    ])
                    ->columns(2)
                    ->visible(fn (Forms\Get $get) => self::isTeacherContext($get))
                    ->columnSpanFull(),
                Forms\Components\Section::make('Información adicional')
                    ->schema([
                        Forms\Components\Toggle::make('is_peasant')
                            ->label('¿Eres Campesino/a?')
                            ->default(false),
                        Forms\Components\Toggle::make('is_migrant_population')
                            ->label('¿Población Migrante?')
                            ->default(false),
                        Forms\Components\Toggle::make('is_social_barra')
                            ->label('¿Barrismo Social?')
                            ->default(false),
                        Forms\Components\Toggle::make('is_private_freedom_population')
                            ->label('¿Población Privada de la Libertad?')
                            ->default(false),
                        Forms\Components\Toggle::make('is_human_rights_defender')
                            ->label('¿Persona Defensora de Derechos Humanos?')
                            ->default(false),
                    ])
                    ->columns(2)
                    ->visible(fn (Forms\Get $get) => self::isTeacherContext($get))
                    ->columnSpanFull(),
                Forms\Components\Select::make('primary_node_id')
                    ->label('Nodo principal')
                    ->options(function () {
                        $query = Node::query()
                            ->with('departments')
                            ->orderByRaw("CAST(REPLACE(name, 'Nodo ', '') AS UNSIGNED)")
                            ->orderBy('name');

                        $user = auth()->user();
                        if ($user && $user->hasRole('node_owner')) {
                            if (! $user->primary_node_id) {
                                return [];
                            }

                            $query->where('id', $user->primary_node_id);
                        }

                        return $query->get()->mapWithKeys(function (Node $node) {
                            $departments = $node->departments->pluck('name')->filter()->values();
                            $suffix = $departments->isEmpty() ? '' : ' - ' . $departments->join(', ');

                            return [$node->id => $node->name . $suffix];
                        });
                    })
                    ->default(fn () => auth()->user()?->hasRole('node_owner') ? auth()->user()?->primary_node_id : null)
                    ->searchable()
                    ->preload()
                    ->required(fn (Forms\Get $get) => in_array(self::resolvedRole($get), ['teacher', 'node_owner'], true))
                    ->visible(fn (Forms\Get $get) => ! auth()->user()?->hasRole('node_owner') && in_array(self::resolvedRole($get), ['teacher', 'node_owner'], true))
                    ->live(),
                Forms\Components\Select::make('municipality_id')
                    ->label('Municipio')
                    ->dehydrated(false)
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateHydrated(function (Forms\Components\Select $component, $state, ?User $record): void {
                        if ($state || ! $record) {
                            return;
                        }

                        $record->loadMissing('campuses.school');
                        $municipalityIds = $record->campuses
                            ->pluck('school.municipality_id')
                            ->filter()
                            ->unique()
                            ->values();

                        if ($municipalityIds->count() === 1) {
                            $component->state($municipalityIds->first());
                        }
                    })
                    ->options(function (Forms\Get $get) {
                        $nodeId = $get('primary_node_id');
                        if (! $nodeId && auth()->user()?->hasRole('node_owner')) {
                            $nodeId = auth()->user()?->primary_node_id;
                        }

                        return self::municipalityOptionsForNode($nodeId ? (int) $nodeId : null);
                    })
                    ->visible(fn (Forms\Get $get) => self::isTeacherContext($get)),
                Forms\Components\Repeater::make('school_campus_assignments')
                    ->label('Colegios y sedes')
                    ->dehydrated(false)
                    ->defaultItems(1)
                    ->minItems(1)
                    ->columnSpanFull()
                    ->grid(2)
                    ->live()
                    ->default(function (?User $record): array {
                        if (! $record) {
                            return [['school_id' => null, 'campus_focalization_key' => null]];
                        }

                        $rows = [];
                        $record->loadMissing('campuses.school');
                        foreach ($record->campuses as $campus) {
                            $focalizationId = $campus->pivot->focalization_id;
                            $rows[] = [
                                'school_id' => $campus->school_id,
                                'campus_focalization_key' => $campus->id . '|' . ($focalizationId ? (string) $focalizationId : ''),
                            ];
                        }

                        return ! empty($rows) ? $rows : [['school_id' => null, 'campus_focalization_key' => null]];
                    })
                    ->schema([
                        Forms\Components\Select::make('school_id')
                            ->label('Nombre Establecimiento Educativo')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->options(function (Forms\Get $get) {
                                $query = School::query()->orderBy('name');

                                $municipalityId = $get('../../municipality_id');
                                if ($municipalityId) {
                                    $query->where('municipality_id', $municipalityId);
                                }

                                $selectedNodeId = $get('../../primary_node_id');
                                if ($selectedNodeId) {
                                    $query->where('node_id', $selectedNodeId);
                                } else {
                                    $user = auth()->user();
                                    if ($user && $user->hasRole('node_owner')) {
                                        if (! $user->primary_node_id) {
                                            return [];
                                        }

                                        $query->where('node_id', $user->primary_node_id);
                                    }
                                }

                                return $query->pluck('name', 'id');
                            })
                            ->afterStateUpdated(function (Forms\Set $set): void {
                                $set('campus_focalization_key', null);
                            }),
                        Forms\Components\Select::make('campus_focalization_key')
                            ->label('Nombre Sede')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->options(function (Forms\Get $get) {
                                $schoolId = $get('school_id');
                                if (! $schoolId) {
                                    return [];
                                }

                                $options = [];
                                $campuses = Campus::query()
                                    ->with(['focalizations' => fn ($q) => $q->orderBy('name')])
                                    ->where('school_id', $schoolId)
                                    ->orderBy('name')
                                    ->get();

                                foreach ($campuses as $campus) {
                                    if ($campus->focalizations->isEmpty()) {
                                        $key = $campus->id . '|';
                                        $options[$key] = $campus->name . ' - Sin focalización';
                                        continue;
                                    }

                                    foreach ($campus->focalizations as $focalization) {
                                        $key = $campus->id . '|' . $focalization->id;
                                        $options[$key] = $campus->name . ' - ' . $focalization->name;
                                    }
                                }

                                return $options;
                            }),
                    ])
                    ->visible(fn (Forms\Get $get) => self::isTeacherContext($get))
                    ->required(fn (Forms\Get $get) => self::isTeacherContext($get)),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->visible(false)
                    ->dehydrated(fn ($state) => filled($state))
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Formador')
                    ->searchable(query: fn (Builder $query, string $search): Builder => LikeSearch::apply($query, 'name', $search)),
                Tables\Columns\TextColumn::make('document_number')
                    ->label('Identificación')
                    ->getStateUsing(function (User $record): string {
                        $type = match ($record->document_type) {
                            'CEDULA_CIUDADANIA' => 'CC',
                            'CEDULA_EXTRANJERIA' => 'CE',
                            'NUIP' => 'NUIP',
                            'NIP' => 'NIP',
                            'PEP' => 'PEP',
                            'PPT' => 'PPT',
                            'NIT' => 'NIT',
                            'OTRO' => 'OTRO',
                            default => $record->document_type ?: '-',
                        };

                        $number = $record->document_number ?: '-';

                        return $type . ' - ' . $number;
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $subQuery) use ($search): void {
                            LikeSearch::apply($subQuery, 'document_number', $search);
                            $subQuery->orWhere('document_type', 'like', '%' . $search . '%');
                        });
                    }),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(query: fn (Builder $query, string $search): Builder => LikeSearch::apply($query, 'email', $search)),
                Tables\Columns\TextColumn::make('generated_password')
                    ->label('Password temporal')
                    ->visible(fn () => auth()->user()?->hasRole('super_admin'))
                    ->getStateUsing(function (User $record): string {
                        if (! $record->hasAnyRole(['super_admin', 'node_owner']) || blank($record->generated_password)) {
                            return '-';
                        }

                        return 'Copiar password';
                    })
                    ->badge()
                    ->color('warning')
                    ->copyable(fn (User $record): bool => $record->hasAnyRole(['super_admin', 'node_owner']) && filled($record->generated_password))
                    ->copyableState(fn (User $record): ?string => $record->generated_password)
                    ->copyMessage('Password copiado al portapapeles')
                    ->copyMessageDuration(1500),
                Tables\Columns\TextColumn::make('dane_ee')
                    ->label('Código DANE Colegios')
                    ->getStateUsing(function (User $record): array|string {
                        $values = $record->campuses
                            ->pluck('school.dane_code')
                            ->filter()
                            ->unique()
                            ->values()
                            ->all();

                        return empty($values) ? '-' : $values;
                    })
                    ->badge(),
                Tables\Columns\TextColumn::make('campuses.school.name')
                    ->label('Colegio(s)')
                    ->badge()
                    ->separator(', ')
                    ->getStateUsing(function (User $record): array|string {
                        if (! $record->hasRole('teacher')) {
                            return '-';
                        }

                        $names = $record->campuses
                            ->pluck('school.name')
                            ->filter()
                            ->unique()
                            ->values()
                            ->all();

                        return empty($names) ? '-' : $names;
                    }),
                Tables\Columns\TextColumn::make('dane_sede')
                    ->label('Código DANE Sede')
                    ->getStateUsing(function (User $record): array|string {
                        $values = $record->campuses
                            ->pluck('dane_code')
                            ->filter()
                            ->unique()
                            ->values()
                            ->all();

                        return empty($values) ? '-' : $values;
                    })
                    ->badge(),
                Tables\Columns\TextColumn::make('campuses.name')
                    ->label('Sedes')
                    ->badge()
                    ->separator(', ')
                    ->getStateUsing(function (User $record): array|string {
                        if (! $record->hasRole('teacher')) {
                            return '-';
                        }

                        $names = $record->campuses
                            ->pluck('name')
                            ->filter()
                            ->unique()
                            ->values()
                            ->all();

                        return empty($names) ? '-' : $names;
                    }),
                Tables\Columns\TextColumn::make('zona')
                    ->label('Zona')
                    ->getStateUsing(function (User $record): array|string {
                        $values = $record->campuses
                            ->pluck('zone')
                            ->filter()
                            ->map(fn ($zone) => $zone === 'urbana' ? 'Urbana' : ($zone === 'rural' ? 'Rural' : $zone))
                            ->unique()
                            ->values()
                            ->all();

                        return empty($values) ? '-' : $values;
                    })
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && $user->hasRole('node_owner') && ! $user->hasRole('super_admin')) {
            if (! $user->primary_node_id) {
                return $query->whereRaw('1=0');
            }

            $query->whereHas('roles', fn (Builder $roleQuery) => $roleQuery->where('name', 'teacher'))
                ->where('primary_node_id', $user->primary_node_id);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
