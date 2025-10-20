<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="CRM Katuete - Inicio de sesión" />
    <meta name="author" content="Junior Enciso" />
    <title>Login - CRM Katuete</title>
    @vite('resources/css/app.css') {{-- Tailwind CSS --}}
</head>
<body class="bg-blue-600 min-h-screen flex flex-col justify-between">

    <main class="flex-grow flex items-center justify-center">
        <div class="w-full max-w-md bg-white shadow-lg rounded-lg p-8">
            
            <h3 class="text-center text-2xl font-semibold text-gray-700 mb-6">Iniciar sesión</h3>

            <form action="{{ route('login.post') }}" method="POST" class="space-y-5">
                @csrf

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-600">Correo electrónico</label>
                    <input id="email" name="email" type="email" required autofocus
                           class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 text-gray-700" 
                           placeholder="name@example.com">
                </div>

                {{-- Password --}}
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-600">Contraseña</label>
                    <input id="password" name="password" type="password" required
                           class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 text-gray-700" 
                           placeholder="********">
                </div>

                {{-- Mensaje de error --}}
                @if(session('error'))
                    <div class="p-3 bg-red-100 text-red-700 rounded text-sm">
                        {{ session('error') }}
                    </div>
                @endif

                {{-- Recordarme --}}
                <div class="flex items-center">
                    <input id="remember" name="remember" type="checkbox" value="1"
                           class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <label for="remember" class="ml-2 block text-sm text-gray-600">Recordarme</label>
                </div>

                {{-- Botón y link --}}
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-400">¿Olvidaste tu contraseña?</span>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded shadow">
                        Entrar
                    </button>
                </div>
            </form>

            <div class="text-center mt-6 text-sm text-gray-500">
                ¿No tenés cuenta? Contactá al administrador
            </div>

            <p class="text-center mt-3 text-xs text-gray-400">
                © {{ date('Y') }} CRM Katuete
            </p>
        </div>
    </main>

    <footer class="py-4 bg-gray-100 mt-6">
        <div class="max-w-5xl mx-auto px-4 flex flex-col md:flex-row justify-between items-center text-sm text-gray-500">
            <div>Hecho con ♥ en Paraguay</div>
            <div class="space-x-2">
                <a href="#" class="hover:text-blue-600">Política de Privacidad</a>
                &middot;
                <a href="#" class="hover:text-blue-600">Términos & Condiciones</a>
            </div>
        </div>
    </footer>

</body>
</html>
