
$(document).ready(function() {
    // Initialize modal
    var historyModal = new bootstrap.Modal(document.getElementById('historyModal'));
    
    // Handle click on barcode history button
    $('.history-btn').on('click', function() {
        var barcode = $(this).data('barcode');
        $('#modalBarcodeTitle').text(barcode);
        
        // Show loader and hide content
        $('#historyLoader').show();
        $('#historyContent').hide();
        $('#noHistoryAlert').hide();
        $('#errorAlert').hide();
        
        // Show the modal
        historyModal.show();
        
        // Fetch barcode history via AJAX
        $.ajax({
            url: window.location.pathname,
            data: {
                ajax: 'getHistory',
                barcode: barcode
            },
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                // Hide loader
                $('#historyLoader').hide();
                
                // Check for error in response
                if (data.error) {
                    $('#errorAlert').text(data.error).show();
                    return;
                }
                
                if (data.length === 0) {
                    // Show no history alert
                    $('#noHistoryAlert').show();
                } else {
                    // Populate and show history content
                    var historyHtml = '<div class="table-responsive">';
                    historyHtml += '<table class="table table-striped">';
                    historyHtml += '<thead><tr><th>Action</th><th>Stage</th><th>Date/Time</th></tr></thead>';
                    historyHtml += '<tbody>';
                    
                    $.each(data, function(index, history) {
                        var actionBadge = '';
                        switch(history.action_type) {
                            case 'INSERT':
                                actionBadge = '<span class="badge bg-success">Created</span>';
                                break;
                            case 'UPDATE':
                                actionBadge = '<span class="badge bg-primary">Updated</span>';
                                break;
                            case 'DELETE':
                                actionBadge = '<span class="badge bg-danger">Deleted</span>';
                                break;
                            default:
                                actionBadge = '<span class="badge bg-secondary">' + history.action_type + '</span>';
                        }
                        
                        historyHtml += '<tr>';
                        historyHtml += '<td>' + actionBadge + '</td>';
                        historyHtml += '<td><span class="badge bg-info text-white">' + (history.stage || 'No Stage') + '</span></td>';
                        historyHtml += '<td><i class="fas fa-calendar-alt"></i> ' + new Date(history.last_update).toLocaleString() + '</td>';
                        historyHtml += '</tr>';
                    });
                    
                    historyHtml += '</tbody></table></div>';
                    $('#historyContent').html(historyHtml).show();
                }
            },
            error: function(xhr, status, error) {
                $('#historyLoader').hide();
                console.error('AJAX Error:', status, error);
                $('#errorAlert').html('<i class="fas fa-exclamation-triangle"></i> Error loading barcode history. Please try again. Error: ' + error).show();
            }
        });
    });
});



$(document).ready(function() {
    // Initialize delete modal
    var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    var barcodeToDelete = '';
    
    // Handle click on delete button
    $('.delete-btn').on('click', function() {
        barcodeToDelete = $(this).data('barcode');
        $('#deleteBarcodeName').text(barcodeToDelete);
        deleteModal.show();
    });
    
    // Handle confirmation of delete
    $('#confirmDelete').on('click', function() {
        // Show loading state
        $(this).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Deleting...');
        $(this).prop('disabled', true);
        
        // Send delete request
        $.ajax({
            url: window.location.pathname,
            data: {
                ajax: 'deleteBarcode',
                barcode: barcodeToDelete
            },
            method: 'POST',
            dataType: 'json',
            success: function(response) {
                deleteModal.hide();
                
                if (response.error) {
                    // Show error message
                    alert('Error: ' + response.error);
                } else {
                    // Remove the deleted row and show success message
                    $('tr, div.card').filter(function() {
                        return $(this).find('.delete-btn').data('barcode') === barcodeToDelete;
                    }).fadeOut(400, function() {
                        $(this).remove();
                    });
                    
                    // Show success toast
                    alert('Barcode successfully deleted');
                    
                    // Reload the page if no items left
                    if ($('tr').length <= 2) { // Header row + the deleted one
                        location.reload();
                    }
                }
            },
            error: function(xhr, status, error) {
                deleteModal.hide();
                console.error('AJAX Error:', status, error);
                alert('Error deleting barcode. Please try again.');
            },
            complete: function() {
                // Reset button state
                $('#confirmDelete').html('<i class="fas fa-trash"></i> Delete');
                $('#confirmDelete').prop('disabled', false);
            }
        });
    });
});

// Add JavaScript for Edit functionality
$(document).ready(function() {
    // Initialize edit modal
    var editModal = new bootstrap.Modal(document.getElementById('editModal'));
    var barcodeToEdit = '';
    
    // Handle click on edit button
    $('.edit-btn').on('click', function() {
        barcodeToEdit = $(this).data('barcode');
        var currentUser = $(this).data('user');
        
        $('#editBarcodeName').text(barcodeToEdit);
        $('#editUserSelect').val(currentUser);
        editModal.show();
    });
    
    // Handle confirmation of edit
    $('#confirmEdit').on('click', function() {
        var newUser = $('#editUserSelect').val();
        
        if (!newUser) {
            alert('Please select a user');
            return;
        }
        
        // Show loading state
        $(this).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
        $(this).prop('disabled', true);
        
        // Send edit request
        $.ajax({
            url: window.location.pathname,
            data: {
                ajax: 'updateUser',
                barcode: barcodeToEdit,
                user: newUser
            },
            method: 'POST',
            dataType: 'json',
            success: function(response) {
                editModal.hide();
                
                if (response.error) {
                    // Show error message
                    alert('Error: ' + response.error);
                } else {
                    // Update the user name in the UI
                    $('tr, div.card').filter(function() {
                        return $(this).find('.edit-btn').data('barcode') === barcodeToEdit;
                    }).find('td:nth-child(9), li:contains("Used by")').text(newUser);
                    
                    // Update the data attribute
                    $('.edit-btn').filter(function() {
                        return $(this).data('barcode') === barcodeToEdit;
                    }).data('user', newUser);
                    
                    // Show success message
                    alert('User successfully updated');
                }
            },
            error: function(xhr, status, error) {
                editModal.hide();
                console.error('AJAX Error:', status, error);
                alert('Error updating user. Please try again.');
            },
            complete: function() {
                // Reset button state
                $('#confirmEdit').html('<i class="fas fa-save"></i> Save Changes');
                $('#confirmEdit').prop('disabled', false);
            }
        });
    });
});