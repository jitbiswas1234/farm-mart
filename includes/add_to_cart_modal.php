<?php
// This file contains the reusable "Add to Cart" modal and its JavaScript logic.
// It should be included on any page where products can be added to the cart via a modal.
// Requires: BASE_URL to be defined in config.php, and a #cartBadge element in navbar.php
?>

<!-- Add to Cart Modal -->
<div class="modal fade" id="addToCartModal" tabindex="-1" aria-labelledby="addToCartModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary-light">
                <h5 class="modal-title fw-bold" id="addToCartModalLabel">Add to Cart: <span id="modalProductName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="modalAddToCartForm">
                    <input type="hidden" name="product_id" id="modalProductId">
                    <input type="hidden" name="add_to_cart" value="1"> <!-- To trigger cart.php logic -->
                    <input type="hidden" name="is_ajax" value="1"> <!-- To signal AJAX request -->

                    <div class="mb-3">
                        <label for="modalQuantity" class="form-label fw-bold">Quantity (<span id="modalProductUnit"></span>)</label>
                        <input type="number" name="quantity" id="modalQuantity" class="form-control" value="1" min="1" max="100" required>
                        <small class="text-muted">Available Stock: <span id="modalProductStock"></span></small>
                    </div>
                    <div id="modalMessage" class="alert d-none mt-3"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="modalAddToCartForm" class="btn btn-farmmart" id="modalAddBtn">Add to Cart</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const addToCartModal = document.getElementById('addToCartModal');
    const modalProductName = document.getElementById('modalProductName');
    const modalProductId = document.getElementById('modalProductId');
    const modalQuantity = document.getElementById('modalQuantity');
    const modalProductStock = document.getElementById('modalProductStock');
    const modalProductUnit = document.getElementById('modalProductUnit');
    const modalAddToCartForm = document.getElementById('modalAddToCartForm');
    const modalAddBtn = document.getElementById('modalAddBtn');
    const modalMessage = document.getElementById('modalMessage');

    // Get the cart badge element in the navbar
    const cartBadge = document.getElementById('cartBadge');

    // Function to reset modal button to "Add to Cart" state
    function resetModalButton() {
        modalAddBtn.textContent = 'Add to Cart';
        modalAddBtn.classList.remove('btn-success');
        modalAddBtn.classList.add('btn-farmmart');
        modalAddBtn.setAttribute('type', 'submit'); // Revert to submit type
        modalAddBtn.onclick = null; // Remove the onclick handler
    }

    addToCartModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget; // Button that triggered the modal
        const productId = button.getAttribute('data-product-id');
        const productName = button.getAttribute('data-product-name');
        const productStock = button.getAttribute('data-product-stock');
        const productUnit = button.getAttribute('data-product-unit');

        modalProductName.textContent = productName;
        modalProductId.value = productId;
        modalQuantity.value = 1; // Reset quantity
        modalQuantity.setAttribute('max', productStock); // Set max quantity
        modalProductStock.textContent = productStock + ' ' + productUnit;
        modalProductUnit.textContent = productUnit;
        modalQuantity.disabled = false; // Ensure quantity input is enabled
        
        modalMessage.classList.add('d-none'); // Hide previous messages
        modalMessage.classList.remove('alert-success', 'alert-danger');
        
        resetModalButton(); // Reset button state on modal show
        modalAddBtn.disabled = false; // Ensure button is enabled
    });

    // Reset modal button state when the modal is hidden
    addToCartModal.addEventListener('hidden.bs.modal', function () {
        resetModalButton();
        modalQuantity.disabled = false; // Re-enable quantity input
        modalMessage.classList.add('d-none'); // Hide message
        modalMessage.classList.remove('alert-success', 'alert-danger');
    });

    modalAddToCartForm.addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent default form submission

        modalAddBtn.disabled = true;
        modalAddBtn.textContent = 'Adding...';
        modalMessage.classList.add('d-none');

        const formData = new FormData(modalAddToCartForm);
        
        fetch('<?= BASE_URL ?>user/includes/cart.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Update cart count in navbar
                if (cartBadge && data.total_cart_items !== undefined) {
                    cartBadge.textContent = data.total_cart_items;
                }
                modalMessage.textContent = data.message;
                modalMessage.classList.remove('d-none', 'alert-danger');
                modalMessage.classList.add('alert-success');

                // Change modal's "Add to Cart" button to "Go to Cart"
                modalAddBtn.textContent = 'Go to Cart';
                modalAddBtn.classList.remove('btn-farmmart');
                modalAddBtn.classList.add('btn-success');
                modalAddBtn.setAttribute('type', 'button'); // Change type from submit to button
                modalAddBtn.onclick = function() {
                    window.location.href = '<?= BASE_URL ?>user/includes/cart.php';
                };
                modalQuantity.disabled = true; // Disable quantity input after successful add
            } else {
                if (data.status === 'not_authorized') { // Handle redirection for unauthenticated users
                    alert(data.message);
                    window.location.href = data.redirect_url;
                }
                modalMessage.textContent = data.message || 'Failed to add item to cart.';
                modalMessage.classList.remove('d-none', 'alert-success');
                modalMessage.classList.add('alert-danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            modalMessage.textContent = 'An error occurred. Please try again.';
            modalMessage.classList.remove('d-none', 'alert-success');
            modalMessage.classList.add('alert-danger');
        })
        .finally(() => {
            // Only reset if it's not already a "Go to Cart" button (meaning it was an error or unhandled exception)
            if (modalAddBtn.textContent === 'Adding...') {
                modalAddBtn.disabled = false;
                modalAddBtn.textContent = 'Add to Cart';
            }
        });
    });
});
</script>