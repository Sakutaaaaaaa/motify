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
// DASHBOARD.PHP: Sales Analytics Chart
// =========================================
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('salesChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Revenue (PHP)',
                    data: [12000, 19000, 15000, 22000, 18000, 28000],
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: { 
                    y: { grid: { color: '#374151' }, ticks: { color: '#9ca3af' } },
                    x: { grid: { display: false }, ticks: { color: '#9ca3af' } }
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

    // ... (Your existing filter logic) ...
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
        alert("❤️ Wishlist saving will be available in Phase 3!");
    }
};


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
    document.getElementById('label-pickup').style.borderColor = (method === 'pickup') ? '#ef4444' : '#374151';
    document.getElementById('label-delivery').style.borderColor = (method === 'delivery') ? '#ef4444' : '#374151';
    
    // SHOW/HIDE ADDRESS FIELD
    var addressGroup = document.getElementById('address-group');
    if (addressGroup) {
        addressGroup.style.display = (method === 'delivery') ? 'block' : 'none';
    }
    
    // ... (rest of your existing calculation code) ...
}

function validateAndCheckout() {
    var methodInput = document.querySelector('input[name="delivery_method"]:checked');
    if (!methodInput) {
        alert("⚠️ Please select whether you want Store Pickup or Delivery!");
        return;
    }

    // NEW: Check address if delivery is selected
    if (methodInput.value === 'delivery') {
        var addressInput = document.querySelector('textarea[name="customer_address"]');
        if (addressInput && addressInput.value.trim() === '') {
            alert("⚠️ Please enter your delivery address.");
            return;
        }
    }
    
    // ... (existing name/phone validation) ...
    Storefront.processOnlineOrder();
}