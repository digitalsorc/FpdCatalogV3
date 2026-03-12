/**
 * FPD Catalog Render
 */
(function($) {
    'use strict';

    class FPDCatalogRenderer {
        constructor($wrapper) {
            this.$wrapper = $wrapper;
            this.$grid = $wrapper.find('.fpd-catalog-grid');
            this.$loader = $wrapper.find('.fpd-catalog-loader');
            this.config = $wrapper.data('config');
            this.page = 1;
            this.isLoading = false;
            this.hasMore = true;
            this.currentFilters = {
                category: this.config.categories ? this.config.categories.join(',') : '',
                base_product: this.config.baseProducts ? this.config.baseProducts.join(',') : '',
                orderby: this.config.orderBy,
                order: this.config.order
            };

            this.init();
        }

        init() {
            // Listen for filter changes
            document.addEventListener('fpd_catalog_filter_changed', (e) => {
                this.applyFilters(e.detail);
            });

            // Initial load
            this.loadItems();

            // Setup Intersection Observer for lazy loading images
            if (this.config.lazyLoad) {
                this.setupIntersectionObserver();
            }
        }

        applyFilters(filters) {
            this.currentFilters = { ...this.currentFilters, ...filters };
            this.page = 1;
            this.hasMore = true;
            this.$grid.empty();
            this.loadItems();
        }

        async loadItems() {
            if (this.isLoading || !this.hasMore) return;

            this.isLoading = true;
            this.$loader.show();

            try {
                const params = new URLSearchParams({
                    category: this.currentFilters.category || '',
                    base_product: this.currentFilters.base_product || '',
                    orderby: this.currentFilters.orderby || 'date',
                    order: this.currentFilters.order || 'DESC',
                    page: this.page,
                    per_page: this.config.perPage,
                    cache_ttl: this.config.cacheTtl
                });

                const response = await fetch(`${fpdCatalogData.restUrl}?${params.toString()}`, {
                    headers: {
                        'X-WP-Nonce': fpdCatalogData.nonce
                    }
                });

                if (!response.ok) throw new Error('Network response was not ok');

                const items = await response.json();

                if (items.length === 0) {
                    this.hasMore = false;
                    if (this.page === 1) {
                        this.$grid.html('<div class="fpd-no-results">No designs found.</div>');
                    }
                } else {
                    this.renderItems(items);
                    this.page++;
                }
            } catch (error) {
                console.error('Error loading FPD items:', error);
            } finally {
                this.isLoading = false;
                this.$loader.hide();
            }
        }

        renderItems(items) {
            items.forEach(item => {
                const $card = $('<div class="fpd-catalog-card"></div>');
                
                const $mediaWrapper = $('<div class="fpd-card-media"></div>');
                
                if (this.config.renderMode === 'css') {
                    this.renderCSSComposite($mediaWrapper, item);
                } else {
                    this.renderCanvasComposite($mediaWrapper, item);
                }

                $card.append($mediaWrapper);

                const $content = $('<div class="fpd-card-content"></div>');
                
                if (this.config.showTitle) {
                    $content.append(`<h3 class="fpd-design-title">${item.design_title}</h3>`);
                }
                
                if (this.config.showLabel && item.base_product_title) {
                    $content.append(`<span class="fpd-base-product-label">${item.base_product_title}</span>`);
                }

                $card.append($content);

                // Link action
                if (this.config.linkAction === 'editor') {
                    $card.wrapInner(`<a href="${item.editor_url}" class="fpd-card-link"></a>`);
                }

                this.$grid.append($card);
            });
        }

        renderCSSComposite($wrapper, item) {
            $wrapper.addClass('fpd-css-composite');
            
            const $baseImg = $(`<img src="${item.base_product_image_url}" class="fpd-base-img" alt="Base Product" crossorigin="anonymous">`);
            $wrapper.append($baseImg);

            if (item.printing_box && item.design_image_url) {
                // Calculate percentages based on base image natural size once loaded
                $baseImg.on('load', function() {
                    const natWidth = this.naturalWidth;
                    const natHeight = this.naturalHeight;
                    
                    const pb = item.printing_box;
                    const leftPct = (pb.x / natWidth) * 100;
                    const topPct = (pb.y / natHeight) * 100;
                    const widthPct = (pb.width / natWidth) * 100;
                    const heightPct = (pb.height / natHeight) * 100;

                    const $designImg = $(`<img src="${item.design_image_url}" class="fpd-design-img" alt="Design" crossorigin="anonymous">`);
                    $designImg.css({
                        position: 'absolute',
                        left: `${leftPct}%`,
                        top: `${topPct}%`,
                        width: `${widthPct}%`,
                        height: `${heightPct}%`,
                        objectFit: 'contain',
                        objectPosition: 'top center'
                    });
                    
                    $wrapper.append($designImg);
                });
            }
        }

        renderCanvasComposite($wrapper, item) {
            const canvas = document.createElement('canvas');
            canvas.className = 'fpd-composite-canvas';
            $wrapper.append(canvas);

            if (this.config.lazyLoad) {
                canvas.dataset.item = JSON.stringify(item);
                this.observer.observe(canvas);
            } else {
                this.drawCanvas(canvas, item);
            }
        }

        async drawCanvas(canvas, item) {
            const ctx = canvas.getContext('2d');
            
            try {
                const baseImg = await this.loadImage(item.base_product_image_url);
                canvas.width = baseImg.width;
                canvas.height = baseImg.height;
                
                // Draw base product
                ctx.drawImage(baseImg, 0, 0);

                if (item.printing_box && item.design_image_url) {
                    const designImg = await this.loadImage(item.design_image_url);
                    const pb = item.printing_box;
                    
                    // Scale design to fit printing box width
                    let dWidth = pb.width;
                    let dHeight = (designImg.height / designImg.width) * dWidth;
                    
                    // If scaled height exceeds printing box height, scale by height instead
                    if (dHeight > pb.height) {
                        dHeight = pb.height;
                        dWidth = (designImg.width / designImg.height) * dHeight;
                    }

                    // Center horizontally, align top vertically
                    const dx = pb.x + (pb.width - dWidth) / 2;
                    const dy = pb.y;

                    ctx.drawImage(designImg, dx, dy, dWidth, dHeight);
                }
            } catch (error) {
                console.error('Error drawing canvas composite:', error);
            }
        }

        loadImage(src) {
            return new Promise((resolve, reject) => {
                if (!src) {
                    reject(new Error('No image source'));
                    return;
                }
                const img = new Image();
                img.crossOrigin = 'anonymous';
                img.onload = () => resolve(img);
                img.onerror = reject;
                img.src = src;
            });
        }

        setupIntersectionObserver() {
            this.observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const canvas = entry.target;
                        if (canvas.dataset.item) {
                            const item = JSON.parse(canvas.dataset.item);
                            this.drawCanvas(canvas, item);
                            delete canvas.dataset.item;
                            this.observer.unobserve(canvas);
                        }
                    }
                });
            }, { rootMargin: '100px' });
        }
    }

    // Initialize widgets
    $(window).on('elementor/frontend/init', function() {
        elementorFrontend.hooks.addAction('frontend/element_ready/fpd_catalog_widget.default', function($scope) {
            const $wrapper = $scope.find('.fpd-catalog-wrapper');
            if ($wrapper.length) {
                new FPDCatalogRenderer($wrapper);
            }
        });
    });

})(jQuery);
