<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected ?string $selectedRole = null;

    public function getTitle(): string
    {
        $user = auth()->user();
        if ($user && $user->hasRole('node_owner')) {
            $nodeName = $user->primaryNode?->name ?? 'Sin nodo';
            return 'Crear Formador - ' . $nodeName;
        }

        if (($this->data['role'] ?? null) === 'teacher') {
            return 'Crear Formador';
        }

        return 'Crear Usuario';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->selectedRole = $data['role'] ?? null;
        unset($data['role']);

        if (! $this->selectedRole && auth()->user()?->hasRole('node_owner')) {
            $this->selectedRole = 'teacher';
        }

        if (auth()->user()?->hasRole('node_owner')) {
            $data['primary_node_id'] = auth()->user()?->primary_node_id;
        }

        $data['name'] = trim(implode(' ', array_filter([
            $data['first_name'] ?? null,
            $data['second_name'] ?? null,
            $data['first_last_name'] ?? null,
            $data['second_last_name'] ?? null,
        ])));

        if (empty($data['password'])) {
            $data['password'] = Str::random(16);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->selectedRole) {
            $this->record->syncRoles([$this->selectedRole]);
        }

        $state = $this->data;
        $this->syncCampusAssignments($state);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function preserveFormDataWhenCreatingAnother(): array
    {
        return [];
    }

    private function syncCampusAssignments(array $state): void
    {
        DB::table('campus_user')
            ->where('user_id', $this->record->id)
            ->delete();

        $assignments = collect(data_get($state, 'school_campus_assignments', []))
            ->pluck('campus_focalization_keys')
            ->flatten()
            ->filter()
            ->unique()
            ->values();

        if ($assignments->isEmpty()) {
            return;
        }

        $now = now();
        $rows = $assignments
            ->map(function (string $key) use ($now): ?array {
                [$campusId, $focalizationId] = array_pad(explode('|', $key, 2), 2, null);
                if (! $campusId) {
                    return null;
                }

                return [
                    'campus_id' => (int) $campusId,
                    'user_id' => $this->record->id,
                    'focalization_id' => $focalizationId !== null && $focalizationId !== '' ? (int) $focalizationId : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->filter()
            ->all();

        if (! empty($rows)) {
            DB::table('campus_user')->insert($rows);
        }
    }
}
