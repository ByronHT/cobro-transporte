/**
 * AJAX Pagination System
 * Maneja la paginación sin recargar la página
 */

class AjaxPagination {
    constructor(config) {
        this.tableBodyId = config.tableBodyId;
        this.paginationContainerId = config.paginationContainerId;
        this.endpoint = config.endpoint;
        this.autoRefresh = config.autoRefresh || false;
        this.refreshInterval = config.refreshInterval || 30000; // 30 segundos por defecto
        this.filters = config.filters || {};
        this.currentPage = 1;
        this.refreshTimer = null;

        this.init();
    }

    init() {
        this.attachPaginationListeners();

        if (this.autoRefresh) {
            this.startAutoRefresh();
        }
    }

    attachPaginationListeners() {
        const container = document.getElementById(this.paginationContainerId);
        if (!container) return;

        container.addEventListener('click', (e) => {
            const button = e.target.closest('[data-page]');
            if (button) {
                e.preventDefault();
                const page = parseInt(button.dataset.page);
                this.loadPage(page);
            }
        });
    }

    async loadPage(page) {
        const tableBody = document.getElementById(this.tableBodyId);
        const paginationContainer = document.getElementById(this.paginationContainerId);

        if (!tableBody) return;

        // Agregar loading indicator
        tableBody.innerHTML = '<tr><td colspan="10" class="px-6 py-12 text-center"><div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div><p class="mt-2 text-sm text-gray-500">Cargando...</p></td></tr>';

        try {
            // Construir URL con filtros
            const url = new URL(this.endpoint, window.location.origin);
            url.searchParams.append('page', page);

            // Agregar filtros
            Object.keys(this.filters).forEach(key => {
                const value = this.getFilterValue(key);
                if (value) {
                    url.searchParams.append(key, value);
                }
            });

            const response = await fetch(url);
            const data = await response.json();

            if (data.success) {
                // Actualizar contenido de la tabla
                tableBody.innerHTML = data.html;

                // Actualizar paginación
                if (paginationContainer) {
                    this.updatePagination(data, paginationContainer);
                }

                this.currentPage = data.page;
            } else {
                tableBody.innerHTML = '<tr><td colspan="10" class="px-6 py-12 text-center text-red-600">Error al cargar los datos</td></tr>';
            }
        } catch (error) {
            console.error('Error loading page:', error);
            tableBody.innerHTML = '<tr><td colspan="10" class="px-6 py-12 text-center text-red-600">Error de conexión</td></tr>';
        }
    }

    getFilterValue(filterKey) {
        const element = document.querySelector(`[name="${filterKey}"]`);
        return element ? element.value : '';
    }

    updatePagination(data, container) {
        const { page, hasMore, hasPrev, total, showing_from, showing_to } = data;

        let html = '';

        if (hasMore || hasPrev) {
            html = `
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-600">
                        Mostrando ${showing_from} - ${showing_to} de ${total} registros
                    </div>
                    <div class="flex gap-2">
                        ${hasPrev ? `
                            <button data-page="${page - 1}"
                                    class="inline-flex items-center gap-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-150 font-semibold">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                Anterior
                            </button>
                        ` : ''}

                        ${hasMore ? `
                            <button data-page="${page + 1}"
                                    class="inline-flex items-center gap-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-150 font-semibold">
                                Siguiente
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        ` : ''}
                    </div>
                </div>
            `;
        }

        container.innerHTML = html;
    }

    startAutoRefresh() {
        this.refreshTimer = setInterval(() => {
            this.loadPage(this.currentPage);
        }, this.refreshInterval);
    }

    stopAutoRefresh() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
            this.refreshTimer = null;
        }
    }

    destroy() {
        this.stopAutoRefresh();
    }
}

// Exportar para uso global
window.AjaxPagination = AjaxPagination;
