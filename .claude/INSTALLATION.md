# Instalación del Proyecto

## Requisitos Previos

- PHP 8.2 o superior
- Composer
- Node.js 18+ y NPM
- MySQL 8.0+ o PostgreSQL 13+

## Pasos de Instalación

### 1. Clonar el Repositorio

```bash
git clone <repository-url>
cd formadores
```

### 2. Instalar Dependencias de PHP

```bash
composer install
```

### 3. Configurar Variables de Entorno

```bash
cp .env.example .env
```

Editar `.env` y configurar:

- `DB_DATABASE`: nombre de la base de datos
- `DB_USERNAME`: usuario de base de datos
- `DB_PASSWORD`: contraseña de base de datos

### 4. Generar Clave de Aplicación

```bash
php artisan key:generate
```

### 5. Ejecutar Migraciones

```bash
php artisan migrate
```

### 6. Crear Usuario Administrador

```bash
php artisan make:filament-user
```

### 7. Instalar Dependencias de Frontend

```bash
npm install
```

### 8. Compilar Assets

```bash
npm run build
```

Para desarrollo:
```bash
npm run dev
```

### 9. Iniciar Servidor de Desarrollo

```bash
php artisan serve
```

### 10. Acceder a la Aplicación

- Frontend: http://localhost:8000
- Panel Filament: http://localhost:8000/admin

## Comandos Útiles

### Limpiar Caché

```bash
php artisan optimize:clear
```

### Crear Enlace de Storage

```bash
php artisan storage:link
```

### Seeders (si existen)

```bash
php artisan db:seed
```

## Solución de Problemas

### Error de Permisos

```bash
chmod -R 775 storage bootstrap/cache
```

### Reinstalar Filament

```bash
php artisan filament:install --panels
```
