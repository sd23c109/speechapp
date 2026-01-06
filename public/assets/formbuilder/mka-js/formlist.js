var formlist = {
    init: function() {
        const table = $('#savedFormsTable').DataTable({
            ajax: '/formbuilder/list.php',
            columns: [
                {
                    data: 'form_uuid',
                    render: function(data) {
                        return `<input type="checkbox" class="form-check-input form-select-checkbox" value="${data}" />`;
                    },
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'form_title',
                    render: function(data, type, row) {
                        return `<a href="/dashboards/formbuilder_buildform.php?form_uuid=${row.form_uuid}">${data}</a>`;
                    }
                },
                { data: 'form_description' },
                {
                    data: null,
                    render: function(data, type, row) {
                        const url = `https://app.mkadvantage.com/formbuilder/${row.company_slug}/${row.form_slug}`;
                        return `
                          <div class="input-group">
                            <input type="text" class="form-url-input form-control" value="${url}" readonly style="max-width: 240px;" />
                            <button class="btn btn-sm btn-outline-secondary copy-url-btn" type="button" data-url="${url}" title="Copy URL">
                              <i class="fa fa-copy"></i>
                            </button>
                          </div>
                        `;
                    },
                    orderable: false,
                    searchable: false
                },
                { data: 'created_at' },
                { data: 'updated_at' },
                {   
                    data: 'is_active',
                    render: function(data, type, row) {
                        let btnClass = (data === 'active') ? 'btn-danger' : 'btn-success';
                        let btnText = (data === 'active') ? 'Disable' : 'Enable';
                        return `
                            <button class="btn btn-sm ${btnClass} toggle-status-btn" 
                                    data-form-id="${row.form_uuid}" 
                                    data-status="${data}">
                                ${btnText}
                            </button>`;
                    },
                    orderable: false,
                    searchable: false
                }
            ]
        });

        // Handle "select all" checkbox
        $('#select-all').on('click', function () {
            $('.form-select-checkbox').prop('checked', this.checked);
        });

        // Copy URL button
        $('#savedFormsTable tbody').on('click', '.copy-url-btn', function () {
            const url = $(this).data('url');
            navigator.clipboard.writeText(url).then(() => {
                $(this).tooltip({ title: 'Copied!', trigger: 'manual' }).tooltip('show');
                setTimeout(() => $(this).tooltip('dispose'), 1000);
            });
        });

        // ✅ Toggle Status Button
        $('#savedFormsTable tbody').on('click', '.toggle-status-btn', function () {
            let btn = $(this);
            let formId = btn.data('form-id');
            let currentStatus = btn.data('status');
            let newStatus = (currentStatus === 'active') ? 'disabled' : 'active';

            $.ajax({
                url: '/formbuilder/toggle_form_status.php',
                method: 'POST',
                data: { form_uuid: formId, status: newStatus },
                success: function(response) {
                    if (response.success) {
                        // Update button
                        btn.data('status', newStatus)
                           .toggleClass('btn-success btn-danger')
                           .text(newStatus === 'active' ? 'Disable' : 'Enable');
                        toastr.success(response.message);
                    } else {
                        toastr.error(response.message || 'Failed to update form status.');
                    }
                },
                error: function() {
                    toastr.error('Server error updating form status.');
                }
            });
        });
    }
};
