// =========================================
// CUSTOMERS.PHP: Filter Customers
// =========================================
function filterCustomers() {
    let input = document.getElementById('searchInput').value.toLowerCase();
    let cards = document.getElementsByClassName('client-card');

    for (let i = 0; i < cards.length; i++) {
        let name = cards[i].getElementsByClassName('client-name')[0].innerText.toLowerCase();
        let phone = cards[i].getElementsByClassName('client-phone')[0].innerText.toLowerCase();
        
        if (name.includes(input) || phone.includes(input)) {
            cards[i].style.display = "flex";
        } else {
            cards[i].style.display = "none";
        }
    }
}

// =========================================
// DASHBOARD SALES CHART (Live Monthly Data)
// =========================================
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('salesChart');
    if (ctx) {
        // Safely pull data from the HTML canvas dataset
        let chartLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        let chartData = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];

        try {
            if (ctx.dataset.labels) chartLabels = JSON.parse(ctx.dataset.labels);
            if (ctx.dataset.values) chartData = JSON.parse(ctx.dataset.values);
        } catch (e) {
            console.error("Error parsing chart data:", e);
        }

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'Revenue (₱)',
                    data: chartData,
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#ef4444',
                    pointBorderColor: '#111827',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '₱' + context.parsed.y.toLocaleString(undefined, {minimumFractionDigits: 2});
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        suggestedMax: 100000, // Forces the chart to scale to at least 100k!
                        grid: { color: '#374151', drawBorder: false },
                        ticks: { 
                            color: '#9ca3af',
                            callback: function(value) {
                                // Formats the side numbers with commas (e.g., ₱20,000)
                                return '₱' + value.toLocaleString();
                            }
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#9ca3af' }
                    }
                }
            }
        });
    }
});

// =========================================
// REPORTS.PHP: Revenue vs Target Chart
// =========================================
document.addEventListener('DOMContentLoaded', function() {
    const reportCtx = document.getElementById('reportChart');
    if (reportCtx) {
        const actualRevenue = parseFloat(reportCtx.dataset.revenue);
        const targetRevenue = actualRevenue * 1.2; 
        
        new Chart(reportCtx, {
            type: 'bar',
            data: {
                labels: ['Current Month Performance'],
                datasets: [
                    { label: 'Actual Revenue (PHP)', data: [actualRevenue], backgroundColor: '#10b981', borderRadius: 6 },
                    { label: 'Projected Target', data: [targetRevenue], backgroundColor: '#374151', borderRadius: 6 }
                ]
            },
            options: {
                scales: {
                    y: { grid: { color: '#374151' }, ticks: { color: '#9ca3af' } },
                    x: { grid: { display: false }, ticks: { color: '#9ca3af' } }
                },
                plugins: { legend: { labels: { color: '#f3f4f6' } } }
            }
        });
    }
});

// =========================================
// SALES.PHP: POS Terminal Cart Engine
// =========================================
const POSTerminal = {
    cart: {},

    addToCart: function(id, name, price, category) {
        if (this.cart[id]) {
            this.cart[id].qty++;
        } else {
            this.cart[id] = { id: id, name: name, price: price, category: category, qty: 1 };
        }
        this.renderCart();
    },

    renderCart: function() {
        const cartDiv = document.getElementById('cart-items');
        if (!cartDiv) return; 

        let total = 0;
        
        if (Object.keys(this.cart).length === 0) {
            cartDiv.innerHTML = '<p style="color:#9ca3af; text-align:center; margin-top:50px;">Cart is empty.<br>Select items to begin.</p>';
            document.getElementById('cart-total-display').innerText = "0.00";
            return;
        }

        let html = '';
        for (let id in this.cart) {
            let item = this.cart[id];
            let itemTotal = item.qty * item.price;
            total += itemTotal;
            
            html += `
            <div class="cart-row">
                <div style="flex:2;">${item.name}</div>
                <div style="flex:1; text-align:center;">x${item.qty}</div>
                <div style="flex:1; text-align:right;">₱${itemTotal.toFixed(2)}</div>
            </div>`;
        }
        
        cartDiv.innerHTML = html;
        document.getElementById('cart-total-display').innerText = total.toFixed(2);
    },

    processCheckout: function() {
        if (Object.keys(this.cart).length === 0) {
            alert("Cannot complete sale: Cart is empty!");
            return;
        }
        
        const receiptBody = document.getElementById('receipt-body');
        if(!receiptBody) return; 

        let receiptHTML = '';
        let grandTotal = 0;
        
        for (let id in this.cart) {
            let item = this.cart[id];
            let itemTotal = item.qty * item.price;
            grandTotal += itemTotal;
            
            receiptHTML += `
            <tr>
             <td>${item.name}</td>
             <td style="text-align:center;">${item.qty}</td>
             <td style="text-align:right;">${itemTotal.toFixed(2)}</td>
             </tr>
         `;
        }
        
        receiptBody.innerHTML = receiptHTML;
        document.getElementById('receipt-total-display').innerText = grandTotal.toFixed(2);
        document.getElementById('receipt-date').innerText = new Date().toLocaleString();
        
        window.print();
        
        const cartArray = Object.values(this.cart);
        document.getElementById('cart-data-input').value = JSON.stringify(cartArray);
        document.getElementById('checkout-form').submit();
    }
};

// =========================================
// SHOP.PHP: Public Storefront Engine
// =========================================
const Storefront = {
    cart: {},

    addToCart: function(id, name, price) {
        if (this.cart[id]) { 
            this.cart[id].qty++; 
        } else { 
            this.cart[id] = { id: id, name: name, price: price, qty: 1 }; 
        }
        
        alert("✅ Added " + name + " to your cart!"); // User feedback!
        this.renderCart();
    },

    changeQty: function(id, delta) {
        if (this.cart[id]) {
            this.cart[id].qty += delta;
            if (this.cart[id].qty <= 0) {
                delete this.cart[id];
            }
            this.renderCart();
        }
    },

    renderCart: function() {
        const cartDiv = document.getElementById('cart-items');
        const checkoutDetails = document.getElementById('checkout-details');
        const cartReceipt = document.getElementById('cart-receipt');
        const subtotalDisplay = document.getElementById('cart-subtotal-display');
        
        if (!checkoutDetails || !cartReceipt) return;

        let subtotal = 0;
        
        // Empty Cart State
        if (Object.keys(this.cart).length === 0) {
            cartDiv.innerHTML = '<p style="color:#9ca3af; text-align:center; margin-top:50px;">Your cart is empty.</p>';
            subtotalDisplay.innerText = "0.00";
            document.getElementById('cart-total-display').innerText = "0.00";
            cartReceipt.style.display = 'none';
            checkoutDetails.style.display = 'none';
            return;
        }

        // Render Cart Items
        let html = '';
        for (let id in this.cart) {
            let item = this.cart[id];
            let itemTotal = item.qty * item.price;
            subtotal += itemTotal;
            
            html += `
            <div style="background: #111827; border: 1px solid #374151; padding: 12px; border-radius: 6px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center;">
                <div style="flex-grow: 1; padding-right: 10px;">
                    <div style="color: white; font-weight: bold; font-size: 14px; margin-bottom: 5px; line-height: 1.2;">${item.name}</div>
                    <div style="color: #ef4444; font-weight: bold; font-size: 13px;">₱${item.price.toFixed(2)}</div>
                </div>
                <div style="display: flex; align-items: center; gap: 8px; background: #1f2937; padding: 4px; border-radius: 6px;">
                    <button type="button" onclick="Storefront.changeQty(${id}, -1)" style="background: #374151; color: white; border: none; width: 25px; height: 25px; border-radius: 4px; cursor: pointer; font-weight: bold;">-</button>
                    <span style="color: white; font-size: 14px; width: 20px; text-align: center; font-weight: bold;">${item.qty}</span>
                    <button type="button" onclick="Storefront.changeQty(${id}, 1)" style="background: #374151; color: white; border: none; width: 25px; height: 25px; border-radius: 4px; cursor: pointer; font-weight: bold;">+</button>
                </div>
            </div>`;
        }
        
        cartDiv.innerHTML = html;
        subtotalDisplay.innerText = subtotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        
        cartReceipt.style.display = 'block';
        checkoutDetails.style.display = 'block';

        // Re-calculate grand total based on the selected delivery option!
        if (typeof updateShippingCost === 'function') {
            updateShippingCost();
        }
    },

    processOnlineOrder: function() {
        const cartArray = Object.values(this.cart);
        document.getElementById('cart-data-input').value = JSON.stringify(cartArray);
        document.getElementById('checkout-form').submit();
    },

    updatePriceLabel: function() {
        const label = document.getElementById('priceLabel');
        const range = document.getElementById('priceRange');
        if(label && range) label.innerText = range.value;
    },

    toggleAllCategories: function() {
        const allCheckbox = document.getElementById('filter-all');
        const categoryCheckboxes = document.querySelectorAll('.filter-category');
        if (allCheckbox && allCheckbox.checked) {
            categoryCheckboxes.forEach(cb => cb.checked = false);
        }
    },

    filterProducts: function() {
        const searchInput = document.getElementById('searchInput');
        if(!searchInput) return;

        const searchQuery = searchInput.value.toLowerCase();
        const maxPrice = parseFloat(document.getElementById('priceRange').value);
        const allCheckbox = document.getElementById('filter-all');
        const categoryCheckboxes = document.querySelectorAll('.filter-category:checked');
        
        if (categoryCheckboxes.length > 0) {
            allCheckbox.checked = false;
        } else {
            allCheckbox.checked = true;
        }

        const selectedCategories = Array.from(categoryCheckboxes).map(cb => cb.value.toLowerCase());
        const products = document.querySelectorAll('.product-card');

        products.forEach(card => {
            const title = card.querySelector('.product-title').innerText.toLowerCase();
            const category = card.querySelector('.badge-category').innerText.toLowerCase();
            const priceText = card.querySelector('.product-price').innerText.replace('₱', '').replace(/,/g, '');
            const price = parseFloat(priceText);

            const matchesSearch = title.includes(searchQuery);
            const matchesPrice = price <= maxPrice;
            const matchesCategory = allCheckbox.checked || selectedCategories.includes(category);

            if (matchesSearch && matchesPrice && matchesCategory) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    },

    toggleWishlist: function(button, event) {
        event.stopPropagation(); 
        button.classList.toggle('active');
        if(button.classList.contains('active')) {
            button.style.transform = 'scale(1.2)';
            setTimeout(() => button.style.transform = 'scale(1)', 150);
        }
    },
    toggleWishlistView: function(event) {
        if(event) event.preventDefault();

        const products = document.querySelectorAll('.product-card');
        
        // Toggle the mode on or off
        this.wishlistMode = !this.wishlistMode;

        const wishlistNav = document.getElementById('nav-wishlist');

        if (this.wishlistMode) {
            // ON: Hide everything except the cards with an active heart
            products.forEach(card => {
                const heart = card.querySelector('.btn-wishlist');
                if (heart && heart.classList.contains('active')) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Update the Navbar text to show it's active
            if(wishlistNav) {
                wishlistNav.style.color = '#ef4444';
                wishlistNav.innerText = 'Close Wishlist ❌';
            }
        } else {
            // OFF: Reset the text and run the normal filter to show items again
            if(wishlistNav) {
                wishlistNav.style.color = '#f3f4f6';
                wishlistNav.innerText = 'Wishlist ❤️';
            }
            this.filterProducts(); 
        }
    }
}


// =========================================
// EDIT PRODUCT FORM LOGIC
// =========================================
function toggleStockField() {
    var categorySelect = document.getElementById('categorySelect');
    var stockGroup = document.getElementById('stockGroup');
    var serviceNotice = document.getElementById('serviceNotice');

    if (!categorySelect || !stockGroup || !serviceNotice) return;

    if (categorySelect.value === 'Service' || categorySelect.value === 'Services') {
        stockGroup.style.display = 'none';
        serviceNotice.style.display = 'block';
    } else {
        stockGroup.style.display = 'block';
        serviceNotice.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    toggleStockField();
});


// =========================================
// ONLINE STORE DELIVERY LOGIC (shop.php)
// =========================================
function updateShippingCost() {
    var methodInput = document.querySelector('input[name="delivery_method"]:checked');
    if (!methodInput) return;
    
    var method = methodInput.value;
    
    // UI Highlighting
    let pickupLabel = document.getElementById('label-pickup');
    let deliveryLabel = document.getElementById('label-delivery');
    
    if(pickupLabel) pickupLabel.style.borderColor = (method === 'pickup') ? '#ef4444' : '#374151';
    if(deliveryLabel) deliveryLabel.style.borderColor = (method === 'delivery') ? '#ef4444' : '#374151';
    
    // SHOW/HIDE ADDRESS FIELD
    var addressGroup = document.getElementById('address-group');
    if (addressGroup) {
        addressGroup.style.display = (method === 'delivery') ? 'block' : 'none';
    }

    // UPDATE GRAND TOTAL
    let subtotal = 0;
    
    // Calculate subtotal
    if (typeof Storefront !== 'undefined' && Storefront.cart) {
        for (let id in Storefront.cart) {
            let item = Storefront.cart[id];
            subtotal += (item.price * item.qty);
        }
    }

    // Calculate shipping
    let shippingCost = (method === 'delivery') ? 150.00 : 0.00;
    
    let shippingLabelElem = document.getElementById('shipping-label');
    if(shippingLabelElem) shippingLabelElem.innerText = (method === 'delivery') ? 'Delivery:' : 'Store Pickup:';

    let grandTotal = subtotal + shippingCost;

    // Push the numbers to the screen
    let subtotalDisplay = document.getElementById('cart-subtotal-display');
    let shippingDisplay = document.getElementById('cart-shipping-display');
    let totalDisplay = document.getElementById('cart-total-display');

    if(subtotalDisplay) subtotalDisplay.innerText = subtotal.toFixed(2);
    if(shippingDisplay) shippingDisplay.innerText = shippingCost.toFixed(2);
    if(totalDisplay) totalDisplay.innerText = grandTotal.toFixed(2);
}

function validateAndCheckout() {
    var methodInput = document.querySelector('input[name="delivery_method"]:checked');
    if (!methodInput) {
        alert("⚠️ Please select whether you want Store Pickup or Delivery!");
        return;
    }

    // Check address if delivery is selected
    if (methodInput.value === 'delivery') {
        // If guest checkout is active
        var streetInput = document.querySelector('input[name="street_name"]');
        if (streetInput && streetInput.value.trim() === '') {
            alert("⚠️ Please fill out all required address fields.");
            return;
        }
        // If logged-in user checkout is active
        var savedAddressSelect = document.querySelector('select[name="saved_address"]');
        if(savedAddressSelect && (!savedAddressSelect.value || savedAddressSelect.value.trim() === '')) {
            alert("⚠️ Please select a delivery address.");
            return;
        }
    }
    
    // Process the order
    Storefront.processOnlineOrder();
}

// =========================================
// SERVICE BOOKING MODAL LOGIC (booking.php)
// =========================================
function openBookingModal(serviceName) {
    var serviceInput = document.getElementById('modalServiceType');
    var modalOverlay = document.getElementById('bookingModal');
    
    if (serviceInput && modalOverlay) {
        serviceInput.value = serviceName;
        modalOverlay.style.display = 'flex';
    }
}

function closeBookingModal() {
    var modalOverlay = document.getElementById('bookingModal');
    if (modalOverlay) {
        modalOverlay.style.display = 'none';
    }
}

// Lock past dates in the booking calendar
document.addEventListener('DOMContentLoaded', function() {
    var dateInput = document.getElementById('bookingDate');
    if(dateInput) {
        var today = new Date().toISOString().split('T')[0];
        dateInput.setAttribute('min', today);
    }
});

// =========================================
// GARAGE MODAL LOGIC (account.php)
// =========================================
function openAddBikeModal() {
    var modal = document.getElementById('addBikeModal');
    if (modal) modal.style.display = 'flex';
}

function closeAddBikeModal() {
    var modal = document.getElementById('addBikeModal');
    if (modal) modal.style.display = 'none';
}

// =========================================
// EDIT PROFILE MODAL LOGIC (account.php)
// =========================================
function openEditProfileModal() {
    var modal = document.getElementById('editProfileModal');
    if (modal) modal.style.display = 'flex';
}

function closeEditProfileModal() {
    var modal = document.getElementById('editProfileModal');
    if (modal) modal.style.display = 'none';
}

// =========================================
// ADDRESS BOOK MODAL LOGIC (account.php)
// =========================================
function openAddAddressModal() {
    var modal = document.getElementById('addAddressModal');
    if (modal) modal.style.display = 'flex';
}

function closeAddAddressModal() {
    var modal = document.getElementById('addAddressModal');
    if (modal) modal.style.display = 'none';
}

// =========================================
// PRODUCT RATING MODAL LOGIC (account.php)
// =========================================
function openRateModal(productName) {
    var display = document.getElementById('rateProductNameDisplay');
    var input = document.getElementById('rateProductInput');
    var modal = document.getElementById('rateModal');
    
    if (display && input && modal) {
        display.innerText = productName;
        input.value = productName;
        modal.style.display = 'flex';
    }
}

function closeRateModal() {
    var modal = document.getElementById('rateModal');
    if (modal) modal.style.display = 'none';
}

// =========================================
// SERVICE RATING MODALS
// =========================================
function openServiceRateModal(serviceName, bookingId) {
    const modal = document.getElementById('rateServiceModal');
    if (modal) {
        modal.style.display = 'flex';
        document.getElementById('rateServiceNameDisplay').innerText = serviceName;
        document.getElementById('rateServiceInput').value = serviceName;
        document.getElementById('rateBookingIdInput').value = bookingId;
    }
}

function closeServiceRateModal() {
    const modal = document.getElementById('rateServiceModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// =========================================
// ACCOUNT DASHBOARD MODALS
// =========================================

function openItemRateModal(productName, salesId) {
    const modal = document.getElementById('rateModal');
    if (modal) {
        modal.style.display = 'flex';
        document.getElementById('rateProductNameDisplay').innerText = productName;
        document.getElementById('rateProductInput').value = productName;
        document.getElementById('rateSalesIdInput').value = salesId;
    }
}

function closeRateModal() {
    const modal = document.getElementById('rateModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// =========================================
// ADMIN DASHBOARD CHARTS
// =========================================
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('salesChart');
    if (ctx) {
        const labels = JSON.parse(ctx.getAttribute('data-labels'));
        const data = JSON.parse(ctx.getAttribute('data-values'));
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Revenue (₱)',
                    data: data,
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#374151' },
                        ticks: { color: '#9ca3af', callback: function(value) { return '₱' + value; } }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#9ca3af' }
                    }
                }
            }
        });
    }
});

// =========================================
// PHILIPPINE ADDRESS API (PSGC) INTEGRATION
// =========================================
document.addEventListener('DOMContentLoaded', function() {
    // Intelligently target the dropdowns whether we are on account.php or shop.php
    const rSel = document.getElementById('ph-region-acc') || document.getElementById('ph-region-shop');
    const pSel = document.getElementById('ph-province-acc') || document.getElementById('ph-province-shop');
    const cSel = document.getElementById('ph-city-acc') || document.getElementById('ph-city-shop');
    const bSel = document.getElementById('ph-barangay-acc') || document.getElementById('ph-barangay-shop');

    // If these dropdowns don't exist on the current page, exit silently
    if (!rSel) return;

    // Load Regions on page load
    fetch('https://psgc.gitlab.io/api/regions/')
        .then(res => res.json())
        .then(data => {
            data.sort((a, b) => a.name.localeCompare(b.name));
            rSel.innerHTML = '<option value="" disabled selected>Select Region</option>';
            data.forEach(r => {
                let opt = document.createElement('option');
                opt.value = r.name;
                opt.dataset.code = r.code;
                opt.textContent = r.name;
                rSel.appendChild(opt);
            });
        }).catch(err => { rSel.innerHTML = '<option value="">Error loading API</option>'; });

    // When Region is Changed
    rSel.addEventListener('change', function() {
        const code = this.options[this.selectedIndex].dataset.code;
        const name = this.value;
        pSel.innerHTML = '<option value="" disabled selected>Loading...</option>';
        cSel.innerHTML = '<option value="" disabled selected>Select City</option>';
        bSel.innerHTML = '<option value="" disabled selected>Select Barangay</option>';

        fetch(`https://psgc.gitlab.io/api/regions/${code}/provinces/`)
            .then(res => res.json())
            .then(data => {
                if (data.length === 0 || code === '130000000') { 
                    // NCR or Region without provinces - Skip directly to Cities
                    pSel.innerHTML = `<option value="${name}" data-code="${code}">${name}</option>`;
                    fetchCities(`https://psgc.gitlab.io/api/regions/${code}/cities-municipalities/`);
                } else {
                    data.sort((a, b) => a.name.localeCompare(b.name));
                    pSel.innerHTML = '<option value="" disabled selected>Select Province</option>';
                    data.forEach(p => {
                        let opt = document.createElement('option');
                        opt.value = p.name; opt.dataset.code = p.code; opt.textContent = p.name;
                        pSel.appendChild(opt);
                    });
                }
            });
    });

    // When Province is Changed
    pSel.addEventListener('change', function() {
        const code = this.options[this.selectedIndex].dataset.code;
        fetchCities(`https://psgc.gitlab.io/api/provinces/${code}/cities-municipalities/`);
    });

    function fetchCities(url) {
        cSel.innerHTML = '<option value="" disabled selected>Loading...</option>';
        bSel.innerHTML = '<option value="" disabled selected>Select Barangay</option>';
        fetch(url).then(res => res.json()).then(data => {
            data.sort((a, b) => a.name.localeCompare(b.name));
            cSel.innerHTML = '<option value="" disabled selected>Select City</option>';
            data.forEach(c => {
                let opt = document.createElement('option');
                opt.value = c.name; opt.dataset.code = c.code; opt.textContent = c.name;
                cSel.appendChild(opt);
            });
        });
    }

    // When City is Changed
    cSel.addEventListener('change', function() {
        const code = this.options[this.selectedIndex].dataset.code;
        bSel.innerHTML = '<option value="" disabled selected>Loading...</option>';
        fetch(`https://psgc.gitlab.io/api/cities-municipalities/${code}/barangays/`)
            .then(res => res.json())
            .then(data => {
                data.sort((a, b) => a.name.localeCompare(b.name));
                bSel.innerHTML = '<option value="" disabled selected>Select Barangay</option>';
                data.forEach(b => {
                    let opt = document.createElement('option');
                    opt.value = b.name; opt.dataset.code = b.code; opt.textContent = b.name;
                    bSel.appendChild(opt);
                });
            });
    });
});

document.addEventListener('DOMContentLoaded', function() {
    toggleStockField();
});