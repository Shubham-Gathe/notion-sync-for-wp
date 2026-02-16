(function ($) {
    'use strict';
    $(function () {
        $('#notion-sync-for-wp-test-connection').on('click', function (e) {
            e.preventDefault();

            var $button = $(this);
            var $spinner = $button.siblings('.spinner');
            var $result = $('#notion-sync-for-wp-test-result');

            $button.prop('disabled', true);
            $spinner.addClass('is-active');
            $result.fadeOut().removeClass('notice notice-success notice-error');

            $.ajax({
                url: notionSyncAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'notion_sync_test_connection',
                    _ajax_nonce: notionSyncAdmin.nonce
                },
                success: function (response) {
                    $result.addClass('notice notice-' + (response.success ? 'success' : 'error'))
                        .html('<p>' + response.data + '</p>')
                        .fadeIn();
                },
                error: function () {
                    $result.addClass('notice notice-error')
                        .html('<p>An unexpected error occurred.</p>')
                        .fadeIn();
                },
                complete: function () {
                    $button.prop('disabled', false);
                    $spinner.removeClass('is-active');
                }
            });
        });

        // Run Mass Sync
        $('#notion-sync-for-wp-run-mass').on('click', function (e) {
            e.preventDefault();

            var $btn = $(this);
            var $spinner = $btn.siblings('.spinner');
            var $logArea = $('#notion-sync-for-wp-mass-log');
            var $logList = $('#notion-sync-for-wp-log-list');

            $btn.prop('disabled', true);
            $spinner.addClass('is-active');
            $logArea.show();
            $logList.empty();
            $logList.append('<li><em>Initializing batch sync...</em></li>');

            function syncBatch(cursor) {
                $.ajax({
                    url: notionSyncAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'notion_sync_mass_sync',
                        _ajax_nonce: notionSyncAdmin.nonce,
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
                                $logList.append('<li><strong>Sync Complete! All "Ready" posts have been processed.</strong></li>');
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
    });
})(jQuery);
