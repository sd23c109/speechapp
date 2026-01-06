export const SaveBadge = (() => {
    const el = document.getElementById('save-status');
    const set = (state, text) => {
        if (!el) return;
        el.className = `save-status ${state}`;
        el.innerHTML = `<span class="dot"></span><span class="text">${text}</span>`;
    };
    return {
        dirty()  { set('dirty',  'Unsaved changes'); },
        saving() { set('saving', 'Saving…'); },
        saved()  { set('clean',  'All changes saved'); },
        error(msg='Save failed') { set('error', msg); }
    };
})();
