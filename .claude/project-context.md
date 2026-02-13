### 🎯 Project Overview
We are building an **administrative dashboard** using **Laravel + Filament** to manage an educational program distributed across **nodes (departments)**.
Each node manages schools, campuses, and teachers. The system must support multiple roles, strict permission rules, and data exports.

### 👥 Roles & Permissions
There are currently **three roles** (but the system must be extensible):
#### 1. Super Administrator
Global access across the entire system.
Responsibilities:
- Create and manage **Nodes (Departments)**.
- Assign **Node Owners** to nodes.
- Create and manage **Users** in any node.
- Define special permissions (e.g., allow a user to belong to multiple nodes).
- View and manage all data across all nodes.
- Export data from:
    - A specific node
    - All nodes combined (global export to Excel)
#### 2. Node Owner
Limited to a specific node.
Responsibilities:
- Create and manage **Users** only within their assigned node.
- Manage **schools and campuses (sedes)** belonging to their node.
- Assign teachers to schools and campuses within their node.
- View and export data **only from their own node**.
Restrictions:
- Cannot create or assign users to other nodes.
- Cannot see data from other nodes.
#### 3. Teacher (Profesor)
Operational role.
Properties:
- Belongs to one node by default.
- Can be assigned to:
    - Multiple schools
    - Multiple campuses (sedes)
Special Case:
- There are rare cases where a teacher can belong to **more than one node** (e.g., Antioquia and Córdoba).
- This must be allowed **only if the Super Admin explicitly enables a special permission** for that user.
- The database model should support multi-node relationships, but the UI and permissions should enforce restrictions by default.

### 🧩 Core Entities & Relationships
Entities to model:
- **User**
    - Personal information
    - Role (Super Admin, Node Owner, Teacher)
    - Relationship with Node(s)
    - Relationship with Schools and Campuses (for teachers)
- **Node (Department)**
    - Has many users
    - Has many schools
- **School (Colegio)**
    - Belongs to one node
    - Has many campuses (sedes)
- **Campus (Sede)**
    - Belongs to one school
    - Can have many teachers assigned
Rules:
- By default, a user belongs to **one node only**.
- A user may belong to multiple nodes **only if explicitly authorized by Super Admin**.
- Node Owners can only manage data inside their own node.

### 📝 User Creation Flow (Form Design)
The user creation form should be divided into **two main sections**:
1. **Personal Information**
    - Basic user data (name, ID, contact info, role, etc.)
2. **Assignment**
    - Node assignment (restricted by role)
    - School(s) assignment
    - Campus(es) assignment
Behavior:
- Super Admin can assign users to any node.
- Node Owner can only assign users to their own node.
- Teachers can be assigned to multiple schools and campuses within their node.

### 📊 Data Export Requirements
The system must support exporting data to **Excel**:
- Node Owner:
    - Export all data from **their own node**.
- Super Admin:
    - Export per node.
    - Export **global dataset across all nodes** into a single Excel file (even if large).
    - The export is mainly for reporting, auditing, and metrics.
Exports should include:
- Users
- Node
- School
- Campus assignments

### 🗂️ Database Model Structure

#### Users Table
- id
- name
- email
- document_type
- document_number
- phone
- role (super_admin, node_owner, teacher)
- primary_node_id (FK to nodes)
- can_belong_multiple_nodes (boolean flag)
- timestamps

#### Nodes Table (Departments)
- id
- name (e.g., "Antioquia", "Córdoba")
- code
- timestamps

#### User_Node Pivot (for multi-node teachers)
- user_id
- node_id
- timestamps

#### Schools Table
- id
- node_id (FK to nodes)
- name
- code
- timestamps

#### Campuses Table
- id
- school_id (FK to schools)
- name
- address
- timestamps

#### School_User Pivot (teacher assignments)
- user_id
- school_id
- timestamps

#### Campus_User Pivot (teacher assignments)
- user_id
- campus_id
- timestamps

### 🔐 Business Rules Summary

#### User-Node Relationship
1. Every user MUST have a `primary_node_id`
2. By default, users belong to ONE node only
3. Multi-node assignment requires:
   - `can_belong_multiple_nodes = true`
   - Only Super Admin can set this flag
   - Records in `user_node` pivot table

#### School-Node Relationship
1. Every school belongs to exactly ONE node
2. Schools cannot be transferred between nodes
3. Node Owners can only see/manage schools in their node

#### Campus-School Relationship
1. Every campus belongs to exactly ONE school
2. A school can have zero or many campuses
3. Campuses inherit the node from their parent school

#### Teacher Assignment Rules
1. Teachers can be assigned to multiple schools
2. Teachers can be assigned to multiple campuses
3. Assignments must respect node boundaries:
   - Node Owner can only assign to schools/campuses in their node
   - Super Admin can assign across any node

#### Data Visibility Rules
1. Super Admin: sees all records across all nodes
2. Node Owner: sees only records where `node_id = their_assigned_node`
3. Teacher: sees only their own record

### ✅ Final Goal
System that enforces:
- Strict role-based data isolation
- Flexible teacher assignments within boundaries
- Multi-node support as exception, not default
- Complete audit trail through timestamps
- Scalable export functionality