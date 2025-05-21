document.addEventListener('DOMContentLoaded', function() {
    // Toggle bulk edit form
    document.getElementById('showBulkEditBtn').addEventListener('click', function() {
        const selectedCheckboxes = document.querySelectorAll('.barcode-checkbox:checked');
        if (selectedCheckboxes.length === 0) {
            alert('Please select at least one barcode to edit');
            return;
        }
        
        document.getElementById('bulkEditForm').style.display = 'block';
        document.getElementById('deleteForm').style.display = 'none';
        
        // Clear previous selections
        document.getElementById('selected_barcodes_container').innerHTML = '';
        
        // Add hidden inputs for selected barcodes
        selectedCheckboxes.forEach(function(checkbox) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'selected_barcodes[]';
            input.value = checkbox.value;
            document.getElementById('selected_barcodes_container').appendChild(input);
        });
    });
    
    // Toggle delete form
    document.getElementById('showDeleteBtn').addEventListener('click', function() {
        const selectedCheckboxes = document.querySelectorAll('.barcode-checkbox:checked');
        if (selectedCheckboxes.length === 0) {
            alert('Please select at least one barcode to delete');
            return;
        }
        
        document.getElementById('deleteForm').style.display = 'block';
        document.getElementById('bulkEditForm').style.display = 'none';
        
        // Clear previous selections
        document.getElementById('delete_barcodes_container').innerHTML = '';
        
        // Add hidden inputs for selected barcodes
        selectedCheckboxes.forEach(function(checkbox) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'selected_barcodes[]';
            input.value = checkbox.value;
            document.getElementById('delete_barcodes_container').appendChild(input);
        });
    });
    
    // Cancel bulk edit
    document.getElementById('cancelBulkEditBtn').addEventListener('click', function() {
        document.getElementById('bulkEditForm').style.display = 'none';
    });
    
    // Cancel delete
    document.getElementById('cancelDeleteBtn').addEventListener('click', function() {
        document.getElementById('deleteForm').style.display = 'none';
    });
    
    // Enable/disable select fields based on checkboxes
    document.getElementById('update_status').addEventListener('change', function() {
        document.getElementById('bulk_status').disabled = !this.checked;
    });
    
    document.getElementById('update_stage').addEventListener('change', function() {
        document.getElementById('bulk_stage').disabled = !this.checked;
    });
    
    document.getElementById('update_chef').addEventListener('change', function() {
        document.getElementById('bulk_chef').disabled = !this.checked;
    });
    
    document.getElementById('update_timestamp').addEventListener('change', function() {
        document.getElementById('bulk_timestamp').disabled = !this.checked;
    });
    
    // Select all checkboxes
    document.getElementById('selectAllBtn').addEventListener('click', function() {
        const checkboxes = document.querySelectorAll('.barcode-checkbox');
        checkboxes.forEach(function(checkbox) {
            checkbox.checked = true;
        });
        document.getElementById('checkAll').checked = true;
    });
    
    // Deselect all checkboxes
    document.getElementById('deselectAllBtn').addEventListener('click', function() {
        const checkboxes = document.querySelectorAll('.barcode-checkbox');
        checkboxes.forEach(function(checkbox) {
            checkbox.checked = false;
        });
        document.getElementById('checkAll').checked = false;
    });
    
    // Check/uncheck all checkboxes
    document.getElementById('checkAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.barcode-checkbox');
        checkboxes.forEach(function(checkbox) {
            checkbox.checked = this.checked;
        });
    });
    
    // Update barcode preview in edit form
    if (document.getElementById('edit_of_number')) {
        const updateBarcodePreview = function() {
            const ofNumber = document.getElementById('edit_of_number').value;
            const size = document.getElementById('edit_size').value;
            const category = document.getElementById('edit_category').value;
            const pieceName = document.getElementById('edit_piece_name').value;
            const orderStr = document.getElementById('edit_order_str').value;
            
            let preview = ofNumber + '-' + size + '-';
            if (category) {
                preview += category + '-';
            }
            preview += pieceName + '-' + orderStr;
            
            document.getElementById('barcode_preview').textContent = preview;
        };
        
        // Add event listeners to form inputs
        document.getElementById('edit_of_number').addEventListener('input', updateBarcodePreview);
        document.getElementById('edit_size').addEventListener('input', updateBarcodePreview);
        document.getElementById('edit_category').addEventListener('input', updateBarcodePreview);
        document.getElementById('edit_piece_name').addEventListener('input', updateBarcodePreview);
        document.getElementById('edit_order_str').addEventListener('input', updateBarcodePreview);
    }
    
});

