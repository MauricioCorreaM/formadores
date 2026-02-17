### Project Overview
- Repository: `d:\projects\formadores`
- Stack: Laravel 12, Filament, Filament Shield.
- Goal: manage users, nodes, schools, campuses, and assignments with role-based access.

### Roles
1. `super_admin`
- Full access across all nodes.
- Can create and manage users, nodes, schools, campuses, and assignments.

2. `node_owner`
- Access limited to one node only.
- Node scope is defined by `users.primary_node_id`.
- Can only manage data within that single node.

3. `teacher`
- Assigned to one node via `primary_node_id`.
- Can be assigned to multiple schools/campuses inside node boundaries.

### Core Rules
- Every user has a single `primary_node_id`.
- Multi-node assignment is not allowed for `node_owner`.
- Node owners can only create/edit users and data inside their own `primary_node_id`.
- Schools belong to one node.
- Campuses belong to one school.

### Data Model Notes
- `users.primary_node_id` is the node scope field.
- `node_user` pivot is deprecated for user-node assignment.
- Teacher campus assignments are handled through `campus_user`.

### Visibility Rules
- `super_admin`: sees all data.
- `node_owner`: sees only records in their `primary_node_id`.
- `teacher`: operational scope according to assignments.

### Current Direction
- Keep access control and queries based on `primary_node_id`.
- Avoid any UI or policy flow that implies multi-node ownership for `node_owner`.
