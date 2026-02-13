# Agent Development Guide

## Stack & Dependencies

### Core Framework
- Laravel 11.x
- PHP 8.2+
- Filament 3.x

### Required Packages
```bash
composer require filament/filament:"^3.0"
composer require spatie/laravel-permission
composer require maatwebsite/excel
```

### Frontend
- Livewire 3.x (included with Filament)
- Alpine.js (included with Filament)
- Tailwind CSS (included with Filament)

## Code Standards

### PHP Standards
- PSR-12 coding style
- Type hints for all method parameters and return types
- Strict types declaration in all files

### Naming Conventions
- Classes: PascalCase
- Methods/Variables: camelCase
- Database tables: snake_case (plural)
- Database columns: snake_case
- Migrations: descriptive names with timestamp

### File Organization
```
app/
├── Filament/
│   ├── Resources/
│   │   ├── NodeResource.php
│   │   ├── SchoolResource.php
│   │   ├── CampusResource.php
│   │   └── UserResource.php
│   └── Pages/
├── Models/
│   ├── User.php
│   ├── Node.php
│   ├── School.php
│   └── Campus.php
├── Policies/
│   ├── NodePolicy.php
│   ├── SchoolPolicy.php
│   ├── CampusPolicy.php
│   └── UserPolicy.php
└── Exports/
    ├── UsersExport.php
    └── GlobalExport.php
```

## Implementation Guidelines

### Models

#### Relationships Pattern
```php
class User extends Authenticatable
{
    public function primaryNode(): BelongsTo
    public function nodes(): BelongsToMany
    public function schools(): BelongsToMany
    public function campuses(): BelongsToMany
}

class Node extends Model
{
    public function users(): HasMany
    public function schools(): HasMany
    public function owner(): BelongsTo
}

class School extends Model
{
    public function node(): BelongsTo
    public function campuses(): HasMany
    public function teachers(): BelongsToMany
}

class Campus extends Model
{
    public function school(): BelongsTo
    public function teachers(): BelongsToMany
}
```

#### Scopes Pattern
```php
class Node extends Model
{
    public function scopeForUser($query, User $user)
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }
        return $query->where('id', $user->primary_node_id);
    }
}
```

### Policies

#### Authorization Logic
- Use Laravel Policies for all resources
- Implement `viewAny`, `view`, `create`, `update`, `delete`
- Node-based filtering in `viewAny` and `view`
- Prevent cross-node operations in `create` and `update`

#### Policy Pattern
```php
class SchoolPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isNodeOwner();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isNodeOwner();
    }

    public function update(User $user, School $school): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        return $user->isNodeOwner() && $user->primary_node_id === $school->node_id;
    }
}
```

### Filament Resources

#### Query Scoping
```php
class SchoolResource extends Resource
{
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return $query;
        }

        return $query->where('node_id', $user->primary_node_id);
    }
}
```

#### Form Structure
- Use Filament form components
- Group related fields with `Section`
- Validate node boundaries in form submission
- Use `Select::make()->relationship()` for relations

#### Table Configuration
- Show relevant columns only
- Add filters for status, node (Super Admin only)
- Include bulk actions where appropriate
- Add export action to header

### Exports

#### Maatwebsite/Excel Pattern
```php
class UsersExport implements FromQuery, WithHeadings, WithMapping
{
    protected $nodeId;

    public function __construct($nodeId = null)
    {
        $this->nodeId = $nodeId;
    }

    public function query()
    {
        $query = User::with(['primaryNode', 'schools', 'campuses']);

        if ($this->nodeId) {
            $query->where('primary_node_id', $this->nodeId);
        }

        return $query;
    }
}
```

### Permissions & Roles

#### Spatie Permission Setup
```php
Role::create(['name' => 'super_admin']);
Role::create(['name' => 'node_owner']);
Role::create(['name' => 'teacher']);

Permission::create(['name' => 'manage_nodes']);
Permission::create(['name' => 'manage_schools']);
Permission::create(['name' => 'manage_users']);
Permission::create(['name' => 'export_data']);

$superAdmin->givePermissionTo(Permission::all());
$nodeOwner->givePermissionTo(['manage_schools', 'manage_users', 'export_data']);
```

#### Helper Methods on User Model
```php
public function isSuperAdmin(): bool
{
    return $this->hasRole('super_admin');
}

public function isNodeOwner(): bool
{
    return $this->hasRole('node_owner');
}

public function isTeacher(): bool
{
    return $this->hasRole('teacher');
}

public function canManageNode(Node $node): bool
{
    if ($this->isSuperAdmin()) {
        return true;
    }
    return $this->isNodeOwner() && $this->primary_node_id === $node->id;
}
```

## Database Migrations

### Migration Order
1. `create_nodes_table`
2. `create_schools_table`
3. `create_campuses_table`
4. `add_node_fields_to_users_table`
5. `create_user_node_table`
6. `create_school_user_table`
7. `create_campus_user_table`

### Key Constraints
- Foreign keys with `cascadeOnDelete()` for hard dependencies
- `restrictOnDelete()` for protected relationships
- Indexes on all foreign keys
- Unique constraints where applicable

### Soft Deletes
Consider adding soft deletes to:
- Users
- Schools
- Campuses

Do NOT soft delete:
- Nodes (permanent records)

## Performance Optimization

### Eager Loading
Always eager load relationships:
```php
User::with(['primaryNode', 'schools.campuses'])->get();
School::with(['node', 'campuses'])->get();
```

### Database Indexes
```php
$table->index('node_id');
$table->index('primary_node_id');
$table->index('school_id');
$table->index(['node_id', 'created_at']);
```

### Query Optimization
- Use `select()` to limit columns
- Implement pagination for large datasets
- Cache static data (nodes list)
- Use chunk() for exports

## Testing Strategy

### Feature Tests Priority
1. Role-based access control
2. Node boundary enforcement
3. Multi-node user assignment
4. Export functionality
5. Form validation

### Test Structure
```php
test('node owner cannot create user in another node')
test('super admin can assign user to multiple nodes')
test('teacher assignment respects node boundaries')
test('export includes only authorized data')
```

## Security Considerations

### Input Validation
- Validate all form inputs
- Sanitize Excel export data
- Prevent mass assignment vulnerabilities
- Use Form Requests for complex validation

### Authorization Layers
1. Middleware (role check)
2. Policy (resource access)
3. Query scope (data filtering)
4. Form validation (business rules)

### Data Integrity
- Validate node_id matches between related records
- Prevent orphaned relationships
- Transaction wrapping for multi-table operations

## Deployment Checklist

- [ ] Run migrations
- [ ] Seed roles and permissions
- [ ] Create super admin user
- [ ] Configure queue for exports
- [ ] Set up storage link
- [ ] Configure mail settings
- [ ] Set up backup strategy
- [ ] Configure logging
- [ ] Enable query logging in production
- [ ] Set up monitoring

## Common Patterns

### Check User Scope
```php
if (!$user->canManageNode($school->node)) {
    abort(403);
}
```

### Filter Query by Node
```php
$schools = School::query()
    ->when(!auth()->user()->isSuperAdmin(), function($q) {
        $q->where('node_id', auth()->user()->primary_node_id);
    })
    ->get();
```

### Validate Cross-Node Assignment
```php
if (!$user->can_belong_multiple_nodes && $user->nodes()->count() > 0) {
    throw ValidationException::withMessages([
        'nodes' => 'This user cannot belong to multiple nodes.'
    ]);
}
```
