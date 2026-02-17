<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected ?string $selectedRole = null;

    public function getTitle(): string
    {
        $user = auth()->user();
        if ($user && $user->hasRole('node_owner')) {
            $nodeName = $user->primaryNode?->name ?? 'Sin nodo';
            return 'Editar Formador - ' . $nodeName;
        }

        return 'Editar Formador';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->selectedRole = $data['role'] ?? null;
        unset($data['role']);

        if (auth()->user()?->hasRole('node_owner')) {
            $data['primary_node_id'] = auth()->user()?->primary_node_id;
        }

        $data['name'] = trim(implode(' ', array_filter([
            $data['first_name'] ?? null,
            $data['second_name'] ?? null,
            $data['first_last_name'] ?? null,
            $data['second_last_name'] ?? null,
        ])));

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->selectedRole) {
            $this->record->syncRoles([$this->selectedRole]);
        }

        $state = $this->data;
        $this->syncCampusAssignments($state);
    }

    private function syncCampusAssignments(array $state): void
    {
        DB::table('campus_user')
            ->where('user_id', $this->record->id)
            ->delete();

        $assignments = collect(data_get($state, 'school_campus_assignments', []))
            ->pluck('campus_focalization_key')
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
