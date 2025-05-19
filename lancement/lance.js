function updateDateTime() {
    const now = new Date();
    const day = String(now.getDate()).padStart(2, '0');
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const year = now.getFullYear();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    const formattedDateTime = `${day}/${month}/${year} ${hours}:${minutes}:${seconds}`;
    document.querySelectorAll('#currentDateTime').forEach(el => {
        el.textContent = formattedDateTime;
    });
}
updateDateTime();
setInterval(updateDateTime, 1000);
document.querySelectorAll('.add-new-btn').forEach(button => {
    button.addEventListener('click', function() {
        const addDataModal = new bootstrap.Modal(document.getElementById('addDataModal'));
        addDataModal.show();
    });
});
document.querySelectorAll('.logout-btn').forEach(button => {
    button.addEventListener('click', function() {
        window.location.href = '../start.php';
    });
});
(function() {
    'use strict';
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {

    form.addEventListener('submit', event => {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        } else {
            event.preventDefault();
            const formData = {
                id: document.getElementById('recordId').value,
                ofNumber: document.getElementById('ofNumber').value,
                ofQuantity: document.getElementById('ofQuantity').value,
                ofType: document.getElementById('ofType').value,
                ofCategory: document.getElementById('ofCategory').value,
                tailles: document.getElementById('tailles').value,
                packNumber: document.getElementById('packNumber').value,
                packOrderStart: document.getElementById('packOrderStart').value,
                packOrderEnd: document.getElementById('packOrderEnd').value,
                dv: document.getElementById('dv').value,
                g: document.getElementById('g').value,
                m: document.getElementById('m').value,
                dos: document.getElementById('dos').value,
            };
            fetch('ayoub.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Data saved successfully!');
                    const addDataModal = bootstrap.Modal.getInstance(document.getElementById('addDataModal'));
                    addDataModal.hide();
                    form.reset();
                    form.classList.remove('was-validated');
                    window.location.reload(); // Refresh to show new data
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving.');
            });
        }
        form.classList.add('was-validated');
    }, false);
    });
})();

document.getElementById('packOrderEnd').addEventListener('change', function() {
    const start = parseInt(document.getElementById('packOrderStart').value) || 0;
    const end = parseInt(this.value) || 0;
    if (end < start) {
        alert('End value must be greater than or equal to Start value');
        this.value = start;
    }
});

document.getElementById('packOrderStart').addEventListener('change', function() {
    const start = parseInt(this.value) || 0;
    const end = parseInt(document.getElementById('packOrderEnd').value) || 0;
    if (start > end && end !== 0) {
        alert('Start value must be less than or equal to End value');
        this.value = end;
    }
});
        
document.addEventListener('click', function(event) {
    if (event.target.classList.contains('edit-btn') || 
        event.target.parentElement.classList.contains('edit-btn')) {
        event.preventDefault();
        const editBtn = event.target.classList.contains('edit-btn') ? 
                        event.target : 
                        event.target.parentElement;
        const id = editBtn.getAttribute('data-id');
        const ofNumber = editBtn.getAttribute('data-of_number');
        const ofQuantity = editBtn.getAttribute('data-of_quantity');
        const tailles = editBtn.getAttribute('data-tailles');
        const packNumber = editBtn.getAttribute('data-pack_number');
        const packOrderStart = editBtn.getAttribute('data-pack_order_start');
        const packOrderEnd = editBtn.getAttribute('data-pack_order_end');
        const dv = editBtn.getAttribute('data-dv');
        const g = editBtn.getAttribute('data-g');
        const m = editBtn.getAttribute('data-m');
        const dos = editBtn.getAttribute('data-dos');
        document.getElementById('recordId').value = id;
        document.getElementById('ofNumber').value = ofNumber;
        document.getElementById('ofQuantity').value = ofQuantity;
        document.getElementById('tailles').value = tailles;
        document.getElementById('packNumber').value = packNumber;
        document.getElementById('packOrderStart').value = packOrderStart;
        document.getElementById('packOrderEnd').value = packOrderEnd;
        document.getElementById('dv').value = dv;
        document.getElementById('g').value = g;
        document.getElementById('m').value = m;
        document.getElementById('dos').value = dos;
        document.getElementById('addDataModalLabel').textContent = 'Edit Data';
        document.getElementById('saveDataBtn').innerHTML = '<i class="bi bi-pencil-square me-1"></i> Update Data';
        const modal = new bootstrap.Modal(document.getElementById('addDataModal'));
        modal.show();
    }
});
document.getElementById('addDataModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('newDataForm').reset();
    document.getElementById('recordId').value = '';
    document.getElementById('addDataModalLabel').textContent = 'Add New Data';
    document.getElementById('saveDataBtn').innerHTML = '<i class="bi bi-save me-1"></i> Save Data';
    document.getElementById('newDataForm').classList.remove('was-validated');
});
document.addEventListener('DOMContentLoaded', function() {
    const filterInputs = document.querySelectorAll('input#offilter');
    const filterButtons = document.querySelectorAll('button#offilter');
    const resetButtons = document.querySelectorAll('button#resetoffilter');
    function filterTable(filterValue) {
        filterValue = filterValue.toLowerCase().trim();
        const tableRows = document.querySelectorAll('.data-table tbody tr');
        let lastDisplayedOF = null;
        let ofVisible = {};
        tableRows.forEach(row => {
            if (!row.classList.contains('of-total-row')) {
                const ofCell = row.cells[0];
                if (ofCell) {
                    const ofNumber = ofCell.textContent.trim().toLowerCase();
                    if (filterValue === '' || ofNumber.includes(filterValue)) {
                        ofVisible[ofNumber] = true;
                    }
                }
            }
        });
        tableRows.forEach(row => {
            if (row.classList.contains('of-total-row')) {
                const totalText = row.cells[0].textContent.trim();
                const ofMatch = totalText.match(/OF #(\d+)/);
                if (ofMatch) {
                    const ofNumber = ofMatch[1].toLowerCase();
                    row.style.display = ofVisible[ofNumber] ? '' : 'none';
                } else {
                    row.style.display = 'none';
                }
            } else {
                const ofCell = row.cells[0];
                if (ofCell) {
                    const ofNumber = ofCell.textContent.trim().toLowerCase();
                    row.style.display = ofVisible[ofNumber] ? '' : 'none';
                }
            }
        });
        const cards = document.querySelectorAll('.mobile-cards-container .card');
        cards.forEach(card => {
            if (card.classList.contains('of-total-card')) {
                const titleEl = card.querySelector('.card-title');
                if (titleEl) {
                    const totalText = titleEl.textContent.trim();
                    const ofMatch = totalText.match(/OF #(\d+)/);
                    if (ofMatch) {
                        const ofNumber = ofMatch[1].toLowerCase();
                        card.style.display = ofVisible[ofNumber] ? '' : 'none';
                    } else {
                        card.style.display = 'none';
                    }
                }
            } else {
                const titleEl = card.querySelector('.table-card-title');
                if (titleEl) {
                    const ofNumber = titleEl.textContent.trim().replace('OF #', '').toLowerCase();
                    card.style.display = (filterValue === '' || ofNumber.includes(filterValue)) ? '' : 'none';
                }
            }
        });
        const noResultsMsg = document.querySelector('.no-results-message');
        if (noResultsMsg) {
            noResultsMsg.remove();
        }
        let anyVisible = false;
        tableRows.forEach(row => {
            if (row.style.display !== 'none' && !row.classList.contains('of-total-row')) {
                anyVisible = true;
            }
        });
        if (!anyVisible && filterValue !== '') {
            const table = document.querySelector('.data-table tbody');
            if (table) {
                const newRow = document.createElement('tr');
                newRow.className = 'no-results-message';
                newRow.innerHTML = `<td colspan="13" class="text-center">No results found for OF JGR: "${filterValue}"</td>`;
                table.appendChild(newRow);
            }
            const mobileContainer = document.querySelector('.mobile-cards-container');
            if (mobileContainer) {
                const noResultsCard = document.createElement('div');
                noResultsCard.className = 'card mb-3 no-results-message';
                noResultsCard.innerHTML = `
                    <div class="card-body text-center">
                        <p class="mb-0">No results found for OF JGR: "${filterValue}"</p>
                    </div>
                `;
                mobileContainer.appendChild(noResultsCard);
            }
        }
    }
    function resetFilter() {
        filterInputs.forEach(input => {
            input.value = '';
        });
        filterTable('');
        const noResultsMsgs = document.querySelectorAll('.no-results-message');
        noResultsMsgs.forEach(msg => msg.remove());
    }
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const parentContainer = button.closest('.search-container');
            const input = parentContainer.querySelector('input#offilter');
            if (input) {
                const filterValue = input.value.trim();
                filterInputs.forEach(otherInput => {
                    otherInput.value = filterValue;
                });
                filterTable(filterValue);
                const offcanvas = button.closest('.offcanvas');
                if (offcanvas) {
                    const bsOffcanvas = bootstrap.Offcanvas.getInstance(offcanvas);
                    if (bsOffcanvas) {
                        bsOffcanvas.hide();
                    }
                }
            }
        });
    });
    resetButtons.forEach(button => {
        button.addEventListener('click', function() {
            resetFilter();
            const offcanvas = button.closest('.offcanvas');
            if (offcanvas) {
                const bsOffcanvas = bootstrap.Offcanvas.getInstance(offcanvas);
                if (bsOffcanvas) {
                    bsOffcanvas.hide();
                }
            }
        });
    });
    filterInputs.forEach(input => {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const filterValue = input.value.trim();
                filterInputs.forEach(otherInput => {
                    otherInput.value = filterValue;
                });
                filterTable(filterValue);
                const offcanvas = input.closest('.offcanvas');
                if (offcanvas) {
                    const bsOffcanvas = bootstrap.Offcanvas.getInstance(offcanvas);
                    if (bsOffcanvas) {
                        bsOffcanvas.hide();
                    }
                }
            }
        });
    });
});
document.addEventListener('click', function(event) {
    if (event.target.classList.contains('delete-btn') || 
        event.target.parentElement.classList.contains('delete-btn')) {
        event.preventDefault();
        const deleteBtn = event.target.classList.contains('delete-btn') ? 
                        event.target : 
                        event.target.parentElement;
        const row = deleteBtn.closest('tr');
        const card = deleteBtn.closest('.card');
        let recordId = deleteBtn.getAttribute('data-id');
        
        if (!recordId) {
            if (row) {
                const editBtn = row.querySelector('.edit-btn');
                recordId = editBtn ? editBtn.getAttribute('data-id') : null;
            } else if (card) {
                const editBtn = card.querySelector('.edit-btn');
                recordId = editBtn ? editBtn.getAttribute('data-id') : null;
            }
        }
        
        if (!recordId) {
            alert('Error: Could not identify the record to delete.');
            return;
        }
        if (confirm('Are you sure you want to delete this record? This action cannot be undone.')) {
            showLoading()
                .then(() => {
                    const formData = new FormData();
                    formData.append('_method', 'DELETE');
                    formData.append('id', recordId);
                    return fetch('ayoub.php', {
                        method: 'POST',
                        body: formData
                    });
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    hideLoading();
                    if (data.success) {
                        alert('Record deleted successfully!');
                        window.location.reload();
                    } else {
                        throw new Error(data.error || 'Unknown error occurred');
                    }
                })
                .catch(error => {
                    hideLoading();
                    console.error('Error:', error);
                    alert('An error occurred while deleting the record: ' + error.message);
                });
        }
    }
});
document.addEventListener('DOMContentLoaded', function() {
    var dynamicTab = document.getElementById('dynamic-tab');
    var staticTab = document.getElementById('static-tab');
    var dynamicMobile = document.getElementById('dynamic-mobile');

    function updateMobileCardsVisibility() {
        if (dynamicTab.classList.contains('active')) {
            dynamicMobile.style.display = '';
        } else {
            dynamicMobile.style.display = 'none';
        }
    }
    updateMobileCardsVisibility();
    dynamicTab.addEventListener('shown.bs.tab', updateMobileCardsVisibility);
    staticTab.addEventListener('shown.bs.tab', updateMobileCardsVisibility);
});
document.addEventListener('DOMContentLoaded', function() {
    const filterInputs = document.querySelectorAll('input#offilter');
    const dateFilterInputs = document.querySelectorAll('input#datefilter');
    const filterButtons = document.querySelectorAll('button#offilter');
    const resetButtons = document.querySelectorAll('button#resetoffilter');
    function filterTable(ofFilterValue, dateFilterValue) {
        ofFilterValue = ofFilterValue.toLowerCase().trim();
        const tableRows = document.querySelectorAll('.data-table tbody tr');
        let ofVisible = {};
        tableRows.forEach(row => {
            if (!row.classList.contains('of-total-row')) {
                const ofCell = row.cells[0];
                const dateCell = row.cells[11]; // Last edit cell
                if (ofCell && dateCell) {
                    const ofNumber = ofCell.textContent.trim().toLowerCase();
                    const rowDate = dateCell.textContent.trim();
                    const passesOfFilter = ofFilterValue === '' || ofNumber.includes(ofFilterValue);
                    const passesDateFilter = dateFilterValue === '' || formatDateForComparison(rowDate) === dateFilterValue;
                    
                    if (passesOfFilter && passesDateFilter) {
                        ofVisible[ofNumber] = true;
                    }
                }
            }
        });
        tableRows.forEach(row => {
            if (row.classList.contains('of-total-row')) {
                const totalText = row.cells[0].textContent.trim();
                const ofMatch = totalText.match(/OF #(\d+)/);
                if (ofMatch) {
                    const ofNumber = ofMatch[1].toLowerCase();
                    row.style.display = ofVisible[ofNumber] ? '' : 'none';
                } else {
                    row.style.display = 'none';
                }
            } else {
                const ofCell = row.cells[0];
                if (ofCell) {
                    const ofNumber = ofCell.textContent.trim().toLowerCase();
                    row.style.display = ofVisible[ofNumber] ? '' : 'none';
                }
            }
        });
        const cards = document.querySelectorAll('.mobile-cards-container .card');
        cards.forEach(card => {
            if (card.classList.contains('of-total-card')) {
                const titleEl = card.querySelector('.card-title');
                if (titleEl) {
                    const totalText = titleEl.textContent.trim();
                    const ofMatch = totalText.match(/OF #(\d+)/);
                    if (ofMatch) {
                        const ofNumber = ofMatch[1].toLowerCase();
                        card.style.display = ofVisible[ofNumber] ? '' : 'none';
                    } else {
                        card.style.display = 'none';
                    }
                }
            } else {
                const titleEl = card.querySelector('.fw-bold.fs-5');
                const dateEl = card.querySelector('.card-body div:last-child div:last-child');
                
                if (titleEl && dateEl) {
                    const ofNumber = titleEl.textContent.trim().replace('OF #', '').toLowerCase();
                    const cardDate = dateEl.textContent.trim();
                    const passesOfFilter = ofFilterValue === '' || ofNumber.includes(ofFilterValue);
                    const passesDateFilter = dateFilterValue === '' || formatDateForComparison(cardDate) === dateFilterValue;
                    
                    card.style.display = (passesOfFilter && passesDateFilter) ? '' : 'none';
                }
            }
        });
        const noResultsMsg = document.querySelector('.no-results-message');
        if (noResultsMsg) {
            noResultsMsg.remove();
        }
        
        let anyVisible = false;
        tableRows.forEach(row => {
            if (row.style.display !== 'none' && !row.classList.contains('of-total-row')) {
                anyVisible = true;
            }
        });
        if (!anyVisible && (ofFilterValue !== '' || dateFilterValue !== '')) {
            let filterText = '';
            if (ofFilterValue !== '' && dateFilterValue !== '') {
                filterText = `OF JGR: "${ofFilterValue}" and Date: "${formatDateDisplay(dateFilterValue)}"`;
            } else if (ofFilterValue !== '') {
                filterText = `OF JGR: "${ofFilterValue}"`;
            } else if (dateFilterValue !== '') {
                filterText = `Date: "${formatDateDisplay(dateFilterValue)}"`;
            }
            const table = document.querySelector('.data-table tbody');
            if (table) {
                const newRow = document.createElement('tr');
                newRow.className = 'no-results-message';
                newRow.innerHTML = `<td colspan="13" class="text-center">No results found for ${filterText}</td>`;
                table.appendChild(newRow);
            }
            const mobileContainer = document.querySelector('.mobile-cards-container');
            if (mobileContainer) {
                const noResultsCard = document.createElement('div');
                noResultsCard.className = 'card mb-3 no-results-message';
                noResultsCard.innerHTML = `
                    <div class="card-body text-center">
                        <p class="mb-0">No results found for ${filterText}</p>
                    </div>
                `;
                mobileContainer.appendChild(noResultsCard);
            }
        }
    }
    function formatDateForComparison(dateStr) {
        if (!dateStr) return '';
        const parts = dateStr.split(' ')[0].split('/');
        if (parts.length !== 3) return '';
        return `${parts[2]}-${parts[1]}-${parts[0]}`;
    }
    function formatDateDisplay(dateStr) {
        if (!dateStr) return '';
        const parts = dateStr.split('-');
        if (parts.length !== 3) return dateStr;
        return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }
    function resetFilter() {
        filterInputs.forEach(input => {
            input.value = '';
        });
        dateFilterInputs.forEach(input => {
            input.value = '';
        });
        filterTable('', '');
        const noResultsMsgs = document.querySelectorAll('.no-results-message');
        noResultsMsgs.forEach(msg => msg.remove());
    }
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const parentContainer = button.closest('.search-container');
            const ofInput = parentContainer.querySelector('input#offilter');
            const dateInput = document.querySelector('input#datefilter');
            if (ofInput) {
                const ofFilterValue = ofInput.value.trim();
                const dateFilterValue = dateInput ? dateInput.value.trim() : '';
                
                filterInputs.forEach(otherInput => {
                    otherInput.value = ofFilterValue;
                });
                dateFilterInputs.forEach(otherInput => {
                    otherInput.value = dateFilterValue;
                });
                filterTable(ofFilterValue, dateFilterValue);
                const offcanvas = button.closest('.offcanvas');
                if (offcanvas) {
                    const bsOffcanvas = bootstrap.Offcanvas.getInstance(offcanvas);
                    if (bsOffcanvas) {
                        bsOffcanvas.hide();
                    }
                }
            }
        });
    });
    resetButtons.forEach(button => {
        button.addEventListener('click', function() {
            resetFilter();
            const offcanvas = button.closest('.offcanvas');
            if (offcanvas) {
                const bsOffcanvas = bootstrap.Offcanvas.getInstance(offcanvas);
                if (bsOffcanvas) {
                    bsOffcanvas.hide();
                }
            }
        });
    });
    filterInputs.forEach(input => {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const ofFilterValue = input.value.trim();
                const dateFilterValue = document.querySelector('input#datefilter').value.trim();
                filterInputs.forEach(otherInput => {
                    otherInput.value = ofFilterValue;
                });
                filterTable(ofFilterValue, dateFilterValue);
                const offcanvas = input.closest('.offcanvas');
                if (offcanvas) {
                    const bsOffcanvas = bootstrap.Offcanvas.getInstance(offcanvas);
                    if (bsOffcanvas) {
                        bsOffcanvas.hide();
                    }
                }
            }
        });
    });
    dateFilterInputs.forEach(input => {
        input.addEventListener('change', function() {
            const dateFilterValue = input.value.trim();
            const ofFilterValue = document.querySelector('input#offilter').value.trim();
            
            dateFilterInputs.forEach(otherInput => {
                otherInput.value = dateFilterValue;
            });
            filterTable(ofFilterValue, dateFilterValue);
        });
    });
});
