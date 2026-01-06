var archivedsubmissions = {
    dt: {},
    init : function(){
        
          archivedsubmissions.dt = $('#archivedSubmissionsTable').DataTable({
                dom: "<'row mb-2'<'col-sm-6'B><'col-sm-6'f>>" + // Buttons left, search right
           "<'row'<'col-sm-12'tr>>" + 
           "<'row mt-2'<'col-sm-5'i><'col-sm-7'p>>",   // Info left, pagination right
                                     
            columnDefs: [
            { orderable: false, targets: 0 },
            {
                targets: [4], // e.g., 3rd column
                visible: false,
                searchable: true
            }
            ]
          });

          // Handle "select all" checkbox
         

    },
    
    viewSubmission: function(form_uuid, entry_uuid) {
    $.ajax({
        url: '/formbuilder/view_archived_submissions.php',
        method: 'POST',
        data: {
            form_uuid: form_uuid,
            entry_uuid: entry_uuid
        },
        dataType: 'json',
        success: function(res) {
            $('#submissionViewModal .modal-title').text(res.submission_title + ' (' + res.submitted_at + ')');
            $('#submissionViewModal .modal-body').html(res.html);
            $('#submissionViewModal').modal('show');
        },
        error: function(xhr) {
            alert('Could not load archived submission.');
            console.error(xhr.responseText);
        }
    });
}

}