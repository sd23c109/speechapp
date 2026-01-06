const GOOGLE_FONTS = [
  "Anton", "Arimo", "Arial", "Asap", "Barlow", "Bebas Neue", "Cabin", "Cormorant Garamond",
  "Courier New", "Crimson Text", "DM Sans", "Exo 2", "Fira Sans", "Heebo", "Hind", "IBM Plex Sans",
  "Inconsolata", "Inter", "Josefin Sans", "Karla", "Lato", "Libre Franklin", "Manrope", "Merriweather",
  "Montserrat", "Mukta", "Muli", "Mulish", "Noto Sans", "Noto Serif", "Nunito", "Open Sans", "Oswald",
  "Overpass", "Playfair Display", "Poppins", "Prompt", "PT Sans", "Quicksand", "Raleway", "Righteous",
  "Roboto", "Rubik", "Signika", "Source Sans Pro", "Teko", "Titillium Web", "Ubuntu", "Varela Round",
  "Work Sans", "Zilla Slab"
];

const SYSTEM_FONTS = [
  'Arial',
  'Courier New',
  'Times New Roman',
  'Georgia',
  'Trebuchet MS',
  'Tahoma',
  'Verdana',
  'Segoe UI'
];


window.__HYDRATING = false;

function markDirtyAndEmit(name, detail) {
    if (window.__HYDRATING) return; // ← block events during hydration
    if (window.FormModel?.setDirty) window.FormModel.setDirty(true);
    const evt = new CustomEvent(name, { detail, bubbles: true, composed: true });
    document.dispatchEvent(evt);
}

function getFieldContainer(rowEl){
    // priority: explicit wrapper, then the bootstrap ".sortable-row" inner, then row itself
    return rowEl.querySelector('.row-fields')
        || rowEl.querySelector('.sortable-row')
        || rowEl;
}



var config = {
  
  isPreviewMode: false,
  isPublic:false,
  isMobile:false,
  formSlug:'',
  companySlug:'',

  companyName:'',
  formSubmitMessage:'',
  formRecipients:'',
  defaultLabelColor: "#000000",
  defaultLabelFont: "Arial",
  defaultTextMaxLength: 100,

  activeRowId:'',
  form_uuid:null,
  currentEditingId:null,
  init: function (LOADED_FORM) {






    const fontSelect = $('#textInputFont, #textareaFont, #numberInputFont, #emailInputFont, #phoneInputFont, #headerFont');
      GOOGLE_FONTS.forEach(font => {
        fontSelect.append(`<option value="${font}">${font}</option>`);
      });

    
    this.initSortable(); // field-level sortable
    
    this.initPickers();
    
    this.initImageUpload();
    
    //Phone mask
    
    $('input[type="tel"]').mask('(000) 000-0000');
    
     //GOOGLE Fonts
    GOOGLE_FONTS.forEach(f => {
  $('#multiSelectFont').append(`<option value="${f}">${f}</option>`);
    });
    
    GOOGLE_FONTS.forEach(f => {
      $('#singleSelectFont').append(`<option value="${f}">${f}</option>`);
    });
    GOOGLE_FONTS.forEach(f => {
      $('#radioInputFont').append(`<option value="${f}">${f}</option>`);
    });
    GOOGLE_FONTS.forEach(f => {
      $('#checkboxInputFont').append(`<option value="${f}">${f}</option>`);
    });
       GOOGLE_FONTS.forEach(f => {
      $('#datePickerFont').append(`<option value="${f}">${f}</option>`);
      });
    
    GOOGLE_FONTS.forEach(f => {
      $('#defaultLabelFont').append(`<option value="${f}">${f}</option>`);
      });
  
      GOOGLE_FONTS.forEach(f => {
      $('#imageCaptionFont').append(`<option value="${f}">${f}</option>`);
      }); 
      
      GOOGLE_FONTS.forEach(f => {
      $('#signatureFont').append(`<option value="${f}">${f}</option>`);
      });
       //in config form to handle slug
      $('#formSlug').on('input', function () {
            let raw = $(this).val();
            let slug = raw
              .toLowerCase()
              .trim()
              .replace(/\s+/g, '-')        // Replace spaces with dashes
              .replace(/[^a-z0-9\-]/g, '') // Remove anything that's not alphanumeric or dash
              .replace(/\-+/g, '-');       // Collapse multiple dashes

            $(this).val(slug); // Update the input field if desired
            $('#slugPreview').text(slug || 'your-form');
          });
      GOOGLE_FONTS.forEach(f => {
      $('#fileUploadFont').append(`<option value="${f}">${f}</option>`);
       });
       
       GOOGLE_FONTS.forEach(font => {
           if (!SYSTEM_FONTS.includes(font)) {
              const link = document.createElement('link');
              link.href = `https://fonts.googleapis.com/css2?family=${font.replace(/ /g, '+')}&display=swap`;
              link.rel = 'stylesheet';
          document.head.appendChild(link);
           }
        });
      /*LOAD A FORM*/
      
      if (LOADED_FORM && LOADED_FORM.fields) {
        try {
            //console.log('LOADED FORM ',LOADED_FORM)
            $('#form-title').html(LOADED_FORM.form_title);
            $('#formTitle').val(LOADED_FORM.form_title) 
            $('#formRecipients').val(LOADED_FORM.email_recipients);
            $('#formSlug').val(LOADED_FORM.form_slug);
            $('#formSubmitMessage').val(LOADED_FORM.submit_message);
            
            config.formRecipients = LOADED_FORM.email_recipients;
            config.formSlug = LOADED_FORM.form_slug;
            config.companySlug = LOADED_FORM.company_slug;
            config.formSubmitMessage = LOADED_FORM.submit_message;
            
            config.formTitle = LOADED_FORM.form_title;

            config.form_uuid = LOADED_FORM.form_uuid || null;
            if (LOADED_FORM.form_title) {
                $('#form-title').text(LOADED_FORM.form_title);
            }
            
            if (LOADED_FORM.defaults) {
               const parsedDefaults = JSON.parse(LOADED_FORM.defaults)
               //console.log(parsedDefaults);
               //This pickr hasn't been loaded yet, load it further down after you set config.defaultLabelColor
               //config.pickrs['defaultLabelColor'].setColor(config.defaultLabelColor);
               config.defaultLabelColor = parsedDefaults.defaultLabelColor 
               $('#defaultLabelFont').val(parsedDefaults.defaultLabelFont)
               config.defaultLabelFont = parsedDefaults.defaultLabelFont 
               $('#defaultTextMaxLength').val(parsedDefaults.defaultTextMaxLength)
               config.defaultTextMaxLength = parsedDefaults.defaultTextMaxLength
            }
           
            const parsedFields = JSON.parse(LOADED_FORM.fields);
            console.log('Parsed form is ',parsedFields);
            if (parsedFields) {
                try {
                    config.renderForm(parsedFields);
                    hydrateParagraphEditorsFromModel(parsedFields);
                } catch (e) {
                    console.error('Failed to load saved form:', e);
                }
            }

            initRowSortable();
            
            //$('#form-title').val(formData.form_title || '');
        } catch (e) {
            console.error('Failed to load saved form:', e);
        }
    }

initRowSortable()

},

  ensureFormInitialized: async function () {
        if (config.form_uuid) return;  // already good

        // If we get here, user hasn't saved Form Config yet.
        // Try to create a skeleton using what we have in memory (title, slug, recipients, etc.)
        // If you prefer to *force* the modal first, just throw an alert here and return.
        await config.saveFormConfig({silent: true}); // will set this.formUuid on success
    },



    pickrs: {},
    formRecipients:'',
    formSlug:'',
    formSubmitMessage:'',
    formTitle:'',
    fieldCounter: 0,
    rowCounter:0,
    initPickers: function(){
      
      const pickrOptions = {
        theme: 'classic', // or 'monolith', 'nano'
        inline: true,
        swatches: [
          'rgba(244, 67, 54, 1)',
          'rgba(233, 30, 99, 0.95)',
          'rgba(156, 39, 176, 0.9)',
          'rgba(103, 58, 183, 0.85)',
          'rgba(63, 81, 181, 0.8)',
          'rgba(33, 150, 243, 0.75)',
          'rgba(3, 169, 244, 0.7)',
          'rgba(0, 188, 212, 0.7)',
          'rgba(0, 150, 136, 0.75)',
          'rgba(76, 175, 80, 0.8)',
          'rgba(139, 195, 74, 0.85)',
          'rgba(205, 220, 57, 0.9)',
          'rgba(255, 235, 59, 0.95)',
          'rgba(255, 193, 7, 1)'
        ],
        components: {
          // Main components
          preview: true,
          opacity: true,
          hue: true,

          // Input / output Options
          interaction: {
            hex: true,
            rgba: true,
            input: true,
            clear: true,
            save: true
             
          }
        }
      };

       // store pickr instances

      const ids = [
  'textInputHeadingColor',
  'textInputBorderColor',
  'textInputTextColor',
  'textInputBgColor',
  'textareaHeadingColor',
  'textareaBorderColor',
  'textareaTextColor',
  'textareaBgColor',
  'numberInputHeadingColor',
  'numberInputBorderColor',
  'numberInputTextColor',
  'numberInputBgColor',
  'emailInputHeadingColor',
    'emailInputBorderColor',
    'emailInputTextColor',
    'emailInputBgColor',
    'phoneInputHeadingColor',
'phoneInputBorderColor',
'phoneInputTextColor',
'phoneInputBgColor',
'multiSelectHeadingColor',
  'multiSelectBorderColor',
  'multiSelectTextColor',
  'multiSelectBgColor',
  'singleSelectHeadingColor',
  'singleSelectBorderColor',
  'singleSelectTextColor',
  'singleSelectBgColor',
  'radioInputHeadingColor',
  'radioInputBorderColor',
  'radioInputTextColor',
  'radioInputBgColor',
  'checkboxInputHeadingColor',
  'checkboxInputBorderColor',
  'checkboxInputTextColor',
  'checkboxInputBgColor',
  'datePickerHeadingColor',
  'datePickerBorderColor',                
  'datePickerTextColor',
  'datePickerBgColor',
  'headerFontColor',
  'imageCaptionColor',
  'signatureFontColor',
  'fileUploadHeadingColor',
  'fileUploadBorderColor',
  'defaultLabelColor'
    
];

      ids.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
          config.pickrs[id] = Pickr.create({
            el: el,
            default: config.defaultLabelColor,
            ...pickrOptions
          });
          
            
        }
        
        
      });
    },
    
    destroyPickers: function(){
        if (!config.pickrs) return;
          Object.values(config.pickrs).forEach(p => p && p.destroy && p.destroy());
          config.pickrs = {};
    },
    
    initImageUpload: function() {
        $('#imageUpload').on('change', async function () {
          const file = this.files[0];
          if (!file) return;
          //console.log('Your form_uuid is '+config.form_uuid+' and your currentEditingId is '+config.currentEditingId)
          const formData = new FormData();
          formData.append('image', file);
          formData.append('form_uuid', config.form_uuid);          // assume already set
          formData.append('field_id', config.currentEditingId);    // track which field

          try {
            const response = await fetch('/formbuilder/image.php', {
              method: 'POST',
              body: formData
            });
            const result = await response.json();

            if (result.success) {
              // Set <img src> to new URL (could use result.url or construct manually)
                           
              const img = $(`#${config.currentEditingId} img`);
              img.attr('src', result.url);  // already good
              img.data('filename', result.filename); // 👈 store original filename separately
            } else {
              toastr.error('Image upload failed');
            }
          } catch (err) {
            console.error(err);
            toastr.error('Error uploading image');
          }
        });
        
    },

    initSortable: function () {
        document.querySelectorAll('.sortable-row').forEach(el => {
              if (el.getAttribute('data-sortable-attached')) return;

              Sortable.create(el, {
                group: 'form-fields',
                animation: 150,
                handle: '.drag-handle',
                ghostClass: 'sortable-ghost',
                onEnd: function (evt) {
                 // console.log('Field moved between rows');
                },
                onAdd: function (evt) {
                  const targetRow = evt.to;
                  if (!config.canFitNewField(targetRow)) {
                    evt.from.insertBefore(evt.item, evt.from.children[evt.oldIndex]);
                    alert('Target row is full.');
                  }
                }
              });

              el.addEventListener('click', () => config.setActiveRow(el));
              el.setAttribute('data-sortable-attached', 'true');
            });

      },
  
 formPreviewToggle: function () {
    this.isPreviewMode = !this.isPreviewMode;

    // Toggle editor-only elements
    document.querySelectorAll('.editor-only').forEach(el => {
      el.style.display = this.isPreviewMode ? 'none' : '';
      
      
    });

    // Optional: Add a class to canvas container
    const canvas = document.getElementById('form-canvas');
    canvas.classList.toggle('preview-mode', this.isPreviewMode);

    // Update button text/icon
    const previewBtn = document.getElementById('previewconfig');
    if (this.isPreviewMode) {
      previewBtn.innerHTML = '<i class="ti ti-edit fs-18 me-2"></i> Edit';
      $(`.summernote`).each(function () {
            if ($(this).next('.note-editor').length) {
              $(this).summernote('destroy');
            }
          });
    } else {
      previewBtn.innerHTML = '<i class="ti ti-eye fs-18 me-2"></i> Preview';
        $(`.summernote`).each(function () {
                if (!$(this).next('.note-editor').length) {
                    $(this).summernote({
                    height: 200,
                        // toolbar, fonts, etc.
                });
                }
        });
    }
    

  },
  
 formPublic: function () {
  this.isPublic = true; // Always true when rendering public view

  // Hide all editor-only controls
  document.querySelectorAll('.editor-only').forEach(el => {
    el.style.display = 'none';
  });

  // Add preview styling to canvas (e.g., hide drag handles)
  const canvas = document.getElementById('form-canvas');
  if (canvas) {
    canvas.classList.add('preview-mode');
  }

  // Destroy any summernote editors that may have initialized
  $(`.summernote`).each(function () {
    if ($(this).next('.note-editor').length) {
      $(this).summernote('destroy');
    }
  });

  // Hide preview toggle button if somehow it slipped in
  const previewBtn = document.getElementById('previewconfig');
  if (previewBtn) {
    previewBtn.style.display = 'none';
  }
  //make fields editable
  const publicCanvas = document.getElementById('form-canvas');
  publicCanvas.classList.remove('preview-mode');
  publicCanvas.classList.add('public-form');
  
  //fix Signature boxes
      if (config.isPublic) {
      document.querySelectorAll('canvas[name]').forEach(canvas => {
        const id = canvas.getAttribute('id');
        const hiddenInputId = `${id}-hidden`;
        const hiddenInput = document.getElementById(hiddenInputId);
         //console.log('Do we have a canvas')
        // console.log(canvas)
        if (!hiddenInput) return;
        
        //this.resizeCanvas(canvas);
         
        const signaturePad = new SignaturePad(canvas);
        // console.log('Do we have SIG PAD')
        // console.log(signaturePad)
        // Save base64 image on mouseup or touchend
        
        signaturePad.addEventListener("endStroke", () => {
              
              if (!signaturePad.isEmpty()) {
                const data = signaturePad.toDataURL("image/jpeg");
               
                hiddenInput.value = data;
              } else {
                
                hiddenInput.value = '';
              }
            });
      });
    }
},

resizeCanvas: function(canvas) {
  const ratio = window.devicePixelRatio || 1;
  const width = canvas.offsetWidth || 400;
  const height = canvas.offsetHeight || 150;

  canvas.width = width * ratio;
  canvas.height = height * ratio;
  canvas.getContext("2d").scale(ratio, ratio);
},

  
 addField: function (type, field = {}) {
     // If rehydrating an existing field, keep its id; otherwise allocate a new one
     const idFromData = field.id && String(field.id).trim();
     const id = idFromData || `field-${this.fieldCounter++}`;

     // make sure counter skips ahead if id looked like "field-12"
     if (idFromData) {
         const m = idFromData.match(/(\d+)$/);
         if (m) {
             const n = parseInt(m[1], 10);
             if (n >= this.fieldCounter) this.fieldCounter = n + 1;
         }
     }

     const row = document.getElementById(this.activeRowId);
     if (!row) { alert('Please select a row first.'); return; }

     if (!idFromData && !this.canFitNewField(row)) { // only enforce capacity for new adds
         alert('Row is full. Add a new row or shrink other fields.');
         return;
     }
  
  
  // Set defaults and allow overrides
     const labelText = (field.label ?? this.getDefaultLabel(type)).replace(/\*+$/, '').trim();
  const required = field.required || '';
  const isRequired = field.required === true;
  const requiredMark = isRequired ? ' <span class="text-danger">*</span>' : '';
  const placeholder = field.placeholder || '';
  const maxlength = field.maxlength || config.defaultTextMaxLength;
  const title = field.tooltip || '';
  const styles = {...(field.styles||{})};
  const colSize = field.colSize || 3;
  const rows = field.rows || 4;
  const isStaticBlock = ['header', 'h4', 'separator'].includes(type); 
  let fieldFontSize = '14px'; // default fallback
 
  if (!styles) {
  styles = {};
  }
  
  if (!styles.labelColor && config.defaultLabelColor) {
  styles.labelColor = config.defaultLabelColor;
  }
  
  if (!styles.fontFamily && config.defaultLabelFont) {
  styles.fontFamily = config.defaultLabelFont;
  }

    
   
 
  
    if (styles && styles.fontSize) {
      let rawSize = styles.fontSize.toString().trim();
      

      if (rawSize.match(/^\d+$/)) {
        // Just a number like "14"
        fieldFontSize = rawSize + 'px';
      } else if (rawSize.endsWith('px')) {
        // Already has px
        fieldFontSize = rawSize;
      } else {
        console.warn('Unexpected fontSize format:', rawSize);
        fieldFontSize = rawSize; // fall back to raw value anyway
      }
    }
  
  
  let inputHTML;
    if (type === 'header' || type == 'h4') {
      inputHTML = `<h4 style="font-family:${styles.fontFamily || config.defaultLabelFont}; color:${styles.labelColor || config.defaultLabelColor}; font-size:${fieldFontSize}; text-align:${styles.textAlign || 'left'};">${labelText}</h4>`;
    } else if (type === 'separator') {
      inputHTML = `<hr />`;
    } else {
      inputHTML = this.getInputHTML(type, id, field);
    }

  // Create wrapper and apply classes
  const wrapper = document.createElement('div');
  wrapper.className = `col-md-${colSize} mb-3 field`;
  wrapper.id = id;
  wrapper.dataset.colSize = colSize;

  wrapper.dataset.fieldConfig = !config.isPublic ? JSON.stringify(field) : '{}';

  // Apply inline field styles
  wrapper.style.color = styles.color || '';
  wrapper.style.backgroundColor = styles.backgroundColor || '';
  wrapper.style.borderColor = styles.borderColor || '';
  var viewportLabelFontSize = null;
  if (config.isMobile == true) {
      viewportLabelFontSize = '18px';
  } 
  // Only show label for applicable types
  const showLabel = !['header', 'h4', 'paragraph', 'separator', 'img'].includes(type);
  const labelHTML = showLabel
    ? `<label class="form-label" style="color:${styles.labelColor || config.defaultLabelColor}; font-family:${styles.fontFamily || config.defaultLabelFont}; font-size:${viewportLabelFontSize || fieldFontSize};">${labelText}${requiredMark}</label>`
    : '';
  const dragControl = !config.isPublic ? `<div class="drag-handle mb-2 editor-only" style="cursor: move;">☰</div>` : '';  
  const editControls = !config.isPublic ? `
   <div class="position-absolute top-0 end-0 m-1 editor-only">
    <button class="btn btn-xs btn-outline-secondary me-1 resize-field-btn" data-field-id="${id}" data-direction="minus">−</button>
    <button class="btn btn-xs btn-outline-secondary me-1 resize-field-btn" data-field-id="${id}" data-direction="plus">+</button>
    <button class="btn btn-xs btn-outline-secondary me-1 fill-width-btn" data-field-id="${id}">↔</button>
    <button class="btn btn-xs btn-outline-primary me-1 duplicate-field-btn" data-field-id="${id}">⧉</button>
    <button class="btn btn-xs btn-outline-info me-1 edit-field-btn" data-field-id="${id}">✎</button>
    <button class="btn btn-xs btn-outline-danger remove-element-btn" data-field-id="${id}">🗑</button>
  </div>
` : '';
    
   
       if (type !== 'separator') {
      // Inner structure
      wrapper.innerHTML = `
        <div class="border p-2 position-relative bg-white rounded shadow-sm">
          ${dragControl}
          ${labelHTML}
          ${inputHTML}
          ${editControls}
        </div>
      `;
       } else {
           wrapper.innerHTML = `
        <div class="border p-2 position-relative bg-white rounded shadow-sm">
           ${dragControl}
           ${inputHTML}
           ${editControls}
        </div>
      `;
       }
   
  row.appendChild(wrapper);
  //initialize summernote
  if (type === 'paragraph' || type === 'p') {
      $(`#${id}-editor`).summernote({
        height: 200,
        toolbar: [
          ['style', ['style']],                     // Paragraph style (h1, h2, etc.)
          ['font', ['bold', 'italic', 'underline', 'clear']], // Font styling
          ['fontname', ['fontname']],              // Font family
          ['fontsize', ['fontsize']],              // Font size
          ['color', ['color']],                    // Font/text/background color
          ['para', ['ul', 'ol', 'paragraph']],     // Lists & paragraph formatting
          ['table', ['table']],                    // Table/grid
          ['insert', ['link', 'picture', 'video']],// Insert media
          ['view', ['fullscreen', 'codeview', 'help']] // View options
        ],
        fontNames: GOOGLE_FONTS,
        fontSizes: ['8', '10', '12', '14', '16', '18', '24', '36', '48', '64', '82', '150'],
        styleTags: ['p', 'blockquote', 'pre', 'h1', 'h2', 'h3', 'h4'],
        placeholder: 'Enter paragraph text...',
        dialogsInBody: true
      });
      
      //now load  your code if you have any
      if (field?.code) {
      $(`#${id}-editor`).summernote('code', field.code);
      }
    }

     // --- NEW: build canonical fieldObj for the registry
     const fieldObj = {
         id,
         type,
         label: labelText,
         colSize,
         required: !!isRequired,
         placeholder,
         maxlength,
         title,           // tooltip/help
         rows,
         styles: { ...(styles || {}) },
         options: Array.isArray(field.options) ? field.options : []
     };

     // --- NEW: registry + dataset on the wrapper
     config.registerField(fieldObj);

     const fieldEl = wrapper; // this is your DOM element for the field
     fieldEl.dataset.fieldId = id;
     fieldEl.dataset.type    = type || '';
     fieldEl.dataset.label   = labelText || '';
     fieldEl.dataset.colSize = String(colSize || 12);

     const meta = config.getField(id); // or just use your freshly built fieldObj
     config.applyStyles(wrapper, meta);


     // If you keep a JSON blob on the node, keep it in sync with the canonical obj
     if (!config.isPublic) {
         fieldEl.dataset.fieldConfig = JSON.stringify(fieldObj);
     }

     // --- append to the proper container (.row-fields if you use it)
     const rowFields = row.querySelector('.row-fields') || row;
     rowFields.appendChild(fieldEl);

  this.initSortable();
     markDirtyAndEmit('fieldChanged', { action: 'add', id });

},

    updateField: function (id, patch) {
        const el = document.getElementById(id);
        if (!el) return;

        // 1) Merge into canonical registry
        const prev   = this.getField(id) || {};
        const merged = {
            ...prev,
            ...patch,
            styles: { ...(prev.styles || {}), ...(patch.styles || {}) }
        };
        this.registerField(merged);

        // 2) Keep DOM datasets in sync (helps rebuildFromDOM + debugging)
        el.dataset.fieldId = id;
        if (merged.type    != null) el.dataset.type    = String(merged.type);
        if (merged.label   != null) el.dataset.label   = String(merged.label);
        if (merged.colSize != null) el.dataset.colSize = String(merged.colSize);

        // 3) (Optional) apply visuals to the DOM immediately
        this.applyStyles && this.applyStyles(el, merged);

        // 4) mark dirty + autosave
        markDirtyAndEmit('fieldChanged', { action: 'update', id });
    },

    applyStyles: function (el, meta) {
        if (!el || !meta) return;
        const s = meta.styles || {};
        const isPublic = !!config.isPublic;
        const type = meta.type || el.dataset.type;

        // ---------- LABEL ----------
        const labelEl = el.querySelector('label.form-label');
        if (labelEl) {
            if (meta.label != null) labelEl.textContent = meta.label;
            if (s.labelColor)  labelEl.style.color = s.labelColor;
            if (s.fontFamily)  labelEl.style.fontFamily = s.fontFamily;
            if (s.fontSize) {
                const size = ('' + s.fontSize).match(/px|rem|em$/) ? s.fontSize : (s.fontSize + 'px');
                labelEl.style.fontSize = size;
            }
        }

        // ---------- HEADER ----------
        if (type === 'header') {
            const h = el.querySelector('h1,h2,h3,h4,h5,h6');
            if (h) {
                if (s.labelColor) h.style.color = s.labelColor;
                if (s.fontFamily) h.style.fontFamily = s.fontFamily;
                if (s.fontSize) {
                    const size = ('' + s.fontSize).match(/px|rem|em$/) ? s.fontSize : (s.fontSize + 'px');
                    h.style.fontSize = size;
                }
            }
        }

        // ---------- PARAGRAPH (public render uses .mka-paragraph) ----------
        if (type === 'paragraph') {
            const body = el.querySelector('.mka-paragraph');
            if (body) {
                if (s.fontFamily) body.style.setProperty('--mka-para-font', s.fontFamily);
                if (s.fontSize) {
                    const size = ('' + s.fontSize).match(/px|rem|em$/) ? s.fontSize : (s.fontSize + 'px');
                    body.style.setProperty('--mka-para-size', size);
                }
                if (s.color) body.style.setProperty('--mka-para-color', s.color);

                // inline fallbacks (in case CSS file isn’t loaded yet)
                if (s.fontFamily) body.style.fontFamily = s.fontFamily;
                if (s.fontSize)   body.style.fontSize   = ('' + s.fontSize).match(/px|rem|em$/) ? s.fontSize : (s.fontSize + 'px');
                if (s.color)      body.style.color      = s.color;
            }
        }

        // ---------- INPUT / CONTROL AREA ----------
        // For checkbox/radio groups, do NOT treat individual inputs like text fields.
        const isChoiceGroup = (type === 'checkbox' || type === 'radio');

        if (!isChoiceGroup) {
            const inputEl = el.querySelector('input, textarea, select');
            if (inputEl) {
                // Content attributes
                if (meta.placeholder != null) inputEl.placeholder = meta.placeholder;
                if (meta.tooltip     != null) inputEl.title       = meta.tooltip;

                // Maxlength applies only to text-ish controls
                const tag = inputEl.tagName.toLowerCase();
                const inputType = (inputEl.getAttribute('type') || '').toLowerCase();
                const textish = (tag === 'textarea') ||
                    (tag === 'input' && ['text','email','tel','number','password','search','url'].includes(inputType));
                if (textish) {
                    if (meta.maxlength != null) inputEl.maxLength = meta.maxlength || 0;
                } else {
                    // avoid stale maxlength on non-text controls
                    inputEl.removeAttribute('maxlength');
                }

                // Required flag
                if (meta.required === true)  inputEl.setAttribute('required','required');
                if (meta.required === false) inputEl.removeAttribute('required');

                // Visuals
                if (s.color)           inputEl.style.color = s.color;
                if (s.backgroundColor) inputEl.style.backgroundColor = s.backgroundColor;
                if (s.borderColor)     inputEl.style.borderColor = s.borderColor;
                if (s.fontFamily)      inputEl.style.fontFamily = s.fontFamily;
                if (s.fontSize) {
                    const size = ('' + s.fontSize).match(/px|rem|em$/) ? s.fontSize : (s.fontSize + 'px');
                    inputEl.style.fontSize = size;
                }
            }
        } else {
            // Checkbox/Radio container-level visuals and safety
            const boxInputs = el.querySelectorAll('input[type="checkbox"], input[type="radio"]');
            boxInputs.forEach(inp => {
                // Never keep text-input-only attributes on choice inputs
                ['placeholder','maxlength','title','readonly','aria-readonly'].forEach(a => inp.removeAttribute(a));
                inp.readOnly = false;

                // Optional accent color if provided via styles.color or a dedicated accentColor
                const accent = s.accentColor || s.borderColor || s.color;
                if (accent) inp.style.accentColor = accent;
            });
        }

        // ---------- WRAPPER VISUALS ----------
        // Apply container colors if present (works for both public/editor cards)
        const inner = el.querySelector('.border, .card, .shadow-sm') || el;
        if (s.backgroundColor) inner.style.backgroundColor = s.backgroundColor;
        if (s.borderColor)     inner.style.borderColor     = s.borderColor;

        // ---------- BOOTSTRAP COL SIZE ----------
        if (meta.colSize) {
            this.setColSize && this.setColSize(el, meta.colSize);
            el.dataset.colSize = String(meta.colSize);
        }
    },



    setColSize: function (el, colSize) {
        // remove any existing col-md-* and apply the one we want
        el.className = el.className
            .split(/\s+/)
            .filter(c => !/^col-(sm|md|lg|xl|xxl)-\d+$/.test(c) && !/^col-\d+$/.test(c))
            .join(' ');
        el.classList.add(`col-md-${colSize}`);
    },

    resizeField: function (id, step, increase = false) {
    const el = document.getElementById(id);
    const classList = el.classList;
    const colClass = [...classList].find(c => c.startsWith('col-md-'));
    let current = parseInt(colClass.split('-')[2]);
    classList.remove(colClass);
    const MIN_COLS = 3;
    let next = increase ? Math.min(current + step, 12) : Math.max(current - step, MIN_COLS);
    classList.add(`col-md-${next}`);
    el.dataset.colSize = next;
    markDirtyAndEmit('rowChanged', {action:'resize', id})
  },
  
  formConfig: function () {
      
     
      const formTitle = document.querySelector('#form-title').textContent;
      $('#formTitle').val(formTitle);

      // Preload saved values if available
      $('#formRecipients').val(config.formRecipients || '');
      $('#formSlug').val(config.formSlug || '');
      $('#formSubmitMessage').val(config.formSubmitMessage || '');

      $('#formConfigModal').modal('show');
  },
  
  

  
duplicateElement: function (id) {
  const original = document.getElementById(id);
  if (!original) return;

  const row = original.closest('.sortable-row');
  if (!row) return;

  // ✅ Generate new unique ID
  const newId = `field-${config.fieldCounter++}`;

  // ✅ Clone and assign new ID to outer wrapper
  const clone = original.cloneNode(true);
  clone.id = newId;
  clone.dataset.colSize = original.dataset.colSize || 4;

  // ✅ Update all `data-field-id` attributes inside clone
  clone.querySelectorAll('[data-field-id]').forEach(btn => {
    btn.setAttribute('data-field-id', newId);
  });

  // ✅ If you’re storing config as JSON in dataset, update it (optional)
  clone.dataset.fieldConfig = '{}';

  // ✅ Update label `for` attribute if applicable
  const label = clone.querySelector('label');
  if (label && label.getAttribute('for')) {
    label.setAttribute('for', `${newId}-input`);
  }

  // ✅ Update input field ID (so label points correctly)
  const input = clone.querySelector('input, textarea, select');
  if (input) {
    input.id = `${newId}-input`;
    input.name = `${newId}`;
  }

  // ✅ Append to row or new row if needed
  const colClass = [...original.classList].find(c => c.startsWith('col-md-'));
  const colWidth = parseInt(colClass.split('-')[2]);
  let totalCols = 0;

  row.querySelectorAll('[class*="col-md-"]').forEach(col => {
    const cls = [...col.classList].find(c => c.startsWith('col-md-'));
    if (cls) totalCols += parseInt(cls.split('-')[2]);
  });

  if (totalCols + colWidth <= 12) {
    row.appendChild(clone);
  } else {
    this.addRow();
    const newRow = document.getElementById(config.activeRowId);
    newRow.appendChild(clone);
  }

  // ✅ Re-init sortable drag logic
  this.initSortable();
  markDirtyAndEmit('fieldChanged', {action:'duplicate', id:newId});
},




  
  fillWidth: function (id) {
  const el = document.getElementById(id);
  if (!el) return;

  const row = el.parentElement;
  if (!row || !row.classList.contains('sortable-row')) return;

  // Calculate how many columns are already used
  let totalUsed = 0;
  Array.from(row.children).forEach(child => {
    const cls = [...child.classList].find(c => c.startsWith('col-md-'));
    if (cls && child.id !== id) {
      totalUsed += parseInt(cls.split('-')[2]);
    }
  });

  // Clamp to minimum of 1 column, max of 12
  const remaining = Math.max(1, 12 - totalUsed);

  // Set new column width
  const currentClass = [...el.classList].find(c => c.startsWith('col-md-'));
  if (currentClass) el.classList.remove(currentClass);
  el.classList.add(`col-md-${remaining}`);
  el.dataset.colSize = remaining;
      markDirtyAndEmit('rowChanged', {action:'resize', id})
},

editField: function (id) {
  const el = document.getElementById(id);

  if (!el) return;
  document.activeElement?.blur();
  const configData = JSON.parse(el.dataset.fieldConfig || '{}');
  console.log('THIS IS CONFIG DATA ',configData);
  const styles = configData.styles || {};
  const label = el.querySelector('label');
  const input = el.querySelector('input');
  const textarea = el.querySelector('textarea');
  const select = el.querySelector('select');
  const img = el.querySelector('img');
  const canvas = el.querySelector('canvas');
  const p = el.querySelector('p');
  const header = el.querySelector('h4');
  const isMultiSelect = select?.multiple;
  const wrapper = el.closest('.field-wrapper') || el;
  

  const getFont = (el) => {
      if (!el) return '';
      try {
        //this keeps coming back with open sans if blank...wrong....>>>let computedFont = getComputedStyle(el).fontFamily.split(',')[0].replace(/['"]/g, '').trim();
        let font = config.defaultLabelFont;
         return font;
      } catch (e) {
        return '';
      }
    };
  const getColor = (el, prop = 'color') => {
        if (!el) return '';
        try {
            let color = getComputedStyle(el)[prop];
            console.log('Computed color ',color)
            if (color === 'rgb(0, 0, 0)' || color === '#000000') {
                return config.defaultLabelColor;
            }
            return color;
        } catch (e) {
            return config.defaultLabelColor;
        }
    }
  
  

  if (textarea) {
    $('#textareaConfigModal').removeAttr('inert');
    $('#textareaConfigModal').off('shown.bs.modal').on('shown.bs.modal', function () {
      requestAnimationFrame(() => {
    $('#textareaLabel').val(label?.textContent || '');
    $('#textareaInputRequired').prop('checked', configData.required === true);
    $('#textareaPlaceholder').val(configData.placeholder || textarea.placeholder);
    $('#textareaTooltip').val(configData.tooltip || textarea.title);
    $('#textareaRows').val(configData.rows || textarea.rows);
    $('#textareaFont').val(styles.fontFamily || getFont(textarea));

    config.pickrs['textareaHeadingColor']?.setColor(styles.labelColor || getColor(label));
    config.pickrs['textareaTextColor']?.setColor(styles.color || getColor(textarea));
    config.pickrs['textareaBgColor']?.setColor(styles.backgroundColor || textarea.style.backgroundColor || '#ffffff');
    config.pickrs['textareaBorderColor']?.setColor(styles.borderColor || textarea.style.borderColor || '#cacaca');
      })
    })
    config.currentEditingId = id;
    $('#textareaConfigModal').modal('show').data('target-field-id', id);
    return;
  }
  if (input?.type === 'text') {
  
  $('#textInputConfigModal').removeAttr('inert');      
  $('#textInputConfigModal').off('shown.bs.modal').on('shown.bs.modal', function () {
      requestAnimationFrame(() => {
  
  $('#textInputLabel').val(label?.textContent || '');
  
  $('#textInputRequired').prop('checked', configData.required === true);
  
  $('#textInputPlaceholder').val(configData.placeholder || input.placeholder || '');
  $('#textInputTooltip').val(configData.tooltip || input.title || '');
  $('#textInputMaxLength').val(configData.maxlength ?? (input.maxLength > 0 ? input.maxLength : 35));
  $('#textInputFont').val(styles.fontFamily || getFont(input));

  config.pickrs['textInputHeadingColor']?.setColor(styles.labelColor || getColor(label));
  config.pickrs['textInputTextColor']?.setColor(styles.color || getColor(input));
  config.pickrs['textInputBgColor']?.setColor(styles.backgroundColor || input.style.backgroundColor || '#ffffff');
  config.pickrs['textInputBorderColor']?.setColor(styles.borderColor || input.style.borderColor || '#111111');
   })
  });
   config.currentEditingId = id;
   $('#textInputConfigModal').modal('show').data('target-field-id', id);
   
   
  return;
}

  if (img) {
     $('#imageConfigModal').removeAttr('inert');  
    $('#imageConfigModal').off('shown.bs.modal').on('shown.bs.modal', function () {
      requestAnimationFrame(() => {
    const caption = el.querySelector('.image-caption');
    $('#imageURL').val(img.src.startsWith('http') || img.src.startsWith('data:') ? img.src : '');
    $('#imageUpload').val('');
    $('#imageAlign').val(styles.textAlign || img.style.textAlign || img.style.float || 'center');
    $('#imageWidth').val(styles.width || img.style.width?.replace('px', '') || '');
    $('#imageHeight').val(styles.height || img.style.height?.replace('px', '') || '');
    $('#imageCaptionText').val(configData.caption || caption?.textContent || '');
    $('#imageCaptionFont').val(styles.fontFamily || caption?.style.fontFamily || '');
    $('#imageCaptionSize').val(styles.fontSize || caption?.style.fontSize?.replace('px', '') || '');
    config.pickrs['imageCaptionColor']?.setColor(caption?.style.color || '#000000');
    config.currentEditingId = id;
      })
    })
    config.currentEditingId = id;
    $('#imageConfigModal').modal('show').data('target-field-id', id);
    return;
  }

  if (canvas) {
    $('#signatureConfigModal').removeAttr('inert');  
    $('#signatureConfigModal').off('shown.bs.modal').on('shown.bs.modal', function () {
      requestAnimationFrame(() => {
    $('#signatureLabel').val(label?.textContent || '');
    $('#signatureInputRequired').prop('checked', configData.required === true);
    $('#signatureFont').val(styles.fontFamily || getFont(input));
    $('#signatureFontSize').val(styles.fontSize || parseInt(getComputedStyle(label).fontSize) || '');
    config.pickrs['signatureFontColor']?.setColor(getColor(label));
    config.currentEditingId = id;
      })
    })
    
    config.currentEditingId = id;
    $('#signatureConfigModal').modal('show').data('target-field-id', id);
    return;
  }

  if (header) {
     $('#headerConfigModal').removeAttr('inert'); 
     $('#headerConfigModal').off('shown.bs.modal').on('shown.bs.modal', function () {
         requestAnimationFrame(() => {
        const fieldId = $(this).data('target-field-id');
        $('#headerText').val(header.textContent.trim());
        $('#headerFont').val(styles.fontFamily || getFont(header));
        $('#headerFontSize').val(parseInt(getComputedStyle(header).fontSize) || 16);
        $('#headerTextAlign').val(getComputedStyle(header).textAlign || 'left');
        config.pickrs['headerFontColor']?.setColor(styles.color || getColor(header));
        
         })
    });

    config.currentEditingId = id;
   $('#headerConfigModal').data('target-field-id', id).modal('show');
   
    return;
  }

  if (p) {
    $('#paragraphConfigModal').removeAttr('inert'); 
    $('#paragraphConfigModal').off('shown.bs.modal').on('shown.bs.modal', function () {
         requestAnimationFrame(() => {
    if (!$('#paragraphEditor').hasClass('summernote-initialized')) {
      $('#paragraphEditor').summernote({ height: 200 }).addClass('summernote-initialized');
    }
    $('#paragraphEditor').summernote('code', p.innerHTML);
    $('#paragraphFont').val(styles.fontFamily || getFont(input));
    config.pickrs['paragraphFontColor']?.setColor(getColor(p));
    
         })
    })
    
    config.currentEditingId = id;
    $('#paragraphConfigModal').modal('show').data('target-field-id', id);
    return;
  }

  if (input?.type === 'file') {
      console.log('File upload config data ',configData)
    $('#fileUploadConfigModal').removeAttr('inert');
     
    $('#fileUploadConfigModal').off('shown.bs.modal').on('shown.bs.modal', function () {
         requestAnimationFrame(() => {
    $('#fileUploadLabel').val(label?.textContent || '');
    $('#fileUploadInputRequired').prop('checked', configData.required === true);
    $('#fileUploadTooltip').val(configData.tooltip || input.title || '');
    $('#fileUploadFont').val(styles.fontFamily || getFont(label));
    config.pickrs['fileUploadHeadingColor']?.setColor(styles.color || label?.style.color || '#000000');
    config.pickrs['fileUploadBorderColor']?.setColor(styles.borderColor || input.style.borderColor || '#000000');
         })
    })
    
    config.currentEditingId = id;
    $('#fileUploadConfigModal').modal('show').data('target-field-id', id);
    return;
  }

  if (input?.type === 'number') {
      
    $('#numberInputConfigModal').removeAttr('inert'); 
    $('#numberInputConfigModal').off('shown.bs.modal').on('shown.bs.modal', function () {
         requestAnimationFrame(() => {
    $('#numberInputLabel').val(label?.textContent || '');
    $('#numberInputRequired').prop('checked', configData.required === true);
    $('#numberInputPlaceholder').val(configData.placeholder || input.placeholder || '');
    $('#numberInputTooltip').val(configData.tooltip || input.title || '');
    $('#numberInputMin').val(configData.min || input.min || '');
    $('#numberInputMax').val(configData.max || input.max || '');
    $('#numberInputStep').val(configData.step || input.step || '');
    $('#numberInputFont').val(styles.fontFamily || getFont(input));
    config.pickrs['numberInputHeadingColor']?.setColor(styles.labelColor || getColor(label));
    config.pickrs['numberInputTextColor']?.setColor(styles.color || getColor(input));
    config.pickrs['numberInputBgColor']?.setColor(styles.backgroundColor || input.style.backgroundColor || '#fcfcfc');
    config.pickrs['numberInputBorderColor']?.setColor(styles.borderColor || input.style.borderColor || '#000000');
         })
    })
    
    config.currentEditingId = id;
    $('#numberInputConfigModal').modal('show').data('target-field-id', id);
    return;
  }
  
  if (input?.type === 'radio') {
  $('#radioInputConfigModal').removeAttr('inert');
  $('#radioInputConfigModal').off('shown.bs.modal').on('shown.bs.modal', function () {
         requestAnimationFrame(() => {
  $('#radioInputLabel').val(label?.textContent || '');
  $('#radioInputRequired').prop('checked', configData.required === true);
  $('#radioInputTooltip').val(configData.tooltip || input.title || '');
  $('#radioInputFont').val(styles.fontFamily || getFont(input));

  // Rebuild options from all same-name radio buttons in the group
  const radioName = input.name;
  const radioGroup = document.querySelectorAll(`input[type="radio"][name="${radioName}"]`);
  const optionValues = [...radioGroup].map(radio => radio.value);
  $('#radioInputOptions').val(optionValues.join('\n'));

  config.pickrs['radioInputHeadingColor']?.setColor(styles.labelColor || getColor(label));
  config.pickrs['radioInputTextColor']?.setColor(styles.color || getColor(input));
  config.pickrs['radioInputBgColor']?.setColor(styles.backgroundColor || input.style.backgroundColor || '#ffffff');
  config.pickrs['radioInputBorderColor']?.setColor(styles.borderColor || input.style.borderColor || '#000000');
         })
  })
  config.currentEditingId = id;
  $('#radioInputConfigModal').modal('show').data('target-field-id', id);
  return;
}

if (input?.type === 'checkbox') {
     console.log('we are editing checkbox')
  $('#checkboxInputConfigModal').removeAttr('inert');
  
  $('#checkboxInputConfigModal').off('shown.bs.modal').on('shown.bs.modal', function () {
         requestAnimationFrame(() => {
  const label = el.querySelector('label');               
  $('#checkboxInputLabel').val(label?.textContent || ''); 
  $('#checkboxInputRequired').prop('checked', configData.required === true);
  $('#checkboxInputTooltip').val(configData.tooltip || input.title || '');
  $('#checkboxInputFont').val(styles.fontFamily || getComputedStyle(input).fontFamily);

  // Extract checkbox options from all matching checkboxes
  const checkboxesInWrapper = el.querySelectorAll('input[type="checkbox"]');
  const optionValues = [...checkboxesInWrapper].map(cb => cb.value);
  
  $('#checkboxInputOptions').val(optionValues.join('\n'));

  config.pickrs['checkboxInputHeadingColor']?.setColor(styles.labelColor || getComputedStyle(label).color);
  config.pickrs['checkboxInputTextColor']?.setColor(styles.color || getComputedStyle(input).color);
  config.pickrs['checkboxInputBgColor']?.setColor(styles.backgroundColor || input.style.backgroundColor || '#ffffff');
  config.pickrs['checkboxInputBorderColor']?.setColor(styles.borderColor || input.style.borderColor || '#000000');
         })
  })

  config.currentEditingId = id;
  
  
  $('#checkboxInputConfigModal').modal('show').data('target-field-id', id);
  return;
}

  if (input?.type === 'email') {
   
    $('#emailInputConfigModal').removeAttr('inert');
  
    $('#emailInputConfigModal').off('shown.bs.modal').on('shown.bs.modal', function () {
         requestAnimationFrame(() => {
    
    $('#emailInputLabel').val(label?.textContent || '');
    $('#emailInputRequired').prop('checked', configData.required === true);
    $('#emailInputPlaceholder').val(configData.placeholder || input.placeholder || '');
    $('#emailInputTooltip').val(configData.tooltip || input.title || '');
    $('#emailInputMaxLength').val(configData.maxlength ?? (input.maxLength > 0 ? input.maxLength : 48));
    $('#emailInputFont').val(styles.fontFamily || getFont(input));
    config.pickrs['emailInputHeadingColor']?.setColor(styles.labelColor || getColor(label));
    config.pickrs['emailInputTextColor']?.setColor(styles.color || getColor(input));
    config.pickrs['emailInputBgColor']?.setColor(styles.backgroundColor || input.style.backgroundColor || '#ffffff');
    config.pickrs['emailInputBorderColor']?.setColor(styles.borderColor || input.style.borderColor || '#000000');
         })
    })
    
    config.currentEditingId = id;
    $('#emailInputConfigModal').modal('show').data('target-field-id', id);
    return;
  }
  
  
 
  if (input?.type === 'tel') {
    $('#phoneInputConfigModal').removeAttr('inert');
  
    $('#phoneInputConfigModal').off('shown.bs.modal').on('shown.bs.modal', function () {
         requestAnimationFrame(() => {
    $('#phoneInputConfigModal').modal('show').data('target-field-id', id);
    $('#phoneInputLabel').val(label?.textContent || '');
    $('#phoneInputRequired').prop('checked', configData.required === true);
    $('#phoneInputPlaceholder').val(configData.placeholder || input.placeholder || '');
    $('#phoneInputTooltip').val(configData.tooltip ||input.title || '');
    $('#phoneInputMaxLength').val(input.maxLength > 0 ? input.maxLength : '');
    $('#phoneInputFont').val(styles.fontFamily || getFont(input));
    config.pickrs['phoneInputHeadingColor']?.setColor(getColor(label));
    config.pickrs['phoneInputTextColor']?.setColor(getColor(input));
    config.pickrs['phoneInputBgColor']?.setColor(input.style.backgroundColor || '#ffffff');
    config.pickrs['phoneInputBorderColor']?.setColor(input.style.borderColor || '#000000');
         })
    })
    
    config.currentEditingId = id;
    $('#phoneInputConfigModal').modal('show').data('target-field-id', id);
    return;
  }

  if (input?.type === 'date') {
    $('#datePickerConfigModal').removeAttr('inert');
    $('#datePickerConfigModal').off('shown.bs.modal').on('shown.bs.modal', function () {
         requestAnimationFrame(() => {
     $('#datePickerInputLabel').val(label?.textContent || '');
     $('#datePickerInputRequired').prop('checked', configData.required === true);
    $('#datePickerPlaceholder').val(configData.placeholder || input.placeholder || '');
    $('#datePickerTooltip').val(configData.tooltip ||input.title || '');
    $('#datePickerFont').val(styles.fontFamily || getFont(input));
    config.pickrs['datePickerHeadingColor']?.setColor(getColor(label));
    config.pickrs['datePickerTextColor']?.setColor(getColor(input));
    config.pickrs['datePickerBgColor']?.setColor(input.style.backgroundColor || '#ffffff');
    config.pickrs['datePickerBorderColor']?.setColor(input.style.borderColor || '#ced4da');
         })
    })
    
    config.currentEditingId = id;
    $('#datePickerConfigModal').modal('show').data('target-field-id', id);
    return;
  }

  if (select && isMultiSelect) {
       
    $('#multiSelectConfigModal').removeAttr('inert');
    $('#multiSelectConfigModal').off('shown.bs.modal').on('shown.bs.modal', function () {
         requestAnimationFrame(() => {
    
    $('#multiSelectLabel').val(label?.textContent || '');
    $('#multiSelectTooltip').val(configData.tooltip || select.title || '');
    $('#multiSelectInputRequired').prop('checked', configData.required === true);
    $('#multiSelectFont').val(styles.fontFamily || getFont(select));
    $('#multiSelectOptions').val([...select.options].map(o => o.textContent).join('\n'));
    config.pickrs['multiSelectHeadingColor']?.setColor(getColor(label));
    config.pickrs['multiSelectTextColor']?.setColor(getColor(select));
    config.pickrs['multiSelectBgColor']?.setColor(select.style.backgroundColor || '#ffffff');
    config.pickrs['multiSelectBorderColor']?.setColor(select.style.borderColor || '#000000');
         })
    })
    
    config.currentEditingId = id;
    $('#multiSelectConfigModal').modal('show').data('target-field-id', id);
    return;
  }


  if (select && !isMultiSelect) {
      //console.log(select.options)
    $('#singleSelectConfigModal').removeAttr('inert');
    $('#singleSelectConfigModal').off('shown.bs.modal').on('shown.bs.modal', function () {
         requestAnimationFrame(() => {
    
    $('#singleSelectLabel').val(label?.textContent || '');
    $('#singleSelectInputRequired').prop('checked', configData.required === true);
    $('#singleSelectTooltip').val(configData.tooltip || select.title || '');
    $('#singleSelectFont').val(styles.fontFamily || getFont(select));
    $('#singleSelectOptions').val([...select.options].map(o => o.textContent).join('\n'));
    config.pickrs['singleSelectHeadingColor']?.setColor(getColor(label));
    config.pickrs['singleSelectTextColor']?.setColor(getColor(select));
    config.pickrs['singleSelectBgColor']?.setColor(select.style.backgroundColor || '#ffffff');
    config.pickrs['singleSelectBorderColor']?.setColor(select.style.borderColor || '#000000');
         })
    })
    
    config.currentEditingId = id;
    $('#singleSelectConfigModal').modal('show').data('target-field-id', id);
    return;
  }

  // Default fallback: basic text input
  $('#textInputConfigModal').removeAttr('inert');
  config.currentEditingId = id;
  $('#textInputConfigModal').modal('show').data('target-field-id', id);
},





    saveImageFieldConfig: async function () {
        const id = $('#imageConfigModal').data('target-field-id');
        const wrapper = document.getElementById(id);
        if (!wrapper) return;

        const fieldConfig = JSON.parse(wrapper.dataset.fieldConfig || '{}');
        const img = wrapper.querySelector('img');
        const figure = wrapper.querySelector('figure') || document.createElement('figure');
        const captionEl = wrapper.querySelector('figcaption') || document.createElement('figcaption');

        // Inputs
        const url        = ($('#imageURL').val() || '').trim();
        const file       = $('#imageUpload')[0]?.files?.[0] || null;
        const widthVal   = ($('#imageWidth').val() || '').trim();
        const heightVal  = ($('#imageHeight').val() || '').trim();
        const align      = $('#imageAlign').val() || 'center';
        const captionTxt = ($('#imageCaptionText').val() || '').trim();
        const capFont    = $('#imageCaptionFont').val() || '';
        const capSize    = $('#imageCaptionSize').val() || '';
        const capColor   = config.pickrs['imageCaptionColor']?.getColor()?.toHEXA()?.toString() || '#000000';

        // --- Resolve the image src (prefer existing filename-based URL if present)
        // If user picked a file, your upload pipeline elsewhere should set data-filename on <img>.
        // Here we just keep the final /formbuilder/image.php?... URL if we have filename, else fall back to typed URL.
        let finalSrc = img?.src || '';
        if (file) {
            // We don't store base64; rely on your upload step to set data-filename.
            const filename = img?.dataset?.filename;
            const formUuid = config.form_uuid;
            if (filename && formUuid) {
                finalSrc = `/formbuilder/image.php?form_uuid=${formUuid}&field_id=${id}&file=${filename}`;
                if (img) img.src = finalSrc; // reflect in DOM
            }
        } else if (url) {
            finalSrc = url;
            if (img) img.src = finalSrc;
        }

        // --- Apply sizing to DOM (optional visual sync)
        if (img) {
            if (widthVal)  img.style.width  = `${parseInt(widthVal,10)}px`; else img.style.removeProperty('width');
            if (heightVal) img.style.height = `${parseInt(heightVal,10)}px`; else img.style.removeProperty('height');

            // Alignment via margins
            img.style.display = 'block';
            img.style.margin  = (align === 'center') ? '0 auto'
                : (align === 'right')  ? '0 0 0 auto'
                    :                        '0 auto 0 0';
        }

        // --- Caption DOM sync (optional)
        captionEl.textContent    = captionTxt;
        captionEl.style.fontFamily = capFont || '';
        captionEl.style.fontSize   = capSize ? `${parseInt(capSize,10)}px` : '';
        captionEl.style.color      = capColor;

        if (!figure.contains(img) && img) {
            figure.innerHTML = '';
            figure.appendChild(img);
        }
        if (captionTxt && !figure.contains(captionEl)) {
            figure.appendChild(captionEl);
        } else if (!captionTxt && figure.contains(captionEl)) {
            figure.removeChild(captionEl);
        }
        if (!wrapper.contains(figure)) {
            wrapper.querySelector('.border')?.appendChild(figure);
        }

        // --- Build the PATCH (canonical data that will be saved)
        const patch = {
            id,
            type: 'img',                  // you use 'img' elsewhere; keep it consistent
            label: '',                    // images usually have no label; keep empty
            src: finalSrc || '',          // persist the resolved URL
            caption: captionTxt,          // store caption text
            styles: {
                // store numeric values as strings or numbers; either way we render
                width:  widthVal || '',
                height: heightVal || '',
                textAlign: align,           // align choice
                fontFamily: capFont || '',  // caption font
                fontSize: capSize || '',    // caption size (raw number is fine in your schema)
                color: capColor || '#000000'
            }
        };

        // Merge into registry + sync datasets + trigger autosave machinery
        config.updateField(id, patch);

        // If you still keep a per-element fieldConfig blob on the wrapper, refresh it (optional)
        const merged = Object.assign({}, fieldConfig, patch, {
            styles: Object.assign({}, fieldConfig.styles || {}, patch.styles || {})
        });
        wrapper.dataset.fieldConfig = JSON.stringify(merged);

        // Close modal and kick a manual save (safe even with autosave)
        $('#imageConfigModal').attr('inert', '');
        $('#imageConfigModal').modal('hide');
        this.saveForm();
    },


    saveSignatureConfig: function () {
        const id = $('#signatureConfigModal').data('target-field-id');
        const wrapper = document.getElementById(id);
        if (!wrapper) return;

        const fieldConfig = JSON.parse(wrapper.dataset.fieldConfig || '{}');

        // Grab DOM nodes
        const labelEl     = wrapper.querySelector('label.form-label');
        const canvasEl    = wrapper.querySelector('canvas'); // your signature pad canvas
        const hiddenInput = document.getElementById(`${id}-hidden`); // where required lives

        // Modal values
        const labelText = ($('#signatureLabel').val() || '').trim();
        const font      = $('#signatureFont').val() || (config.defaultLabelFont || '');
        const fontSize  = ($('#signatureFontSize').val() || '').toString().trim();
        const required  = $('#signatureInputRequired').is(':checked');
        const color     = config.pickrs['signatureFontColor']?.getColor()?.toHEXA()?.toString() || '#000000';

        // ----- DOM: visual updates so user sees changes immediately
        if (labelEl) {
            labelEl.textContent = labelText || '';
            labelEl.style.fontFamily = font || '';
            labelEl.style.color = color || '';
            if (fontSize) {
                const px = fontSize.match(/^\d+$/) ? `${fontSize}px` : fontSize;
                labelEl.style.fontSize = px;
            } else {
                labelEl.style.removeProperty('font-size');
            }
        }

        // Required toggle on the hidden input (the submit target)
        if (hiddenInput) {
            if (required) hiddenInput.setAttribute('required', 'required');
            else hiddenInput.removeAttribute('required');
        }

        // You may have stroke color/width for the canvas; if you style it via CSS, skip.
        // If you keep in styles, it's fine to store color & font for the label only.

        // ----- PATCH into canonical registry
        const patch = {
            id,
            type: 'signature',
            label: labelText,
            required: !!required,
            // include any signature-specific options here if you add them later (e.g., line width)
            styles: {
                labelColor: color || '#000000',
                fontFamily: font || (config.defaultLabelFont || ''),
                fontSize: fontSize || '' // raw number/string; your renderer can add 'px' when applying
            }
        };

        // Merge & emit
        config.updateField(id, patch);

        // Optional: keep wrapper.dataset.fieldConfig in sync (handy during rebuildFromDOM)
        const merged = Object.assign({}, fieldConfig, patch, {
            styles: Object.assign({}, fieldConfig.styles || {}, patch.styles || {})
        });
        wrapper.dataset.fieldConfig = JSON.stringify(merged);

        // Close + save
        $('#signatureConfigModal').attr('inert', '');
        $('#signatureConfigModal').modal('hide');
        this.saveForm();
    },

    saveHeaderConfig: function () {
        const id =
            $('#headerConfigModal').data('target-field-id') ||
            this.currentEditingId;
        const el = document.getElementById(id);
        if (!el) return;

        const fieldConfig = JSON.parse(el.dataset.fieldConfig || '{}');

        // Modal values
        const text      = ($('#headerText').val() || '').trim();
        const font      = $('#headerFont').val() || (config.defaultLabelFont || '');
        const fontSize  = ($('#headerFontSize').val() || '').toString().trim();
        const textAlign = $('#headerTextAlign').val() || 'left';
        const fontColor = this.pickrs['headerFontColor']?.getColor()?.toHEXA()?.toString() || '#000000';

        // DOM updates for immediate feedback
        let headerEl = el.querySelector('h4');
        if (!headerEl) {
            // if for some reason it wasn't there, create it inside the card
            const card = el.querySelector('.border') || el;
            headerEl = document.createElement('h4');
            card.prepend(headerEl);
        }
        headerEl.textContent = text;
        headerEl.style.fontFamily = font || '';
        headerEl.style.textAlign  = textAlign || 'left';
        if (fontSize) {
            headerEl.style.fontSize = fontSize.match(/^\d+$/) ? `${fontSize}px` : fontSize;
        } else {
            headerEl.style.removeProperty('font-size');
        }
        headerEl.style.color = fontColor;

        // Patch into canonical registry
        const patch = {
            id,
            type: 'h4',                 // keep consistent with your addField('h4'/'header')
            label: text,                // header text is stored as label
            required: false,
            styles: {
                fontFamily: font,
                fontSize:  fontSize || '',
                textAlign: textAlign,
                // keep both for consistency with your addField usage
                labelColor: fontColor,
                color:      fontColor
            }
        };
        config.updateField(id, patch);

        // Keep dataset.fieldConfig in sync (useful for rebuildFromDOM)
        const merged = Object.assign({}, fieldConfig, patch, {
            styles: Object.assign({}, fieldConfig.styles || {}, patch.styles || {})
        });
        el.dataset.fieldConfig = JSON.stringify(merged);

        // Close + save
        $('#headerConfigModal').attr('inert', '');
        $('#headerConfigModal').modal('hide');
        this.saveForm();
    },

    saveTextareaConfig: function () {
        const id = $('#textareaConfigModal').data('target-field-id');
        const wrapper = document.getElementById(id);
        if (!wrapper) return;

        const fieldConfig = JSON.parse(wrapper.dataset.fieldConfig || '{}');

        // Read modal values
        const label       = ($('#textareaLabel').val() || '').trim();
        const placeholder = $('#textareaPlaceholder').val() || '';
        const tooltip     = $('#textareaTooltip').val() || '';
        const font        = $('#textareaFont').val() || (config.defaultLabelFont || '');
        const rowsVal     = $('#textareaRows').val();
        const rows        = Number.parseInt(rowsVal, 10) > 0 ? Number.parseInt(rowsVal, 10) : 4;

        const headingColor = config.pickrs['textareaHeadingColor']?.getColor()?.toHEXA()?.toString() || '';
        const textColor    = config.pickrs['textareaTextColor']?.getColor()?.toHEXA()?.toString() || '';
        const bgColor      = config.pickrs['textareaBgColor']?.getColor()?.toHEXA()?.toString() || '';
        const borderColor  = config.pickrs['textareaBorderColor']?.getColor()?.toHEXA()?.toString() || '';

        const required = $('#textareaInputRequired').is(':checked');

        // ---- DOM updates for immediate feedback ----
        const labelEl = wrapper.querySelector('label.form-label') || wrapper.querySelector('label');
        if (labelEl) {
            labelEl.textContent = label;
            if (headingColor) labelEl.style.color = headingColor;
            if (font)         labelEl.style.fontFamily = font;
        }

        const ta = wrapper.querySelector('textarea');
        if (ta) {
            ta.placeholder = placeholder;
            ta.title       = tooltip;
            ta.rows        = rows;
            if (textColor)   ta.style.color           = textColor;
            if (bgColor)     ta.style.backgroundColor = bgColor;
            if (borderColor) ta.style.borderColor     = borderColor;
            if (font)        ta.style.fontFamily      = font;

            if (required) {
                ta.setAttribute('required', 'required');
            } else {
                ta.removeAttribute('required');
            }
        }

        // ---- Patch into canonical registry ----
        const patch = {
            id,
            type: 'textarea',
            label,
            required,
            placeholder,
            tooltip,
            rows,
            styles: {
                fontFamily:     font,
                labelColor:     headingColor,
                color:          textColor,
                backgroundColor:bgColor,
                borderColor:    borderColor
            }
        };
        config.updateField(id, patch);

        // ---- Keep dataset.fieldConfig in sync (useful for rebuildFromDOM) ----
        const merged = Object.assign({}, fieldConfig, patch, {
            styles: Object.assign({}, fieldConfig.styles || {}, patch.styles || {})
        });
        wrapper.dataset.fieldConfig = JSON.stringify(merged);

        // ---- Close + save ----
        $('#textareaConfigModal').attr('inert', '');
        $('#textareaConfigModal').modal('hide');
        this.saveForm();
    },



saveTextInputConfig: function () {
  const id = $('#textInputConfigModal').data('target-field-id');
  const wrapper = document.getElementById(id);
  if (!wrapper) return;
  
  const fieldConfig = JSON.parse(wrapper.dataset.fieldConfig || '{}')
  

  const labelText = $('#textInputLabel').val();
  const placeholder = $('#textInputPlaceholder').val();
  const tooltip = $('#textInputTooltip').val();
  const font = $('#textInputFont').val();
  const maxLength = $('#textInputMaxLength').val();
  const required = $('#textInputRequired').is(':checked');

  const headingColor = config.pickrs['textInputHeadingColor']?.getColor()?.toHEXA().toString();
  const borderColor = config.pickrs['textInputBorderColor']?.getColor()?.toHEXA().toString();
  const textColor = config.pickrs['textInputTextColor']?.getColor()?.toHEXA().toString();
  const bgColor = config.pickrs['textInputBgColor']?.getColor()?.toHEXA().toString();

  // Apply to label
  const labelEl = wrapper.querySelector('label.form-label');
  if (labelEl) {
    labelEl.textContent = labelText;
    if (headingColor) labelEl.style.color = headingColor;
    if (font) labelEl.style.fontFamily = font;
  }

  // Apply to input[type=text]
  const inputEl = wrapper.querySelector('input[type="text"]');
  if (inputEl) {
    inputEl.placeholder = placeholder;
    inputEl.title = tooltip;
    inputEl.maxLength = maxLength || '';
    if (textColor) inputEl.style.color = textColor;
    if (bgColor) inputEl.style.backgroundColor = bgColor;
    if (borderColor) inputEl.style.borderColor = borderColor;
    if (font) inputEl.style.fontFamily = font;
    //update fieldConfig
    
    if (fieldConfig && Object.keys(fieldConfig).length > 0) {
        fieldConfig.tooltip = tooltip
        fieldConfig.required = required
        if (required) {
          inputEl.setAttribute('required', 'required');
        } else {
          inputEl.removeAttribute('required');
        }
    
        fieldConfig.maxlength = maxLength
        fieldConfig.styles.fontFamily=font
        fieldConfig.styles.color = textColor
        fieldConfig.styles.labelColor = headingColor
        fieldConfig.styles.backgroundColor = bgColor
        fieldConfig.styles.borderColor = borderColor
        wrapper.dataset.fieldConfig = JSON.stringify(fieldConfig)
    }
  }


    const patch = {
        id,
        type: 'text',
        label: ($('#textInputLabel').val() || '').trim(),
        required: $('#textInputRequired').is(':checked'),
        placeholder: $('#textInputPlaceholder').val() || '',
        tooltip: $('#textInputTooltip').val() || '',
        maxlength: parseInt($('#textInputMaxLength').val() || '0', 10) || null,
        styles: {
            fontFamily: $('#textInputFont').val() || config.defaultLabelFont || '',
            labelColor: config.pickrs['textInputHeadingColor']?.getColor()?.toHEXA()?.toString() || '',
            color:      config.pickrs['textInputTextColor']?.getColor()?.toHEXA()?.toString() || '',
            backgroundColor: config.pickrs['textInputBgColor']?.getColor()?.toHEXA()?.toString() || '',
            borderColor:     config.pickrs['textInputBorderColor']?.getColor()?.toHEXA()?.toString() || ''
        }
    };
    config.updateField(id, patch);

  $('#textInputConfigModal').attr('inert', '');
  $('#textInputConfigModal').modal('hide');
  
  this.saveForm();
},

    saveNumberInputConfig: function () {
        const id = $('#numberInputConfigModal').data('target-field-id');
        const wrapper = document.getElementById(id);
        if (!wrapper) return;

        const fieldConfig = JSON.parse(wrapper.dataset.fieldConfig || '{}');

        // Read modal values
        const labelText   = ($('#numberInputLabel').val() || '').trim();
        const placeholder = $('#numberInputPlaceholder').val() || '';
        const tooltip     = $('#numberInputTooltip').val() || '';
        const minVal      = $('#numberInputMin').val();
        const maxVal      = $('#numberInputMax').val();
        const stepVal     = $('#numberInputStep').val();
        const font        = $('#numberInputFont').val() || (config.defaultLabelFont || '');
        const required    = $('#numberInputRequired').is(':checked');

        const headingColor = config.pickrs['numberInputHeadingColor']?.getColor()?.toHEXA()?.toString() || '';
        const textColor    = config.pickrs['numberInputTextColor']?.getColor()?.toHEXA()?.toString() || '';
        const bgColor      = config.pickrs['numberInputBgColor']?.getColor()?.toHEXA()?.toString() || '';
        const borderColor  = config.pickrs['numberInputBorderColor']?.getColor()?.toHEXA()?.toString() || '';

        // ---- DOM updates ----
        const labelEl = wrapper.querySelector('label.form-label') || wrapper.querySelector('label');
        if (labelEl) {
            labelEl.textContent = labelText;
            if (headingColor) labelEl.style.color = headingColor;
            if (font)         labelEl.style.fontFamily = font;
        }

        const input = wrapper.querySelector('input[type="number"]') || wrapper.querySelector('input');
        if (input) {
            input.type        = 'number';
            input.placeholder = placeholder;
            input.title       = tooltip;

            // Only set numeric attrs when provided (avoid "null" strings)
            if (minVal !== '')  input.min  = String(minVal);  else input.removeAttribute('min');
            if (maxVal !== '')  input.max  = String(maxVal);  else input.removeAttribute('max');
            if (stepVal !== '') input.step = String(stepVal); else input.removeAttribute('step');

            if (required) input.setAttribute('required', 'required');
            else          input.removeAttribute('required');

            if (textColor)   input.style.color           = textColor;
            if (bgColor)     input.style.backgroundColor = bgColor;
            if (borderColor) input.style.borderColor     = borderColor;
            if (font)        input.style.fontFamily      = font;
        }

        // ---- Patch canonical registry ----
        const patch = {
            id,
            type: 'number',
            label: labelText,
            required,
            placeholder,
            tooltip,
            // Keep these as numbers when possible; null when blank
            min:  (minVal  === '' ? null : Number(minVal)),
            max:  (maxVal  === '' ? null : Number(maxVal)),
            step: (stepVal === '' ? null : (isNaN(Number(stepVal)) ? stepVal : Number(stepVal))),
            styles: {
                fontFamily:     font,
                labelColor:     headingColor,
                color:          textColor,
                backgroundColor:bgColor,
                borderColor:    borderColor
            }
        };
        config.updateField(id, patch);

        // ---- Sync dataset.fieldConfig for rebuildFromDOM ----
        const merged = Object.assign({}, fieldConfig, patch, {
            styles: Object.assign({}, fieldConfig.styles || {}, patch.styles || {})
        });
        wrapper.dataset.fieldConfig = JSON.stringify(merged);

        // ---- Close + save ----
        $('#numberInputConfigModal').attr('inert', '');
        $('#numberInputConfigModal').modal('hide');
        this.saveForm();
    },

    saveParagraphConfig: function () {
        const id = this.currentEditingId;
        const wrapper = document.getElementById(id);
        if (!wrapper) return;

        // Paragraph element (your addField creates a <p> inside the wrapper)
        const p = wrapper.querySelector('p');
        if (!p) return;

        // Read from modal
        const html = $('#paragraphEditor').summernote('code') || '';
        const font = $('#paragraphFont').val() || (config.defaultLabelFont || '');
        const color = config.pickrs['paragraphFontColor']?.getColor()?.toHEXA()?.toString() || '';

        // ---- DOM updates ----
        p.innerHTML = html;
        if (font)  p.style.fontFamily = font;
        if (color) p.style.color      = color;

        // ---- Patch canonical registry ----
        // We store the rich text HTML on the field (commonly as `code`).
        const patch = {
            id,
            type: 'paragraph',
            // no label/required/placeholder for paragraph
            code: html,        // keep full HTML for restore
            styles: {
                fontFamily: font,
                color:      color
            }
        };
        config.updateField(id, patch);

        // ---- Sync dataset.fieldConfig for rebuildFromDOM ----
        const prevCfg = JSON.parse(wrapper.dataset.fieldConfig || '{}');
        const merged = Object.assign({}, prevCfg, patch, {
            styles: Object.assign({}, prevCfg.styles || {}, patch.styles || {})
        });
        wrapper.dataset.fieldConfig = JSON.stringify(merged);

        // ---- Close + save ----
        $('#paragraphConfigModal').attr('inert', '');
        $('#paragraphConfigModal').modal('hide');
        this.saveForm();
    },

    saveEmailConfig: function () {
        const id = $('#emailInputConfigModal').data('target-field-id');
        const el = document.getElementById(id);
        if (!el) return;

        const input = el.querySelector('input[type="email"]');
        const label = el.querySelector('label.form-label');
        if (!input || !label) return;

        // Read modal values
        const labelText  = ($('#emailInputLabel').val() || '').trim();
        const placeholder = $('#emailInputPlaceholder').val() || '';
        const tooltip     = $('#emailInputTooltip').val() || '';
        const font        = $('#emailInputFont').val() || (config.defaultLabelFont || '');
        const maxLength   = parseInt($('#emailInputMaxLength').val() || '0', 10) || null;
        const required    = $('#emailInputRequired').is(':checked');

        const headingColor = config.pickrs['emailInputHeadingColor']?.getColor()?.toHEXA()?.toString() || '';
        const borderColor  = config.pickrs['emailInputBorderColor']?.getColor()?.toHEXA()?.toString() || '';
        const textColor    = config.pickrs['emailInputTextColor']?.getColor()?.toHEXA()?.toString() || '';
        const bgColor      = config.pickrs['emailInputBgColor']?.getColor()?.toHEXA()?.toString() || '';

        // ---- DOM updates ----
        label.textContent = labelText;
        if (headingColor) label.style.color = headingColor;
        if (font)         label.style.fontFamily = font;

        input.placeholder = placeholder;
        input.title       = tooltip;
        if (maxLength != null) input.maxLength = maxLength; else input.removeAttribute('maxlength');
        if (required) input.setAttribute('required', 'required'); else input.removeAttribute('required');
        if (font)         input.style.fontFamily     = font;
        if (textColor)    input.style.color          = textColor;
        if (bgColor)      input.style.backgroundColor= bgColor;
        if (borderColor)  input.style.borderColor    = borderColor;

        // ---- Patch canonical registry ----
        const patch = {
            id,
            type: 'email',
            label: labelText,
            required,
            placeholder,
            tooltip,
            maxlength: maxLength,
            styles: {
                fontFamily: font,
                labelColor: headingColor,
                color:      textColor,
                backgroundColor: bgColor,
                borderColor:     borderColor
            }
        };
        config.updateField(id, patch);

        // ---- Sync dataset.fieldConfig for rebuildFromDOM ----
        const prevCfg = JSON.parse(el.dataset.fieldConfig || '{}');
        const merged = Object.assign({}, prevCfg, patch, {
            styles: Object.assign({}, prevCfg.styles || {}, patch.styles || {})
        });
        el.dataset.fieldConfig = JSON.stringify(merged);

        // ---- Close + save ----
        $('#emailInputConfigModal').attr('inert', '');
        $('#emailInputConfigModal').modal('hide');
        this.saveForm();
    },


    savePhoneConfig: function () {
        const id = $('#phoneInputConfigModal').data('target-field-id');
        const el = document.getElementById(id);
        if (!el) return;

        const input = el.querySelector('input[type="tel"]');
        const label = el.querySelector('label.form-label, label');
        if (!input || !label) return;

        // Read modal values
        const labelText   = ($('#phoneInputLabel').val() || '').trim();
        const placeholder = $('#phoneInputPlaceholder').val() || '';
        const tooltip     = $('#phoneInputTooltip').val() || '';
        const font        = $('#phoneInputFont').val() || (config.defaultLabelFont || '');
        const maxLength   = parseInt($('#phoneInputMaxLength').val() || '0', 10) || null; // optional, if you have this control
        const required    = $('#phoneInputRequired').is(':checked');

        const headingColor = config.pickrs['phoneInputHeadingColor']?.getColor()?.toHEXA()?.toString() || '';
        const borderColor  = config.pickrs['phoneInputBorderColor']?.getColor()?.toHEXA()?.toString() || '';
        const textColor    = config.pickrs['phoneInputTextColor']?.getColor()?.toHEXA()?.toString() || '';
        const bgColor      = config.pickrs['phoneInputBgColor']?.getColor()?.toHEXA()?.toString() || '';

        // ---- DOM updates ----
        label.textContent = labelText;
        if (headingColor) label.style.color = headingColor;
        if (font)         label.style.fontFamily = font;

        input.placeholder = placeholder;
        input.title       = tooltip;
        if (maxLength != null) input.maxLength = maxLength; else input.removeAttribute('maxlength');
        if (required) input.setAttribute('required', 'required'); else input.removeAttribute('required');
        if (font)        input.style.fontFamily      = font;
        if (textColor)   input.style.color           = textColor;
        if (bgColor)     input.style.backgroundColor = bgColor;
        if (borderColor) input.style.borderColor     = borderColor;

        // ---- Patch canonical registry ----
        const patch = {
            id,
            type: 'phone',
            label: labelText,
            required,
            placeholder,
            tooltip,
            maxlength: maxLength, // harmless if null
            styles: {
                fontFamily:      font,
                labelColor:      headingColor,
                color:           textColor,
                backgroundColor: bgColor,
                borderColor:     borderColor
            }
        };
        config.updateField(id, patch);

        // ---- Sync dataset.fieldConfig for rebuildFromDOM ----
        const prevCfg = JSON.parse(el.dataset.fieldConfig || '{}');
        const merged = Object.assign({}, prevCfg, patch, {
            styles: Object.assign({}, prevCfg.styles || {}, patch.styles || {})
        });
        el.dataset.fieldConfig = JSON.stringify(merged);

        // ---- Close + save ----
        $('#phoneInputConfigModal').attr('inert', '');
        $('#phoneInputConfigModal').modal('hide');
        this.saveForm();
    },


    saveMultiSelectConfig: function () {
        const id = $('#multiSelectConfigModal').data('target-field-id');
        const el = document.getElementById(id);
        if (!el) return;

        const labelEl  = el.querySelector('label.form-label, label');
        const selectEl = el.querySelector('select[multiple], select'); // handles both, but intended for multiple
        if (!labelEl || !selectEl) return;

        // ---- Read modal values ----
        const labelText = ($('#multiSelectLabel').val() || '').trim();
        const tooltip   = $('#multiSelectTooltip').val() || '';
        const font      = $('#multiSelectFont').val() || (config.defaultLabelFont || '');
        const required  = $('#multiSelectInputRequired').is(':checked');

        // Options: split by newline, drop empties, trim
        const optionsArr = ($('#multiSelectOptions').val() || '')
            .split('\n')
            .map(s => s.trim())
            .filter(Boolean);

        // Colors (may be undefined/empty)
        const headingColor = config.pickrs['multiSelectHeadingColor']?.getColor()?.toHEXA()?.toString() || '';
        const borderColor  = config.pickrs['multiSelectBorderColor']?.getColor()?.toHEXA()?.toString() || '';
        const textColor    = config.pickrs['multiSelectTextColor']?.getColor()?.toHEXA()?.toString() || '';
        const bgColor      = config.pickrs['multiSelectBgColor']?.getColor()?.toHEXA()?.toString() || '';

        // ---- DOM updates ----
        labelEl.textContent = labelText;
        if (headingColor) labelEl.style.color = headingColor;
        if (font)         labelEl.style.fontFamily = font;

        // Tooltip + required
        selectEl.title = tooltip;
        if (required) selectEl.setAttribute('required', 'required');
        else          selectEl.removeAttribute('required');

        // Font + colors
        if (font)        selectEl.style.fontFamily      = font;
        if (textColor)   selectEl.style.color           = textColor;
        if (bgColor)     selectEl.style.backgroundColor = bgColor;
        if (borderColor) selectEl.style.borderColor     = borderColor;

        // Rebuild <option> list
        // Preserve currently selected values where possible
        const prevSelected = Array.from(selectEl.selectedOptions).map(o => o.value);
        selectEl.innerHTML = '';
        optionsArr.forEach(opt => {
            const o = document.createElement('option');
            o.value = opt;
            o.textContent = opt;
            if (prevSelected.includes(opt)) o.selected = true;
            selectEl.appendChild(o);
        });

        // ---- Patch canonical registry ----
        const patch = {
            id,
            type: 'multiselect',
            label: labelText,
            required,
            tooltip,
            options: optionsArr,
            styles: {
                fontFamily:      font,
                labelColor:      headingColor,
                color:           textColor,
                backgroundColor: bgColor,
                borderColor:     borderColor
            }
        };
        config.updateField(id, patch);

        // ---- Sync dataset.fieldConfig for rebuildFromDOM ----
        const prevCfg = (() => { try { return JSON.parse(el.dataset.fieldConfig || '{}'); } catch { return {}; } })();
        const merged = Object.assign({}, prevCfg, patch, {
            styles: Object.assign({}, prevCfg.styles || {}, patch.styles || {})
        });
        el.dataset.fieldConfig = JSON.stringify(merged);

        // ---- Close + save ----
        $('#multiSelectConfigModal').attr('inert', '');
        $('#multiSelectConfigModal').modal('hide');
        this.saveForm();
    },

    saveFileUploadConfig: function () {
        const id = $('#fileUploadConfigModal').data('target-field-id');
        const el = document.getElementById(id);
        if (!el) return;

        const labelEl = el.querySelector('label.form-label, label');
        const inputEl = el.querySelector('input[type="file"]');
        if (!inputEl) return;

        // --- Read modal values ---
        const labelText = ($('#fileUploadLabel').val() || '').trim();
        const tooltip   = ($('#fileUploadTooltip').val() || '').trim();
        const required  = $('#fileUploadInputRequired').is(':checked');
        const font      = $('#fileUploadFont').val() || (config.defaultLabelFont || '');

        // Colors (some may not exist in your pickr set; that's fine)
        const headingColor = config.pickrs['fileUploadHeadingColor']?.getColor()?.toHEXA()?.toString() || '';
        const borderColor  = config.pickrs['fileUploadBorderColor']?.getColor()?.toHEXA()?.toString() || '';
        const textColor    = config.pickrs['fileUploadTextColor']?.getColor()?.toHEXA()?.toString() || '';
        const bgColor      = config.pickrs['fileUploadBgColor']?.getColor()?.toHEXA()?.toString() || '';

        // --- DOM updates ---
        if (labelEl) {
            labelEl.textContent = labelText;
            if (font)         labelEl.style.fontFamily = font;
            if (headingColor) labelEl.style.color = headingColor;
        }

        inputEl.title = tooltip;
        if (required) inputEl.setAttribute('required', 'required');
        else          inputEl.removeAttribute('required');

        if (font)        inputEl.style.fontFamily      = font;
        if (borderColor) inputEl.style.borderColor     = borderColor;
        if (textColor)   inputEl.style.color           = textColor;
        if (bgColor)     inputEl.style.backgroundColor = bgColor;

        // --- Patch canonical registry ---
        const prevCfg = (() => { try { return JSON.parse(el.dataset.fieldConfig || '{}'); } catch { return {}; } })();
        const patch = {
            id,
            // keep existing type if present; fall back to 'file'
            type: prevCfg.type || 'file',
            label: labelText,
            tooltip,
            required,
            styles: {
                fontFamily:      font,
                labelColor:      headingColor,
                borderColor:     borderColor,
                // the next two are harmless if empty (undefined won't overwrite in your merge)
                color:           textColor,
                backgroundColor: bgColor,
            }
        };
        config.updateField(id, patch);

        // --- Sync dataset.fieldConfig for rebuildFromDOM ---
        const merged = Object.assign({}, prevCfg, patch, {
            styles: Object.assign({}, prevCfg.styles || {}, patch.styles || {})
        });
        el.dataset.fieldConfig = JSON.stringify(merged);

        // --- Close + save ---
        $('#fileUploadConfigModal').attr('inert', '');
        $('#fileUploadConfigModal').modal('hide');
        this.saveForm();
    },


    saveRadioInputConfig: function () {
        const id = $('#radioInputConfigModal').data('target-field-id');
        const el = document.getElementById(id);
        if (!el) return;

        const labelEl = el.querySelector('label.form-label, label');
        const required = $('#radioInputRequired').is(':checked');

        const font       = $('#radioInputFont').val() || (config.defaultLabelFont || '');
        const labelText  = ($('#radioInputLabel').val() || '').trim();
        const tooltip    = ($('#radioInputTooltip').val() || '').trim();
        const optionsTxt = ($('#radioInputOptions').val() || '');

        // Colors
        const headingColor = config.pickrs['radioInputHeadingColor']?.getColor()?.toHEXA()?.toString() || '';
        const borderColor  = config.pickrs['radioInputBorderColor']?.getColor()?.toHEXA()?.toString() || '';
        const textColor    = config.pickrs['radioInputTextColor']?.getColor()?.toHEXA()?.toString() || '';
        const bgColor      = config.pickrs['radioInputBgColor']?.getColor()?.toHEXA()?.toString() || '';

        // Options array (trim empties)
        const optionsArr = optionsTxt.split('\n').map(s => s.trim()).filter(Boolean);

        // Containers
        const radios = el.querySelectorAll('input[type="radio"]');
        const groupContainer = radios.length ? (radios[0].closest('.radio-group') || el) : el;
        let optionsContainer = el.querySelector('.radio-options');
        if (!optionsContainer) {
            optionsContainer = document.createElement('div');
            optionsContainer.className = 'radio-options';
            el.appendChild(optionsContainer);
        }

        // --- Preserve selection
        const prevSelected = (() => {
            const checked = el.querySelector('input[type="radio"]:checked');
            return checked ? checked.value : null;
        })();

        // --- DOM updates (label)
        if (labelEl) {
            labelEl.textContent = labelText;
            if (font)         labelEl.style.fontFamily = font;
            if (headingColor) labelEl.style.color = headingColor;
            if (tooltip)      labelEl.title = tooltip;
        }

        // --- Rebuild radio options
        optionsContainer.innerHTML = '';
        const nameAttr = `radio-${id}`;
        optionsArr.forEach((opt, idx) => {
            const wrap = document.createElement('div');
            wrap.className = 'form-check';

            const input = document.createElement('input');
            input.type = 'radio';
            input.className = 'form-check-input';
            input.name = nameAttr;
            input.value = opt;
            input.title = tooltip;
            // Required only on the first radio to satisfy native validation
            if (required && idx === 0) input.setAttribute('required', 'required');

            const lbl = document.createElement('label');
            lbl.className = 'form-check-label';
            lbl.textContent = opt;

            // Restore selection if applicable
            if (prevSelected && prevSelected === opt) input.checked = true;

            wrap.appendChild(input);
            wrap.appendChild(lbl);
            optionsContainer.appendChild(wrap);
        });

        // --- Group/container styles
        if (groupContainer) {
            if (textColor)   groupContainer.style.color = textColor;
            if (borderColor) groupContainer.style.borderColor = borderColor;
            if (bgColor)     groupContainer.style.backgroundColor = bgColor;
            if (font)        groupContainer.style.fontFamily = font;
        }

        // --- Also ensure all radios reflect required flag correctly
        const rebuiltRadios = optionsContainer.querySelectorAll('input[type="radio"]');
        rebuiltRadios.forEach((r, idx) => {
            if (required && idx === 0) r.setAttribute('required', 'required');
            else                       r.removeAttribute('required');
        });

        // --- Patch canonical registry
        const patch = {
            id,
            type: 'radio',
            label: labelText,
            required,
            tooltip,
            options: optionsArr,
            styles: {
                fontFamily:      font,
                labelColor:      headingColor,
                color:           textColor,
                backgroundColor: bgColor,
                borderColor:     borderColor
            }
        };
        config.updateField(id, patch);

        // --- Sync dataset.fieldConfig for rebuildFromDOM
        const prevCfg = (() => { try { return JSON.parse(el.dataset.fieldConfig || '{}'); } catch { return {}; } })();
        const merged = Object.assign({}, prevCfg, patch, {
            styles: Object.assign({}, prevCfg.styles || {}, patch.styles || {})
        });
        el.dataset.fieldConfig = JSON.stringify(merged);

        // --- Close + save
        $('#radioInputConfigModal').attr('inert', '');
        $('#radioInputConfigModal').modal('hide');
        this.saveForm();
    },

    saveCheckboxInputConfig: function () {
        const id = $('#checkboxInputConfigModal').data('target-field-id');
        const el = document.getElementById(id);
        if (!el) return;

        const labelEl = el.querySelector('label.form-label, label');
        let optionsContainer = el.querySelector('.checkbox-options');
        if (!optionsContainer) {
            optionsContainer = document.createElement('div');
            optionsContainer.className = 'checkbox-options';
            el.appendChild(optionsContainer);
        }

        // ---- Read modal values ----
        const required   = $('#checkboxInputRequired').is(':checked');
        const labelText  = ($('#checkboxInputLabel').val() || '').trim();
        const tooltip    = ($('#checkboxInputTooltip').val() || '').trim();
        const font       = $('#checkboxInputFont').val() || (config.defaultLabelFont || '');
        const optionsTxt = ($('#checkboxInputOptions').val() || '');

        // Colors
        const headingColor = config.pickrs['checkboxInputHeadingColor']?.getColor()?.toHEXA()?.toString() || '';
        const borderColor  = config.pickrs['checkboxInputBorderColor']?.getColor()?.toHEXA()?.toString() || '';
        const textColor    = config.pickrs['checkboxInputTextColor']?.getColor()?.toHEXA()?.toString() || '';
        const bgColor      = config.pickrs['checkboxInputBgColor']?.getColor()?.toHEXA()?.toString() || '';

        // Options array (trim empties)
        const optionsArr = optionsTxt.split('\n').map(s => s.trim()).filter(Boolean);

        // ---- Preserve previous selections
        const prevSelected = Array.from(optionsContainer.querySelectorAll('input[type="checkbox"]:checked')).map(i => i.value);

        // ---- DOM updates: label
        if (labelEl) {
            labelEl.textContent = labelText;
            if (font)         labelEl.style.fontFamily = font;
            if (headingColor) labelEl.style.color = headingColor;
            if (tooltip)      labelEl.title = tooltip;
        }

        // ---- Rebuild checkbox options
        optionsContainer.innerHTML = '';
        const nameAttr = `checkbox-${id}`;
        optionsArr.forEach(opt => {
            const wrap = document.createElement('div');
            wrap.className = 'form-check';

            const cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.className = 'form-check-input';
            cb.name = nameAttr;
            cb.value = opt;
            cb.title = tooltip;
            if (required) cb.setAttribute('required', 'required'); else cb.removeAttribute('required');
            if (prevSelected.includes(opt)) cb.checked = true;

            const lbl = document.createElement('label');
            lbl.className = 'form-check-label';
            lbl.textContent = opt;

            wrap.appendChild(cb);
            wrap.appendChild(lbl);
            optionsContainer.appendChild(wrap);
        });

        // ---- Container styles
        if (font)        optionsContainer.style.fontFamily = font;
        if (textColor)   optionsContainer.style.color = textColor;
        if (borderColor) optionsContainer.style.borderColor = borderColor;
        if (bgColor)     optionsContainer.style.backgroundColor = bgColor;

        // ---- Patch canonical registry
        const patch = {
            id,
            type: 'checkbox',
            label: labelText,
            required,
            tooltip,
            options: optionsArr,
            styles: {
                fontFamily:      font,
                labelColor:      headingColor,
                color:           textColor,
                backgroundColor: bgColor,
                borderColor:     borderColor
            }
        };
        config.updateField(id, patch);

        // ---- Sync dataset.fieldConfig for rebuildFromDOM
        const prevCfg = (() => { try { return JSON.parse(el.dataset.fieldConfig || '{}'); } catch { return {}; } })();
        const merged = Object.assign({}, prevCfg, patch, {
            styles: Object.assign({}, prevCfg.styles || {}, patch.styles || {})
        });
        el.dataset.fieldConfig = JSON.stringify(merged);

        // ---- Close + save
        $('#checkboxInputConfigModal').attr('inert', '');
        $('#checkboxInputConfigModal').modal('hide');
        this.saveForm();
    },


    saveSingleSelectConfig: function () {
        const id = $('#singleSelectConfigModal').data('target-field-id');
        const el = document.getElementById(id);
        if (!el) return;

        const labelEl  = el.querySelector('label.form-label, label');
        const selectEl = el.querySelector('select');
        if (!labelEl || !selectEl) return;

        // ---- Read modal values ----
        const required   = $('#singleSelectInputRequired').is(':checked');
        const labelText  = ($('#singleSelectLabel').val() || '').trim();
        const tooltip    = ($('#singleSelectTooltip').val() || '').trim();
        const font       = $('#singleSelectFont').val() || (config.defaultLabelFont || '');
        const optionsTxt = ($('#singleSelectOptions').val() || '');

        // Colors
        const headingColor = config.pickrs['singleSelectHeadingColor']?.getColor()?.toHEXA()?.toString() || '';
        const borderColor  = config.pickrs['singleSelectBorderColor']?.getColor()?.toHEXA()?.toString() || '';
        const textColor    = config.pickrs['singleSelectTextColor']?.getColor()?.toHEXA()?.toString() || '';
        const bgColor      = config.pickrs['singleSelectBgColor']?.getColor()?.toHEXA()?.toString() || '';

        // Options array (trim empties)
        const optionsArr = optionsTxt.split('\n').map(s => s.trim()).filter(Boolean);

        // ---- Preserve selection
        const prevSelected = selectEl.value;

        // ---- DOM updates: label
        labelEl.textContent = labelText;
        if (font)         labelEl.style.fontFamily = font;
        if (headingColor) labelEl.style.color = headingColor;
        if (tooltip)      labelEl.title = tooltip;

        // ---- Rebuild <option> list
        selectEl.innerHTML = '';
        optionsArr.forEach(opt => {
            const o = document.createElement('option');
            o.value = opt;
            o.textContent = opt;
            if (prevSelected && prevSelected === opt) o.selected = true;
            selectEl.appendChild(o);
        });

        // ---- Select attributes + styles
        selectEl.title = tooltip;
        if (required) selectEl.setAttribute('required', 'required');
        else          selectEl.removeAttribute('required');

        if (font)        selectEl.style.fontFamily      = font;
        if (textColor)   selectEl.style.color           = textColor;
        if (bgColor)     selectEl.style.backgroundColor = bgColor;
        if (borderColor) selectEl.style.borderColor     = borderColor;

        // ---- Patch canonical registry
        const patch = {
            id,
            type: 'singleselect',
            label: labelText,
            required,
            tooltip,
            options: optionsArr,
            styles: {
                fontFamily:      font,
                labelColor:      headingColor,
                color:           textColor,
                backgroundColor: bgColor,
                borderColor:     borderColor
            }
        };
        config.updateField(id, patch);

        // ---- Sync dataset.fieldConfig for rebuildFromDOM
        const prevCfg = (() => { try { return JSON.parse(el.dataset.fieldConfig || '{}'); } catch { return {}; } })();
        const merged = Object.assign({}, prevCfg, patch, {
            styles: Object.assign({}, prevCfg.styles || {}, patch.styles || {})
        });
        el.dataset.fieldConfig = JSON.stringify(merged);

        // ---- Close + save
        $('#singleSelectConfigModal').attr('inert', '');
        $('#singleSelectConfigModal').modal('hide');
        this.saveForm();
    },


    saveDatePickerConfig: function () {
        const id = $('#datePickerConfigModal').data('target-field-id');
        const el = document.getElementById(id);
        if (!el) return;

        const labelEl = el.querySelector('label.form-label, label');
        const inputEl = el.querySelector('input[type="date"]');
        if (!inputEl) return;

        // ---- Read modal values ----
        const required    = $('#datePickerInputRequired').is(':checked');
        const labelText   = ($('#datePickerInputLabel').val() || '').trim();
        const placeholder = ($('#datePickerPlaceholder').val() || '').trim();
        const tooltip     = ($('#datePickerTooltip').val() || '').trim();
        const font        = $('#datePickerFont').val() || (config.defaultLabelFont || '');

        // Colors
        const headingColor = config.pickrs['datePickerHeadingColor']?.getColor()?.toHEXA()?.toString() || '';
        const borderColor  = config.pickrs['datePickerBorderColor']?.getColor()?.toHEXA()?.toString() || '';
        const textColor    = config.pickrs['datePickerTextColor']?.getColor()?.toHEXA()?.toString() || '';
        const bgColor      = config.pickrs['datePickerBgColor']?.getColor()?.toHEXA()?.toString() || '';

        // ---- DOM updates ----
        if (labelEl) {
            labelEl.textContent = labelText;
            if (font)         labelEl.style.fontFamily = font;
            if (headingColor) labelEl.style.color = headingColor;
            if (tooltip)      labelEl.title = tooltip;
        }

        // Note: many browsers ignore placeholder on type="date", but we still set it
        inputEl.placeholder = placeholder;
        inputEl.title = tooltip;

        if (required) inputEl.setAttribute('required', 'required');
        else          inputEl.removeAttribute('required');

        if (font)        inputEl.style.fontFamily      = font;
        if (textColor)   inputEl.style.color           = textColor;
        if (bgColor)     inputEl.style.backgroundColor = bgColor;
        if (borderColor) inputEl.style.borderColor     = borderColor;

        // ---- Patch canonical registry ----
        const patch = {
            id,
            type: 'date',
            label: labelText,
            required,
            placeholder,
            tooltip,
            styles: {
                fontFamily:      font,
                labelColor:      headingColor,
                color:           textColor,
                backgroundColor: bgColor,
                borderColor:     borderColor
            }
        };
        config.updateField(id, patch);

        // ---- Sync dataset.fieldConfig for rebuildFromDOM ----
        const prevCfg = (() => { try { return JSON.parse(el.dataset.fieldConfig || '{}'); } catch { return {}; } })();
        const merged = Object.assign({}, prevCfg, patch, {
            styles: Object.assign({}, prevCfg.styles || {}, patch.styles || {})
        });
        el.dataset.fieldConfig = JSON.stringify(merged);

        // ---- Close + save ----
        $('#datePickerConfigModal').attr('inert', '');
        $('#datePickerConfigModal').modal('hide');
        this.saveForm();
    },


    saveFormConfig: function () {
        const title = $('#formTitle').val().trim();
        const recipients = $('#formRecipients').val().trim();
        const slug = $('#formSlug').val().trim();
        const companySlug = config.companySlug || '';
        const message = $('#formSubmitMessage').val().trim();

        const defaultLabelColor = config.pickrs['defaultLabelColor']?.getColor()?.toHEXA().toString() || null;
        const defaultTextMaxLength = $('#defaultTextMaxLength').val().trim() || null;
        const defaultLabelFont = $('#defaultLabelFont').val().trim() || null;

        if (!slug || !companySlug) {
            alert("Form slug and company are required.");
            return;
        }

        // helper: run slug check only when the slug actually changed
        const checkSlugUnique = () => {
            if (slug === (config.formSlug || '')) {
                // unchanged → skip check
                return $.Deferred().resolve({ exists: false }).promise();
            }
            return $.post('/formbuilder/check_slug.php', { slug, company_slug: companySlug }, null, 'json');
        };

        // helper: persist settings to backend and set config.form_uuid
        const persistSettings = () => {
            // reflect title in UI immediately
            if (title) {
                document.querySelector('#form-title').textContent = title;
            }

            // stash in config (source of truth for the builder)
            config.formRecipients = recipients;
            config.formSlug = slug;
            config.formTitle = title;
            config.formSubmitMessage = message;
            config.defaultLabelColor = defaultLabelColor;
            config.defaultTextMaxLength = defaultTextMaxLength;
            config.defaultLabelFont = defaultLabelFont;

            return fetch('/formbuilder/save.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': config.csrf_token || ''
                },
                body: JSON.stringify({
                    csrf_token: config.csrf_token,
                    form_uuid:  config.form_uuid || null,
                    form_title: title,
                    form_recipients: recipients,
                    form_slug:  slug,
                    company_slug: companySlug, // important
                    submit_message: message,
                    defaults: {
                        labelColor: defaultLabelColor,
                        labelFont:  defaultLabelFont,
                        maxTextLength: defaultTextMaxLength
                    },
                    fields: []
                })
            })
                .then(r => r.json())
                .then(res => {
                    console.log(res);
                    if (res?.status !== 'success') {
                        console.warn('save_form_config failed:', res);
                        throw new Error(res?.message || 'Failed to save Form Settings');
                    }
                    if (res.form_uuid) config.form_uuid = res.form_uuid;
                    $('#formConfigModal').attr('inert', '');
                    $('#formConfigModal').modal('hide');
                    toastr.success('Form settings saved.  You can now add fields.');
                    //document.dispatchEvent(new Event('configChanged'));
                    markDirtyAndEmit('configChanged');
                    return res;
                });


        };

        // run flow
        checkSlugUnique()
            .done((response) => {
                if (response?.exists) {
                    alert("That form slug is already in use for your company. Please choose a different one.");
                    return;
                }
                if (typeof window.SaveBadge?.saving === 'function') window.SaveBadge.saving();
                persistSettings()
                    .then(() => {
                        if (typeof window.SaveBadge?.saved === 'function') window.SaveBadge.saved();
                    })
                    .catch((err) => {
                        console.error(err);
                        if (typeof window.SaveBadge?.error === 'function') window.SaveBadge.error('Save failed');
                        toastr.error(err.message || 'Unable to save Form Settings');
                    });
            })
            .fail(() => {
                alert("Unable to validate slug. Please try again.");
            });
    },



    addRow: function () {
        if (!this.checkFormConfig()) {
            alert('Please fill out the Form Config first');
            return;
        }

        const rowId = `row-${this.rowCounter++}`;

        const rowWrapper = document.createElement('div');
        rowWrapper.className = 'draggable-row mb-3';
        rowWrapper.dataset.rowId = rowId;                 // ← make row id discoverable

        // Make the inner container BOTH the bootstrap row AND the field bucket
        rowWrapper.innerHTML = `
    <div class="text-end mb-2 editor-only">
      <button class="btn btn-sm btn-outline-secondary row-drag-handle me-2">↕</button>
      <button class="btn btn-sm btn-outline-danger" onclick="config.removeRow('${rowId}')">X</button>
    </div>
    <div class="row sortable-row row-fields" id="${rowId}"></div>
  `;

        document.getElementById('form-canvas').appendChild(rowWrapper);

        // Set active row to the INNER container (it holds .field cards)
        const inner = rowWrapper.querySelector('.row-fields');
        this.setActiveRow(inner);

        // Bind sortables:
        //  - init field sortable for THIS row (pass the OUTER wrapper!)
        initFieldSortableForRow(rowWrapper);

        //  - ensure row-level sortable is initialized once
        initRowSortable();

        // Notify
        markDirtyAndEmit('rowChanged', { action: 'addRow', rowId });

        return inner; // keep your existing callers happy
    },


    checkFormConfig: function(){
    if (config.isPublic) {
        return true;
    } else {
        const title = $('#formTitle').val().trim();
        const recipients = $('#formRecipients').val().trim();
        const slug = $('#formSlug').val().trim();

        if (title.length == 0 || recipients.length == 0 || slug.length == 0) {
            return false;
        } else {
            return true;
        } 
    }
    

  
},

setActiveRow: function (rowElement) {
  if (!rowElement || !rowElement.id) {
   
    return;
  }

  // Remove .active-row from all rows
  document.querySelectorAll('.sortable-row').forEach(r => r.classList.remove('active-row'));

  // Add .active-row to the selected one
  rowElement.classList.add('active-row');

  // Store active row ID
  config.activeRowId = rowElement.id;
},




canFitNewField: function (rowEl) {
  let total = 0;
  Array.from(rowEl.children).forEach(col => {
    const cls = [...col.classList].find(c => c.startsWith('col-md-'));
    if (cls) total += parseInt(cls.split('-')[2]);
  });
  return total < 12;
},
    
  removeElement: function (id) {
  const el = document.getElementById(id);
  if (el) el.remove();
  markDirtyAndEmit('fieldChanged', {action:'remove', id})
},
  
  removeRow: function (rowId) {
  const row = document.getElementById(rowId);
  if (!row) return;

  const wrapper = row.closest('.draggable-row');
  if (wrapper) wrapper.remove();

  // If the removed row was the active one, clear the focus
  if (config.activeRowId === rowId) {
    config.activeRowId = null;
  }
      markDirtyAndEmit('rowChanged', {action:'remove', rowId})
},

/*FORM SAVE*/

    saveForm: async function (show = false) {
        // 1) Basic guards from Form Config
        if (!config.formTitle || !config.formTitle.trim()) {
            alert('Please use form config to give the form a title');
            return;
        }
        if (!config.formRecipients || !config.formRecipients.trim()) {
            alert('Please use form config to set email recipients');
            return;
        }
        if (!config.formSlug || !config.formSlug.trim()) {
            alert('Please use form config to give the form a URL/slug');
            return;
        }

        // 2) Ensure form is initialized (creates the definition and sets config.form_uuid)
        if (!config.form_uuid) toastr.info('Saving Form Settings to initialize…');
        await config.ensureFormInitialized();
        if (!config.form_uuid) {
            alert('Could not initialize the form. Please save Form Config and try again.');
            return;
        }

        // 3) Skip unnecessary saves unless explicitly requested
        if (typeof window.FormModel?.isDirty === 'function' && !window.FormModel.isDirty() && !show) {
            return;
        }

        // 4) Badge: Saving…
        if (window.SaveBadge?.saving) window.SaveBadge.saving();

        // 5) Defaults from Form Config modal
        const defaults = {
            defaultLabelColor:   config.defaultLabelColor || null,
            defaultLabelFont:    config.defaultLabelFont || null,
            defaultTextMaxLength: config.defaultTextMaxLength || null
        };

        // 6) Build payload
        const version = (typeof config.form_version !== 'undefined')
            ? config.form_version
            : (typeof window.FORM_VERSION !== 'undefined' ? window.FORM_VERSION : 1);



        // NEW: capture Summernote HTML and inject into the FormModel JSON
                const richtextMap = collectSummernoteHtmlMap('#form-canvas');

        // Get your canonical JSON from the model
                let fieldsJsonRaw = (typeof window.FormModel?.toJSON === 'function')
                    ? window.FormModel.toJSON()
                    : (window._FORMMODEL_JSON || []);

        // Ensure it’s an object/array, then apply paragraph HTML
                if (typeof fieldsJsonRaw === 'string') {
                    try { fieldsJsonRaw = JSON.parse(fieldsJsonRaw); } catch(e) { fieldsJsonRaw = []; }
                }
                applyRichtextToFields(fieldsJsonRaw, richtextMap);


        const payload = {
            csrf_token:    config.csrf_token,
            form_uuid:     config.form_uuid,                 // required
            version,                                        // optimistic locking
            title:         config.formTitle || '',
            slug:          config.formSlug || '',
            company_slug:  config.companySlug || '',
            description:   config.formDescription || '',
            defaults,                                      // keep as object; stringify server-side if needed
            email_recipients: config.formRecipients || '',
            submit_message:   config.formSubmitMessage || '',

            // Canonical fields/config captured from your builder model
            fields_json: JSON.stringify(fieldsJsonRaw),
            config_json: (typeof exportConfigJson === 'function') ? exportConfigJson() : {}
        };

       // payload.fields_json = JSON.stringify(window._FORMMODEL_JSON || window.FormModel.toJSON());

        try {
            const res = await fetch('/formbuilder/save.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': config.csrf_token || ''
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json().catch(() => ({}));

            // 7) Handle optimistic-lock conflict
            if (res.status === 409) {
                if (window.SaveBadge?.error) window.SaveBadge.error('Newer version exists on server');
                if (window.toastr) toastr.warning('A newer version exists. Reload recommended.');
                return;
            }

            if (!res.ok || data.ok === false || data.success === false) {
                const msg = data.error || data.message || 'Save failed';
                if (window.SaveBadge?.error) window.SaveBadge.error(msg);
                if (window.toastr) toastr.error(msg);
                return;
            }

            // 8) Success: sync version + uuid if provided
            if (typeof data.version !== 'undefined') {
                config.form_version = data.version;
                window.FORM_VERSION = data.version; // keep old global in sync if anything still reads it
            }
            if (data.form_uuid && data.form_uuid !== config.form_uuid) {
                config.form_uuid = data.form_uuid;
            }

            // 9) Clear dirty flag and show saved
            if (typeof window.FormModel?.resetDirty === 'function') window.FormModel.resetDirty();
            if (window.SaveBadge?.saved) window.SaveBadge.saved();
            if (show && window.toastr) toastr.success('Form saved!');

            // 10) Let anything listening know the form is now clean
            document.dispatchEvent(new Event('form:clean'));

            return data;
        } catch (err) {
            console.error(err);
            if (window.SaveBadge?.error) window.SaveBadge.error('Network error');
            if (window.toastr) toastr.error('Network error while saving.');
        }
    },



    renderForm: function(input) {
        window.__HYDRATING = true;
        try {
            // hard reset
            config.fieldMap = {};
            config.fieldCounter = 0;
            $('#form-canvas').empty();

            // --- normalize input into an array of row objects with .fields ---
            const coerce = (v) => {
                if (!v) return [];
                if (Array.isArray(v)) return v;                 // already rows array
                if (typeof v === 'string') {                    // JSON string?
                    try { return coerce(JSON.parse(v)); } catch { return []; }
                }
                if (typeof v === 'object') {
                    if (Array.isArray(v.rows)) return v.rows;     // { rows: [...] }
                    // Some legacy shapes like {0:[...],1:[...]}
                    const vals = Object.values(v);
                    if (vals.length && vals.every(x => Array.isArray(x) || typeof x === 'object')) return vals;
                }
                return [];
            };

            let rows = coerce(input);
            if (!Array.isArray(rows)) rows = [];

            rows = normalizeLegacyForm(rows);

            rows.forEach((rowLike, rowIdx) => {
                const rowObj = Array.isArray(rowLike)
                    ? { id: `row-${rowIdx}`, order: rowIdx, fields: rowLike }
                    : (rowLike || { id: `row-${rowIdx}`, order: rowIdx, fields: [] });

                // create a canvas row
                const currentRow = config.addRow();
                const desiredRowId = rowObj.id || `row-${rowIdx}`;
                currentRow.id = desiredRowId;
                currentRow.dataset.rowId = desiredRowId;
                config.setActiveRow(currentRow);
                config.activeRowId = desiredRowId;

                const fields = Array.isArray(rowObj.fields) ? rowObj.fields
                    : (Array.isArray(rowLike) ? rowLike : []);

                fields.forEach((f, i) => {
                    const fieldObj = {
                        id:        f.id || `field-${config.fieldCounter}`,
                        type:      f.type || 'text',
                        label:     f.label ?? '',
                        required:  !!f.required,
                        placeholder: f.placeholder || '',
                        tooltip:   f.tooltip || '',
                        maxlength: (f.maxlength != null ? f.maxlength : null),
                        colSize:   f.colSize ?? 3,
                        rows:      f.rows,
                        styles:    f.styles || {},
                        options:   f.options,
                        order:     (f.order != null ? f.order : i)
                    };

                    // keep counter ahead of any restored numeric suffix
                    const m = String(fieldObj.id).match(/(\d+)$/);
                    if (m) {
                        const n = parseInt(m[1], 10);
                        if (n >= config.fieldCounter) config.fieldCounter = n + 1;
                    }

                    // add to DOM honoring id/values
                    config.addField(fieldObj.type, fieldObj);

                    // registry + datasets + visuals
                    config.registerField(fieldObj);
                    const el = document.getElementById(fieldObj.id);
                    if (el) {
                        config.syncDatasets?.(el, fieldObj);   // keeps data-* in sync
                        config.applyStyles?.(el, fieldObj);    // applies colors/fonts/etc.
                    }
                    //summernote clean up
                    if (config.isPublic && el) {
                        // nuke Summernote/editor shells and editor-only controls
                        el.querySelectorAll('.summernote, [id$="-editor"], .editor-only, .drag-handle, .resize-field-btn, .duplicate-field-btn, .edit-field-btn, .remove-element-btn').forEach(n => n.remove());
                    }

                    // ✅ Legacy paragraph content injection (works in editor + public)
                    if (el && fieldObj.type === 'paragraph') {
                        const html = (fieldObj.html ?? f.html ?? f.code ?? f.text ?? '').trim();
                        if (html) {
                            const safe = sanitizeParagraphHtml(html);

                            // Use existing container if present; otherwise create one
                            let container =
                                el.querySelector('.mka-paragraph') ||
                                el.querySelector('.paragraph-body');

                            if (!container) {
                                const inner = el.querySelector('.border, .card, .shadow-sm') || el;
                                container = document.createElement('div');
                                container.className = 'mka-paragraph';
                                inner.appendChild(container);
                            }

                            container.innerHTML = safe;

                            // apply simple styles
                            const s = fieldObj.styles || {};
                            container.style.fontFamily = s.fontFamily || 'Arial';
                            container.style.fontSize   = s.fontSize   || '14px';
                            container.style.color      = s.color      || '#444';
                            container.style.textAlign  = s.textAlign  || 'left';
                        }
                    }

                });
            });

            initRowSortable();
            initAllFieldSortables();

            window.FormModel?.rebuildFromDOM?.();
        } finally {
            window.__HYDRATING = false; // re-enable events/autosave after render
        }
    },





    validateCheckboxGroups: function(form) {
        const groups = {};
        form.querySelectorAll('.checkbox-options[data-required="true"]').forEach(container => {
            const groupName = container.getAttribute('data-group');
            const boxes = Array.from(container.querySelectorAll(`input[type="checkbox"][data-group="${groupName}"]`))
                .filter(cb => !cb.disabled); // ignore disabled
            if (boxes.length) groups[groupName] = boxes;
        });

        let allGood = true;

        Object.values(groups).forEach(boxes => {
            const oneChecked = boxes.some(cb => cb.checked);
            // attach to first box so reportValidity() focuses it
            boxes[0].setCustomValidity(oneChecked ? '' : 'Please select at least one option.');
            if (!oneChecked) allGood = false;
        });

        return allGood;
    },


 getDefaultLabel: function (type) {
  const labels = {
    textarea: 'Long Text',
    number: 'Number',
    email: 'Email',
    tel: 'Phone',
    file: 'Upload File',
    checkbox: 'Checkbox',
    radio: 'Radio Buttons',
    'select-multiple': 'Multi-Select',
    select: 'Dropdown',
    date: 'Date Picker',
    header: 'Header Text',
    h4: 'Header Text',
    paragraph: 'Helper Text',
    image: 'Image',
    separator: '',
    signature: 'Signature',
    default: 'Text Input'
  };
  return labels[type] || labels.default;
},

wireSignatureValidation: function() {
    document.querySelectorAll('canvas.signature-pad[name]').forEach(canvas => {
        const fieldName = canvas.getAttribute('name');
        const hidden = document.querySelector(`input[type="hidden"][name="${fieldName}"]`);
        if (!hidden) return;

        // If using SignaturePad library (recommended)
        if (window.SignaturePad) {
            const pad = new SignaturePad(canvas);
            canvas._sigpad = pad;
            pad.onEnd = () => {
                if (!pad.isEmpty()) {
                    hidden.value = 'signed'; // allows required validation to pass
                }
            };
            return;
        }

        // Fallback: detect mouse/touch draw
        let drew = false;
        ['mousedown', 'touchstart'].forEach(evt => canvas.addEventListener(evt, () => { drew = true; }));
        ['mouseup', 'touchend', 'mouseleave'].forEach(evt => canvas.addEventListener(evt, () => {
            if (drew) {
                hidden.value = 'signed';
                drew = false;
            }
        }));
    });
},

    getInputHTML: function (type, id, field) {
        // Generic attrs for standard inputs (NOT used by paragraph)
        let publicInputs = config.isPublic
            ? `name="${id}" id="${id}" data-field-id="${id}"`
            : "";

        const requiredAttr = field?.required ? 'required="required"' : '';

        switch (type) {
            // --- FIXED: paragraph split (public vs editor) ---
            case 'paragraph':
                if (config.isPublic) {
                    // PUBLIC: display-only container (no editor shell, no name/id)
                    return `<div class="mka-paragraph"></div>`;
                } else {
                    // EDITOR: Summernote shell (editor-only so it never shows in public)
                    return `
          <div
            id="${id}-editor"
            class="form-control summernote editor-only"
            data-field-type="paragraph"
            data-field-id="${id}">
          </div>
        `;
                }

            // --- FIXED: header uses field.label safely ---
            case 'header':
                return `<h4>${(field?.label ?? '').toString()}</h4>`;

            case 'textarea':
                return `<textarea class="form-control" ${publicInputs} ${requiredAttr} rows="${field?.rows ?? 3}" placeholder="${field?.placeholder ?? ''}" title="${field?.title ?? ''}"></textarea>`;

            case 'number':
                return `<input type="number" ${publicInputs} ${requiredAttr} class="form-control" placeholder="${field?.placeholder ?? ''}" />`;

            case 'email':
                return `<input type="email" ${publicInputs} ${requiredAttr} class="form-control" placeholder="${field?.placeholder || 'you@example.com'}" />`;

            case 'tel':
                return `<input type="tel" ${publicInputs} ${requiredAttr} class="form-control" placeholder="${field?.placeholder || '(xxx) xxx-xxxx'}" />`;

            case 'file': {
                const isMultiple = !!field?.multiple;
                const publicAttrs = config.isPublic
                    ? `name="${id}${isMultiple ? '[]' : ''}" id="${id}" ${isMultiple ? 'multiple' : ''} data-field-id="${id}"`
                    : '';
                return `<input type="file" ${publicAttrs} ${field?.required ? 'required' : ''} class="form-control" />`;
            }

            case 'checkbox': {
                const options = Array.isArray(field?.options) && field.options.length ? field.options : ['Option 1', 'Option 2'];
                const isRequiredGroup = !!field?.required;
                const groupName = id;
                const nameAttr = config.isPublic ? `name="${groupName}[]"` : '';

                const checkboxesHTML = options.map((opt, index) => {
                    const inputId = `${groupName}-${index}`;
                    return `
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="${inputId}" value="${opt}" ${nameAttr}
                   data-group="${groupName}" data-field-id="${groupName}">
            <label class="form-check-label" for="${inputId}">${opt}</label>
          </div>
        `;
                }).join('');

                return `
        <div class="checkbox-options" data-group="${groupName}" ${isRequiredGroup && config.isPublic ? 'data-required="true"' : ''}>
          ${checkboxesHTML}
        </div>
      `;
            }

            case 'radio': {
                const options = Array.isArray(field?.options) && field.options.length ? field.options : ['Option 1', 'Option 2'];
                const nameAttr = config.isPublic ? `name="${id}"` : '';

                const radioHTML = options.map((opt, index) => {
                    const inputId = `${id}-${index}`;
                    const req = (index === 0 && field?.required && config.isPublic) ? 'required' : '';
                    return `
          <div class="form-check">
            <input class="form-check-input" type="radio" id="${inputId}" value="${opt}" ${req} ${nameAttr} data-field-id="${id}">
            <label class="form-check-label" for="${inputId}">${opt}</label>
          </div>
        `;
                }).join('');

                return `<div class="radio-options">${radioHTML}</div>`;
            }

            case 'select': {
                const options = Array.isArray(field?.options) ? field.options : [];
                const defaultVal = field?.defaultValue ?? '';
                const publicAttrs = config.isPublic ? `name="${id}" id="${id}" data-field-id="${id}"` : '';
                const optionHTML = options.map(opt => `<option value="${opt}"${opt === defaultVal ? ' selected' : ''}>${opt}</option>`).join('');
                return `<select class="form-select" ${requiredAttr} ${publicAttrs}>${optionHTML}</select>`;
            }

            case 'select-multiple': {
                const options = Array.isArray(field?.options) ? field.options : [];
                const defaultVal = field?.defaultValue ?? '';
                const publicAttrs = config.isPublic ? `name="${id}[]" id="${id}" data-field-id="${id}"` : '';
                const optionHTML = options.map(opt => `<option value="${opt}"${opt === defaultVal ? ' selected' : ''}>${opt}</option>`).join('');
                return `<select multiple ${requiredAttr} class="form-select" ${publicAttrs}>${optionHTML}</select>`;
            }

            case 'date':
                return `<input type="date" ${publicInputs} ${requiredAttr} class="form-control" />`;

            case 'img': {
                const imgSrc = field?.src || 'https://www.mkadvantage.com/wp-content/uploads/2025/03/PNGVersion.png';
                const captionText = field?.caption || 'Caption here';
                const captionStyle = `font-family:${field?.captionFont || ''}; font-size:${field?.captionSize || '14px'}; color:${field?.captionColor || '#000000'};`;
                return `
        <img src="${imgSrc}" alt="Uploaded Image" class="img-fluid" style="max-width:100%;height:auto;" />
        <div class="image-caption mt-2 text-center" style="${captionText ? '' : 'display:none;'} ${captionStyle}">${captionText}</div>
      `;
            }

            case 'image': {
                const imageSrc = field?.src || 'https://www.mkadvantage.com/wp-content/uploads/2025/03/PNGVersion.png';
                const imageCaptionText = field?.caption || 'Caption here';
                const imageCaptionStyle = `font-family:${field?.captionFont || ''}; font-size:${field?.captionSize || '14px'}; color:${field?.captionColor || '#000000'};`;
                return `
        <img src="${imageSrc}" alt="Uploaded Image" class="img-fluid" style="max-width:100%;height:auto;" />
        <div class="image-caption mt-2 text-center" style="${imageCaptionText ? '' : 'display:none;'} ${imageCaptionStyle}">${imageCaptionText}</div>
      `;
            }

            case 'separator':
                return `<hr />`;

            case 'signature': {
                const isRequired = !!field?.required;
                const publicHidden = config.isPublic ? `name="${id}" id="${id}-hidden" ${isRequired ? 'required' : ''} data-field-id="${id}" aria-hidden="true"` : '';
                return `
        <canvas class="signature-pad border rounded" width="300" height="100" style="width:100%;height:100px" name="${id}" id="${id}" title="${field?.tooltip || ''}"></canvas>
        ${config.isPublic ? `<input type="hidden" ${publicHidden}>` : ''}
      `;
            }

            default:
                return `<input type="text" class="form-control" ${publicInputs} ${requiredAttr}
               placeholder="${field?.placeholder ?? 'placeholder'}"
               maxlength="${field?.maxlength ?? config?.defaultTextMaxLength ?? 80}"
               title="${field?.title ?? ''}"/>`;
        }
}


    
};

(function patchSave(){
    if (!window.config) return;

    const _origSave = config.saveForm;
    config.saveForm = async function(show = false){
        // Ensure model is up to date before building payload
        window.FormModel.rebuildFromDOM();
        const modelJSON = window.FormModel.toJSON();
        // Helpful debug
        console.log('Saving model:', modelJSON);

        // If you have a buildPayload hook, inject here
        if (typeof config.buildPayload === 'function') {
            const p = config.buildPayload(show) || {};
            p.fields_json = JSON.stringify(modelJSON);
            // You may also want to include a version bump, updated_at, etc.
            return await config._postSave?.(p) || await _origSave?.call(this, show, p);
        }

        // If original saveForm accepts a payload param, pass it; otherwise we set a global the original reads.
        try {
            // Provide a conventional global the legacy save path can read
            window._FORMMODEL_JSON = modelJSON;

            // If your original accepts only the show flag, call it and let backend read fields_json from the request body we’ll set next:
            // (If your original internally builds `payload`, make sure it reads window._FORMMODEL_JSON when constructing fields_json)
            return await _origSave?.call(this, show);
        } finally {
            // no-op
        }
    };
})();

console.log('saveForm is patched:', !!config.saveForm.toString().includes('window.FormModel.rebuildFromDOM'));


// Canonical store for field metadata (type, label, styles, options, etc.)
window.config = window.config || {};
config.fieldMap = config.fieldMap || {}; // id -> fieldObj

// Call this whenever a field is created or edited
config.registerField = function(fieldObj) {
    if (!fieldObj || !fieldObj.id) return;
    const prev = config.fieldMap[fieldObj.id] || {};
    config.fieldMap[fieldObj.id] = { ...prev, ...fieldObj };
};

// Convenience to read it safely
config.getField = function(id) {
    return config.fieldMap[id] || null;
};

// --- DOM helpers
config.getFieldDom = function(id){
    return document.getElementById(id);
};

config.syncDatasets = function(el, fo){
    if (!el || !fo) return;
    el.dataset.fieldId = fo.id;
    el.dataset.type    = fo.type || '';
    el.dataset.label   = fo.label || '';
    el.dataset.colSize = String(fo.colSize || 12);
    if (!config.isPublic) el.dataset.fieldConfig = JSON.stringify(fo);
};

config.applyStyles = function(el, fo){
    if (!el || !fo) return;
    const wrap = el.querySelector('.border') || el;
    const labelEl = el.querySelector('label');
    const input   = el.querySelector('input, textarea, select');

    const s = fo.styles || {};
    if (wrap){
        wrap.style.color            = s.color || '';
        wrap.style.backgroundColor  = s.backgroundColor || '';
        wrap.style.borderColor      = s.borderColor || '';
    }
    if (labelEl){
        if (fo.label != null) labelEl.innerHTML = fo.label + (fo.required ? ' <span class="text-danger">*</span>' : '');
        labelEl.style.color       = s.labelColor || config.defaultLabelColor || '';
        labelEl.style.fontFamily  = s.fontFamily || config.defaultLabelFont || '';
        if (s.fontSize) labelEl.style.fontSize = String(s.fontSize).match(/px$/) ? s.fontSize : (s.fontSize + 'px');
    }
    if (input){
        input.title        = fo.tooltip || '';
        input.placeholder  = fo.placeholder || '';
        if (fo.maxlength != null && Number(fo.maxlength) > 0) input.maxLength = Number(fo.maxlength);
        if (s.color)           input.style.color = s.color;
        if (s.backgroundColor) input.style.backgroundColor = s.backgroundColor;
        if (s.borderColor)     input.style.borderColor = s.borderColor;
        if (fo.required != null) input.required = !!fo.required;
    }
};

// --- Canonical update
config.updateField = function(id, patch){
    const prev = config.getField(id) || { id };
    const merged = {
        ...prev,
        ...patch,
        styles: { ...(prev.styles||{}), ...(patch.styles||{}) }
    };
    config.registerField(merged);

    const el = config.getFieldDom(id);
    if (el){
        config.syncDatasets(el, merged);
        config.applyStyles(el, merged);
    }
    // If label changed but your label element was missing, you may want to regenerate HTML — optional.

    markDirtyAndEmit('fieldChanged', { action:'edit', id });
};




/*END CONFIG, START HELPERS */


window.emitFormEvent = function(name, detail) {
    const evt = new CustomEvent(name, { detail, bubbles: true, composed: true });
    document.dispatchEvent(evt);
};
// Map DOM -> ids you already use
function getRowIdFromEl(rowEl){ return rowEl?.dataset?.rowId || rowEl?.id || null; }
function getFieldIdFromEl(fieldEl){ return fieldEl?.dataset?.fieldId || fieldEl?.id || null; }
// Collect HTML from all Summernote editors currently on the canvas
function collectSummernoteHtmlMap(scope = '#form-canvas') {
    const map = {};
    document.querySelectorAll(`${scope} .summernote`).forEach(el => {
        const wrapper = el.closest('[data-field-id]');
        if (!wrapper) return;
        const id = wrapper.dataset.fieldId;
        if (!id) return;

        let html = '';
        try {
            html = $(el).summernote ? $(el).summernote('code') : el.innerHTML;
        } catch (e) {
            html = el.innerHTML; // safe fallback
        }
        map[id] = html ?? '';
    });
    return map;
}

/*Google fonts load public*/

function collectUsedFonts(rows){
    const set = new Set();
    const rowsArr = Array.isArray(rows) ? rows : [];
    rowsArr.forEach(r => {
        const fields = Array.isArray(r) ? r : (Array.isArray(r?.fields) ? r.fields : []);
        fields.forEach(f => {
            const s = f?.styles || {};
            // Common places you store fonts
            if (s.fontFamily) set.add(s.fontFamily);
            if (s.labelFont)  set.add(s.labelFont);
            if (f.type === 'header' && s.headerFont) set.add(s.headerFont);
        });
    });
    return [...set].filter(Boolean);
}



function ensureFontsLoaded(families){
    if (!families || !families.length) return;

    // Normalize + dedupe
    const uniq = Array.from(new Set(
        families
            .map(f => (f || '').toString().trim())
            .filter(Boolean)
    ));
    if (!uniq.length) return;

    // Preconnect once
    if (!document.querySelector('link[data-mka-preconnect-gf]')) {
        const l1 = document.createElement('link');
        l1.rel = 'preconnect'; l1.href = 'https://fonts.googleapis.com';
        l1.setAttribute('data-mka-preconnect-gf','1');
        document.head.appendChild(l1);

        const l2 = document.createElement('link');
        l2.rel = 'preconnect'; l2.href = 'https://fonts.gstatic.com';
        l2.crossOrigin = 'anonymous';
        l2.setAttribute('data-mka-preconnect-gs','1');
        document.head.appendChild(l2);
    }

    // Build family params: "Bebas Neue" -> Bebas+Neue, add weights if likely needed
    const famParams = uniq.map(f => {
        const fam = f.replace(/\s+/g, '+');
        // For display fonts like Bebas Neue only 400 exists; leave no weights
        if (/bebas\+neue/i.test(fam)) return `family=${fam}`;
        // Common families with weights
        return `family=${fam}:wght@400;600;700`;
    }).join('&');

    const href = `https://fonts.googleapis.com/css2?${famParams}&display=swap`;

    // Skip if already added
    if ([...document.querySelectorAll('link[rel="stylesheet"]')]
        .some(l => (l.href || '').includes(href))) return;

    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = href;
    document.head.appendChild(link);
}



// Walk your FormModel JSON and apply richtext HTML into paragraph fields
function applyRichtextToFields(fields, htmlMap) {
    if (!fields) return fields;

    const patchField = (f) => {
        // match on your field structure; adjust if your keys differ
        const id   = f.id || f.fieldId || f.uuid;
        const type = (f.type || f.field_type || '').toLowerCase();

        if (type === 'paragraph' && id && Object.prototype.hasOwnProperty.call(htmlMap, id)) {
            const html = htmlMap[id] || '';
            // store in a couple of common places so both builder + public can use it
            f.value = html;
            f.html  = html;
            if (f.props && typeof f.props === 'object') {
                f.props.html = html;
            }
        }

        // recurse into common containers if your model nests rows/cols/children
        ['children', 'fields', 'rows', 'cols', 'columns', 'items'].forEach(k => {
            if (Array.isArray(f[k])) f[k].forEach(patchField);
        });
    };

    if (Array.isArray(fields)) fields.forEach(patchField);
    else if (typeof fields === 'object') patchField(fields);
    return fields;
}

// Optional: rebuild model positions after any drag
function initSummernotes(scope=document) {
    $(scope).find('.summernote').each(function(){
        if (!$(this).data('sn-initialized')) {
            $(this).summernote({
                height: 180,
                toolbar: [
                    ['style', ['bold','italic','underline','clear']],
                    ['para', ['ul','ol','paragraph']],
                    ['insert', ['link','picture']],
                    ['view', ['codeview']]
                ]
            });
            $(this).data('sn-initialized', true);
        }
    });
}
// call after rendering rows/fields:
initSummernotes('#form-canvas');

function hydrateParagraphEditorsFromModel(fields) {
    const getField = {};
    // Build a quick lookup by id
    (function indexFields(arr){
        (arr || []).forEach(f => {
            const id = f.id || f.fieldId || f.uuid;
            if (id) getField[id] = f;
            ['children','fields','rows','cols','columns','items'].forEach(k => {
                if (Array.isArray(f[k])) indexFields(f[k]);
            });
        });
    })(Array.isArray(fields) ? fields : [fields]);

    document.querySelectorAll('#form-canvas .summernote').forEach(el => {
        const id = el.closest('[data-field-id]')?.dataset.fieldId;
        if (!id) return;
        const f = getField[id];
        if (!f) return;
        const html = f.value || f.html || f?.props?.html || '';
        try { $(el).summernote('code', html); } catch(e) { el.innerHTML = html; }
    });
}

/*LEGACY PARAGRAPHS PRIOR TO FORM MODEL */

function normalizeLegacyField(field) {
    // Paragraph: old forms stored HTML under `code`
    if (field?.type === 'paragraph') {
        if (field.code && !field.html && !field.text) {
            // Prefer a single canonical prop in your renderer; pick `html` or `text`
            field.html = field.code;   // keep the HTML
        }
        // Paragraphs shouldn’t have maxlength; remove legacy cruft
        if ('maxlength' in field) delete field.maxlength;
    }
    return field;
}

// When loading the form definition:
function normalizeLegacyForm(def) {
    // def can be an array of rows or a flat array—handle both
    if (Array.isArray(def) && def.length && Array.isArray(def[0])) {
        def.forEach(row => row.forEach(normalizeLegacyField));
    } else if (Array.isArray(def)) {
        def.forEach(normalizeLegacyField);
    }
    return def;
}

// very small whitelist sanitizer (DOMPurify is great if you already have it)
function sanitizeParagraphHtml(html) {
    // Allow only a conservative set of tags/attrs suitable for paragraphs
    const ALLOWED = /^(P|BR|STRONG|EM|U|UL|OL|LI|A|SPAN|DIV)$/;
    const ATTR_OK = { 'a': ['href','target','rel'], 'span': ['style'], 'p': ['style'], 'div': ['style'] };

    const tmp = document.createElement('div');
    tmp.innerHTML = html || '';

    const walker = document.createTreeWalker(tmp, NodeFilter.SHOW_ELEMENT, null);
    let node;
    while ((node = walker.nextNode())) {
        if (!ALLOWED.test(node.nodeName)) {
            node.replaceWith(...node.childNodes);
            continue;
        }
        // strip disallowed attrs
        [...node.attributes].forEach(attr => {
            const tag = node.nodeName.toLowerCase();
            if (!(ATTR_OK[tag] || []).includes(attr.name)) node.removeAttribute(attr.name);
        });
        // normalize <a> safety
        if (node.nodeName === 'A') {
            node.setAttribute('rel', 'nofollow noopener noreferrer');
            if (!node.getAttribute('target')) node.setAttribute('target', '_blank');
        }
    }
    return tmp.innerHTML;
}

function renderParagraphField(field) {
    // after normalizeLegacyField, prefer field.html
    const html = field.html || field.text || field.code || '';
    const safe = sanitizeParagraphHtml(html);

    const wrapper = document.createElement('div');
    wrapper.className = 'col-md-' + (field.colSize || 12) + ' mb-3 field';
    wrapper.id = field.id;
    wrapper.dataset.type = 'paragraph';

    wrapper.innerHTML = `
    <div class="border p-2 bg-white rounded shadow-sm">
      <div class="mka-paragraph"></div>
    </div>
  `;

    const container = wrapper.querySelector('.mka-paragraph');
    container.innerHTML = safe; // <- key: innerHTML, not textContent

    // Apply simple styles (optional)
    const s = field.styles || {};
    container.style.fontFamily   = s.fontFamily || 'Arial';
    container.style.fontSize     = s.fontSize   || '14px';
    container.style.color        = s.color      || '#666';
    container.style.textAlign    = s.textAlign  || 'left';

    return wrapper;
}


/* FIX CHECKBOX ISSUES */

function normalizeCheckboxGroup(wrapper){
    if (!wrapper || wrapper.dataset.type !== 'checkbox') return;

    // Group label should never block clicks
    const groupLabel = wrapper.querySelector('.form-label');
    if (groupLabel) groupLabel.style.pointerEvents = 'none';

    wrapper.querySelectorAll('.checkbox-options .form-check').forEach((row, idx) => {
        const cb  = row.querySelector('input[type="checkbox"]');
        const lab = row.querySelector('label.form-check-label');
        if (!cb || !lab) return;

        // Strip text-field-only props (these caused your issue)
        ['placeholder','maxlength','title','readonly','aria-readonly'].forEach(a => cb.removeAttribute(a));
        cb.readOnly = false;

        // Ensure ids/for
        cb.id = `${wrapper.id}-${idx}`;
        lab.setAttribute('for', cb.id);

        // Kill any inline cosmetics that can break native/Bootstrap visuals
        cb.removeAttribute('style');
    });
}

function applyFieldStyling(wrapper, cfg = {}) {
    const type = wrapper.dataset.type;

    // shared container styles
    if (cfg.labelColor)  wrapper.style.setProperty('--label-color', cfg.labelColor);
    if (cfg.borderColor) wrapper.style.borderColor = cfg.borderColor;
    if (cfg.bgColor)     wrapper.style.backgroundColor = cfg.bgColor;

    if (type === 'checkbox') {
        const boxes = wrapper.querySelectorAll('input[type="checkbox"]');
        boxes.forEach(cb => {
            // DO NOT apply text-input props to checkboxes
            ['placeholder','maxlength','title','readonly','aria-readonly'].forEach(a => cb.removeAttribute(a));
            cb.readOnly = false;
            cb.disabled = !!cfg.disabled;

            // visual: ok to use accent-color if desired
            if (cfg.accentColor) cb.style.accentColor = cfg.accentColor;

            // clear any stray inline cosmetics
            cb.removeAttribute('style');
        });

        // guard against label overlays
        const gl = wrapper.querySelector('.form-label');
        if (gl) gl.style.pointerEvents = 'none';
    }
}

/*ROW AND FIELD SORTABLES*/



function initFieldSortableForRow(rowEl){
    const container =
        rowEl.querySelector('.row-fields') ||     // preferred
        rowEl.querySelector('.sortable-row') ||   // fallback
        rowEl;                                    // last resort

    if (!container || container._fieldSortable) return;

    container._fieldSortable = Sortable.create(container, {
        group: 'form-fields',
        draggable: '.field',
        handle: '.field-drag-handle, .drag-handle',
        animation: 150,
        ghostClass: 'drag-ghost',

        onStart(){ window._dragging = true; },

        onAdd(evt){
            if (window.__HYDRATING) return;
            const toRowId = rowEl.dataset.rowId || rowEl.id;
            const fieldId = evt.item?.dataset?.fieldId || evt.item?.id;
            // don’t save here—wait for onEnd; just rebuild so model reflects move immediately
            window.FormModel.rebuildFromDOM();
            markDirtyAndEmit('rowChanged',   { action:'fieldMovedIn',  toRowId, fieldId });
            markDirtyAndEmit('fieldChanged', { action:'move',          toRowId, fieldId });
        },

        onRemove(evt){
            if (window.__HYDRATING) return;
            const fromRowId = rowEl.dataset.rowId || rowEl.id;
            const fieldId   = evt.item?.dataset?.fieldId || evt.item?.id;
            window.FormModel.rebuildFromDOM();
            markDirtyAndEmit('rowChanged', { action:'fieldMovedOut', fromRowId, fieldId });
        },

        onUpdate(){
            console.log('MOVING A FIELD WITHIN A ROW')
            if (window.__HYDRATING) return;
            const rowId = rowEl.dataset.rowId || rowEl.id;
            window.FormModel.rebuildFromDOM();
            markDirtyAndEmit('rowChanged', { action:'reorderFields', rowId });
            // no save here; let onEnd do it once
        },

        onEnd(){
            console.log('MOVE ACTION ON FIELD ENDED')
            window._dragging = false;
            if (window.__HYDRATING) return;
            requestAnimationFrame(() => {
                window.FormModel.rebuildFromDOM();
                markDirtyAndEmit('fieldChanged', { action:'drag:end' }); // your autosave listener will fire
            });
        }
    });
}

function initRowSortable(){
    const canvas = document.querySelector('#form-canvas');
    if (!canvas || canvas._rowSortable) return;

    canvas._rowSortable = Sortable.create(canvas, {
        draggable: '.draggable-row',
        handle: '.row-drag-handle',
        animation: 150,
        ghostClass: 'drag-ghost',
        onStart(){ window._dragging = true; },
        onEnd(){
            window._dragging = false;
            if (window.__HYDRATING) return;
            requestAnimationFrame(() => {
                window.FormModel.rebuildFromDOM();
                markDirtyAndEmit('rowChanged',   { action:'reorderRows:end' });
                markDirtyAndEmit('fieldChanged', { action:'rowReorderBump' });
            });
        }
    });
}


function initAllFieldSortables(){
    document
        .querySelectorAll('#form-canvas .draggable-row')
        .forEach(initFieldSortableForRow);
}

function inferColSizeFromClasses(el){
    // Works with Bootstrap col-*-N or your own col-N
    const classes = Array.from(el.classList);
    const m = classes
        .map(c => {
            // match col-6, col-md-4, etc.
            let mm = c.match(/^col-(?:xs-|sm-|md-|lg-|xl-)?(\d{1,2})$/);
            if (mm) return parseInt(mm[1],10);
            return null;
        })
        .filter(Boolean);
    return m.length ? m[m.length-1] : null;
}

function getFieldSnapshotFromDOM(fieldEl, orderIndex){
    const id      = getFieldIdFromEl(fieldEl);
    const meta    = config.getField(id) || {};
    const type    = fieldEl.dataset.type  || meta.type  || null;
    const label   = fieldEl.dataset.label || meta.label || '';
    const colSize =
        (fieldEl.dataset.colSize && parseInt(fieldEl.dataset.colSize,10)) ||
        meta.colSize ||
        inferColSizeFromClasses(fieldEl) ||
        12;

    // Carry over any richer metadata you already track
    const out = {
        id,
        type,
        label,
        order: orderIndex,
        colSize
    };

    if (meta.required != null) out.required = !!meta.required;
    if (meta.style)   out.style   = { ...meta.style };
    if (meta.options) out.options = Array.isArray(meta.options) ? [...meta.options] : meta.options;
    if (meta.placeholder != null) out.placeholder = meta.placeholder;
    if (meta.helpText != null)    out.helpText = meta.helpText;
    if (meta.maxLength != null)   out.maxLength = meta.maxLength;
    if (meta.inputType)           out.inputType = meta.inputType; // text/email/phone/etc.

    return out;
}


window.FormModel = window.FormModel || {
    _dirty:false, rows:[],
    setDirty(v){ this._dirty = !!v; },
    isDirty(){ return !!this._dirty; },
    resetDirty(){ this._dirty = false; },

    _colSizeFrom(el){
        const ds = el?.dataset?.colSize;
        if (ds) return parseInt(ds, 10) || 12;
        const cls = (el?.className || '').split(/\s+/);
        const m = cls.find(c => /^col-(sm|md|lg|xl|xxl)-\d+$/.test(c) || /^col-\d+$/.test(c));
        if (!m) return 12;
        const n = parseInt(m.split('-').pop(), 10);
        return isFinite(n) ? n : 12;
    },

    rebuildFromDOM(){
        const out = [];
        document.querySelectorAll('#form-canvas .draggable-row').forEach((rowEl, rowIndex) => {
            const rowId = rowEl.dataset.rowId || rowEl.id || `row-${rowIndex}`;
            const container = getFieldContainer(rowEl);

            // ONLY look at direct children of the container to avoid picking up nested UI
            const fieldEls = container.querySelectorAll(':scope > .field');

            const fields = [];
            fieldEls.forEach((fEl, i) => {
                const id  = fEl.dataset.fieldId || fEl.id;
                const reg = (window.config?.getField ? window.config.getField(id) : null) || {};
                fields.push({
                    id,
                    type:      reg.type ?? fEl.dataset.type ?? null,
                    label:     reg.label ?? fEl.dataset.label ?? '',
                    order:     i,
                    colSize:   parseInt(fEl.dataset.colSize || '3', 10) || 3,
                    required:  !!reg.required,
                    placeholder: reg.placeholder || '',
                    tooltip:     reg.tooltip || '',
                    maxlength:   (reg.maxlength != null ? reg.maxlength : null),
                    styles:      reg.styles || {},
                    options:     Array.isArray(reg.options) ? reg.options : []
                });
            });

            out.push({ id: rowId, order: rowIndex, fields });
        });

        this.rows = out;

        // helpful trace while you test
        const totalFields = this.rows.reduce((a,r)=>a+(r.fields?.length||0),0);
        console.debug('[FormModel] rebuildFromDOM rows:', this.rows.length, 'fields:', totalFields);
    },

    toJSON(){
        const out = { rows: [] };
        (this.rows || []).forEach(r => {
            const fields = [];
            (r.fields || []).forEach(f => {
                const meta = (window.config?.getField && window.config.getField(f.id)) || {};
                fields.push({
                    id: f.id,
                    order: f.order,
                    colSize: f.colSize,
                    type: meta.type ?? null,
                    label: meta.label ?? '',
                    required: !!meta.required,
                    placeholder: meta.placeholder ?? '',
                    tooltip: meta.tooltip ?? '',
                    maxlength: meta.maxlength ?? null,
                    options: Array.isArray(meta.options) ? meta.options : [],
                    styles: meta.styles || {}
                });
            });
            out.rows.push({ id: r.id, order: r.order, fields });
        });
        return out;
    }
};


// Ensure model is rebuilt after render/add/remove operations too
(function wireModelSync(){
    if (!window.config) return;

    // Patch once
    if (config._patchedForModelSync) return;
    config._patchedForModelSync = true;

    // Patch init render path if you have one
    const _render = config.render;
    config.render = function(...args){
        const out = _render?.apply(this, args);
        // (Re)init sortables after a render
        initRowSortable();
        initAllFieldSortables();
        // Rebuild model from DOM snapshot
        window.FormModel.rebuildFromDOM();
        markDirtyAndEmit('configChanged', { action:'render' });
        return out;
    };

    // Patch addRow
    const _addRow = config.addRow;
    config.addRow = function(...args){
        const out = _addRow?.apply(this, args);
        initRowSortable();
        initAllFieldSortables();
        window.FormModel.rebuildFromDOM();
        markDirtyAndEmit('rowChanged', { action:'addRow' });
        return out;
    };

    // Patch addField
    const _addField = config.addField;
    config.addField = function(...args){
        const out = _addField?.apply(this, args);
        initAllFieldSortables();
        window.FormModel.rebuildFromDOM();
        markDirtyAndEmit('fieldChanged', { action:'addField' });
        return out;
    };

    // Patch removeElement (fields)
    const _removeElement = config.removeElement;
    config.removeElement = function(...args){
        const out = _removeElement?.apply(this, args);
        window.FormModel.rebuildFromDOM();
        markDirtyAndEmit('fieldChanged', { action:'removeField' });
        return out;
    };

    // If you have duplicate / resize operations
    const _duplicate = config.duplicateElement;
    if (_duplicate) {
        config.duplicateElement = function(...args){
            const out = _duplicate.apply(this, args);
            initAllFieldSortables();
            window.FormModel.rebuildFromDOM();
            markDirtyAndEmit('fieldChanged', { action:'duplicate' });
            return out;
        };
    }

    const _resize = config.resizeField;
    if (_resize) {
        config.resizeField = function(...args){
            const out = _resize.apply(this, args);
            window.FormModel.rebuildFromDOM();
            markDirtyAndEmit('fieldChanged', { action:'resize' });
            return out;
        };
    }
})();

// ---- Auto-normalize & (optionally) apply styles for newly added checkbox fields ----
(function bootstrapCheckboxNormalizer(){
    if (typeof normalizeCheckboxGroup !== 'function') return;

    // 1) Sweep existing fields on load
    function sweep(root=document){
        root.querySelectorAll('.field[data-type="checkbox"]').forEach(normalizeCheckboxGroup);
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => sweep());
    } else {
        sweep();
    }

    // 2) Watch for future inserts (covers editor + public render)
    const targets = [
        document.getElementById('form-canvas'),     // editor canvas
        document.getElementById('renderedForm')     // public container
    ].filter(Boolean);

    const observer = new MutationObserver(muts => {
        for (const m of muts) {
            // new fields inserted directly
            m.addedNodes.forEach(node => {
                if (!(node instanceof HTMLElement)) return;
                if (node.matches && node.matches('.field[data-type="checkbox"]')) {
                    normalizeCheckboxGroup(node);
                    // Optional: if you keep per-field style config, apply it here
                    if (typeof applyFieldStyling === 'function') {
                        const cfg = safeParse(node.dataset.fieldConfig) || {};
                        applyFieldStyling(node, cfg.styles || {});
                    }
                }
                // fields inserted somewhere deeper
                node.querySelectorAll?.('.field[data-type="checkbox"]').forEach(wrapper => {
                    normalizeCheckboxGroup(wrapper);
                    if (typeof applyFieldStyling === 'function') {
                        const cfg = safeParse(wrapper.dataset.fieldConfig) || {};
                        applyFieldStyling(wrapper, cfg.styles || {});
                    }
                });
            });
        }
    });

    targets.forEach(t => observer.observe(t, { childList: true, subtree: true }));

    // small helper
    function safeParse(s){ try { return s ? JSON.parse(s) : null; } catch(e){ return null; } }
})();











