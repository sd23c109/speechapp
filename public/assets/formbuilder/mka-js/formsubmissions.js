var formsubmissions = {
    dt: {},
    init: function(){
        
          formsubmissions.dt = $('#savedSubmissionsTable').DataTable({
                dom: "<'row mb-2'<'col-sm-6'B><'col-sm-6'f>>" + // Buttons left, search right
           "<'row'<'col-sm-12'tr>>" + 
           "<'row mt-2'<'col-sm-5'i><'col-sm-7'p>>",   // Info left, pagination right
              buttons: [
                {
                  text: '<i class="fa fa-archive"></i> Archive',
                  className: 'btn btn-warning btn-sm',
                  action: function (e, dt, node, config) {
                    formsubmissions.archiveSelectedRows();
                  }
                }
              ],
                        
            columnDefs: [
            { orderable: false, targets: 0 },
            {
                targets: [5], // e.g., 3rd column
                visible: false,
                searchable: true
            }
            ]
          });

          // Handle "select all" checkbox
          
          
          $('#selectAllSubmissions').on('click', function () {
              const isChecked = $(this).is(':checked');
              $('.submission-checkbox').prop('checked', isChecked);
            });
            
            //uncheck the header box if a single row is unchecked.   May have an occassion where most are checked except a few
            
            $('#submissionsTable').on('click', '.submission-checkbox', function () {
                  const allChecked = $('.submission-checkbox').length === $('.submission-checkbox:checked').length;
                  $('#selectAllSubmissions').prop('checked', allChecked);
            });
          
          //PDF Download in the modal
          $('#downloadPdfBtn').off('click').on('click', function () {
                const formUUID = $('#submissionViewModal').data('formuuid');
                const entryUUID = $('#submissionViewModal').data('entryuuid');

                if (formUUID && entryUUID) {
                    window.open(`/formbuilder/download_pdf.php?form_uuid=${formUUID}&entry_uuid=${entryUUID}`, '_blank');
                }
            });
            
            //Archive submissions modal
            
            $('#confirmArchiveBtn').on('click', function () {
              const selected = formsubmissions._selectedToArchive || [];

              if (selected.length === 0) {
                toastr.error('Nothing selected to archive.');
                return;
              }

              fetch('/formbuilder/archive_submissions.php', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json'
                },
                body: JSON.stringify({ entry_uuids: selected })
              })
                .then(res => res.json())
                .then(data => {
                  if (data.success) {
                    selected.forEach(uuid => {
                      formsubmissions.dt.row(`#row-${uuid}`).remove().draw(false);
                    });
                    toastr.success(`${selected.length} submission(s) archived.`);

                    // Hide modal
                    bootstrap.Modal.getInstance(document.getElementById('confirmArchiveModal')).hide();
                  } else {
                    toastr.error('Failed to archive: ' + (data.message || 'Unknown error.'));
                  }
                })
                .catch(err => {
                  console.error('Archive error:', err);
                  toastr.error('A network error occurred while archiving.');
                });
            });



    },
    
    archiveSelectedRows: function () {
          const selected = $('.submission-checkbox:checked').map(function () {
            return this.value;
          }).get();

          if (selected.length === 0) {
            toastr.warning('Please select at least one submission to archive.');
            return;
          }

          // Save to formsubmissions.temp for use when modal confirms
          formsubmissions._selectedToArchive = selected;

          // Update modal text
          $('#archive-count').text(selected.length);

          // Show the modal (Bootstrap 5)
          const modal = new bootstrap.Modal(document.getElementById('confirmArchiveModal'));
          modal.show();
        },

    
    viewSubmission: function(form_uuid, entry_uuid){
        
          $('#submissionModalBody').html('<div class="text-center">Loading...</div>');
          $('#submissionViewModal').modal('show');
        
          $('#submissionViewModal')
                .data('formuuid', form_uuid)
                .data('entryuuid', entry_uuid);

          $.ajax({
            url: '/formbuilder/view_submissions.php',
            method: 'POST',
            data: {
              form_uuid: form_uuid,
              entry_uuid: entry_uuid
            },
            success: function (response) {
              try {
                 
                json = response
               
                $('#submissionModalBody').html(json.html || '<div class="text-danger">No data found.</div>');
                $('#submissionModalTitle').html(json.submission_title)
                $('#submissionModalTime').html(json.submitted_at)
                
                //update the row font weight and opened_time
                const rowSelector = `#row-${json.entry_uuid}`;
                const $row = $(rowSelector);
                const now = new Date();
                const formatted = now.getUTCFullYear() + '-' +
                                  String(now.getUTCMonth() + 1).padStart(2, '0') + '-' +
                                  String(now.getUTCDate()).padStart(2, '0') + ' ' +
                                  String(now.getUTCHours()).padStart(2, '0') + ':' +
                                  String(now.getUTCMinutes()).padStart(2, '0') + ':' +
                                  String(now.getUTCSeconds()).padStart(2, '0');

                $row.find('.open-time').text(formatted);
                $row.find('.form-title').removeClass('font-weight-bold');
                
              } catch (e) {
                $('#submissionModalBody').html('<div class="text-danger">Error loading submission.</div>');
                console.error('Invalid JSON returned:', response);
              }
            },
            error: function (xhr, status, error) {
              $('#submissionModalBody').html('<div class="text-danger">Failed to load submission.</div>');
              console.error('AJAX Error:', status, error);
            }
          });


    },
    
    
    
}