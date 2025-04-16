
        // Base API URL - will be set to the server's address
        let API_BASE_URL = '';
        
        // DOM Elements
        const barcodesContainer = document.getElementById('barcodes-container');
        const loadingIndicator = document.getElementById('loading');
        const viewTitle = document.getElementById('view-title');
        const currentDateElement = document.getElementById('current-date');
        const createBarcodeBtn = document.getElementById('create-barcode');
        const barcodeModal = document.getElementById('barcode-modal');
        const modalTitle = document.getElementById('modal-title');
        const barcodeForm = document.getElementById('barcode-form');
        const barcodeIdInput = document.getElementById('barcode-id');
        const barcodeNumberInput = document.getElementById('barcode-number');
        const barcodeTypeInput = document.getElementById('barcode-type');
        const barcodeDescriptionInput = document.getElementById('barcode-description');
        const cancelBarcodeBtn = document.getElementById('cancel-barcode');
        const closeModalBtn = document.querySelector('.close');
        const networkStatus = document.getElementById('network-status');
        const sidebarItems = document.querySelectorAll('.sidebar-item');
        
        // Current view
        let currentView = 'all';
        
        // Format date for display
        function formatDate(dateString) {
            const date = new Date(dateString);
            const options = { year: 'numeric', month: 'long', day: 'numeric' };
            return date.toLocaleDateString('en-US', options);
        }
        
        // Update current date display
        function updateCurrentDate() {
            const today = new Date();
            currentDateElement.textContent = `${formatDate(today)}`;
        }
        
        // Load barcodes from API
        async function loadBarcodes() {
            showLoading();
            try {
                const response = await fetch(`${API_BASE_URL}/api/barcodes`);
                if (!response.ok) {
                    throw new Error('Failed to load barcodes');
                }
                
                const barcodes = await response.json();
                renderBarcodes(barcodes);
                hideLoading();
                updateNetworkStatus(true);
            } catch (error) {
                console.error('Error loading barcodes:', error);
                hideLoading();
                updateNetworkStatus(false);
                
                // Try to load from IndexedDB if network request fails
                loadBarcodesFromIndexedDB();
            }
        }
        
        // Render barcodes to the UI
        function renderBarcodes(barcodes) {
            barcodesContainer.innerHTML = '';
            
            if (barcodes.length === 0) {
                barcodesContainer.innerHTML = '<p>No barcodes found.</p>';
                return;
            }
            
            // Filter barcodes based on current view
            let filteredBarcodes = barcodes;
            if (currentView === 'today') {
                const today = new Date().toISOString().split('T')[0];
                filteredBarcodes = barcodes.filter(barcode => 
                    barcode.created_date.startsWith(today));
            } else if (currentView === 'manufactured') {
                filteredBarcodes = barcodes.filter(barcode => 
                    barcode.type === 'manufactured');
            }
            
            filteredBarcodes.forEach(barcode => {
                const card = document.createElement('div');
                card.className = 'barcode-card';
                card.innerHTML = `
                    <div class="barcode-header">
                        <div class="barcode-title">Barcode #${barcode.barcode_number}</div>
                        <div class="barcode-actions">
                            <button class="action-btn edit-btn" data-id="${barcode.id}">✏️</button>
                            <button class="action-btn delete-btn" data-id="${barcode.id}">🗑️</button>
                        </div>
                    </div>
                    <div class="barcode-date">Created on ${formatDate(barcode.created_date)}</div>
                    ${barcode.type ? `<div class="barcode-type">Type: ${barcode.type}</div>` : ''}
                    ${barcode.description ? `<div class="barcode-description">${barcode.description}</div>` : ''}
                `;
                barcodesContainer.appendChild(card);
            });
            
            // Add event listeners to edit and delete buttons
            document.querySelectorAll('.edit-btn').forEach(btn => {
                btn.addEventListener('click', handleEditBarcode);
            });
            
            document.querySelectorAll('.delete-btn').forEach(btn => {
                btn.addEventListener('click', handleDeleteBarcode);
            });
        }
        
        // Show loading indicator
        function showLoading() {
            loadingIndicator.style.display = 'block';
        }
        
        // Hide loading indicator
        function hideLoading() {
            loadingIndicator.style.display = 'none';
        }
        
        // Open modal for creating a new barcode
        function openCreateModal() {
            modalTitle.textContent = 'Create New Barcode';
            barcodeIdInput.value = '';
            barcodeForm.reset();
            barcodeModal.style.display = 'block';
        }
        
        // Open modal for editing a barcode
        async function handleEditBarcode(event) {
            const barcodeId = event.target.dataset.id;
            modalTitle.textContent = 'Edit Barcode';
            
            try {
                const response = await fetch(`${API_BASE_URL}/api/barcodes/${barcodeId}`);
                if (!response.ok) {
                    throw new Error('Failed to fetch barcode data');
                }
                
                const barcode = await response.json();
                barcodeIdInput.value = barcode.id;
                barcodeNumberInput.value = barcode.barcode_number;
                barcodeTypeInput.value = barcode.type || '';
                barcodeDescriptionInput.value = barcode.description || '';
                
                barcodeModal.style.display = 'block';
            } catch (error) {
                console.error('Error fetching barcode:', error);
                alert('Failed to load barcode data. Please try again.');
            }
        }
        
        // Handle barcode deletion
        async function handleDeleteBarcode(event) {
            const barcodeId = event.target.dataset.id;
            
            if (confirm('Are you sure you want to delete this barcode?')) {
                try {
                    const response = await fetch(`${API_BASE_URL}/api/barcodes/${barcodeId}`, {
                        method: 'DELETE'
                    });
                    
                    if (!response.ok) {
                        throw new Error('Failed to delete barcode');
                    }
                    
                    // Reload barcodes after deletion
                    loadBarcodes();
                } catch (error) {
                    console.error('Error deleting barcode:', error);
                    alert('Failed to delete barcode. Please try again.');
                }
            }
        }
        
        // Close the modal
        function closeModal() {
            barcodeModal.style.display = 'none';
        }
        
        // Handle form submission
        async function handleFormSubmit(event) {
            event.preventDefault();
            
            const barcodeId = barcodeIdInput.value;
            const barcodeData = {
                barcode_number: barcodeNumberInput.value,
                type: barcodeTypeInput.value,
                description: barcodeDescriptionInput.value
            };
            
            try {
                let response;
                
                if (barcodeId) {
                    // Update existing barcode
                    response = await fetch(`${API_BASE_URL}/api/barcodes/${barcodeId}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(barcodeData)
                    });
                } else {
                    // Create new barcode
                    response = await fetch(`${API_BASE_URL}/api/barcodes`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(barcodeData)
                    });
                }
                
                if (!response.ok) {
                    throw new Error('Failed to save barcode');
                }
                
                // Close modal and reload barcodes
                closeModal();
                loadBarcodes();
            } catch (error) {
                console.error('Error saving barcode:', error);
                alert('Failed to save barcode. Please try again.');
            }
        }
        
        // IndexedDB Setup
        let db;
        
        function initializeIndexedDB() {
            const request = indexedDB.open('BarcodeHubDB', 1);
            
            request.onerror = function(event) {
                console.error('IndexedDB error:', event.target.error);
            };
            
            request.onupgradeneeded = function(event) {
                db = event.target.result;
                
                // Create object store for barcodes
                if (!db.objectStoreNames.contains('barcodes')) {
                    const store = db.createObjectStore('barcodes', { keyPath: 'id', autoIncrement: true });
                    store.createIndex('barcode_number', 'barcode_number', { unique: false });
                    store.createIndex('created_date', 'created_date', { unique: false });
                    store.createIndex('type', 'type', { unique: false });
                }
            };
            
            request.onsuccess = function(event) {
                db = event.target.result;
                console.log('IndexedDB initialized');
            };
        }
        
        // Save barcodes to IndexedDB
        function saveBarcodeToIndexedDB(barcode) {
            if (!db) return;
            
            const transaction = db.transaction(['barcodes'], 'readwrite');
            const store = transaction.objectStore('barcodes');
            
            store.put(barcode);
            
            transaction.oncomplete = function() {
                console.log('Barcode saved to IndexedDB');
            };
            
            transaction.onerror = function(event) {
                console.error('Error saving to IndexedDB:', event.target.error);
            };
        }
        
        // Load barcodes from IndexedDB
        function loadBarcodesFromIndexedDB() {
            if (!db) return;
            
            const transaction = db.transaction(['barcodes'], 'readonly');
            const store = transaction.objectStore('barcodes');
            const request = store.getAll();
            
            request.onsuccess = function(event) {
                const barcodes = event.target.result;
                renderBarcodes(barcodes);
            };
            
            request.onerror = function(event) {
                console.error('Error loading from IndexedDB:', event.target.error);
                barcodesContainer.innerHTML = '<p>Unable to load barcodes. Please check your connection.</p>';
            };
        }
        
        // Update network status indicator
        function updateNetworkStatus(isOnline) {
            if (isOnline) {
                networkStatus.className = 'network-status online';
                networkStatus.textContent = 'Connected to server';
                setTimeout(() => {
                    networkStatus.style.display = 'none';
                }, 3000);
            } else {
                networkStatus.className = 'network-status offline';
                networkStatus.textContent = 'Working offline';
                networkStatus.style.display = 'block';
            }
        }
        
        // Update view when sidebar item is clicked
        function handleSidebarClick(event) {
            event.preventDefault();
            
            const clickedItem = event.currentTarget;
            const view = clickedItem.dataset.view;
            
            // Update active class
            sidebarItems.forEach(item => {
                item.classList.remove('active');
            });
            clickedItem.classList.add('active');
            
            // Update current view
            currentView = view;
            
            // Update view title
            switch (view) {
                case 'dashboard':
                    viewTitle.textContent = 'Barcodes Overview';
                    break;
                case 'today':
                    viewTitle.textContent = 'Scanned Today';
                    break;
                case 'manufactured':
                    viewTitle.textContent = 'Manufactured';
                    break;
                case 'history':
                    viewTitle.textContent = 'History';
                    break;
                case 'export':
                    viewTitle.textContent = 'Export';
                    break;
            }
            
            // Reload barcodes with the new filter
            loadBarcodes();
        }
        
        // Detect server address
        function detectServerAddress() {
            // Get the current URL
            const currentUrl = window.location.origin;
            API_BASE_URL = currentUrl;
            console.log('Server detected at:', API_BASE_URL);
        }
        
        // Initialize the application
        function init() {
            // Detect server
            detectServerAddress();
            
            // Update date
            updateCurrentDate();
            
            // Initialize IndexedDB
            initializeIndexedDB();
            
            // Load barcodes
            loadBarcodes();
            
            // Event listeners
            createBarcodeBtn.addEventListener('click', openCreateModal);
            closeModalBtn.addEventListener('click', closeModal);
            cancelBarcodeBtn.addEventListener('click', closeModal);
            barcodeForm.addEventListener('submit', handleFormSubmit);
            
            // Sidebar navigation
            sidebarItems.forEach(item => {
                item.addEventListener('click', handleSidebarClick);
            });
            
            // Network status monitoring
            window.addEventListener('online', () => {
                updateNetworkStatus(true);
                loadBarcodes(); // Reload when connection is restored
            });
            
            window.addEventListener('offline', () => {
                updateNetworkStatus(false);
            });
        }
        
        // Start the application when the page loads
        document.addEventListener('DOMContentLoaded', init);

// Get the elements
const lostBarcodeCheckbox = document.getElementById('lost-barcode');
const lostBarcodeNumberInput = document.getElementById('lost-barcode-number');
const generate2pcsCheckbox = document.getElementById('generate-costume-2pcs');
const generate3pcsCheckbox = document.getElementById('generate-costume-3pcs');
const generatePdfOnlyCheckbox = document.getElementById('generate-pdf-only');
const rangeFromInput = document.getElementById('range-from');
const rangeToInput = document.getElementById('range-to');

// Function to handle lost barcode checkbox changes
function handleLostBarcodeChange() {
    if (lostBarcodeCheckbox.checked) {
        // Enable the lost barcode number input
        lostBarcodeNumberInput.disabled = false;
        
        // Disable and uncheck the generate options
        generate2pcsCheckbox.checked = false;
        generate2pcsCheckbox.disabled = true;
        
        generate3pcsCheckbox.checked = false;
        generate3pcsCheckbox.disabled = true;
        
        // Ensure PDF only is checked
        generatePdfOnlyCheckbox.checked = true;
        generatePdfOnlyCheckbox.disabled = false;
        
        // Disable the range inputs
        rangeFromInput.disabled = true;
        rangeToInput.disabled = true;
    } else {
        // Disable the lost barcode number input
        lostBarcodeNumberInput.disabled = true;
        lostBarcodeNumberInput.value = '';
        
        // Enable the generate options
        generate2pcsCheckbox.disabled = false;
        generate3pcsCheckbox.disabled = false;
        
        // PDF only can be toggled
        generatePdfOnlyCheckbox.disabled = false;
        
        // Enable the range inputs
        rangeFromInput.disabled = false;
        rangeToInput.disabled = false;
    }
}

// Function to handle the generate checkboxes changes
function handleGenerateCheckboxesChange() {
    if (generate2pcsCheckbox.checked || generate3pcsCheckbox.checked) {
        // Disable and uncheck the lost barcode option
        lostBarcodeCheckbox.checked = false;
        lostBarcodeCheckbox.disabled = true;
        lostBarcodeNumberInput.disabled = true;
        lostBarcodeNumberInput.value = '';
        
        // Enable the range inputs in case they were disabled
        rangeFromInput.disabled = false;
        rangeToInput.disabled = false;
    } else {
        // Enable the lost barcode option
        lostBarcodeCheckbox.disabled = false;
    }
}

// Add event listeners to the checkboxes
lostBarcodeCheckbox.addEventListener('change', handleLostBarcodeChange);
generate2pcsCheckbox.addEventListener('change', handleGenerateCheckboxesChange);
generate3pcsCheckbox.addEventListener('change', handleGenerateCheckboxesChange);

// Initialize the form state
window.addEventListener('DOMContentLoaded', function() {
    // Disable the lost barcode number input by default
    lostBarcodeNumberInput.disabled = true;
    
    // Call the handler to set initial state based on checkbox values
    handleLostBarcodeChange();
    handleGenerateCheckboxesChange();
});
