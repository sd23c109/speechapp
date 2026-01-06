var formhelpers = {
    
    gad7: function(){
        // === GAD-7 scoring helper ================================================
// Map option text -> score
const GAD7_ORDER = ["Not at all","Several days","More than half the days","Nearly every day"];
const GAD7_SCORE = { "Not at all":0, "Several days":1, "More than half the days":2, "Nearly every day":3 };

// Field IDs from your JSON:
const GAD7_ITEM_IDS = ["field-4","field-5","field-6","field-7","field-8","field-9","field-10"];
const TOTAL_ID = "field-14";     // number (0–21)
const SEVERITY_ID = "field-15";  // select with severity labels

function gad7Severity(total){
  if (total <= 4) return "Minimal (0–4)";
  if (total <= 9) return "Mild (5–9)";
  if (total <= 14) return "Moderate (10–14)";
  return "Severe (15–21)";
}

// Try multiple selector patterns to find the checked radio for a field row.
// Adjust these if your renderer wraps inputs differently.
function getCheckedRadioForField(fieldId){
  // 1) name="field-4" style
  let $r = document.querySelector(`input[type="radio"][name="${fieldId}"]:checked`);
  if ($r) return $r;

  // 2) inputs inside a container with id or data-field-id
  const container = document.getElementById(fieldId) ||
                    document.querySelector(`[data-field-id="${fieldId}"]`) ||
                    document.querySelector(`#${fieldId} .form-field`);
  if (container) {
    return container.querySelector('input[type="radio"]:checked');
  }
  return null;
}

// Derive score from either explicit value text or option order index
function scoreForRadio(el){
  if (!el) return null;
  const val = (el.value || "").trim();
  if (val in GAD7_SCORE) return GAD7_SCORE[val];

  // Fallback: find label text next to the input (common pattern)
  let labelText = "";
  const label = el.closest('label');
  if (label) labelText = label.textContent.trim();
  if (labelText in GAD7_SCORE) return GAD7_SCORE[labelText];

  // Last resort: compute by index within its named group in DOM order
  const groupName = el.name;
  const all = Array.from(document.querySelectorAll(`input[type="radio"][name="${groupName}"]`));
  const idx = all.findIndex(x => x === el);
  return (idx >= 0 && idx < 4) ? idx : null;
}


// If your page is SPA-ish, call bindGAD7() after the form renders.
// Otherwise just run it now on DOMContentLoaded.
document.addEventListener('DOMContentLoaded', bindGAD7);

// (Optional) Ensure the Total field stays user-proof (but still submitted)
document.addEventListener('input', (e) => {
  const t = e.target;
  if (t && (t.id === TOTAL_ID || t.name === TOTAL_ID)) {
    this.computeGAD7(); // overwrite manual edits
  }
});
    },

    bindGAD7: function(){
        GAD7_ITEM_IDS.forEach(id => {
    // name="field-X" radios
    document.querySelectorAll(`input[type="radio"][name="${id}"]`)
      .forEach(r => r.addEventListener('change', computeGAD7));

    // also bind inside a container (in case name differs)
    const cont = document.getElementById(id) ||
                 document.querySelector(`[data-field-id="${id}"]`);
    if (cont){
      cont.addEventListener('change', e => {
        if (e.target && e.target.matches('input[type="radio"]')) computeGAD7();
      });
    }
  });

  // Initial compute after render
   this.computeGAD7();
        
    },
    
    computeGAD7: function(){
       
              let missing = false;
              let total = 0;

              for (const id of GAD7_ITEM_IDS){
                const chosen = getCheckedRadioForField(id);
                const score = scoreForRadio(chosen);
                if (score == null) { missing = true; continue; }
                total += score;
              }

              // Write total (only if we have at least one answer; you can require all 7 if you want)
              const totalEl =
                document.getElementById(TOTAL_ID) ||
                document.querySelector(`input[name="${TOTAL_ID}"]`);
              if (totalEl){
                totalEl.value = missing && total === 0 ? "" : String(total);
              }

              // Write severity if all answered; otherwise clear
              const sevEl =
                document.getElementById(SEVERITY_ID) ||
                document.querySelector(`select[name="${SEVERITY_ID}"]`);
              if (sevEl){
                if (!missing){
                  const sev = gad7Severity(total);
                  // Try to pick the exact matching option; if not present, set value directly.
                  const match = Array.from(sevEl.options).find(o => o.text.trim() === sev || o.value.trim() === sev);
                  if (match) sevEl.value = match.value; else sevEl.value = sev;
                  sevEl.dispatchEvent(new Event('change', { bubbles: true })); // if your UI listens
                } else {
                  sevEl.value = "";
                  sevEl.dispatchEvent(new Event('change', { bubbles: true }));
                }
              }

        
    }
}