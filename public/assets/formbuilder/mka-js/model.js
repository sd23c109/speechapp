// ---- Single Source Of Truth for the form ----
export const FormModel = {
    // Array of rows, each row is array of field objects
    _rows: [],

    // stable id counter (never reindex on render)
    _idCounter: 0,

    // selection (client-only; never save to DB)
    _activeRowIndex: null,   // number | null
    _activeField: null,      // { rowIndex, fieldId } | null

    // dirty flag (for autosave / UI badges)
    _dirty: false,

    // --- INIT / SERIALIZE ----------------------------------------------------
    fromJSON(json) {
        const data = Array.isArray(json) ? json : [];
        // defensive clone to avoid external mutation
        this._rows = typeof structuredClone === 'function' ? structuredClone(data) : JSON.parse(JSON.stringify(data));

        // find max field-X and continue counter
        let max = 0;
        for (const row of this._rows) {
            for (const f of row) {
                const n = Number(String(f.id || '').replace('field-',''));
                if (!Number.isNaN(n)) max = Math.max(max, n);
            }
        }
        this._idCounter = max + 1;

        // reset UI-only state
        this._activeRowIndex = null;
        this._activeField = null;
        this._dirty = false;
    },

    toJSON() {
        return typeof structuredClone === 'function' ? structuredClone(this._rows) : JSON.parse(JSON.stringify(this._rows));
    },

    // --- DIRTY / SNAPSHOTS ---------------------------------------------------
    markDirty() {
        this._dirty = true;
        document.dispatchEvent(new CustomEvent('form:dirty'));
    },


    isDirty() { return !!this._dirty; },
    resetDirty() {
        this._dirty = false;
        document.dispatchEvent(new CustomEvent('form:clean'));
    },

    snapshot() { return this.toJSON(); },                 // lightweight state capture
    restore(snap) { this.fromJSON(snap); this.markDirty(); },

    // --- HELPERS -------------------------------------------------------------
    newId() { return `field-${this._idCounter++}`; },
    rowsCount() { return this._rows.length; },
    rowWidth(rowIndex) {
        const row = this._rows[rowIndex] || [];
        return row.reduce((sum, f) => sum + (Number(f.colSize) || 12), 0);
    },
    canFit(rowIndex, extraCols) {
        return (this.rowWidth(rowIndex) + (Number(extraCols)||0)) <= 12;
    },

    // --- SELECTION -----------------------------------------------------------
    setActiveRow(index) {
        this._activeRowIndex = (typeof index === 'number') ? index : null;
        this._activeField = null; // clear field selection if row changes
    },
    getActiveRow() { return this._activeRowIndex; },

    setActiveField(rowIndex, fieldId) {
        this._activeRowIndex = rowIndex;
        this._activeField = { rowIndex, fieldId };
    },
    getActiveField() { return this._activeField; },

    clearSelection() { this._activeRowIndex = null; this._activeField = null; },

    // --- ROW OPS -------------------------------------------------------------
    addRow() {
        this._rows.push([]);
        this._activeRowIndex = this._rows.length - 1;
        this.markDirty();
        return this._activeRowIndex;
    },

    insertRowAt(index) {
        const i = Math.max(0, Math.min(index, this._rows.length));
        this._rows.splice(i, 0, []);
        // shift active selection if row inserted above
        if (this._activeRowIndex !== null && this._activeRowIndex >= i) {
            this._activeRowIndex++;
        }
        this._activeRowIndex = i;
        this.markDirty();
        return i;
    },

    addRowAfter(rowIndex) {
        const i = (typeof rowIndex === 'number') ? rowIndex + 1 : this._rows.length;
        return this.insertRowAt(i);
    },

    removeRow(rowIndex) {
        if (rowIndex < 0 || rowIndex >= this._rows.length) return;
        this._rows.splice(rowIndex, 1);

        // Adjust selection
        if (this._activeRowIndex === rowIndex) {
            this.clearSelection();
        } else if (this._activeRowIndex > rowIndex) {
            this._activeRowIndex--;
        }
        this.markDirty();
    },

    moveRow(fromIndex, toIndex) {
        if (fromIndex < 0 || fromIndex >= this._rows.length) return;
        if (toIndex   < 0 || toIndex   >= this._rows.length) return;

        const [row] = this._rows.splice(fromIndex, 1);
        this._rows.splice(toIndex, 0, row);

        // Keep selection with the moved row
        if (this._activeRowIndex === fromIndex) this._activeRowIndex = toIndex;

        this.markDirty();
    },

    // --- FIELD FINDERS -------------------------------------------------------
    findField(fieldId) {
        for (let r = 0; r < this._rows.length; r++) {
            const i = this._rows[r].findIndex(f => f.id === fieldId);
            if (i !== -1) return { rowIndex: r, index: i, field: this._rows[r][i] };
        }
        return null;
    },

    getField(rowIndex, fieldId) {
        const row = this._rows[rowIndex] || [];
        return row.find(f => f.id === fieldId) || null;
    },

    // --- FIELD OPS -----------------------------------------------------------
    addField(rowIndex, field) {
        if (!this._rows[rowIndex]) this._rows[rowIndex] = [];
        if (!field.id) field.id = this.newId();
        // default sane colSize if missing
        if (!field.colSize) field.colSize = 12;
        if (!this.canFit(rowIndex, field.colSize)) {
            // fail fast: caller should handle UI message
            throw new Error('Row width would exceed 12 columns');
        }
        this._rows[rowIndex].push(field);
        this._activeRowIndex = rowIndex;
        this._activeField = { rowIndex, fieldId: field.id };
        this.markDirty();
        return field.id;
    },

    insertFieldAt(rowIndex, insertIndex, field) {
        if (!this._rows[rowIndex]) this._rows[rowIndex] = [];
        if (!field.id) field.id = this.newId();
        if (!field.colSize) field.colSize = 12;
        if (!this.canFit(rowIndex, field.colSize)) {
            throw new Error('Row width would exceed 12 columns');
        }
        const i = Math.max(0, Math.min(insertIndex, this._rows[rowIndex].length));
        this._rows[rowIndex].splice(i, 0, field);
        this._activeRowIndex = rowIndex;
        this._activeField = { rowIndex, fieldId: field.id };
        this.markDirty();
        return field.id;
    },

    removeField(rowIndex, fieldId) {
        if (!this._rows[rowIndex]) return;
        const beforeLen = this._rows[rowIndex].length;
        this._rows[rowIndex] = this._rows[rowIndex].filter(f => f.id !== fieldId);
        if (this._rows[rowIndex].length !== beforeLen) {
            if (this._activeField && this._activeField.fieldId === fieldId) {
                this._activeField = null;
            }
            this.markDirty();
        }
    },

    moveField(srcRow, fieldId, destRow, destIndex) {
        const src = this._rows[srcRow] || [];
        const idx = src.findIndex(f => f.id === fieldId);
        if (idx < 0) return;

        const [field] = src.splice(idx, 1);
        if (!this._rows[destRow]) this._rows[destRow] = [];
        const di = (typeof destIndex === 'number')
            ? Math.max(0, Math.min(destIndex, this._rows[destRow].length))
            : this._rows[destRow].length;

        // grid guard: if moving across rows increases width, validate
        const addedCols = Number(field.colSize) || 12;
        if (!this.canFit(destRow, addedCols)) {
            // put it back to avoid corruption
            src.splice(idx, 0, field);
            throw new Error('Row width would exceed 12 columns');
        }

        this._rows[destRow].splice(di, 0, field);

        // selection follows the moved field
        this._activeRowIndex = destRow;
        this._activeField = { rowIndex: destRow, fieldId };

        this.markDirty();
    },

    updateField(rowIndex, fieldId, patch) {
        const row = this._rows[rowIndex] || [];
        const f = row.find(x => x.id === fieldId);
        if (!f) return;

        // guard colSize changes
        if (Object.prototype.hasOwnProperty.call(patch, 'colSize')) {
            const diff = (Number(patch.colSize) || 12) - (Number(f.colSize) || 12);
            if (diff !== 0 && !this.canFit(rowIndex, diff)) {
                throw new Error('Row width would exceed 12 columns');
            }
        }

        Object.assign(f, patch);
        this.markDirty();
    },

    // convenience: strict resize helper
    setFieldColSize(rowIndex, fieldId, newSize) {
        const row = this._rows[rowIndex] || [];
        const f = row.find(x => x.id === fieldId);
        if (!f) return;
        const diff = (Number(newSize)||12) - (Number(f.colSize)||12);
        if (diff !== 0 && !this.canFit(rowIndex, diff)) {
            throw new Error('Row width would exceed 12 columns');
        }
        f.colSize = Number(newSize)||12;
        this.markDirty();
    }
};
