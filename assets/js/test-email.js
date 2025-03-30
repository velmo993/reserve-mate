jQuery(document).ready(function($) {
    // Test client email button
    $('#test_client_email').on('click', function() {
        var testEmail = $('#test_email').val();
        var resultSpan = $('#email_test_result');
        
        resultSpan.html('<span style="color: #999;">Sending test client email...</span>');
        
        $.ajax({
            type: 'POST',
            url: email_test_data.ajax_url,
            data: {
                action: 'test_client_email',
                nonce: email_test_data.nonce,
                test_email: testEmail
            },
            success: function(response) {
                if (response.success) {
                    resultSpan.html('<span style="color: #46b450;">' + response.data + '</span>');
                } else {
                    resultSpan.html('<span style="color: #dc3232;">' + response.data + '</span>');
                }
            },
            error: function() {
                resultSpan.html('<span style="color: #dc3232;">Error: Could not connect to server.</span>');
            }
        });
    });
    
    // Test admin email button
    $('#test_admin_email').on('click', function() {
        var testEmail = $('#test_email').val();
        var resultSpan = $('#email_test_result');
        
        resultSpan.html('<span style="color: #999;">Sending test admin email...</span>');
        
        $.ajax({
            type: 'POST',
            url: email_test_data.ajax_url,
            data: {
                action: 'test_admin_email',
                nonce: email_test_data.nonce,
                test_email: testEmail
            },
            success: function(response) {
                if (response.success) {
                    resultSpan.html('<span style="color: #46b450;">' + response.data + '</span>');
                } else {
                    resultSpan.html('<span style="color: #dc3232;">' + response.data + '</span>');
                }
            },
            error: function() {
                resultSpan.html('<span style="color: #dc3232;">Error: Could not connect to server.</span>');
            }
        });
    });
});