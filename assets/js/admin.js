(function ($) {
    'use strict';
    $(function () {
        $('#notion-sync-for-wp-test-connection, .notion-sync-test-conn').on('click', function (e) {
            e.preventDefault();

            var $trigger = $(this);
            var $spinner = $trigger.parent().find('.spinner');
            var dbId = $trigger.data('dbid') || $('#notion_sync_database_id').val();

            // On mapping page, if dbId is empty, try to get it from the input directly
            if (!dbId && $('#notion_sync_database_id').length) {
                dbId = $('#notion_sync_database_id').val();
            }

            $trigger.css('pointer-events', 'none').css('opacity', '0.5');
            $spinner.addClass('is-active');

            $.ajax({
                url: notionSyncAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'notion_sync_test_connection',
                    _ajax_nonce: notionSyncAdmin.nonce,
                    database_id: dbId
                },
                success: function (response) {
                    alert(response.data);
                },
                error: function () {
                    alert('An unexpected error occurred during the connection test.');
                },
                complete: function () {
                    $trigger.css('pointer-events', 'auto').css('opacity', '1');
                    $spinner.removeClass('is-active');
                }
            });
        });

        // Run Mass Sync (General or Connection-specific)
        $('.notion-sync-run-single').on('click', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var connId = $btn.data('id');
            window.location.href = notionSyncAdmin.adminUrl + 'admin.php?page=notion-sync-for-wp-logs&sync_conn=' + connId;
        });

        // If we are on the logs page and have a sync_conn parameter, auto-trigger sync
        var urlParams = new URLSearchParams(window.location.search);
        var autoSyncConn = urlParams.get('sync_conn');
        if (autoSyncConn) {
            $(document).ready(function () {
                $('#notion-sync-for-wp-run-mass').trigger('click');
            });
        }

        $('#notion-sync-for-wp-run-mass').on('click', function (e) {
            e.preventDefault();

            var $btn = $(this);
            var $spinner = $btn.siblings('.spinner');
            var $logArea = $('#notion-sync-for-wp-mass-log');
            var $logList = $('#notion-sync-for-wp-log-list');
            var connId = autoSyncConn || $('#notion_sync_target_connection').val();

            if (!connId) {
                alert('Please select a connection to sync.');
                return;
            }

            $btn.prop('disabled', true);
            $spinner.addClass('is-active');
            $logArea.show();
            $logList.empty();
            $logList.append('<li><em>Initializing sync for connection...</em></li>');

            function syncBatch(cursor) {
                $.ajax({
                    url: notionSyncAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'notion_sync_mass_sync',
                        _ajax_nonce: notionSyncAdmin.nonce,
                        connection_id: connId,
                        cursor: cursor
                    },
                    success: function (response) {
                        if (response.success) {
                            var results = response.data;

                            // Append batch results
                            $logList.append('<li><strong>Batch Results: ' + results.success + ' success, ' + results.errors + ' errors</strong></li>');
                            results.details.forEach(function (msg) {
                                $logList.append('<li>' + msg + '</li>');
                            });

                            if (results.has_more && results.next_cursor) {
                                $logList.append('<li><em>Fetching next batch...</em></li>');
                                syncBatch(results.next_cursor);
                            } else {
                                $logList.append('<li><strong>Sync Complete! All "Ready" posts from this connection have been processed.</strong></li>');
                                $spinner.removeClass('is-active');
                                $btn.prop('disabled', false);
                            }
                        } else {
                            $logList.append('<li class="error" style="color:red;"><strong>Error: ' + (response.data || 'Batch sync failed.') + '</strong></li>');
                            $spinner.removeClass('is-active');
                            $btn.prop('disabled', false);
                        }
                    },
                    error: function () {
                        $logList.append('<li class="error" style="color:red;"><strong>An unexpected server error occurred.</strong></li>');
                        $spinner.removeClass('is-active');
                        $btn.prop('disabled', false);
                    }
                });
            }

            // Start first batch
            syncBatch(null);
        });

        // Mapping Page: Live AJAX refresh when Post Type OR Database ID changes
        $('#notion_sync_post_type, #notion_sync_database_id').on('input change', function () {
            var $container = $('#notion-sync-for-wp-dynamic-mapping');
            var postType = $('#notion_sync_post_type').val();
            var dbId = $('#notion_sync_database_id').val();

            // Only trigger if we have a plausible Database ID (32 chars is standard Notion DB ID)
            if (!dbId || dbId.length < 32) {
                $container.html('<div class="notice notice-info inline"><p>Enter a valid Notion Database ID to see mapping fields.</p></div>');
                return;
            }

            $container.css('opacity', '0.5').addClass('is-loading');

            $.ajax({
                url: notionSyncAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'notion_sync_get_mapping_fields',
                    _ajax_nonce: notionSyncAdmin.nonce,
                    post_type: postType,
                    database_id: dbId
                },
                success: function (response) {
                    if (response.success) {
                        $container.html(response.data);
                    } else {
                        $container.html('<div class="notice notice-error inline"><p>' + response.data + '</p></div>');
                    }
                },
                error: function () {
                    $container.html('<div class="notice notice-error inline"><p>Error connecting to Notion. Please check your API Key.</p></div>');
                },
                complete: function () {
                    $container.css('opacity', '1').removeClass('is-loading');
                }
            });
        });
        // Mapping Page: Handle dynamic Custom Meta rows
        $(document).on('click', '#add-meta-mapping', function (e) {
            e.preventDefault();
            var $tbody = $('#notion-sync-for-wp-meta-mapping tbody');
            var template = $('#tmpl-notion-sync-meta-row').html();

            // Use a unique index based on timestamp to avoid collisions
            var index = Date.now();
            var rowHtml = template.replace(/{{INDEX}}/g, index);

            $tbody.append(rowHtml);
        });


        $(document).on('click', '.remove-meta-row', function (e) {
            e.preventDefault();
            if (confirm('Are you sure you want to remove this field mapping?')) {
                $(this).closest('tr').remove();
            }
        });
    });
})(jQuery);
