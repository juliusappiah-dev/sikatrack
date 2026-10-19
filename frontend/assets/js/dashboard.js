$(document).ready(function () {
    // Handle Add Transaction Submission
    $('#transactionForm').on('submit', function (e) {
        e.preventDefault();

        $.ajax({
            url: '../api/save_transaction.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    alert(response.message);
                    $('#transactionModal').modal('hide');
                    $('#transactionForm')[0].reset();
                    loadDashboardSummary(); // Refresh dashboard cards
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function () {
                alert('Server error occurred. Please try again.');
            }
        });
    });
});