/**
 * FPD Catalog Filter & URL Sync
 */
(function() {
    'use strict';

    class FPDCatalogFilter {
        constructor() {
            this.init();
        }

        init() {
            // Read URL params on load
            this.readUrlParams();

            // Listen for popstate (back/forward navigation)
            window.addEventListener('popstate', () => {
                this.readUrlParams();
            });

            // Expose global function for custom filter widgets
            window.fpdCatalogUpdateFilter = this.updateFilter.bind(this);
        }

        readUrlParams() {
            const params = new URLSearchParams(window.location.search);
            const filters = {
                category: params.get('fpd_design_cat') || '',
                base_product: params.get('fpd_base') || '',
                orderby: params.get('fpd_orderby') || '',
                order: params.get('fpd_order') || ''
            };

            // Clean empty filters
            Object.keys(filters).forEach(key => {
                if (!filters[key]) delete filters[key];
            });

            if (Object.keys(filters).length > 0) {
                this.emitFilterEvent(filters);
            }
        }

        updateFilter(newFilters) {
            const params = new URLSearchParams(window.location.search);
            
            // Update URL params
            if (newFilters.category !== undefined) {
                if (newFilters.category) params.set('fpd_design_cat', newFilters.category);
                else params.delete('fpd_design_cat');
            }
            
            if (newFilters.base_product !== undefined) {
                if (newFilters.base_product) params.set('fpd_base', newFilters.base_product);
                else params.delete('fpd_base');
            }

            if (newFilters.orderby !== undefined) {
                if (newFilters.orderby) params.set('fpd_orderby', newFilters.orderby);
                else params.delete('fpd_orderby');
            }

            if (newFilters.order !== undefined) {
                if (newFilters.order) params.set('fpd_order', newFilters.order);
                else params.delete('fpd_order');
            }

            const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
            window.history.pushState({ path: newUrl }, '', newUrl);

            this.emitFilterEvent(newFilters);
        }

        emitFilterEvent(filters) {
            const event = new CustomEvent('fpd_catalog_filter_changed', {
                detail: filters
            });
            document.dispatchEvent(event);
            
            // Update dynamic tags if present
            this.updateDynamicTags(filters);
        }
        
        updateDynamicTags(filters) {
            const labels = [];
            if (filters.category) labels.push(`Category: ${filters.category}`);
            if (filters.base_product) labels.push(`Product: ${filters.base_product}`);
            
            const labelText = labels.length > 0 ? labels.join(', ') : 'All';
            
            document.querySelectorAll('.fpd-dynamic-filter-label-v3').forEach(el => {
                el.textContent = labelText;
            });
        }
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', () => {
        new FPDCatalogFilter();
    });

})();
