**Project Context**
- Repo: `d:\projects\formadores`
- Stack: Laravel 12 + Filament + Filament Shield.

**Business Decisions**
- Department <-> Node: many-to-many.
- Municipality belongs to Secretaria.
- School belongs to Municipality and Node.
- Campus belongs to School.
- Teacher belongs to one Node (`primary_node_id`) and can be assigned to many campuses.
- `node_owner` belongs to one Node only (`primary_node_id`).
- `super_admin` has global access.

**Node Owner Scope Rule**
- `node_owner` can create/edit teachers only within their `primary_node_id`.
- Resource queries and policies must filter by `primary_node_id`.
- No multi-node ownership behavior should remain for `node_owner`.

**Technical Notes**
- User-node multi-assignment via `node_user` is deprecated.
- User campus assignment uses `campus_user`.
- Import/materialization commands continue to build core entities from staging data.

**Operational Goal**
- Enforce strict single-node ownership for `node_owner` and keep all access checks consistent with that rule.
