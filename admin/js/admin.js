/**
 * Podbaz Robot Admin JavaScript
 */
(function($) { 'use strict';
    $(document).ready(function() {
    PBR.init();
});

var PBR = {
    
    init: function() {
        this.bindEvents();
        this.checkApiStatus();
        
        // Initialize queue page if present
        if ($('#pbr-queue-items').length) {
            this.refreshQueueStats();
            this.refreshQueueItems();
        }
    },
    
    bindEvents: function() {
        // Product form
        $('#pbr-product-form').on('submit', this.handleProductSubmit);
        
        // Post form
        $('#pbr-post-form').on('submit', this.handlePostSubmit);
        
        // Update form
        $('#pbr-update-form').on('submit', this.handleUpdateSubmit);
        $('#pbr-load-content-btn').on('click', this.handleLoadContent);
        $('input[name="update_type"]').on('change', this.toggleUpdateSelects);
        
        // Settings form
        $('#pbr-settings-form').on('submit', this.handleSettingsSave);
        
        // Prompts form
        $('#pbr-prompts-form').on('submit', this.handlePromptsSave);
        $('.pbr-reset-prompt').on('click', this.handlePromptReset);
        
        // API tests
        $('.pbr-test-api').on('click', this.handleApiTest);
        
        // Toggle password
        $('.pbr-toggle-password').on('click', this.togglePassword);
        
        // Research method toggle
        $('input[name="research_method"]').on('change', this.toggleResearchInput);
        $('input[name="post_research_method"]').on('change', this.togglePostResearchInput);
        
        // Modal close
        $('.pbr-modal-close').on('click', this.closeModal);
        
        // Logs actions
        $('#pbr-clear-logs').on('click', this.handleClearLogs);
        $('#pbr-export-logs').on('click', this.handleExportLogs);
        
        // Prompt tabs
        $('.pbr-tab-btn').on('click', this.switchPromptTab);
        
        // Queue and Bulk actions
        $('#pbr-bulk-add-form').on('submit', this.handleBulkAdd);
        $('#pbr-process-queue-btn').on('click', this.handleProcessQueue);
        $('#pbr-stop-queue-btn').on('click', this.handleStopQueue);
        $('#pbr-refresh-queue-btn').on('click', this.refreshQueueItems);
        $('#pbr-clear-completed-btn').on('click', this.handleClearCompleted);
        $('#pbr-queue-filter').on('change', this.refreshQueueItems);
        $(document).on('click', '.pbr-delete-queue-item', this.handleDeleteQueueItem);
        $(document).on('click', '.pbr-retry-queue-item', this.handleRetryQueueItem);
    },
    
    // ============================================
    // Product Generation
    // ============================================
    
    handleProductSubmit: function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $btn = $('#pbr-generate-btn');
        
        if (!$('#product_name').val().trim()) {
            PBR.showNotice('error', 'لطفاً نام محصول را وارد کنید');
            return;
        }
        
        PBR.showProgressModal();
        $btn.prop('disabled', true);
        
        PBR.updateProgressStep('research', 'loading');
        
        $.ajax({
            url: pbr_ajax.url,
            type: 'POST',
            data: {
                action: 'pbr_generate_product',
                nonce: pbr_ajax.nonce,
                product_name: $('#product_name').val(),
                keywords: $('#keywords').val(),
                research_method: $('input[name="research_method"]:checked').val(),
                manual_research: $('#manual_research').val(),
                publish_status: $('input[name="publish_status"]:checked').val()
            },
            timeout: 300000,
            success: function(response) {
                PBR.updateProgressStep('research', 'done');
                PBR.updateProgressStep('content', 'done');
                PBR.updateProgressStep('publish', 'done');
                
                setTimeout(function() {
                    PBR.hideProgressModal();
                    
                    if (response.success) {
                        PBR.showResultModal(response.data);
                        $form[0].reset();
                    } else {
                        PBR.showNotice('error', response.data.message);
                    }
                }, 500);
            },
            error: function(xhr, status, error) {
                PBR.hideProgressModal();
                PBR.showNotice('error', 'خطا در ارتباط با سرور: ' + error);
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    },
    
    // ============================================
    // Post Generation
    // ============================================
    
    handlePostSubmit: function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $btn = $('#pbr-generate-post-btn');
        
        if (!$('#post_topic').val().trim()) {
            PBR.showNotice('error', 'لطفاً موضوع پست را وارد کنید');
            return;
        }
        
        PBR.showModal('#pbr-post-progress-modal');
        $btn.prop('disabled', true);
        
        $.ajax({
            url: pbr_ajax.url,
            type: 'POST',
            data: {
                action: 'pbr_generate_post',
                nonce: pbr_ajax.nonce,
                topic: $('#post_topic').val(),
                keywords: $('#post_keywords').val(),
                post_type: $('#post_type').val(),
                research_method: $('input[name="post_research_method"]:checked').val(),
                manual_research: $('#post_manual_research').val(),
                publish_status: $('input[name="post_publish_status"]:checked').val()
            },
            timeout: 300000,
            success: function(response) {
                PBR.hideModal('#pbr-post-progress-modal');
                
                if (response.success) {
                    PBR.showPostResultModal(response.data);
                    $form[0].reset();
                } else {
                    PBR.showNotice('error', response.data.message);
                }
            },
            error: function(xhr, status, error) {
                PBR.hideModal('#pbr-post-progress-modal');
                PBR.showNotice('error', 'خطا: ' + error);
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    },
    
    // ============================================
    // Update Content
    // ============================================
    
    handleUpdateSubmit: function(e) {
        e.preventDefault();
        
        var updateType = $('input[name="update_type"]:checked').val();
        var itemId = updateType === 'product' 
            ? $('#product_id').val() 
            : $('#post_id').val();
        
        if (!itemId) {
            PBR.showNotice('error', 'لطفاً یک مورد انتخاب کنید');
            return;
        }
        
        var $btn = $('#pbr-update-btn');
        PBR.showModal('#pbr-update-progress-modal');
        $btn.prop('disabled', true);
        
        $.ajax({
            url: pbr_ajax.url,
            type: 'POST',
            data: {
                action: 'pbr_update_content',
                nonce: pbr_ajax.nonce,
                update_type: updateType,
                item_id: itemId,
                instructions: $('#update_instructions').val(),
                refresh_research: $('input[name="refresh_research"]:checked').val() || 'no'
            },
            timeout: 300000,
            success: function(response) {
                PBR.hideModal('#pbr-update-progress-modal');
                
                if (response.success) {
                    PBR.showUpdateResultModal(response.data);
                } else {
                    PBR.showNotice('error', response.data.message);
                }
            },
            error: function(xhr, status, error) {
                PBR.hideModal('#pbr-update-progress-modal');
                PBR.showNotice('error', 'خطا: ' + error);
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    },
    
    handleLoadContent: function() {
        var updateType = $('input[name="update_type"]:checked').val();
        var itemId = updateType === 'product' 
            ? $('#product_id').val() 
            : $('#post_id').val();
        
        if (!itemId) {
            PBR.showNotice('error', 'لطفاً یک مورد انتخاب کنید');
            return;
        }
        
        $.ajax({
            url: pbr_ajax.url,
            type: 'POST',
            data: {
                action: 'pbr_load_content',
                nonce: pbr_ajax.nonce,
                type: updateType,
                item_id: itemId
            },
            success: function(response) {
                if (response.success) {
                    var content = response.data.raw_content || 
                                  response.data.description || 
                                  response.data.content || '';
                    
                    $('#pbr-current-content-display').html(content.substring(0, 3000));
                    $('#pbr-current-content').show();
                } else {
                    PBR.showNotice('error', response.data.message);
                }
            }
        });
    },
    
    toggleUpdateSelects: function() {
        var type = $(this).val();
        if (type === 'product') {
            $('#product-select-row').show();
            $('#post-select-row').hide();
        } else {
            $('#product-select-row').hide();
            $('#post-select-row').show();
        }
        $('#pbr-current-content').hide();
    },
    
    // ============================================
    // Settings & Prompts
    // ============================================
    
    handleSettingsSave: function(e) {
        e.preventDefault();
        
        var $btn = $('#pbr-save-settings-btn');
        $btn.prop('disabled', true).text('در حال ذخیره...');
        
        $.ajax({
            url: pbr_ajax.url,
            type: 'POST',
            data: $(this).serialize() + '&action=pbr_save_settings&nonce=' + pbr_ajax.nonce,
            success: function(response) {
                if (response.success) {
                    PBR.showNotice('success', response.data.message);
                } else {
                    PBR.showNotice('error', response.data.message);
                }
            },
            complete: function() {
                $btn.prop('disabled', false).text('💾 ذخیره تنظیمات');
            }
        });
    },
    
    handlePromptsSave: function(e) {
        e.preventDefault();
        
        var $btn = $('#pbr-save-prompts-btn');
        $btn.prop('disabled', true).text('در حال ذخیره...');
        
        $.ajax({
            url: pbr_ajax.url,
            type: 'POST',
            data: $(this).serialize() + '&action=pbr_save_prompts&nonce=' + pbr_ajax.nonce,
            success: function(response) {
                if (response.success) {
                    PBR.showNotice('success', response.data.message);
                } else {
                    PBR.showNotice('error', response.data.message);
                }
            },
            complete: function() {
                $btn.prop('disabled', false).text('💾 ذخیره همه پرامپت‌ها');
            }
        });
    },
    
    handlePromptReset: function() {
        var promptType = $(this).data('prompt');
        
        if (!confirm('آیا مطمئن هستید؟')) {
            return;
        }
        
        $.ajax({
            url: pbr_ajax.url,
            type: 'POST',
            data: {
                action: 'pbr_reset_prompt',
                nonce: pbr_ajax.nonce,
                prompt_type: promptType
            },
            success: function(response) {
                if (response.success) {
                    $('#prompt_' + promptType).val(response.data.content);
                    PBR.showNotice('success', response.data.message);
                }
            }
        });
    },
    
    switchPromptTab: function() {
        var tab = $(this).data('tab');
        
        $('.pbr-tab-btn').removeClass('active');
        $(this).addClass('active');
        
        $('.pbr-prompt-tab').removeClass('active');
        $('.pbr-prompt-tab[data-tab="' + tab + '"]').addClass('active');
    },
    
    // ============================================
    // API Testing
    // ============================================
    
    handleApiTest: function() {
        var $btn = $(this);
        var apiType = $btn.data('api');
        var $status = $('#' + apiType + '-status');
        
        $btn.prop('disabled', true).text('در حال تست...');
        $status.html('<span class="pbr-testing">⏳ در حال بررسی...</span>');
        
        $.ajax({
            url: pbr_ajax.url,
            type: 'POST',
            data: {
                action: 'pbr_test_api',
                nonce: pbr_ajax.nonce,
                api_type: apiType
            },
            success: function(response) {
                if (response.success) {
                    $status.html('<span class="pbr-success">' + response.message + '</span>');
                } else {
                    $status.html('<span class="pbr-error">❌ ' + response.message + '</span>');
                }
            },
            complete: function() {
                $btn.prop('disabled', false).text('تست اتصال');
            }
        });
    },
    
    checkApiStatus: function() {
        var $container = $('#pbr-api-status-content');
        if (!$container.length) return;
        
        $.ajax({
            url: pbr_ajax.url,
            type: 'POST',
            data: {
                action: 'pbr_check_api_status',
                nonce: pbr_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    var html = '';
                    
                    if (response.data.blackbox.success) {
                        html += '<p class="pbr-api-ok">✅ Blackbox API: متصل</p>';
                    } else {
                        html += '<p class="pbr-api-error">❌ Blackbox API: ' + response.data.blackbox.message + '</p>';
                    }
                    
                    if (response.data.tavily.success) {
                        html += '<p class="pbr-api-ok">✅ Tavily API: متصل</p>';
                    } else {
                        html += '<p class="pbr-api-error">❌ Tavily API: ' + response.data.tavily.message + '</p>';
                    }
                    
                    $container.html(html);
                }
            }
        });
    },
    
    // ============================================
    // Logs
    // ============================================
    
    handleClearLogs: function() {
        if (!confirm('آیا مطمئن هستید؟')) return;
        
        $.ajax({
            url: pbr_ajax.url,
            type: 'POST',
            data: {
                action: 'pbr_clear_logs',
                nonce: pbr_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                }
            }
        });
    },
    
    handleExportLogs: function() {
        $.ajax({
            url: pbr_ajax.url,
            type: 'POST',
            data: {
                action: 'pbr_export_logs',
                nonce: pbr_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    var blob = new Blob([atob(response.data.csv)], {type: 'text/csv;charset=utf-8;'});
                    var link = document.createElement('a');
                    link.href = URL.createObjectURL(blob);
                    link.download = response.data.filename;
                    link.click();
                }
            }
        });
    },
    
    // ============================================
    // UI Helpers
    // ============================================
    
    togglePassword: function() {
        var $input = $(this).siblings('input');
        var type = $input.attr('type') === 'password' ? 'text' : 'password';
        $input.attr('type', type);
    },
    
    toggleResearchInput: function() {
        var method = $(this).val();
        if (method === 'manual') {
            $('.pbr-manual-research').show();
        } else {
            $('.pbr-manual-research').hide();
        }
    },
    
    togglePostResearchInput: function() {
        var method = $(this).val();
        if (method === 'manual') {
            $('.pbr-post-manual-research').show();
        } else {
            $('.pbr-post-manual-research').hide();
        }
    },
    
    // ============================================
    // Modals
    // ============================================
    
    showProgressModal: function() {
        $('#pbr-progress-modal').fadeIn(200);
        $('.pbr-step').each(function() {
            $(this).find('.pbr-step-icon').text('⏳');
        });
        $('.pbr-progress-fill').css('width', '0%');
    },
    
    hideProgressModal: function() {
        $('#pbr-progress-modal').fadeOut(200);
    },
    
    updateProgressStep: function(step, status) {
        var $step = $('.pbr-step[data-step="' + step + '"]');
        var icon = status === 'loading' ? '🔄' : (status === 'done' ? '✅' : '❌');
        $step.find('.pbr-step-icon').text(icon);
        
        var steps = ['research', 'content', 'publish'];
        var currentIndex = steps.indexOf(step);
        var progress = ((currentIndex + 1) / steps.length) * 100;
        $('.pbr-progress-fill').css('width', progress + '%');
    },
    
    showResultModal: function(data) {
        var html = '<div class="pbr-result-success">';
        html += '<h2>' + data.message + '</h2>';
        html += '<div class="pbr-result-details">';
        html += '<p><strong>عنوان:</strong> ' + PBR.escapeHtml(data.title) + '</p>';
        html += '<p><strong>شناسه محصول:</strong> ' + data.product_id + '</p>';
        html += '<p><strong>طول HTML:</strong> ' + data.html_length + ' کاراکتر</p>';
        html += '<p><strong>فیلدهای سفارشی:</strong> ' + data.custom_fields_count + '</p>';
        html += '</div>';
        html += '<div class="pbr-result-actions">';
        html += '<a href="' + data.edit_link + '" class="button button-primary" target="_blank">✏️ ویرایش محصول</a>';
        html += '<a href="' + data.view_link + '" class="button" target="_blank">👁️ مشاهده محصول</a>';
        html += '</div>';
        html += '</div>';
        
        $('#pbr-result-content').html(html);
        $('#pbr-result-modal').fadeIn(200);
    },
    
    showPostResultModal: function(data) {
        var html = '<div class="pbr-result-success">';
        html += '<h2>' + data.message + '</h2>';
        html += '<p><strong>عنوان:</strong> ' + PBR.escapeHtml(data.title) + '</p>';
        html += '<div class="pbr-result-actions">';
        html += '<a href="' + data.edit_link + '" class="button button-primary" target="_blank">✏️ ویرایش پست</a>';
        html += '<a href="' + data.view_link + '" class="button" target="_blank">👁️ مشاهده پست</a>';
        html += '</div>';
        html += '</div>';
        
        $('#pbr-post-result-content').html(html);
        $('#pbr-post-result-modal').fadeIn(200);
    },
    
    showUpdateResultModal: function(data) {
        var html = '<div class="pbr-result-success">';
        html += '<h2>' + data.message + '</h2>';
        html += '<div class="pbr-result-actions">';
        html += '<a href="' + data.edit_link + '" class="button button-primary" target="_blank">✏️ مشاهده و ویرایش</a>';
        html += '</div>';
        html += '</div>';
        
        $('#pbr-update-result-content').html(html);
        $('#pbr-update-result-modal').fadeIn(200);
    },
    
    showModal: function(selector) {
        $(selector).fadeIn(200);
    },
    
    hideModal: function(selector) {
        $(selector).fadeOut(200);
    },
    
    closeModal: function() {
        $(this).closest('.pbr-modal').fadeOut(200);
    },
    
    // ============================================
    // Utilities
    // ============================================
    
    showNotice: function(type, message) {
        var $notice = $('<div class="notice notice-' + type + ' is-dismissible pbr-notice"><p>' + message + '</p></div>');
        $('.pbr-wrap h1').after($notice);
        
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    },
    
    escapeHtml: function(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },
    
    // ============================================
    // Queue and Bulk Functions
    // ============================================
    
    queueProcessing: false,
    queueProcessInterval: null,
    
    handleBulkAdd: function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');
        var btnText = $btn.html();
        
        $btn.prop('disabled', true).html('⏳ در حال افزودن...');
        
        $.ajax({
            url: pbr_ajax.url,
            type: 'POST',
            data: $form.serialize() + '&action=pbr_add_to_queue',
            success: function(response) {
                if (response.success) {
                    PBR.showNotice('success', response.data.message);
                    $form[0].reset();
                    PBR.refreshQueueStats();
                    PBR.refreshQueueItems();
                } else {
                    PBR.showNotice('error', response.data.message || 'خطا در افزودن به صف');
                }
            },
            error: function() {
                PBR.showNotice('error', 'خطا در ارتباط با سرور');
            },
            complete: function() {
                $btn.prop('disabled', false).html(btnText);
            }
        });
    },
    
    handleProcessQueue: function(e) {
        e.preventDefault();
        
        if (PBR.queueProcessing) {
            return;
        }
        
        PBR.queueProcessing = true;
        $('#pbr-process-queue-btn').hide();
        $('#pbr-stop-queue-btn').show();
        $('#pbr-processing-progress').show();
        
        PBR.processNextQueueItem();
    },
    
    handleStopQueue: function(e) {
        e.preventDefault();
        
        PBR.queueProcessing = false;
        
        if (PBR.queueProcessInterval) {
            clearTimeout(PBR.queueProcessInterval);
        }
        
        $('#pbr-stop-queue-btn').hide();
        $('#pbr-process-queue-btn').show();
        $('#pbr-processing-progress').hide();
        
        PBR.showNotice('info', 'پردازش صف متوقف شد');
        PBR.refreshQueueStats();
        PBR.refreshQueueItems();
    },
    
    processNextQueueItem: function() {
        if (!PBR.queueProcessing) {
            return;
        }
        
        // Get next pending item
        $.ajax({
            url: pbr_ajax.url,
            type: 'POST',
            data: {
                action: 'pbr_get_queue_items',
                nonce: pbr_ajax.nonce,
                status: 'pending'
            },
            success: function(response) {
                if (response.success && response.data.items && response.data.items.length > 0) {
                    var item = response.data.items[0];
                    PBR.processQueueItem(item);
                } else {
                    // No more pending items
                    PBR.handleStopQueue({preventDefault: function(){}});
                    PBR.showNotice('success', '✅ همه آیتم‌های صف پردازش شدند');
                }
            },
            error: function() {
                PBR.handleStopQueue({preventDefault: function(){}});
                PBR.showNotice('error', 'خطا در دریافت آیتم‌های صف');
            }
        });
    },
    
    processQueueItem: function(item) {
        $('#processing-item-name').text(item.item_name);
        $('#processing-status').text('در حال پردازش...');
        
        $.ajax({
            url: pbr_ajax.url,
            type: 'POST',
            data: {
                action: 'pbr_process_queue_item',
                nonce: pbr_ajax.nonce,
                item_id: item.id
            },
            success: function(response) {
                if (response.success) {
                    $('#processing-status').text('✅ موفق');
                    PBR.updateQueueProgress();
                    
                    // Continue with next item after delay
                    PBR.queueProcessInterval = setTimeout(function() {
                        PBR.processNextQueueItem();
                    }, 2000);
                } else {
                    $('#processing-status').text('❌ ناموفق: ' + (response.data.message || 'خطا'));
                    
                    // Continue with next item after delay
                    PBR.queueProcessInterval = setTimeout(function() {
                        PBR.processNextQueueItem();
                    }, 2000);
                }
            },
            error: function() {
                $('#processing-status').text('❌ خطا در پردازش');
                
                // Continue with next item after delay
                PBR.queueProcessInterval = setTimeout(function() {
                    PBR.processNextQueueItem();
                }, 2000);
            }
        });
    },
    
    updateQueueProgress: function() {
        $.ajax({
            url: pbr_ajax.url,
            type: 'POST',
            data: {
                action: 'pbr_get_queue_stats',
                nonce: pbr_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    var stats = response.data.stats;
                    var processed = stats.completed + stats.failed;
                    var total = stats.total;
                    var percent = total > 0 ? (processed / total * 100) : 0;
                    
                    $('#processed-count').text(processed);
                    $('#total-count').text(total);
                    $('#pbr-progress-fill').css('width', percent + '%');
                    
                    PBR.refreshQueueStats();
                }
            }
        });
    },
    
    refreshQueueStats: function() {
        $.ajax({
            url: pbr_ajax.url,
            type: 'POST',
            data: {
                action: 'pbr_get_queue_stats',
                nonce: pbr_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    var stats = response.data.stats;
                    $('#stat-total').text(stats.total);
                    $('#stat-pending').text(stats.pending);
                    $('#stat-processing').text(stats.processing);
                    $('#stat-completed').text(stats.completed);
                    $('#stat-failed').text(stats.failed);
                }
            }
        });
    },
    
    refreshQueueItems: function() {
        var status = $('#pbr-queue-filter').val() || 'all';
        
        $.ajax({
            url: pbr_ajax.url,
            type: 'POST',
            data: {
                action: 'pbr_get_queue_items',
                nonce: pbr_ajax.nonce,
                status: status
            },
            success: function(response) {
                if (response.success) {
                    PBR.renderQueueItems(response.data.items);
                }
            }
        });
    },
    
    renderQueueItems: function(items) {
        var $tbody = $('#pbr-queue-items');
        $tbody.empty();
        
        if (!items || items.length === 0) {
            $tbody.html('<tr><td colspan="7" style="text-align: center;">آیتمی در صف وجود ندارد</td></tr>');
            return;
        }
        
        $.each(items, function(index, item) {
            var statusClass = 'pbr-status-' + item.status;
            var statusText = {
                'pending': 'در انتظار',
                'processing': 'در حال پردازش',
                'completed': 'موفق',
                'failed': 'ناموفق'
            }[item.status] || item.status;
            
            var typeText = item.item_type === 'product' ? 'محصول' : 'پست';
            
            var actions = '';
            if (item.status === 'pending') {
                actions += '<button class="button pbr-delete-queue-item" data-id="' + item.id + '">حذف</button>';
            } else if (item.status === 'failed') {
                actions += '<button class="button pbr-retry-queue-item" data-id="' + item.id + '">تلاش مجدد</button> ';
                actions += '<button class="button pbr-delete-queue-item" data-id="' + item.id + '">حذف</button>';
            } else if (item.status === 'completed') {
                actions += '<button class="button pbr-delete-queue-item" data-id="' + item.id + '">حذف</button>';
            }
            
            var row = '<tr>' +
                '<td>' + item.id + '</td>' +
                '<td>' + PBR.escapeHtml(item.item_name) + '</td>' +
                '<td>' + typeText + '</td>' +
                '<td><span class="' + statusClass + '">' + statusText + '</span></td>' +
                '<td>' + item.created_at + '</td>' +
                '<td>' + (item.processed_at || '-') + '</td>' +
                '<td>' + actions + '</td>' +
                '</tr>';
            
            $tbody.append(row);
        });
    },
    
    handleDeleteQueueItem: function(e) {
        e.preventDefault();
        
        if (!confirm('آیا از حذف این آیتم اطمینان دارید؟')) {
            return;
        }
        
        var itemId = $(this).data('id');
        
        $.ajax({
            url: pbr_ajax.url,
            type: 'POST',
            data: {
                action: 'pbr_delete_queue_item',
                nonce: pbr_ajax.nonce,
                item_id: itemId
            },
            success: function(response) {
                if (response.success) {
                    PBR.showNotice('success', response.data.message);
                    PBR.refreshQueueStats();
                    PBR.refreshQueueItems();
                } else {
                    PBR.showNotice('error', response.data.message || 'خطا در حذف آیتم');
                }
            },
            error: function() {
                PBR.showNotice('error', 'خطا در ارتباط با سرور');
            }
        });
    },
    
    handleRetryQueueItem: function(e) {
        e.preventDefault();
        
        var itemId = $(this).data('id');
        
        $.ajax({
            url: pbr_ajax.url,
            type: 'POST',
            data: {
                action: 'pbr_retry_queue_item',
                nonce: pbr_ajax.nonce,
                item_id: itemId
            },
            success: function(response) {
                if (response.success) {
                    PBR.showNotice('success', response.data.message);
                    PBR.refreshQueueStats();
                    PBR.refreshQueueItems();
                } else {
                    PBR.showNotice('error', response.data.message || 'خطا در تلاش مجدد');
                }
            },
            error: function() {
                PBR.showNotice('error', 'خطا در ارتباط با سرور');
            }
        });
    },
    
    handleClearCompleted: function(e) {
        e.preventDefault();
        
        if (!confirm('آیا از پاک کردن آیتم‌های موفق اطمینان دارید؟')) {
            return;
        }
        
        $.ajax({
            url: pbr_ajax.url,
            type: 'POST',
            data: {
                action: 'pbr_clear_completed',
                nonce: pbr_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    PBR.showNotice('success', response.data.message);
                    PBR.refreshQueueStats();
                    PBR.refreshQueueItems();
                } else {
                    PBR.showNotice('error', response.data.message || 'خطا در پاک کردن');
                }
            },
            error: function() {
                PBR.showNotice('error', 'خطا در ارتباط با سرور');
            }
        });
    }
};

window.PBR = PBR;
})(jQuery);