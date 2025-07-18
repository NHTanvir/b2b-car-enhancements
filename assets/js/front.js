let pc_modal = ( show = true ) => {
	if(show) {
		jQuery('#plugin-client-modal').show();
	}
	else {
		jQuery('#plugin-client-modal').hide();
	}
}

jQuery(function($){
    // ✅ Show pending status if cookie exists
        var cookieValue = jQuery.cookie("b2b_pending_approval");

        console.log(cookieValue)

        if (cookieValue && cookieValue !== "") {
            const pendingNotice = `
                <div class="xoo-aff-group xoo-el-pending-approval-message" 
                    style="padding: 10px; margin-left: 10px; margin-bottom: 10px; background: #fff4c2; border-left: 5px solid #ffcc00; float: initial">
                    <strong>Account Status:</strong> Pending Approval by Admin
                </div>
            `;

            const header = $('.xoo-el-form-container.xoo-el-form-popup .xoo-el-header');

            if (header.length) {
                $(pendingNotice).insertAfter(header);
            }
        }

    // ✅ Your custom fields
    const customFields = `
        <div class="xoo-aff-group xoo-aff-cont-text one xoo-aff-cont-required xoo_el_reg_b2b_phone_cont">
            <div class="xoo-aff-input-group">
                <span class="xoo-aff-input-icon fas fa-phone"></span>
                <input type="text" class="xoo-aff-required xoo-aff-text" name="b2b_phone_number" placeholder="Phone Number" required>
            </div>
        </div>

        <div class="xoo-aff-group xoo-aff-cont-text one xoo-aff-cont-required xoo_el_reg_b2b_vat_cont">
            <div class="xoo-aff-input-group">
                <span class="xoo-aff-input-icon fas fa-receipt"></span>
                <input type="text" class="xoo-aff-required xoo-aff-text" name="b2b_vat_number" placeholder="VAT Number" required>
            </div>
        </div>

        <div class="xoo-aff-group xoo-aff-cont-text one xoo-aff-cont-required xoo_el_reg_b2b_company_cont">
            <div class="xoo-aff-input-group">
                <span class="xoo-aff-input-icon fas fa-building"></span>
                <input type="text" class="xoo-aff-required xoo-aff-text" name="b2b_commercial_name" placeholder="Commercial Name" required>
            </div>
        </div>
    `;

    const passwordField = $('form.xoo-el-action-form.xoo-el-form-register .xoo_el_reg_pass_cont');
    if (passwordField.length > 0) {
        $(customFields).insertBefore(passwordField);
    }


    $('body').on('click', '.b2b-favorite-btn', function(e) {
        e.preventDefault();
        e.stopPropagation(); 
        var button = $(this);
        var post_id = button.data('post-id');
        
        $.ajax({
            url: PUA.ajax_url,
            type: 'POST',
            data: {
                action: 'toggle_favorite',
                post_id: post_id,
                nonce: PUA.nonce
            },
            beforeSend: function() {
                button.text('...');
            },
            success: function(response) {
                if (response.success) {
                    button.text(response.data.text);
                    button.toggleClass('is-favorite');

                    if (!button.hasClass('is-favorite')) {
                        button.closest('.b2b_favorite').fadeOut();
                    }
                } else {
                    alert(response.data.message);
                    button.text('Error');
                }
            }

        });
    });
})