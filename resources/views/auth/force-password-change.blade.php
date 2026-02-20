<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cambiar password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f6f7fb;
            margin: 0;
            padding: 2rem;
            color: #1f2937;
        }
        .card {
            max-width: 460px;
            margin: 3rem auto;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
        }
        h1 {
            margin: 0 0 1rem 0;
            font-size: 1.25rem;
        }
        p {
            margin: 0 0 1rem 0;
            font-size: 0.95rem;
        }
        label {
            display: block;
            margin-bottom: 0.35rem;
            font-weight: 600;
            font-size: 0.9rem;
        }
        input {
            width: 100%;
            box-sizing: border-box;
            padding: 0.6rem 0.7rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            margin-bottom: 0.85rem;
        }
        button {
            background: #111827;
            color: #fff;
            border: 0;
            border-radius: 8px;
            padding: 0.65rem 1rem;
            cursor: pointer;
        }
        .error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 0.6rem 0.7rem;
            margin-bottom: 0.9rem;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>Cambio obligatorio de password</h1>
        <p>Debes actualizar tu password temporal para continuar.</p>

        @if ($errors->any())
            <div class="error">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.force.update') }}">
            @csrf
            @method('PUT')

            <label for="password">Nuevo password</label>
            <input id="password" name="password" type="password" autocomplete="new-password" required>

            <label for="password_confirmation">Confirmar password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>

            <button type="submit">Guardar y continuar</button>
        </form>
    </div>
</body>
</html>
