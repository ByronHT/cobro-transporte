<x-app-layout>
    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-lg rounded-lg p-6">
                @if ($errors->any())
                    <div class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 border border-red-200">
                        <ul class="list-disc pl-5 text-sm">
                            @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nombre</label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                               class="mt-1 w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Correo</label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                               class="mt-1 w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">NIT (Opcional - para facturación)</label>
                        <input type="text" name="nit" value="{{ old('nit') }}" maxlength="20"
                               placeholder="Ej: 1234567890"
                               class="mt-1 w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <p class="mt-1 text-xs text-gray-500">Número de Identificación Tributaria para generar facturas</p>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">CI (Carnet de Identidad)</label>
                            <input type="text" name="ci" value="{{ old('ci') }}" maxlength="20"
                                   placeholder="Ej: 1234567 LP"
                                   class="mt-1 w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Fecha de Nacimiento</label>
                            <input type="date" name="birth_date" value="{{ old('birth_date') }}"
                                   class="mt-1 w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tipo de Usuario</label>
                        <select name="user_type" id="user_type"
                                class="mt-1 w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="adult" {{ old('user_type') === 'adult' ? 'selected' : '' }}>Adulto (2.30 Bs)</option>
                            <option value="senior" {{ old('user_type') === 'senior' ? 'selected' : '' }}>Adulto Mayor (1.00 Bs)</option>
                            <option value="minor" {{ old('user_type') === 'minor' ? 'selected' : '' }}>Menor (1.00 Bs)</option>
                            <option value="student_school" {{ old('user_type') === 'student_school' ? 'selected' : '' }}>Estudiante Escolar (1.00 Bs)</option>
                            <option value="student_university" {{ old('user_type') === 'student_university' ? 'selected' : '' }}>Estudiante Universitario (1.00 Bs)</option>
                        </select>
                    </div>

                    <div id="student_school_fields" style="display:none;">
                        <label class="block text-sm font-medium text-gray-700">Nombre del Colegio</label>
                        <input type="text" name="school_name" value="{{ old('school_name') }}"
                               class="mt-1 w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div id="student_university_fields" style="display:none;">
                        <div class="grid sm:grid-cols-3 gap-4">
                            <div class="sm:col-span-1">
                                <label class="block text-sm font-medium text-gray-700">Universidad</label>
                                <input type="text" name="university_name" value="{{ old('university_name') }}"
                                       class="mt-1 w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Año Actual</label>
                                <input type="number" name="university_year" value="{{ old('university_year') }}" min="1" max="7"
                                       class="mt-1 w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Año Finalización</label>
                                <input type="number" name="university_end_year" value="{{ old('university_end_year') }}" min="2025" max="2035"
                                       class="mt-1 w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Código de Login (4 dígitos)</label>
                        <input type="text" name="login_code" value="{{ old('login_code', str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT)) }}"
                               maxlength="4" pattern="\d{4}" required
                               class="mt-1 w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <p class="mt-1 text-xs text-gray-500">El usuario usará este código para iniciar sesión en la app móvil</p>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Contraseña</label>
                            <input type="password" name="password" required
                                   class="mt-1 w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Confirmar contraseña</label>
                            <input type="password" name="password_confirmation" required
                                   class="mt-1 w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Rol</label>
                            <select name="role"
                                    class="mt-1 w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="admin" {{ old('role')==='admin'?'selected':'' }}>Administrador</option>
                                <option value="driver" {{ old('role')==='driver'?'selected':'' }}>Chofer</option>
                                <option value="passenger" {{ old('role')==='passenger'?'selected':'' }}>Pasajero</option>
                            </select>
                        </div>

                        <div class="flex items-center gap-3 mt-6">
                            <input id="active" type="checkbox" name="active" value="1" {{ old('active',1) ? 'checked' : '' }}
                                   class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <label for="active" class="text-sm text-gray-700">Activo</label>
                        </div>
                    </div>

                    <div class="flex justify-between pt-4">
                        <a href="{{ route('admin.users.index') }}"
                           class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                            ← Volver
                        </a>
                        <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const userTypeSelect = document.getElementById('user_type');
            const schoolFields = document.getElementById('student_school_fields');
            const universityFields = document.getElementById('student_university_fields');

            function toggleFields() {
                const type = userTypeSelect.value;
                schoolFields.style.display = type === 'student_school' ? 'block' : 'none';
                universityFields.style.display = type === 'student_university' ? 'block' : 'none';
            }

            // Ejecutar al cargar y al cambiar
            toggleFields();
            userTypeSelect.addEventListener('change', toggleFields);
        });
    </script>
</x-app-layout>
