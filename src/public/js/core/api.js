/**
 * Core API Client Engine - Document Tracking System
 * Handles secure, asynchronous network transmissions globally.
 */
const AppAPI = {
    /**
     * Read the active CSRF protection token from the document headers
     */
    getCsrfToken() {
        const tokenMeta = document.querySelector('meta[name="csrf-token"]');
        if (!tokenMeta) {
            console.warn('Security Warning: Laravel CSRF token meta element missing from viewport.');
            return '';
        }
        return tokenMeta.getAttribute('content');
    },

    /**
     * Process an asynchronous HTTP Request
     * @param {string} url - Target routing endpoint
     * @param {string} method - HTTP Verb (GET, POST, PUT, DELETE)
     * @param {Object|null} data - Payload data object
     */
    async request(url, method = 'GET', data = null) {
        const headers = {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        };

        // Add security tokens and payload parameters if modifying server states
        if (method !== 'GET') {
            headers['X-CSRF-TOKEN'] = this.getCsrfToken();
            if (data) {
                headers['Content-Type'] = 'application/json';
            }
        }

        const config = {
            method: method,
            headers: headers
        };

        if (data && method !== 'GET') {
            config.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(url, config);
            
            // Catch structural server faults elegantly
            if (!response.ok) {
                const errorPayload = await response.json().catch(() => ({}));
                const error = new Error(errorPayload.message || `HTTP Execution Exception: ${response.status}`);
                error.status = response.status;
                error.data = errorPayload;
                throw error;
            }

            return await response.json();
        } catch (error) {
            console.error(`[API Engine Fault] Failure on ${method} Request to: ${url}`, error);
            throw error;
        }
    },

    /**
     * Standardized GET Interceptor
     */
    get(url) {
        return this.request(url, 'GET');
    },

    /**
     * Standardized POST Interceptor
     */
    post(url, data) {
        return this.request(url, 'POST', data);
    }
};

// Bind to window context so feature modules can access it globally
window.AppAPI = AppAPI;
