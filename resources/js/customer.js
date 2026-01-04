window.Customer = function () {
    window.datatables.push({
        selector: '#customers-table',
        resource: 'customers',
        ajax: '/customer/data-table',
        columns: [
            {
                "orderable": false,
                "class":"text-left",
                "title": "",
                "data": function (data) {
                    return `<a type="button" class="table-icon-button" href="${data['link_edit']}">
                                <i class="picon-edit-filled icon-lg" title="Edit"></i>
                            </a>`;
                },
            },
            {
                "orderable": false,
                "class":"text-left",
                "title": "Hold",
                "data": function (data) {
                    const checked = data.is_hold ? 'checked' : '';
            
                    return `
                        <label class="toggle float-left">
                            <input type="checkbox"
                                   class="toggle-hold"
                                   data-action="${ data.toggle_hold_url }"
                                   data-id="${data.id}"
                                   ${checked}>
                            <span class="toggle-slider"></span>
                        </label>
                    `;
                }
            },
            
            {"title": "Name", "data": "name", "name": "contact_informations.name"},
            {"title": "Company Name", "data": "company_name", "name": "contact_informations.company_name"},

            {
                "orderable": false,
                "class":"text-left",
                "title": "Store",
                "data": function (data) {
                    if (data['store_name'])
                        return `<a type="button" class="table-icon-button" href="${data['link_store']}" target="_blank">
                                    ${data['store_name']}
                                </a>`;
                    return ``
                },
            },

            {"title": "Address", "data": "address", "name": "contact_informations.address"},
            {"title": "Address2", "data": "address2", "name": "contact_informations.address2"},
            {"title": "Zip", "data": "zip", "name": "contact_informations.zip"},
            {"title": "City", "data": "city", "name": "contact_informations.city"},
            {"title": "Email", "data": "email", "name": "contact_informations.email"},
            {"title": "Phone", "data": "phone", "name": "contact_informations.phone"},
            {
                'non_hiddable': true,
                "orderable": false,
                "class": "text-left",
                "title": "",
                "data": function (data) {
                    return app.tableDeleteButton(
                        `Are you sure you want to delete ${data.name}?`,
                        data.link_delete
                    );
                }
            }
        ],
    })
    
    $(document).on('click', '.toggle-hold', function (e) {
        e.preventDefault();            // stop label toggle
        e.stopImmediatePropagation();  // stop browser default
    
        const checkbox = this; // raw DOM element
        const $checkbox = $(this);
        const action = $checkbox.data('action');
    
        const willHold = !checkbox.checked;
    
        const title = willHold ? 'Hold Customer' : 'Unhold Customer';
        const message = willHold
            ? 'This customer will be put on hold.'
            : 'This customer will be removed from hold.';
    
        app.confirm(title, message, function () {
            $.ajax({
                method: 'PATCH',
                url: action,
                success: function (response) {
                    window.dtInstances['#customers-table'].ajax.reload()
                    toastr.success(response.message);
                },
                error: function () {
                    toastr.error('Failed to update hold status');
                }
            });
        });    
    });    
}
