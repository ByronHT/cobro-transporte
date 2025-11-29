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

                    <!-- PASO 1: PRIMERO EL ROL -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Rol *</label>
                        <select name="role" id="role" required
                                class="mt-1 w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">-- Seleccionar Rol --</option>
                            <option value="admin" {{ old('role')==='admin'?'selected':'' }}>Administrador</option>
                            <option value="driver" {{ old('role')==='driver'?'selected':'' }}>Chofer</option>
                            <option value="passenger" {{ old('role')==='passenger'?'selected':'' }}>Pasajero</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Primero selecciona el rol del usuario</p>
                    </div>

                    <!-- PASO 2: TIPO DE PASAJERO (Solo si es Pasajero) -->
                    <div id="passenger_type_section" style="display:none;">
                        <label class="block text-sm font-medium text-gray-700">Tipo de Pasajero *</label>
                        <select name="user_type" id="user_type"
                                class="mt-1 w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">-- Seleccionar Tipo de Pasajero --</option>
                            <option value="adult" {{ old('user_type') === 'adult' ? 'selected' : '' }}>Regular (adulto) - 2.30 Bs</option>
                            <option value="senior" {{ old('user_type') === 'senior' ? 'selected' : '' }}>Mayor de Edad - 1.00 Bs</option>
                            <option value="minor" {{ old('user_type') === 'minor' ? 'selected' : '' }}>Menor de Edad - 1.00 Bs</option>
                            <option value="student_school" {{ old('user_type') === 'student_school' ? 'selected' : '' }}>Estudiante Colegial - 1.00 Bs</option>
                            <option value="student_university" {{ old('user_type') === 'student_university' ? 'selected' : '' }}>Estudiante Universitario - 1.00 Bs</option>
                        </select>
                    </div>

                    <!-- PASO 3: Campos adicionales para Estudiantes -->
                    <div id="student_school_fields" style="display:none;">
                        <label class="block text-sm font-medium text-gray-700">Nombre del Colegio *</label>
                        <input type="text" name="school_name" value="{{ old('school_name') }}"
                               class="mt-1 w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div id="student_university_fields" style="display:none;">
                        <div class="grid sm:grid-cols-3 gap-4">
                            <div class="sm:col-span-1">
                                <label class="block text-sm font-medium text-gray-700">Universidad *</label>
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

                    <!-- PASO 4: Datos generales -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nombre Completo *</label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                               class="mt-1 w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Correo Electrónico *</label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                               class="mt-1 w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
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
                        <label class="block text-sm font-medium text-gray-700">NIT (Opcional - para facturación)</label>
                        <input type="text" name="nit" value="{{ old('nit') }}" maxlength="20"
                               placeholder="Ej: 1234567890"
                               class="mt-1 w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <p class="mt-1 text-xs text-gray-500">Número de Identificación Tributaria para generar facturas</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Código de Login (4 dígitos) *</label>
                        <input type="text" name="login_code" value="{{ old('login_code', str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT)) }}"
                               maxlength="4" pattern="\d{4}" required
                               class="mt-1 w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <p class="mt-1 text-xs text-gray-500">El usuario usará este código para iniciar sesión en la app móvil</p>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Contraseña *</label>
                            <input type="password" name="password" required
                                   class="mt-1 w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Confirmar Contraseña *</label>
                            <input type="password" name="password_confirmation" required
                                   class="mt-1 w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <input id="active" type="checkbox" name="active" value="1" {{ old('active',1) ? 'checked' : '' }}
                               class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <label for="active" class="text-sm text-gray-700">Usuario Activo</label>
                    </div>

                    <div class="flex justify-between pt-4">
                        <a href="{{ route('admin.users.index') }}"
                           class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                            ← Volver
                        </a>
                        <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Guardar Usuario
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const roleSelect = document.getElementById('role');
            const passengerTypeSection = document.getElementById('passenger_type_section');
            const userTypeSelect = document.getElementById('user_type');
            const schoolFields = document.getElementById('student_school_fields');
            const universityFields = document.getElementById('student_university_fields');

            // Función para mostrar/ocultar sección de tipo de usuario
            function togglePassengerSection() {
                const role = roleSelect.value;

                if (role === 'passenger') {
                    passengerTypeSection.style.display = 'block';
                    userTypeSelect.setAttribute('required', 'required');
                } else {
                    passengerTypeSection.style.display = 'none';
                    userTypeSelect.removeAttribute('required');
                    userTypeSelect.value = '';
                    schoolFields.style.display = 'none';
                    universityFields.style.display = 'none';
                }
            }

            // Función para mostrar/ocultar campos de estudiantes
            function toggleStudentFields() {
                const type = userTypeSelect.value;

                if (type === 'student_school') {
                    schoolFields.style.display = 'block';
                    universityFields.style.display = 'none';
                } else if (type === 'student_university') {
                    schoolFields.style.display = 'none';
                    universityFields.style.display = 'block';
                } else {
                    schoolFields.style.display = 'none';
                    universityFields.style.display = 'none';
                }
            }

            // Eventos
            roleSelect.addEventListener('change', togglePassengerSection);
            userTypeSelect.addEventListener('change', toggleStudentFields);

            // Ejecutar al cargar para preservar estado si hay errores de validación
            togglePassengerSection();
            toggleStudentFields();
        });
    </script>
</x-app-layout>
